#!/usr/bin/env python3
"""StudyScape Alibaba OCR worker.

Reads credentials from the project-root ``AccessKey .env`` file and never prints
credential values. PDF input requires PyMuPDF; images use the Alibaba structured
paper OCR endpoint directly.
"""
from __future__ import annotations

import argparse
import base64
import json
import os
import re
import sys
from pathlib import Path

from alibabacloud_ocr_api20210707.client import Client as OcrClient
from alibabacloud_ocr_api20210707 import models as ocr_models
from alibabacloud_tea_openapi import models as open_api_models
from alibabacloud_tea_util import models as util_models

ROOT = Path(__file__).resolve().parents[1]
ENV_FILE = ROOT / "AccessKey .env"


def read_credentials():
    values = {}
    if not ENV_FILE.is_file():
        raise RuntimeError(f"missing credential file: {ENV_FILE.name}")
    for raw in ENV_FILE.read_text(encoding="utf-8-sig").splitlines():
        line = raw.strip()
        if not line or line.startswith("#") or "=" not in line:
            continue
        key, value = line.split("=", 1)
        values[key.strip()] = value.strip().strip('"').strip("'")
    key_id = values.get("ALIYUN_ACCESS_KEY_ID") or values.get("AccessKey ID")
    secret = values.get("ALIYUN_ACCESS_KEY_SECRET") or values.get("AccessKey Secret")
    if not key_id or not secret:
        raise RuntimeError("AccessKey .env must define ALIYUN_ACCESS_KEY_ID and ALIYUN_ACCESS_KEY_SECRET")
    return key_id, secret


def client():
    key_id, secret = read_credentials()
    config = open_api_models.Config(access_key_id=key_id, access_key_secret=secret)
    config.endpoint = "ocr-api.cn-hangzhou.aliyuncs.com"
    return OcrClient(config)


def parse_data(response):
    raw = response.to_map()
    body = raw.get("body", {})
    data = body.get("Data")
    if not data:
        raise RuntimeError("Alibaba OCR response did not contain Data")
    return json.loads(data) if isinstance(data, str) else data


def render_pdf(path):
    try:
        import fitz
    except ImportError as exc:
        raise RuntimeError("PDF processing requires PyMuPDF: install package pymupdf") from exc
    document = fitz.open(path)
    for page in document:
        pixmap = page.get_pixmap(matrix=fitz.Matrix(2, 2), alpha=False)
        yield pixmap.tobytes("png")
    document.close()


def ocr_bytes(api_client, image_bytes, subject):
    request = ocr_models.RecognizeEduPaperStructedRequest()
    request.body = image_bytes
    request.need_rotate = True
    request.output_oricoord = True
    if subject:
        request.subject = subject
    return parse_data(api_client.recognize_edu_paper_structed_with_options(request, util_models.RuntimeOptions()))


def clean_option(value):
    """Strip $$ delimiters and the leading option label (A./B . 等)."""
    text = (value or "").strip()
    # 选项中 $$ 仅作为分隔符，不可能出现在公式内部，全部删除
    text = text.replace("$$", "")
    # 去掉开头的选项标签字母（A. / B . / C） 等），字母与点之间允许空格
    text = re.sub(r"^\s*[A-Za-zＡ-Ｄ]\s*[.．、)）:：]\s*", "", text, count=1)
    # 折叠多余空白
    text = re.sub(r"\s+", " ", text).strip()
    return text


# 选项起始标记：字母前不能是字母（避免匹配 "ABC" 中的 A），后接 . ． 、 ) ） : ：
_OPTION_MARKER = re.compile(r"(?<![A-Za-zＡ-Ｚ])[A-DＡ-Ｄ]\s*[.．、)）:：]\s*")


def split_content_options(content, options):
    """如果题干里混入了选项，剥离出题干；若 options 为空则从题干切分。"""
    text = (content or "").replace("\r", "")
    marker = _OPTION_MARKER.search(text)
    if not marker:
        return text, options
    prefix = text[: marker.start()].strip()
    # 只去掉题干末尾残留的 $$ 分隔符，保留题干中间的合法公式 $$...$$
    prefix = re.sub(r"\$\$\s*$", "", prefix).strip()
    if options:
        # 选项数组已存在，仅把题干中的选项部分截掉
        return prefix, options
    # 选项数组为空，从题干切分
    tail = text[marker.start():].strip()
    parts = [p.strip() for p in re.split(r"(?=\s*[A-DＡ-Ｄ]\s*[.．、)）:：]\s*)", tail) if p.strip()]
    if len(parts) >= 2:
        return prefix, parts
    return text, options


def normalize(data, paper_name, subject, education_level):
    questions = []
    for part in data.get("part_info", []) or []:
        part_title = part.get("part_title", "")
        for item in part.get("subject_list", []) or []:
            elements = item.get("element_list", []) or []
            text = item.get("text", "")
            options = []
            answer = ""
            explanation = ""
            for element in elements:
                kind = element.get("type")
                value = element.get("text", "")
                if kind == 1 and value:
                    options.append(clean_option(value))
                elif kind == 3 and value:
                    answer = value
                elif kind == 2 and value:
                    explanation = value
            # 题干里可能混入了选项，剥离并清洗
            text, options = split_content_options(text, options)
            figures = []
            for fig in item.get("figure_list", []) or []:
                points = fig.get("points") or []
                figures.append({"type": fig.get("type"), "bbox": points, "base64": None})
            questions.append({
                "source_qid": str(len(questions) + 1),
                "part_title": part_title,
                "question_type": "single",
                "content": text,
                "options": options,
                "correct_answer": answer,
                "explanation": explanation,
                "difficulty": 3,
                "points": 1,
                "is_html": 0,
                "images": figures,
                "included": True,
            })
    return {"paper_name": paper_name, "subject": subject or "综合", "education_level": education_level, "questions": questions}


def run(input_path, output_path, subject, education_level, dry_run=False):
    path = Path(input_path).resolve()
    if not path.is_file():
        raise RuntimeError("input file does not exist")
    if path.stat().st_size > 50 * 1024 * 1024:
        raise RuntimeError("input file exceeds 50MB")
    suffix = path.suffix.lower()
    if suffix not in {".pdf", ".jpg", ".jpeg", ".png", ".webp", ".bmp", ".tiff"}:
        raise RuntimeError("only PDF and image files are supported")
    if dry_run:
        return {"paper_name": path.name, "subject": subject or "综合", "education_level": education_level, "questions": []}
    api_client = client()
    pages = render_pdf(path) if suffix == ".pdf" else [path.read_bytes()]
    merged = {"part_info": []}
    for page in pages:
        page_data = ocr_bytes(api_client, page, subject)
        merged["part_info"].extend(page_data.get("part_info", []) or [])
    return normalize(merged, path.name, subject, education_level)


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--input", required=True)
    parser.add_argument("--output")
    parser.add_argument("--subject", default="")
    parser.add_argument("--education-level", default="junior", choices=["junior", "senior"])
    parser.add_argument("--dry-run", action="store_true")
    args = parser.parse_args()
    try:
        result = run(args.input, args.output, args.subject, args.education_level, args.dry_run)
        text = json.dumps(result, ensure_ascii=False, indent=2)
        if args.output:
            Path(args.output).write_text(text, encoding="utf-8")
        else:
            print(text)
    except Exception as exc:
        print(f"OCR failed: {exc}", file=sys.stderr)
        raise SystemExit(1)


if __name__ == "__main__":
    main()
