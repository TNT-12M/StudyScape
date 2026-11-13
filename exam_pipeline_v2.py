#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
StudyScape PDF → JSON Pipeline v2 (Better Layout + OCR)
======================================================
用 DocLayout-YOLO 做版面分析 + RapidOCR 做文字识别，替代 MinerU flash tier。
然后复用 study_ocr_rules.py 里的规则引擎切题 + Contract B 输出。

依赖:
    pip install doclayout-yolo rapidocr-onnxruntime pypdfium2
"""

import sys
import os
import re
import json
import time
import argparse
import hashlib
from typing import Any

import pypdfium2 as pdfium

# ============================================================
# 模块 0: 依赖检查 + 模型加载
# ============================================================

print("=" * 60)
print("StudyScape PDF → JSON Pipeline v2 (DocLayout-YOLO + RapidOCR)")
print("=" * 60)

try:
    from doclayout_yolo import YOLOv10
    print("[OK] DocLayout-YOLO 已安装")
except ImportError:
    print("[!!] 请先安装: pip install doclayout-yolo")
    sys.exit(1)

try:
    from rapidocr_onnxruntime import RapidOCR
    print("[OK] RapidOCR 已安装")
except ImportError:
    print("[!!] 请先安装: pip install rapidocr-onnxruntime")
    sys.exit(1)

# 全局模型（延迟加载）
_layout_model = None
_ocr_engine = None

def get_layout_model():
    global _layout_model
    if _layout_model is None:
        print("[Model] 加载 DocLayout-YOLO 版面分析模型...")
        _layout_model = YOLOv10("doclayout_yolo_docstructbench_imgsz1024.pt")
        print(f"[Model] ✅ DocLayout-YOLO 加载完成")
    return _layout_model

def get_ocr_engine():
    global _ocr_engine
    if _ocr_engine is None:
        print("[Model] 加载 RapidOCR 引擎...")
        _ocr_engine = RapidOCR()
        print(f"[Model] ✅ RapidOCR 加载完成")
    return _ocr_engine


# ============================================================
# 模块 1: PDF → 图片
# ============================================================

def pdf_to_images(pdf_path: str, dpi: int = 200) -> list[Any]:
    """把 PDF 每页转成 PIL Image"""
    print(f"[PDF] 加载: {pdf_path}")
    pdf = pdfium.PdfDocument(pdf_path)
    images = []
    for i in range(len(pdf)):
        page = pdf[i]
        bitmap = page.render(scale=dpi / 72.0)
        pil_image = bitmap.to_pil()
        images.append(pil_image)
    print(f"[PDF] ✅ {len(images)} 页, DPI={dpi}")
    return images


# ============================================================
# 模块 2: 版面分析 (DocLayout-YOLO) + OCR (RapidOCR)
# ============================================================

def analyze_page(image, page_idx: int) -> list[dict]:
    """
    对单页 PDF 做版面分析 + OCR。
    
    Returns:
        [{"page_idx": 0, "type": "title|text|figure|table|formula|list|abandon|header|footer",
          "bbox": [x1,y1,x2,y2] (像素坐标), "content": "..."}]
    """
    layout_model = get_layout_model()
    ocr = get_ocr_engine()
    
    # DocLayout-YOLO 预测 (不做 image size 转换，直接用 PIL)
    import numpy as np
    img_np = np.array(image)
    results = layout_model.predict(img_np, conf=0.2, iou=0.45, verbose=False)
    
    blocks = []
    img_w, img_h = image.size
    
    for result in results:
        boxes = result.boxes  # Boxes object
        if boxes is None or len(boxes) == 0:
            continue
        
        # doclayout_yolo 返回的 classes:
        # 0=title, 1=plain_text, 2=abandon, 3=figure, 4=table, 5=formula, 6=list
        # 7=figure_caption, 8=table_caption, 9=header, 10=footer, 11=subfigure_caption
        # 12=seal, 13=checkbox
        cls_mapping = {
            0: "title",        # 标题 (可能是 section header 或题号开头)
            1: "text",         # 正文
            2: "abandon",      # 废弃/噪声
            3: "figure",       # 图片/几何图
            4: "table",        # 表格
            5: "formula",      # 数学公式
            6: "list",         # 列表 (可能是选项 A. B. C. D.)
            7: "caption",      # 图注
            8: "caption",      # 表注
            9: "header",       # 页眉
            10: "footer",      # 页脚
            11: "caption",
            12: "seal",
            13: "checkbox",
        }
        
        for box_idx in range(len(boxes)):
            cls_id = int(boxes.cls[box_idx])
            x1, y1, x2, y2 = boxes.xyxy[box_idx].tolist()
            conf = float(boxes.conf[box_idx])
            
            block_type = cls_mapping.get(cls_id, "text")
            
            # 提取 bbox 区域做 OCR
            x1, y1, x2, y2 = map(int, [x1, y1, x2, y2])
            x1 = max(0, x1); y1 = max(0, y1)
            x2 = min(img_w, x2); y2 = min(img_h, y2)
            
            crop = image.crop((x1, y1, x2, y2))
            
            # OCR
            ocr_result, _ = ocr(crop)
            if ocr_result:
                # 按 y 坐标排序后拼接
                ocr_result.sort(key=lambda r: r[0][0][1])
                content = " ".join(r[1] for r in ocr_result)
            else:
                content = ""
            
            blocks.append({
                "page_idx": page_idx,
                "type": block_type,
                "bbox": [x1 / img_w, y1 / img_h, x2 / img_w, y2 / img_h],  # 归一化
                "bbox_px": [x1, y1, x2, y2],  # 像素坐标（用于排序）
                "content": content.strip(),
                "conf": conf,
            })
    
    return blocks


# ============================================================
# 模块 3: 噪声过滤 + 排序（复用 study_ocr_rules 里的逻辑）
# ============================================================

NOISE_TYPES = {"header", "footer", "abandon", "seal", "checkbox"}

def is_noise_content(content: str) -> bool:
    """过滤广告/页码/注意事项"""
    if not content or len(content) < 3:
        return True
    if re.search(r'https?://|www\.\w|下载网址|扫码下载', content):
        return True
    if re.search(r'第\s*\d+\s*页|共\s*\d+\s*页', content):
        return True
    if re.search(r'答卷前|回答选择题时|考试结束后|将答题卡交回', content):
        return True
    if re.search(r'长按图片|关注他|扫码关注|公众号|微信公众号', content):
        return True
    if len(content) < 80 and re.search(r'启封|密封线|考试时间|满分.*分', content):
        return True
    return False


def process_blocks(all_blocks: list[dict]) -> list[dict]:
    """过滤噪声 + 排序"""
    filtered = []
    skipped = 0
    
    for b in all_blocks:
        if b["type"] in NOISE_TYPES:
            skipped += 1
            continue
        if is_noise_content(b["content"]):
            skipped += 1
            continue
        filtered.append(b)
    
    # 按 page_idx → y → x 排序
    filtered.sort(key=lambda b: (b["page_idx"], b["bbox_px"][1], b["bbox_px"][0]))
    
    print(f"[Blocks] ✅ {len(filtered)} blocks (过滤噪声 {skipped} 个)")
    return filtered


# ============================================================
# 模块 4: 复用 exam_pipeline.py 的规则引擎
# ============================================================
# 直接 import exam_pipeline 的核心函数

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))

def run_pipeline(pdf_path: str, output_path: str, subject: str, 
                 education_level: str, paper_name: str):
    total_start = time.time()
    
    # Step 1: PDF → 图片
    images = pdf_to_images(pdf_path, dpi=200)
    
    # Step 2: 版面分析 + OCR
    all_blocks = []
    for pi, img in enumerate(images):
        t0 = time.time()
        blocks = analyze_page(img, pi)
        elapsed = time.time() - t0
        print(f"[Layout+OCR] 页 {pi}: {len(blocks)} blocks, {elapsed:.1f}s")
        all_blocks.extend(blocks)
    
    # Step 3: 噪声过滤 + 排序
    blocks = process_blocks(all_blocks)
    
    # Step 4: 扁平化到 exam_pipeline 格式
    # exam_pipeline 期望: page_idx, type (doc_title/text/paragraph_title), bbox, content, level, index
    flat = []
    for b in blocks:
        # type 映射: DocLayout-YOLO → MinerU 风格
        t = b["type"]
        if t == "title":
            new_type = "paragraph_title"
        elif t == "text":
            new_type = "text"
        elif t == "formula":
            new_type = "equation"
        elif t == "figure":
            new_type = "image"
        elif t == "table":
            new_type = "table"
        elif t == "list":
            new_type = "text"  # list 里可能有选项
        elif t == "caption":
            new_type = "text"
        else:
            new_type = "text"
        
        flat.append({
            "page_idx": b["page_idx"],
            "type": new_type,
            "bbox": b["bbox"],  # 归一化 [0-1]
            "content": b["content"],
            "level": None,
            "index": len(flat),
        })
    
    # Step 5: 调用 exam_pipeline 的规则引擎
    import exam_pipeline as ep
    
    questions_raw = ep.group_questions(flat)
    questions_cb = ep.build_contract_b_questions(questions_raw)
    questions_clean = ep.validate_and_clean(questions_cb)
    
    if not questions_clean:
        print("\n❌ 最终没有有效题目")
        return
    
    result = ep.output_json(
        questions_clean,
        paper_name=paper_name,
        subject=subject,
        education_level=education_level,
    )
    
    with open(output_path, "w", encoding="utf-8") as f:
        json.dump(result, f, ensure_ascii=False, indent=2)
    
    total_elapsed = time.time() - total_start
    
    print(f"\n{'='*60}")
    print(f"✅ 完成!")
    print(f"{'='*60}")
    print(f"总耗时: {total_elapsed:.1f}s")
    print(f"输出: {output_path}")
    print(f"题目: {len(questions_clean)} 道")
    print(f"  单选: {sum(1 for q in questions_clean if q['question_type']=='单选题')}")
    print(f"  多选: {sum(1 for q in questions_clean if q['question_type']=='多选题')}")
    print(f"  判断: {sum(1 for q in questions_clean if q['question_type']=='判断题')}")
    print(f"  填空: {sum(1 for q in questions_clean if q['question_type']=='填空题')}")
    print(f"  简答: {sum(1 for q in questions_clean if q['question_type']=='简答题')}")


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("pdf")
    parser.add_argument("-o", "--output", default=None)
    parser.add_argument("--subject", default="综合")
    parser.add_argument("--education_level", default="")
    parser.add_argument("--paper_name", default="")
    args = parser.parse_args()
    
    if not os.path.exists(args.pdf):
        print(f"❌ PDF 不存在: {args.pdf}")
        sys.exit(1)
    
    out_path = args.output or os.path.splitext(args.pdf)[0] + "_v2.json"
    paper_name = args.paper_name or os.path.splitext(os.path.basename(args.pdf))[0]
    
    run_pipeline(args.pdf, out_path, args.subject, args.education_level, paper_name)


if __name__ == "__main__":
    main()
