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


def _as_dict(value):
    """确保返回值是 dict：字符串先 JSON 解析；list 取首元素或返回空 dict。"""
    if value is None:
        return {}
    if isinstance(value, str):
        try:
            value = json.loads(value)
        except (json.JSONDecodeError, TypeError):
            return {}
    if isinstance(value, dict):
        return value
    if isinstance(value, list):
        # list 通常意味着单元素被包了一层；取第一个非空 dict
        for item in value:
            if isinstance(item, dict):
                return item
        return {}
    return {}


def _as_list(value, key=None):
    """安全地获取 list：从 dict[key] 取值，确保返回 list。非 list / 非 dict 都返回空列表。"""
    if value is None:
        return []
    if key is not None:
        if isinstance(value, dict):
            value = value.get(key)
        else:
            return []
    if isinstance(value, list):
        return value
    if value is None:
        return []
    # 单个 dict 包一层
    if isinstance(value, dict):
        return [value]
    return []


def parse_data(response):
    raw = response.to_map()
    body = raw.get("body", {}) if isinstance(raw, dict) else {}
    if not isinstance(body, dict):
        body = {}
    data = body.get("Data")
    if not data:
        raise RuntimeError("Alibaba OCR response did not contain Data")

    # Data 可能是 JSON 字符串，也可能已经是 dict/list
    if isinstance(data, str):
        try:
            parsed = json.loads(data)
        except (json.JSONDecodeError, TypeError) as exc:
            raise RuntimeError(f"Alibaba OCR Data 不是合法 JSON: {exc}") from exc
    else:
        parsed = data

    # 兼容三种结构：
    #   1) dict 含 part_info — 标准结构化返回
    #   2) list of dicts — 多个 part 扁平展开
    #   3) 其他异常结构
    if isinstance(parsed, dict):
        return parsed
    if isinstance(parsed, list):
        # 如果 list 第一个元素有 part_info，说明是多页结果，合并 part_info
        has_part_info = any(
            isinstance(item, dict) and "part_info" in item
            for item in parsed
        )
        if has_part_info:
            merged_parts = []
            for item in parsed:
                if isinstance(item, dict):
                    merged_parts.extend(_as_list(item, "part_info"))
            return {"part_info": merged_parts}
        # 否则把整个 list 包成单个 part 的 subject_list
        return {"part_info": [{"part_title": "", "subject_list": parsed}]}

    raise RuntimeError(f"Alibaba OCR Data 格式异常（类型: {type(parsed).__name__}），无法解析为题目结构")


def render_pdf(path):
    # PyMuPDF 新版推荐 pymupdf，兼容旧版 fitz
    try:
        import pymupdf as fitz_mod
    except ImportError:
        try:
            import fitz as fitz_mod
        except ImportError as exc:
            raise RuntimeError("PDF processing requires PyMuPDF: install package pymupdf") from exc
    document = fitz_mod.open(path)
    for page in document:
        pixmap = page.get_pixmap(matrix=fitz_mod.Matrix(2, 2), alpha=False)
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


def _crop_image_from_bbox(image_bytes, bbox):
    """根据 bbox 坐标从原图中裁切图片，返回 base64（data:image/png;base64,... 格式）。
    bbox 格式：[[x1,y1],[x2,y2],[x3,y3],[x4,y4]] 或 [x1,y1,x2,y2]
    """
    if not bbox or not image_bytes:
        return None
    try:
        from PIL import Image
        import io
    except ImportError:
        return None
    try:
        img = Image.open(io.BytesIO(image_bytes))
        width, height = img.size
        # 解析 bbox
        if isinstance(bbox[0], list):
            xs = [p[0] for p in bbox]
            ys = [p[1] for p in bbox]
            x1, y1, x2, y2 = min(xs), min(ys), max(xs), max(ys)
        else:
            x1, y1, x2, y2 = bbox[0], bbox[1], bbox[2], bbox[3]
        # 边界检查 + 稍微扩展一点边距
        pad = 3
        x1 = max(0, int(x1) - pad)
        y1 = max(0, int(y1) - pad)
        x2 = min(width, int(x2) + pad)
        y2 = min(height, int(y2) + pad)
        if x2 <= x1 or y2 <= y1:
            return None
        cropped = img.crop((x1, y1, x2, y2))
        buf = io.BytesIO()
        cropped.save(buf, format="PNG")
        import base64 as _b64
        return "data:image/png;base64," + _b64.b64encode(buf.getvalue()).decode("ascii")
    except Exception:
        return None


# 材料题/大题题干模式：匹配"阅读材料/根据材料/阅读下文...完成下列小题/完成1~2题"等
_MATERIAL_PATTERN = re.compile(
    r"(阅读(?:下列|下面|以上|图文)?材料|根据(?:以上|以下|上述|图文)?材料|阅读下文|读(?:下)?文|阅读图文|结合材料).*?"
    r"(?:完成|回答|回答下列|下列各题|各题|小题|下面各题|以下各题|1\s*[-~～]\s*\d+\s*题|\d+\s*[-~～]\s*\d+\s*题)",
    re.DOTALL
)

# 题目序号模式："13." "一、" "(1)" "【1】" 等，用于识别大题边界
_QUESTION_NUMBER = re.compile(r"^\s*(?:[一二三四五六七八九十]+[、.．]|\d+\s*[.．、)）]|【\d+】|\(\s*\d+\s*\))\s*")


def _is_material_stem(text):
    """判断一段文本是否是材料题的大题题干（材料阅读类）。"""
    if not text:
        return False
    # 长度过短不可能是材料
    if len(text) < 30:
        return False
    return bool(_MATERIAL_PATTERN.search(text))


