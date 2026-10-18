#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
StudyScape 试卷导入全链路 Pipeline
=================================
传入 PDF  →  MinerU Flash Tier 本地解析  →  PageJSON blocks  →  规则引擎切题  →  Contract B JSON 输出

用法:
    python3 exam_pipeline.py input.pdf [-o output.json] [--subject 数学] [--education_level 初中] [--paper_name "2025安徽中考数学"]

依赖:
    1. MinerU flash tier parse-server 在 127.0.0.1:16580 运行
       启动命令:
         export LD_LIBRARY_PATH="/home/lian/.local/lib/python3.12/site-packages/mineru_llama_cpp/bin:$LD_LIBRARY_PATH"
         python3 -m mineru.parser.api_server --tier flash --host 127.0.0.1 --port 16580 --allow-local-source --log-level warning
    2. Python 3.10+，requests 库

作者: StudyScape Team
"""

import sys
import os
import re
import json
import time
import argparse
import hashlib
import base64
import io
import zipfile
import tempfile
from pathlib import Path
from typing import Any

import requests

# ============================================================
# 配置区
# ============================================================

MINERU_API_BASE = os.environ.get("MINERU_API_BASE", "http://127.0.0.1:16580")
MINERU_TIMEOUT = 600  # 秒
POLL_INTERVAL = 2     # 秒

# ============================================================
# 噪声/广告/页眉页脚 过滤规则
# ============================================================

# MinerU 原生的噪声 block type（直接跳过）
NOISE_BLOCK_TYPES = {"header", "footer", "page_number"}

# 用正则判断 block content 是否是噪声
def is_noise_content(content: str) -> bool:
    """
    判断一段文本是否是噪声（页眉页脚、广告、注意事项、试卷格式信息等）。
    """
    if not content or len(content) < 3:
        return True
    
    # URL / 下载网址 / APP 下载
    if re.search(r'https?://|www\.\w|下载网址|扫码下载|点击下载', content):
        return True
    
    # 页码: "第X页" / "共X页" / "第1页（共4页）"
    if re.search(r'第\s*\d+\s*页|共\s*\d+\s*页', content):
        return True
    
    # 注意事项（答题卡填写说明）
    if re.search(r'答卷前|回答选择题时|回答非选择题时|考试结束后|将答题卡交回|答题卡上对应题目的答案', content):
        return True
    
    # 广告/推广
    if re.search(r'长按图片|关注他|扫码关注|关注我|公众号|微信公众号|微博|抖音', content):
        return True
    
    # 查分/查成绩（仅限短文本）
    if len(content) < 80 and re.search(r'查分|查成绩|成绩查询', content):
        return True
    
    # 试卷格式信息（短文本）
    if len(content) < 80 and re.search(r'启封|密封线|试卷.*时间|考试时间|试卷类型|满分.*分', content):
        return True
    
    # 纯章节引导文字（"注意事项:" "一、选择题" —— 短的 section header）
    if len(content) < 40 and re.search(r'^[一二三四五六七八九十]+[、．]$', content):
        return True
    
    # 孤立的引导词
    if content.strip() in ("注意事项:", "注意事项", "秘密★启封并使用完毕前"):
        return True
    
    return False

# ============================================================
# 模块 1: MinerU Flash Tier HTTP 调用
# ============================================================

def mineru_parse_pdf(pdf_path: str, poll_interval: int = POLL_INTERVAL) -> dict:
    """
    调用 MinerU flash tier 本地 parse-server，返回 (PageJSON, images_dir)。
    
    流程:
      POST /v1/parse/jobs  →  获取 job_id (请求 zip + middle_json)
      POLL /v1/parse/jobs/{id}  →  等完成
      GET /v1/files/{file_id}/content  →  下载 middle_json + zip
      解压 zip → images_dir（图片文件落盘位置）
    
    Args:
        pdf_path: 绝对路径 PDF
        
    Returns:
        (page_json, images_dir) — images_dir 是解压后的图片根目录（zip 里有 images/ 子目录），
                                  若 zip 不可用则为 None
    """
    if not os.path.exists(pdf_path):
        raise FileNotFoundError(f"PDF 不存在: {pdf_path}")
    
    print(f"[MinerU] 提交解析任务: {pdf_path}")
    
    # Step 1: 创建解析任务（同时请求 middle_json 和 zip，zip 里有落盘图片）
    create_resp = requests.post(
        f"{MINERU_API_BASE}/v1/parse/jobs",
        json={
            "files": [{"source": {"type": "local", "path": pdf_path}}],
            "tier": "flash",
            "output_formats": ["middle_json", "zip"],
        },
        timeout=30,
    )
    create_resp.raise_for_status()
    job = create_resp.json()
    job_id = job.get("job_id")
    if not job_id:
        raise RuntimeError(f"创建 job 失败: {json.dumps(job, ensure_ascii=False)}")
    
    print(f"[MinerU] job_id={job_id}, 轮询中...")
    
    # Step 2: 轮询直到完成
    start_time = time.time()
    result = None
    while True:
        time.sleep(poll_interval)
        poll_resp = requests.get(f"{MINERU_API_BASE}/v1/parse/jobs/{job_id}", timeout=30)
        poll_resp.raise_for_status()
        result = poll_resp.json()
        status = result.get("status", "?")
        elapsed = int(time.time() - start_time)
        
        if status in ("completed", "done", "success"):
            print(f"[MinerU] ✅ 完成, 耗时 {elapsed}s")
            break
        if status in ("failed", "error", "canceled"):
            err = result.get("error", {})
            raise RuntimeError(f"MinerU 解析失败: {json.dumps(err, ensure_ascii=False)}")
        
        # 进度日志
        progress = result.get("progress", {})
        done = progress.get("completed", 0)
        total = progress.get("total", 1)
        print(f"[MinerU]   {status}  {done}/{total}  ({elapsed}s)")
        
        if elapsed > MINERU_TIMEOUT:
            raise TimeoutError(f"MinerU 解析超时 {MINERU_TIMEOUT}s")
    
    # Step 3: 提取 middle_json 文件 ID
    files = result.get("files", [])
    if not files:
        raise RuntimeError(f"Job 结果无 files: {json.dumps(result, ensure_ascii=False)[:500]}")
    
    file_obj = files[0]
    parse_info = file_obj.get("parse", {})
    # parse_info 里的 status 可能是 None (已完成时不返回)
    parse_status = parse_info.get("status")
    if parse_status and parse_status not in ("completed", "done", None):
        raise RuntimeError(f"文件 parse 状态异常: {json.dumps(parse_info, ensure_ascii=False)[:500]}")
    
    output_files = file_obj.get("output_files", {})
    middle_json_info = output_files.get("middle_json")
    if not middle_json_info:
        raise RuntimeError(f"未生成 middle_json, 可用输出: {list(output_files.keys())}")
    
    # --- Step 4a: 下载 middle_json ---
    mj_file_id = middle_json_info.get("file_id")
    print(f"[MinerU] 下载 middle_json (file_id={mj_file_id})...")
    mj_resp = requests.get(f"{MINERU_API_BASE}/v1/files/{mj_file_id}/content", timeout=60)
    mj_resp.raise_for_status()
    page_json = mj_resp.json()
    
    page_count = len(page_json.get("pages", []))
    block_count = sum(len(p.get("blocks", [])) for p in page_json.get("pages", []))
    print(f"[MinerU] ✅ PageJSON: {page_count} 页, {block_count} 个 blocks")
    
    # --- Step 4b: 下载 zip（含落盘图片），可选 ---
    images_dir = None
    zip_info = output_files.get("zip")
    if zip_info and zip_info.get("file_id"):
        try:
            zip_fid = zip_info["file_id"]
            print(f"[MinerU] 下载 zip (file_id={zip_fid})...")
            zip_resp = requests.get(f"{MINERU_API_BASE}/v1/files/{zip_fid}/content", timeout=120)
            zip_resp.raise_for_status()
            
            # 解压到临时目录
            tmpdir = tempfile.mkdtemp(prefix="mineru_img_")
            with zipfile.ZipFile(io.BytesIO(zip_resp.content), "r") as zf:
                zf.extractall(tmpdir)
            
            # zip 内部通常有 images/ 子目录
            candidates = [os.path.join(tmpdir, "images"), tmpdir]
            for c in candidates:
                if os.path.isdir(c) and any(f.lower().endswith(('.png','.jpg','.jpeg','.webp','.bmp')) for f in os.listdir(c)):
                    images_dir = tmpdir  # 返回根目录，flatten 里拼相对路径
                    break
            if not images_dir:
                images_dir = tmpdir  # 兜底：解压根目录
            print(f"[MinerU] ✅ zip 解压 → {images_dir}")
        except Exception as e:
            print(f"[MinerU] ⚠️ zip 下载失败（不影响文字，只丢图片）: {e}")
            images_dir = None
    
    return page_json, images_dir


# ============================================================
# 模块 2: Blocks → 扁平化跨页列表
# ============================================================

def extract_text_from_block_content(raw_content: Any) -> str:
    """
    MINERU 4.x Flash Tier 的 block['content'] 是嵌套结构！
    可能是:
      str                        → 直接返回
      list[str/dict]             → 拼接每一项
      {"type": "text", "content": "..."}  → 提取 .content 字段
      {"text": "..."}            → 提取 .text 字段
    """
    if raw_content is None:
        return ""
    
    if isinstance(raw_content, str):
        return raw_content.strip()
    
    if isinstance(raw_content, list):
        parts = [extract_text_from_block_content(x) for x in raw_content]
        return "\n".join(p for p in parts if p).strip()
    
    if isinstance(raw_content, dict):
        # 优先找 content / text 字段
        for key in ("content", "text"):
            if key in raw_content:
                return extract_text_from_block_content(raw_content[key])
        # 否则拼接所有 str value
        parts = [str(v) for v in raw_content.values() if isinstance(v, (str, int, float))]
        return " ".join(parts).strip()
    
    return str(raw_content).strip()


def flatten_blocks(page_json: dict) -> list[dict]:
    """
    把 MinerU PageJSON 按阅读顺序扁平化为一个 blocks 列表。
    同时过滤噪声（header/footer/page_number/广告/注意事项）。
    image block 保留 image_path / image_url 供后续关联题目。
    """
    flat = []
    pages = page_json.get("pages", [])
    skipped_noise = 0
    
    for page_idx, page in enumerate(pages):
        for block in page.get("blocks", []):
            btype = block.get("type", "text")
            content_raw = block.get("content")
            content = extract_text_from_block_content(content_raw)
            
            # 1. MinerU 原生噪声 type → 跳过
            if btype in NOISE_BLOCK_TYPES:
                skipped_noise += 1
                continue
            
            # 2. 正则噪声过滤（但 image block 不做文本噪声过滤，它们可能只有路径）
            if btype != "image" and is_noise_content(content):
                skipped_noise += 1
                continue
            
            entry = {
                "page_idx": page_idx,
                "type": btype,
                "bbox": block.get("bbox", [0, 0, 1, 1]),
                "content": content,
                "level": block.get("level"),
                "index": block.get("index"),
            }
            
            # ★ 图片类型: image / chart / table —— MinerU flash tier 三种块都带 image_path
            # image_path 藏在嵌套结构里: block.content[0].image_path (数组里的 dict)
            IMAGE_TYPES = {"image", "chart", "table"}
            if btype in IMAGE_TYPES:
                img_path = None
                img_url = None
                # 优先级: 嵌套 content[0] > 顶层 block 字段 > content_raw (仅当它是 dict)
                nested = None
                if isinstance(content_raw, list) and content_raw:
                    nested = content_raw[0] if isinstance(content_raw[0], dict) else None
                elif isinstance(content_raw, dict):
                    nested = content_raw
                if nested:
                    img_path = nested.get("image_path") or nested.get("path")
                    img_url = nested.get("image_url") or nested.get("url")
                if not img_path:
                    img_path = block.get("image_path") or block.get("path")
                if not img_url:
                    img_url = block.get("image_url") or block.get("url")
                entry["image_path"] = img_path  # zip 解压后的相对路径（如 "images/page_0_chart_14.jpg"）
                entry["image_url"] = img_url
                entry["btype_original"] = btype  # 保留原始 type 供 classify_block 用
            
            flat.append(entry)
    
    # 按 page_idx → 分栏 → y 排序
    # MinerU bbox 是归一化坐标 (0~1)，两栏试卷 x=0~0.4 左栏，x=0.4~1.0 右栏
    # 先左栏从上到下，再右栏从上到下
    def col_key(b):
        x = b["bbox"][0] if len(b["bbox"]) > 0 else 0
        return 0 if x < 0.4 else 1
    
    flat.sort(key=lambda b: (b["page_idx"], col_key(b), b["bbox"][1], b["bbox"][0]))
    
    # 清理 block 开头的题号前缀噪声
    # 处理: "2 2." → "2." / "x 12." → "12." / "√x,x≥0 13." → "13." 等
    for b in flat:
        content = b["content"]
        # 先处理 "数字+空格+数字." 形式（页眉粘字）
        content = re.sub(r'^(\d{1,3})\s+(\d{1,3})[\s]*[、。.．:：]', r'\2. ', content)
        # 再处理非数字前缀噪声（<= 15 字符）
        m = re.search(r'(\d{1,3})[\s]*[、。.．:：]', content)
        if m and m.start() > 0 and m.start() <= 15:
            prefix = content[:m.start()]
            # 前缀不能包含太多有意义的文字（检查是否大部分是符号/空格）
            symbol_count = sum(1 for c in prefix if not c.isalnum() and c not in ' \u4e00-\u9fff')
            if symbol_count >= len(prefix) * 0.3 or len(prefix) <= 5:
                content = content[m.start():]
        b["content"] = content
    
    extra = f"  过滤噪声 {skipped_noise} 个" if skipped_noise else ""
    print(f"[Flatten] ✅ {len(flat)} blocks 扁平化完成{extra}")
    for di, b in enumerate(flat[:25]):
        y1 = b["bbox"][1] if len(b["bbox"]) > 1 else 0
        x1 = b["bbox"][0] if len(b["bbox"]) > 0 else 0
        col = "L" if col_key(b) == 0 else "R"
        print(f"  [D{di:2d}] p{b['page_idx']} {col} y={y1:4.2f} x={x1:4.2f} | {b['content'][:70]}")
    return flat


# ============================================================
# 模块 3: 规则引擎 — blocks → 题目分组
# ============================================================

# 主题号识别（真正的大题起点）
# 匹配: "1、", "1。", "1.", "1)", "①"
# 不匹配: "(1)", "（1）", "(1.)" —— 这些是子题，归父题
MAIN_QUESTION_PATTERNS = [
    r'^(\d{1,3})[\s]*[、。.．:：]\s',          # "1、" "1。" "1." "1："
    r'^(\d{1,3})[\s]*[\)）]\s+',              # "1) xxx" "1） xxx" 后面必须有空格
    r'[①②③④⑤⑥⑦⑧⑨⑩⑪⑫⑬⑭⑮⑯⑰⑱⑲⑳]\s',      # 圆圈数字
]
MAIN_QUESTION_RE = re.compile('|'.join(MAIN_QUESTION_PATTERNS))

# 子题识别: (1)(2) （1）（2） → 归到父题，不切新题
SUB_QUESTION_PATTERN = re.compile(r'^[\(（]\s*\d+\s*[\)）]')

# 选项行识别 —— 匹配 "A. xxx" / "B、xxx" / "A) xxx"
OPTION_PATTERN = re.compile(r'^\s*([A-Z])[\.、)）:：\s]\s*(.+)$')

# 章节标题 (大节，不是小题号): 一、二、三、或（一）（二）
SECTION_HEADING_PATTERNS = [
    r'^([一二三四五六七八九十]+)[、．]\s*',           # "一、实验概述"
    r'^[\(（]([一二三四五六七八九十]+)[\)）][\s]',   # "（一）选择题"
]
SECTION_HEADING_RE = re.compile('|'.join(SECTION_HEADING_PATTERNS))

# 材料题/大题边界
SECTION_START_PATTERNS = [
    r'^[\(（][一二三四五六七八九十]+[\)）][\s]*(选择题|非选择题|填空题|简答题|判断题|问答题|论述题)',
    r'^第[一二三四五六七八九十]+[部分章节大题]',
]
SECTION_RE = re.compile('|'.join(SECTION_START_PATTERNS))

# 答案/参考答案行
ANSWER_PATTERN = re.compile(
    r'(参考答案|答案|解析|解答|分析|【答案】|【解析】|（\d+）答案)'
    r'[:：]?\s*(.+)$'
)

# ============================================================
# 关键: 从内联题干文本里抠出选项 A. B. C. D.
# MinerU 经常把选项粘在同一行:
#   "1. 已知集合A=... 则A∩B的子集个数是（）A. 2个 B. 3个 C. 4个 D. 8个"
# 需要把 "A. 2个 B. 3个 C. 4个 D. 8个" 拆出来
# ============================================================

# 匹配内联选项 A. B. C. D. 的 regex
# 选项内容可以是任何非大写字母+分隔符的组合（允许括号、数字、符号）
INLINE_OPTION_RE = re.compile(
    r'([A-Z])[\s]*[\.、)）:：]\s+'        # A.  / A、 / A) / A： 后面必须有空格
    r'((?:(?!\s[A-Z][\s]*[\.、)）:：]\s).)+?)'
    r'(?=\s[A-Z][\s]*[\.、)）:：]\s|$)'
)

# 题干结束标记：选项通常出现在这些标记之后
STEM_END_MARKERS = [
    r'[（(]\s*[)）]\s*',       # （）/ ()
    r'[？?]\s*',               # 问号
    r'正确的是',
    r'错误的是',
    r'真命题',
    r'假命题',
]
STEM_END_RE = re.compile('|'.join(STEM_END_MARKERS))


# 选项标记位置查找（不依赖选项之间的空格，只匹配 A-D / a-d）
OPT_MARK_RE = re.compile(r'(?<![A-Za-z])([A-Da-d])[\s]*[\.、)）:：]\s*')


def extract_inline_options(text: str) -> tuple[str, list[str]]:
    """
    从一段文本里抠出内联的 A. B. C. D. 选项。

    策略（位置切分法，比 regex lookahead 鲁棒得多）:
      1. 找题干结束标记（？/（）/正确的是 等）
      2. 在标记之后找所有 [A-Z]. 标记位置
      3. 按位置切分选项内容（不要求选项之间有空格）
      4. 必须连续字母（A B C D / A B C 等）
    """
    if not re.search(r'(?<![A-Za-z])[A-Z][\s]*[\.、)）:：]', text):
        return text, []

    # Step 1: 找题干结束位置
    stem_end_pos = -1

    matches = list(STEM_END_RE.finditer(text))
    for m in reversed(matches):
        pos = m.end()
        after = text[pos:]
        if re.search(r'(?:[\s（(）)？?]|^)[A][\s]*[\.、)）:：]', after):
            stem_end_pos = pos
            break

    if stem_end_pos == -1:
        a_match = re.search(r'([（(）)？?。.]\s*|[^A-Za-z])A[\s]*[\.、)）:：]', text)
        if a_match:
            stem_end_pos = max(0, a_match.start() + len(a_match.group(1)))
        else:
            return text, []

    # Step 2: 找所有选项标记位置
    after = text[stem_end_pos:]
    marks = list(OPT_MARK_RE.finditer(after))

    if len(marks) < 2:
        return text, []

    letters = [m.group(1).upper() for m in marks]

    # 验证连续字母（统一大写后比较）
    is_consecutive = True
    for i in range(1, len(letters)):
        if ord(letters[i]) - ord(letters[0]) != i:
            is_consecutive = False
            break

    if not is_consecutive:
        return text, []

    # Step 3: 按位置切分选项内容
    options = []
    for i, m in enumerate(marks):
        start = m.end()
        end = marks[i + 1].start() if i + 1 < len(marks) else len(after)
        content = after[start:end].strip()
        content = re.sub(r'[\(\)（）\s]+$', '', content)
        if content:
            # 选项字母统一大写
            letter = m.group(1).upper()
            options.append(f"{letter}. {content}")

    if not options or len(options) < 2:
        return text, []

    stem = text[:stem_end_pos].rstrip()
    stem = re.sub(r'[（(]\s*[)）]?\s*$', '', stem).rstrip()

    return stem, options


def classify_block(block: dict) -> str:
    """
    给 block 分类：
      question_start   — 题目的起始 block（含题号，如 "1、下列..."）
      question_body    — 题目的正文延续（题干内容）
      option           — 选项行（A. B. C. D.）
      answer           — 答案/解析
      section_header   — 大题/材料题标题（如 "（一）选择题"）
      material         — 材料/阅读文本（长段落）
      page_header      — 页眉/文档标题
      separator        — 分隔空行、短段落
    """
    content = block["content"]
    btype = block["type"]
    
    # ★ 所有图片类型 —— image / chart / table 都带 image_path, 统一归为 image
    # (MinerU flash tier: 三种块都在嵌套 content[0] 里有 image_path)
    if btype in ("image", "chart", "table"):
        return "image"
    
    if not content:
        return "separator"
    
    # doc_title → 文档总标题（页眉/卷名）
    if btype == "doc_title":
        return "page_header"
    
    # paragraph_title —— 最核心的边界信号！
    if btype == "paragraph_title":
        if SECTION_HEADING_RE.search(content):
            return "section_header"
        if SECTION_RE.search(content):
            return "section_header"
        if MAIN_QUESTION_RE.match(content):
            return "question_start"
        if SUB_QUESTION_PATTERN.match(content):
            return "question_body"  # 子题归父题
        if len(content) < 60:
            return "section_header"
        return "question_body"
    
    # equation → 公式，属于题干内容
    if btype == "equation":
        return "question_body"
    
    # text block —— 章节标题
    if SECTION_HEADING_RE.search(content):
        return "section_header"
    
    # 选项（MinerU 极少切出独立选项行，更多是内联）
    if OPTION_PATTERN.match(content.strip()):
        return "option"
    
    # 子题: (1) / （1） → 归父题
    if SUB_QUESTION_PATTERN.match(content.strip()):
        return "question_body"
    
    # 主题起始
    if MAIN_QUESTION_RE.match(content.strip()):
        return "question_start"
    
    # 答案/解析
    if ANSWER_PATTERN.match(content):
        return "answer"
    
    # 大题边界
    if SECTION_RE.search(content):
        return "section_header"
    
    # 长段落（>200 字）且没有题号开头 → 可能是材料/阅读
    if len(content) > 200 and not MAIN_QUESTION_RE.match(content):
        return "material"
    
    # 默认：题干延续
    return "question_body"


def detect_question_type(blocks_in_question: list[dict]) -> str:
    """
    根据题目内部的 blocks 推断题型。
    
    Returns:
        中文题型标签: "单选题" / "多选题" / "判断题" / "填空题" / "简答题" / "选择题"(不确定时)
    """
    # 拼出整个题目的文本
    full_text = "\n".join(b["content"] for b in blocks_in_question)
    
    # 1. 先看有没有明确的题型标签
    if re.search(r'多选题|不定项选择', full_text):
        return "多选题"
    if re.search(r'判断题|对错题|正确的打√|错误的打×|正确的选[A]|错误的选[B]', full_text):
        return "判断题"
    
    # 2. 看选项数量
    option_blocks = [b for b in blocks_in_question if b["_class"] == "option"]
    option_count = len(option_blocks)
    
    if option_count == 0:
        # 无选项 —— 填空 or 简答
        if re.search(r'[填空横线]{2,}|[___]{2,}|（\s*[，、]*\s*）', full_text):
            return "填空题"
        return "简答题"
    
    if option_count == 2:
        # 2 选项 → 大概率判断题
        opts_text = " ".join(b["content"] for b in option_blocks).upper()
        if any(k in opts_text for k in ["正确", "错误", "对", "错", "T", "F"]):
            return "判断题"
        return "判断题"  # 默认
    
    if option_count >= 4:
        # 4+ 选项 —— 看有没有"正确的是"/"错误的是"/"所有正确"来区分多/单
        if re.search(r'所有.*正确|正确的是.*组|全部正确|不定项', full_text):
            return "多选题"
        return "单选题"
    
    # 其他情况
    return "选择题"


def group_questions(blocks: list[dict]) -> list[dict]:
    """
    核心规则引擎：把分类好的 blocks 切分成独立题目。
    
    策略:
      1. 所有 blocks 先 classify
      2. 遇到 question_start → 新题开始
      3. 遇到 section_header → 新题开始（大题标题后紧跟小题）
      4. page_header / separator → 跳过
      5. material 跟随最近的 question_start（材料题绑定）
      
    Returns:
        [{"qid": "1", "blocks": [...], "qtype": "单选题", "page_start": 0, "page_end": 1}]
    """
    # Step 1: 分类
    for block in blocks:
        block["_class"] = classify_block(block)
    
    print(f"[Group] 分类分布:")
    from collections import Counter
    cls_count = Counter(b["_class"] for b in blocks)
    for cls, cnt in sorted(cls_count.items()):
        print(f"  {cls:20s}: {cnt}")
    
    # Step 2: 切题
    questions = []
    current_q = None
    section_title = ""  # 当前大题标题（如"（一）选择题"）
    material_text = ""   # 当前材料文本
    
    for block in blocks:
        cls = block["_class"]
        
        if cls == "page_header" or cls == "separator":
            # 跳过页眉和空块
            continue
        
        if cls == "section_header":
            section_title = block["content"]
            # section_header 本身也算题目起始信号
            # 不单独成题，等下一个 question_start 跟它合并
            continue
        
        if cls == "material":
            # 材料 —— 跟随下一题
            material_text += "\n" + block["content"]
            continue
        
        if cls == "question_start":
            # 保存上一题
            if current_q is not None:
                questions.append(finalize_question(current_q, section_title, material_text))
            
            # 新题开始
            current_q = {
                "blocks": [block],
                "qid_text": extract_qid(block["content"]),
                "page_start": block["page_idx"],
                "page_end": block["page_idx"],
            }
            # 材料只归第一题
            material_text = ""
            continue
        
        # question_body / option / answer → 归到当前题目
        if current_q is not None:
            current_q["blocks"].append(block)
            current_q["page_end"] = block["page_idx"]
        else:
            # 还没遇到 question_start 就有 body block —— 可能是材料/前言，跳过
            pass
    
    # 保存最后一题
    if current_q is not None:
        questions.append(finalize_question(current_q, section_title, material_text))
    
    # 题型识别先不做 —— 因为需要合并文本、提取内联选项后才能准确判断
    # 真正的题型判断在 build_contract_b_questions() 里用 detect_qtype_from_content()
    
    print(f"\n[Group] ✅ 切出 {len(questions)} 道题")
    for i, q in enumerate(questions[:5]):
        first_line = q["blocks"][0]["content"][:50] if q["blocks"] else ""
        pages = f"p{q['page_start']}-{q['page_end']}" if q['page_start'] != q['page_end'] else f"p{q['page_start']}"
        print(f"  [{i+1}] {q['qid_text']} {pages}: {first_line}")
    
    return questions


def extract_qid(content: str) -> str:
    """从题目标题文本里提取题号 (1 / 11 / ①)"""
    m = MAIN_QUESTION_RE.match(content.strip())
    if m:
        qid = m.group(1) or m.group(0)
        return qid.strip()[:10]
    return "?"


def finalize_question(raw_q: dict, section_title: str, material_text: str) -> dict:
    """
    把原始切出来的题目补全 section/material 信息。
    """
    blocks = raw_q["blocks"]
    
    # 把 section_header 和 material prepend 到第一题（如果 material_text 不为空）
    # 但保持 blocks 原样 —— material 作为单独字段记录
    return {
        "qid_text": raw_q["qid_text"],
        "blocks": blocks,
        "page_start": raw_q["page_start"],
        "page_end": raw_q["page_end"],
        "section_title": section_title,
        "material_text": material_text.strip(),
        "qtype": "选择题",  # 占位，后面由 detect_question_type 填
    }


# ============================================================
# 模块 4: 题目 → Contract B JSON 转换
# ============================================================

def _read_image_as_base64(image_block: dict, images_dir: str | None) -> str | None:
    """
    从 image block 读取图片 → base64（用于 question_images 字段）。
    
    三种来源，按优先级:
      1. image_url 是 data URI → 直接用
      2. image_path + images_dir → 从本地 zip 解压目录读文件
      3. image_url 是 http(s) → 下载（兜底）
    """
    # 1) 已经是 data URI
    url = image_block.get("image_url") or ""
    if url.startswith("data:image/"):
        return url  # 直接返回
    
    # 2) 本地文件路径
    rel_path = image_block.get("image_path")
    if rel_path and images_dir:
        # MinerU zip 里的路径可能是 "images/xxx.png" 或相对路径
        candidates = [
            os.path.join(images_dir, rel_path),
            os.path.join(images_dir, "images", os.path.basename(rel_path)),
            os.path.join(images_dir, os.path.basename(rel_path)),
        ]
        for fp in candidates:
            if os.path.isfile(fp):
                try:
                    with open(fp, "rb") as f:
                        data = f.read()
                    mime = "image/png"
                    ext = os.path.splitext(fp)[1].lower()
                    if ext in (".jpg", ".jpeg"): mime = "image/jpeg"
                    elif ext == ".webp": mime = "image/webp"
                    elif ext == ".bmp": mime = "image/bmp"
                    return f"data:{mime};base64,{base64.b64encode(data).decode()}"
                except Exception:
                    continue
    
    # 3) HTTP URL 兜底
    if url.startswith("http"):
        try:
            r = requests.get(url, timeout=10)
            if r.status_code == 200:
                ct = r.headers.get("Content-Type", "image/png")
                return f"data:{ct};base64,{base64.b64encode(r.content).decode()}"
        except Exception:
            pass
    
    return None


def build_contract_b_questions(questions: list[dict], images_dir: str | None = None) -> list[dict]:
    """
    把切好的题目列表转成 StudyScape Contract B (PaperCutter-VL) 格式。
    
    关键: MinerU 经常把选项 A. B. C. D. 内联在题干文本里，
    这里会先从题干里把选项抠出来，再判断题型。
    
    image block 通过 images_dir + image_path 读成 base64 → question_images
    """
    output = []
    
    for i, q in enumerate(questions):
        content_parts = []
        options = []
        answer_text = ""
        resolve_text = ""
        image_blocks = []  # 收集当前题关联的 image blocks
        
        if q.get("material_text"):
            content_parts.append(q["material_text"])
        
        for block in q["blocks"]:
            cls = block["_class"]
            content = block["content"]
            
            # ★ image block → 收集，不进题干
            if cls == "image":
                image_blocks.append(block)
                continue
            
            if cls == "option":
                continue  # option blocks 在后面统一合并（去重+格式化）
            
            if cls == "answer":
                ans_match = ANSWER_PATTERN.match(content)
                if ans_match:
                    extracted = ans_match.group(2).strip()
                    if "答案" in content and "解析" not in content:
                        answer_text = extracted
                    elif "解析" in content or "解答" in content or "分析" in content:
                        resolve_text = extracted
                continue
            
            if content:
                content_parts.append(content)
        
        # 合并题干（先去重、去噪声残留）
        question_content = "\n".join(content_parts).strip()
        # 合并后去掉页码残留
        question_content = re.sub(r'第\s*\d+\s*页.*共\s*\d+\s*页', '', question_content)
        
        # ★★★ 关键步骤: 从合并后的题干里抠内联选项 ★★★
        stem_clean, inline_opts = extract_inline_options(question_content)
        if inline_opts:
            question_content = stem_clean
            options = list(inline_opts)
        else:
            options = []
        
        # ★ 合并独立 option blocks（MinerU 把选项切成独立 block 时）
        option_blocks = [b for b in q["blocks"] if b.get("_class") == "option"]
        # 提取 option block 里的选项字母用于去重
        existing_letters = set()
        for opt in options:
            m = re.match(r'^([A-Da-d])[\.、)）:：]', opt)
            if m:
                existing_letters.add(m.group(1).upper())
        
        for ob in option_blocks:
            oc = ob["content"].strip()
            m = OPTION_PATTERN.match(oc)
            if m:
                letter = m.group(1).upper()
                if letter not in existing_letters:
                    options.append(f"{letter}. {m.group(2).strip()}")
                    existing_letters.add(letter)
            else:
                # 可能已经是 "X. xxx" 格式
                m2 = re.match(r'^([A-Da-d])[\.、)）:：]\s*(.+)', oc)
                if m2 and m2.group(1).upper() not in existing_letters:
                    options.append(f"{m2.group(1).upper()}. {m2.group(2).strip()}")
                    existing_letters.add(m2.group(1).upper())
        
        # 题型判断（现在有了正确的 options 数量）
        qtype = detect_qtype_from_content(question_content, options, q["qtype"])
        
        diff = estimate_difficulty(question_content, qtype)
        
        try:
            qid = int(q["qid_text"])
        except (ValueError, TypeError):
            qid = i + 1000
        
        # ★ question_images: 从 image blocks + images_dir 读 base64
        question_images = []
        for ib in image_blocks:
            img_b64 = _read_image_as_base64(ib, images_dir)
            if img_b64:
                question_images.append(img_b64)
        
        item = {
            "question_id": qid,
            "question_type": qtype,
            "question_content": question_content,
            "question_options": options if options else [],
            "question_images": question_images,
            "question_tables": [],
            "answer": answer_text,
            "resolve": resolve_text,
            "difficulty": diff,
            "source": q.get("section_title", ""),
        }
        output.append(item)
    
    return output


def detect_qtype_from_content(stem: str, options: list[str], fallback: str = "简答题") -> str:
    """
    根据题干 + 选项列表判断题型。
    
    优先级:
      1. 选项数量 (最可靠 —— 内联提取后拿到的)
      2. 题干里的题型标签 (如 "多选题" "判断题")
      3. 填空横线 (_ / （）)
    """
    # 1. 题干里明确写了题型
    if re.search(r'多选题|不定项选择', stem):
        return "多选题"
    if re.search(r'判断题|对错题|正确的打√|错误的打×', stem):
        return "判断题"
    
    # 2. 看选项数量
    n = len(options)
    
    if n == 0:
        # 无选项 —— 填空 or 简答
        if re.search(r'_{2,}|\u3000{2,}|（\s*[，、]*\s*）|\(\s*[，、]*\s*\)', stem):
            return "填空题"
        return "简答题"
    
    if n == 2:
        opts_text = " ".join(options).upper()
        if any(k in opts_text for k in ["正确", "错误", "对", "错", "T", "F", "√", "×"]):
            return "判断题"
        return "判断题"  # 默认 2 选项 → 判断
    
    if n >= 4:
        if re.search(r'所有.*正确|全部正确|不定项|至少一个', stem):
            return "多选题"
        return "单选题"
    
    return "选择题"  # 3 个等模糊情况


def estimate_difficulty(content: str, qtype: str) -> int:
    """粗略推断难度 1-5"""
    score = 2  # 默认中等
    
    # 长题干 / 多空 → 较难
    length = len(content)
    if length > 500: score += 1
    if length > 1000: score += 1
    
    # 简答题 / 论述题 → 较难
    if qtype in ("简答题", "论述题", "问答题"):
        score += 1
    
    # 关键词
    hard_keywords = ["综合运用", "拓展", "探究", "分析", "推导", "计算", "证明"]
    for kw in hard_keywords:
        if kw in content:
            score += 0.5
            break
    
    # 封顶
    return max(1, min(5, int(round(score))))


# ============================================================
# 模块 5: 验证 + 输出
# ============================================================

def validate_and_clean(questions: list[dict]) -> list[dict]:
    """
    最终清洗:
      - 去 content 为 empty 的
      - 去 question_type 为 None/empty 的
      - options 为 [] → 保持（后端接受空数组）
    """
    valid = []
    skipped = 0
    for q in questions:
        content = q.get("question_content", "")
        qtype = q.get("question_type", "")
        
        if not content or len(content.strip()) < 5:
            skipped += 1
            continue
        if not qtype:
            skipped += 1
            continue
        
        valid.append(q)
    
    if skipped:
        print(f"[Validate] ⚠️ 跳过 {skipped} 道空题/无效题")
    print(f"[Validate] ✅ 保留 {len(valid)} 道有效题")
    
    return valid


def output_json(questions: list[dict], paper_name: str = "", 
                subject: str = "", education_level: str = "") -> dict:
    """
    组装最终 Contract B 包装格式 (api.php importExtractQuestions 模式 0a)。
    """
    paper_hash = hashlib.md5(
        (paper_name + "|" + subject + "|" + str(len(questions))).encode()
    ).hexdigest()[:12]
    
    return {
        "match_key": paper_hash,
        "paper_name": paper_name,
        "subject": subject,
        "education_level": education_level,
        "questions": questions,
    }


# ============================================================
# CLI 入口
# ============================================================

def main():
    parser = argparse.ArgumentParser(description="StudyScape PDF → 题库 JSON Pipeline")
    parser.add_argument("pdf", help="输入 PDF 文件路径")
    parser.add_argument("-o", "--output", default=None, help="输出 JSON 文件路径 (默认: 同名 .json)")
    parser.add_argument("--subject", default="综合", help="科目 (默认: 综合)")
    parser.add_argument("--education_level", default="", help="学段 (初中/高中/大学)")
    parser.add_argument("--paper_name", default="", help="试卷名称 (默认从 PDF 文件名推断)")
    parser.add_argument("--raw-markdown", action="store_true", help="额外保存 MinerU 原始 markdown")
    args = parser.parse_args()
    
    pdf_path = os.path.abspath(args.pdf)
    if not os.path.exists(pdf_path):
        print(f"❌ PDF 不存在: {pdf_path}")
        sys.exit(1)
    
    # 推断输出路径
    if args.output:
        out_path = os.path.abspath(args.output)
    else:
        base = os.path.splitext(pdf_path)[0]
        out_path = base + ".json"
    
    # 推断试卷名称
    paper_name = args.paper_name or os.path.splitext(os.path.basename(pdf_path))[0]
    
    print(f"\n{'='*60}")
    print(f"StudyScape PDF → JSON Pipeline")
    print(f"{'='*60}")
    print(f"输入: {pdf_path}")
    print(f"输出: {out_path}")
    print(f"科目: {args.subject}  学段: {args.education_level}")
    print(f"试卷: {paper_name}")
    print(f"MinerU API: {MINERU_API_BASE}")
    print()
    
    total_start = time.time()
    
    # --- Step 1: MinerU 解析 ---
    try:
        page_json, images_dir = mineru_parse_pdf(pdf_path)
        if images_dir:
            print(f"\n📷 图片解压目录: {images_dir}")
    except requests.exceptions.ConnectionError:
        print(f"\n❌ MinerU parse-server 连接失败!")
        print(f"   请先启动:")
        print(f"   export LD_LIBRARY_PATH=\"/home/lian/.local/lib/python3.12/site-packages/mineru_llama_cpp/bin:$LD_LIBRARY_PATH\"")
        print(f"   python3 -m mineru.parser.api_server --tier flash --host 127.0.0.1 --port 16580 --allow-local-source --log-level warning")
        sys.exit(1)
    except Exception as e:
        print(f"\n❌ MinerU 解析失败: {e}")
        sys.exit(1)
    
    # --- Step 2: Flatten ---
    blocks = flatten_blocks(page_json)
    
    # --- Step 3: Group questions ---
    questions_raw = group_questions(blocks)
    
    # --- Step 4: Build Contract B ---
    questions_cb = build_contract_b_questions(questions_raw, images_dir=images_dir)
    
    # --- Step 5: Validate + Output ---
    questions_clean = validate_and_clean(questions_cb)
    
    if not questions_clean:
        print(f"\n❌ 最终没有有效题目 —— 检查 PDF 质量或调整规则")
        sys.exit(1)
    
    result = output_json(
        questions_clean,
        paper_name=paper_name,
        subject=args.subject,
        education_level=args.education_level,
    )
    
    # 写 JSON
    with open(out_path, "w", encoding="utf-8") as f:
        json.dump(result, f, ensure_ascii=False, indent=2)
    
    total_elapsed = time.time() - total_start
    
    print(f"\n{'='*60}")
    print(f"✅ 完成!")
    print(f"{'='*60}")
    print(f"总耗时: {total_elapsed:.1f}s")
    print(f"输出: {out_path}")
    print(f"题目: {len(questions_clean)} 道")
    print(f"  单选: {sum(1 for q in questions_clean if q['question_type']=='单选题')}")
    print(f"  多选: {sum(1 for q in questions_clean if q['question_type']=='多选题')}")
    print(f"  判断: {sum(1 for q in questions_clean if q['question_type']=='判断题')}")
    print(f"  填空: {sum(1 for q in questions_clean if q['question_type']=='填空题')}")
    print(f"  简答: {sum(1 for q in questions_clean if q['question_type']=='简答题')}")
    print()
    print(f"📋 下一步: 把 {out_path} 上传到 StudyScape 前端试卷导入页面即可")


if __name__ == "__main__":
    main()
