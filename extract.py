#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
文档提取与题目解析模块
支持 PDF（含扫描件 OCR）、DOC、DOCX、TXT。

用法：
    extract.py <文件路径>            # 仅提取纯文本
    extract.py <文件路径> --parse    # 提取文本并尝试解析选择题

输出（JSON）：
    {
        "success": true,
        "file": "...",
        "type": "pdf",
        "method": "ocr",
        "text": "...",
        "questions": [ ... ]   # 仅 --parse 时存在
    }
"""

import json
import os
import re
import subprocess
import sys
import tempfile


def run(cmd, timeout=300):
    """执行外部命令并返回 (returncode, stdout)。"""
    try:
        proc = subprocess.run(
            cmd, stdout=subprocess.PIPE, stderr=subprocess.DEVNULL,
            timeout=timeout, check=False
        )
        return proc.returncode, proc.stdout.decode("utf-8", errors="ignore")
    except Exception:
        return -1, ""


def file_type(path):
    ext = os.path.splitext(path)[1].lower()
    mapping = {
        ".pdf": "pdf",
        ".doc": "doc",
        ".docx": "docx",
        ".txt": "txt",
        ".md": "txt",
    }
    return mapping.get(ext, ext.lstrip(".") or "unknown")


def extract_txt(path):
    with open(path, "r", encoding="utf-8", errors="ignore") as f:
        return f.read()


def extract_doc(path):
    code, out = run(["antiword", path])
    if code == 0 and out.strip():
        return out, "antiword"
    # antiword 失败时退回 strings（尽力而为）
    code, out = run(["catdoc", path])
    if code == 0 and out.strip():
        return out, "catdoc"
    return "", "antiword"


def extract_docx(path):
    try:
        from docx import Document
    except Exception:
        return "", "docx"
    doc = Document(path)
    parts = [p.text for p in doc.paragraphs]
    for table in doc.tables:
        for row in table.rows:
            parts.append("\t".join(cell.text for cell in row.cells))
    return "\n".join(parts), "docx"


def extract_pdf(path):
    """先尝试 pdftotext，文本过少则走 OCR。"""
    code, out = run(["pdftotext", "-layout", path, "-"])
    if code == 0:
        cleaned = re.sub(r"\s+", "", out)
        if len(cleaned) >= 50:
            return out, "pdftotext"

    # OCR 扫描件
    try:
        from pdf2image import convert_from_path
        import pytesseract
    except Exception:
        return "", "ocr"
    try:
        pages = convert_from_path(path, dpi=200)
    except Exception:
        return "", "ocr"

    texts = []
    for page in pages:
        try:
            txt = pytesseract.image_to_string(page, lang="chi_sim+eng", config="--psm 6")
        except Exception:
            txt = ""
        texts.append(txt)
    return "\n".join(texts), "ocr"


# 选项标记：A. / A、 / A， / A- / A。 等 OCR 常见变体
OPTION_MARK_RE = re.compile(r"(?<![A-Za-z])([A-H])\s*[.、．，,。\-_:]\s*")
QUESTION_RE = re.compile(r"^(\d{1,3})\s*[.、．，,、]?\s*(.*)$")


def extract_options(line):
    """从一行中提取所有选项，返回 ['A. xxx', 'B. yyy', ...]。"""
    matches = list(OPTION_MARK_RE.finditer(line))
    if not matches:
        return []
    opts = []
    for i, m in enumerate(matches):
        start = m.end()
        end = matches[i + 1].start() if i + 1 < len(matches) else len(line)
        text = line[start:end].strip(" \t,，。;；")
        if text:
            opts.append("%s. %s" % (m.group(1), text))
    return opts


def parse_choice_questions(text):
    """解析选择题（题号 + A-D 选项），返回题目列表。"""
    lines = [l.rstrip() for l in text.split("\n")]
    questions = []
    current = None

    def flush():
        nonlocal current
        if current and len(current["options"]) >= 2 and current["stem"].strip():
            questions.append(current)
        current = None

    for line in lines:
        s = line.strip()
        if not s:
            continue

        # 跳过答案区间行（如 1-5: DBCCB）
        if re.match(r"^\d{1,3}\s*[-~—]\s*\d{1,3}\s*[:：]", s):
            continue

        m = QUESTION_RE.match(s)
        if m:
            rest = m.group(2).strip()
            # 若紧跟选项标记，说明上一题题干为空，忽略该“题号”
            if OPTION_MARK_RE.match(rest):
                # 当作选项继续处理
                if current is not None:
                    current["options"].extend(extract_options(s))
                continue
            flush()
            current = {
                "number": int(m.group(1)),
                "stem": rest,
                "options": [],
            }
            # 同行可能带选项
            current["options"].extend(extract_options(rest))
            continue

        if current is not None:
            opts = extract_options(s)
            if opts:
                current["options"].extend(opts)
            else:
                current["stem"] += " " + s

    flush()

    # 过滤：题干与选项不能为空，且选项数量合理（2-8）
    result = []
    for q in questions:
        stem = re.sub(r"\s+", " ", q["stem"]).strip()
        if not stem:
            continue
        opts = q["options"]
        if len(opts) < 2 or len(opts) > 8:
            continue
        result.append({
            "number": q["number"],
            "question_type": "single" if len(opts) == 4 else "multiple",
            "content": stem,
            "options": opts,
            "correct_answer": "",
            "points": 2,
        })
    return result


def parse_answer_key(text):
    """解析参考答案区间，如 '1-5: DBCCB'，返回 {题号: 字母}。"""
    answers = {}
    for m in re.finditer(r"(\d{1,3})\s*[-~—]\s*(\d{1,3})\s*[:：]\s*([A-Ha-h]+)", text):
        start = int(m.group(1))
        end = int(m.group(2))
        letters = m.group(3).upper()
        for i, ch in enumerate(letters):
            num = start + i
            if num <= end:
                answers[num] = ch
    return answers


def apply_answers(questions, text):
    answers = parse_answer_key(text)
    for q in questions:
        letter = answers.get(q["number"])
        if letter:
            for opt in q["options"]:
                if opt.startswith(letter + "."):
                    q["correct_answer"] = opt
                    break


def main():
    if len(sys.argv) < 2:
        print(json.dumps({"success": False, "message": "缺少文件路径"}, ensure_ascii=False))
        return 1

    path = sys.argv[1]
    do_parse = "--parse" in sys.argv

    result = {
        "success": False,
        "file": os.path.basename(path),
        "type": file_type(path),
        "method": "",
        "text": "",
    }

    if not os.path.isfile(path):
        result["message"] = "文件不存在"
        print(json.dumps(result, ensure_ascii=False))
        return 1

    ftype = result["type"]
    try:
        if ftype == "pdf":
            text, method = extract_pdf(path)
        elif ftype == "doc":
            text, method = extract_doc(path)
        elif ftype == "docx":
            text, method = extract_docx(path)
        elif ftype == "txt":
            text, method = extract_txt(path), "txt"
        else:
            result["message"] = "不支持的文件类型: %s" % ftype
            print(json.dumps(result, ensure_ascii=False))
            return 1
    except Exception as exc:
        result["message"] = "提取失败: %s" % exc
        print(json.dumps(result, ensure_ascii=False))
        return 1

    if not text.strip():
        result["message"] = "未能提取到任何文本"
        print(json.dumps(result, ensure_ascii=False))
        return 1

    result["success"] = True
    result["method"] = method
    result["text"] = text

    if do_parse:
        questions = parse_choice_questions(text)
        apply_answers(questions, text)
        result["questions"] = questions

    print(json.dumps(result, ensure_ascii=False))
    return 0


if __name__ == "__main__":
    sys.exit(main())