def _question_number_prefix(text):
    """提取题干开头的题号前缀，返回 (题号文本, 剩余文本)。没匹配到返回 ('', text)。"""
    m = _QUESTION_NUMBER.match(text)
    if m:
        return m.group(0).strip(), text[m.end():].strip()
    return "", text.strip()


def normalize(data, paper_name, subject, education_level, page_images=None):
    """
    归一化 OCR 结果。
    page_images: 每页的原始图片 bytes 列表（用于裁切 figure 图片），可选。
    """
    page_images = page_images or []
    raw_questions = []

    for part_idx, part in enumerate(_as_list(data, "part_info")):
        if not isinstance(part, dict):
            continue
        part_title = part.get("part_title", "") if isinstance(part, dict) else ""
        for item in _as_list(part, "subject_list"):
            if not isinstance(item, dict):
                continue
            elements = item.get("element_list", []) or []
            text = item.get("text", "")
            options = []
            answer = ""
            explanation = ""
            for element in elements:
                if not isinstance(element, dict):
                    continue
                kind = element.get("type")
                value = element.get("text", "")
                if kind == 1 and value:
                    options.append(clean_option(value))
                elif kind == 3 and value:
                    answer = value
                elif kind == 2 and value:
                    explanation = value

            # 从 figure_list 裁切图片
            figures = []
            page_idx = item.get("_page_idx", 0)
            page_img = page_images[page_idx] if page_idx < len(page_images) else None
            for fig in _as_list(item, "figure_list"):
                if not isinstance(fig, dict):
                    continue
                points = fig.get("points") or []
                b64 = None
                if page_img and points:
                    b64 = _crop_image_from_bbox(page_img, points)
                figures.append({
                    "type": fig.get("type"),
                    "bbox": points,
                    "base64": b64 or "",
                })

            raw_questions.append({
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
                "_part_idx": part_idx,
            })

    # ========== 修复 1：材料题合并 ==========
    # 策略：遍历题目，如果某题的题干是"材料题"样式（阅读材料...完成下列各题），
    # 且它的 options 很少/没有，则认为它是大题材料，把后面的小题合并进去。
    questions = []
    i = 0
    while i < len(raw_questions):
        q = raw_questions[i]
        content = q["content"].strip()

        # 判断是否是材料题大题题干
        is_material = _is_material_stem(content)
        # 启发式：如果题干很长（>150字）、选项为空或极少（0-1个），也可能是材料
        if not is_material and len(content) > 150 and len(q["options"]) <= 1:
            # 再看末尾有没有 "下列小题" "各题" 之类
            if re.search(r"(下列|以下|以上|各|小题|完成\s*(\d+[~～-]\s*)?\d+\s*题)", content[-100:]):
                is_material = True

        if is_material and i + 1 < len(raw_questions):
            # 收集属于这个材料的小题
            sub_questions = []
            j = i + 1
            while j < len(raw_questions):
                next_q = raw_questions[j]
                next_content = next_q["content"].strip()
                # 如果下一题也是材料题，停止
                if _is_material_stem(next_content):
                    break
                # 如果下一题的 part 变了，停止
                if next_q.get("_part_idx") != q.get("_part_idx"):
                    break
                # 安全限制：一个材料题最多带 10 个小题
                if len(sub_questions) >= 10:
                    break
                sub_questions.append(next_q)
                j += 1

            if sub_questions:
                # 有小题：把材料作为大题题干 + 多道小题
                # 这里采用"保留材料为独立题 + 后面小题不变"的策略，
                # 但把大题类型标记为 material，题干就是材料文本
                q["question_type"] = "material"
                q["options"] = []
                q["_sub_questions"] = len(sub_questions)
                questions.append(q)
                # 小题正常加入，但把 part_title 加上材料说明
                for idx, sq in enumerate(sub_questions):
                    sq["part_title"] = (q["part_title"] + " / 材料题" if q["part_title"] else "材料题")
                    # 小题题干去掉题号前缀，避免和列表序号重复
                    prefix, rest = _question_number_prefix(sq["content"])
                    if prefix:
                        sq["content"] = rest
                    questions.append(sq)
                i = j
                continue

        # 普通题
        questions.append(q)
        i += 1

    # ========== 修复 2：题干选项剥离（更保守的策略） ==========
    # 只有当 options 为空时才从题干里剥离选项；
    # 如果 options 已经有了，绝不从题干里再剥（避免张冠李戴）
    for q in questions:
        text = q["content"]
        opts = q["options"]
        if not opts:
            # options 为空，尝试从题干剥离
            new_text, new_opts = split_content_options(text, [])
            if new_opts and len(new_opts) >= 2:
                q["content"] = new_text
                q["options"] = new_opts
        # 否则保持原样，不做剥离

    # 重新编号
    for idx, q in enumerate(questions):
        q["source_qid"] = str(idx + 1)
        # 移除内部字段
        q.pop("_part_idx", None)

    return {
        "paper_name": paper_name,
        "subject": subject or "综合",
        "education_level": education_level,
        "questions": questions,
    }


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
    pages = list(render_pdf(path)) if suffix == ".pdf" else [path.read_bytes()]
    merged = {"part_info": []}
    page_count = 0
    for page_bytes in pages:
        page_count += 1
        page_data = ocr_bytes(api_client, page_bytes, subject)
        # 给这一页的每个 subject 加上 _page_idx，方便后面找原始图片裁切
        for part in _as_list(page_data, "part_info"):
            if isinstance(part, dict):
                for item in _as_list(part, "subject_list"):
                    if isinstance(item, dict):
                        item["_page_idx"] = page_count - 1
        merged["part_info"].extend(_as_list(page_data, "part_info"))
    return normalize(merged, path.name, subject, education_level, page_images=pages)


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
