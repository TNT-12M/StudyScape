#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
StudyScape PDF 生成脚本 — AI 提示词导出 (家里云运行)
====================================================
输入: JSON 文件 (题目列表 + base64 图片 + 试卷信息)
输出: PDF 文件 (纯文本题目 + 图片 + AI 提示词模板)

用法: python3 export_pdf.py input.json output.pdf
"""

import sys, os, json, base64, tempfile, html
from io import BytesIO

from reportlab.lib.pagesizes import A4
from reportlab.lib.units import cm
from reportlab.lib.styles import getSampleStyleSheet, ParagraphStyle
from reportlab.lib.enums import TA_LEFT, TA_CENTER
from reportlab.platypus import SimpleDocTemplate, Paragraph, Spacer, Image as RLImage, HRFlowable, KeepTogether
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont
from reportlab.lib.colors import HexColor, black

# ======================= 中文字体注册 (自动发现) =======================
FONT_NAME = "Helvetica"
FONT_REGISTERED_PATH = None

def _find_cjk_font():
    """用 fc-list 自动找系统中可用的中文字体文件"""
    import subprocess as _sp
    # 优先级: 从 fc-list 找 .ttc / .ttf / .otf 中文字体
    for cmd in [
        ['fc-list', ':lang=zh', ':file'],
        ['fc-list', ':lang=zh'],
        ['fc-list', ':file'],
    ]:
        try:
            r = _sp.run(cmd, capture_output=True, text=True, timeout=8)
            if r.returncode == 0 and r.stdout.strip():
                # fc-list 输出格式: "/path/to/font.ttc: Font Name: style"
                for line in r.stdout.strip().split('\n'):
                    if ':' in line:
                        path = line.split(':', 1)[0].strip().strip('"')
                        if path.endswith(('.ttc', '.ttf', '.otf')) and os.path.isfile(path):
                            return path
        except Exception:
            continue
    return None

def _try_register(fp, family_name="CJK"):
    """尝试用 reportlab 注册一个字体文件"""
    if not fp or not os.path.isfile(fp):
        return False
    try:
        pdfmetrics.registerFont(TTFont(family_name, fp))
        return True
    except Exception as e:
        # .ttc 需要指定 subfontIndex
        if fp.endswith('.ttc'):
            try:
                pdfmetrics.registerFont(TTFont(family_name, fp, subfontIndex=0))
                return True
            except Exception:
                pass
        print(f"  ⚠️ 注册失败 {fp}: {e}")
        return False

# 1. fc-list 自动发现
_found = _find_cjk_font()
if _found and _try_register(_found, "CJK"):
    FONT_NAME = "CJK"
    FONT_REGISTERED_PATH = _found
else:
    # 2. 硬编码常见路径兜底 (Debian/Ubuntu/CentOS/Arch)
    _fallback_paths = [
        # wqy 文泉驿
        "/usr/share/fonts/truetype/wqy/wqy-zenhei.ttc",
        "/usr/share/fonts/truetype/wqy/wqy-microhei.ttc",
        "/usr/share/fonts/wqy-microhei/wqy-microhei.ttc",
        # Noto CJK
        "/usr/share/fonts/truetype/noto/NotoSansCJK-Regular.ttc",
        "/usr/share/fonts/opentype/noto/NotoSansCJK-Regular.ttc",
        "/usr/share/fonts/noto-cjk/NotoSansCJK-Regular.ttc",
        "/usr/share/fonts/google-noto-cjk/NotoSansCJK-Regular.ttc",
        # 思源
        "/usr/share/fonts/opentype/source-han-sans/SourceHanSansSC-Regular.otf",
        "/usr/share/fonts/truetype/source-han-sans/SourceHanSansSC-Regular.otf",
        # Debian fonts-noto-cjk
        "/usr/share/fonts/truetype/noto-cjk/NotoSansCJK-Regular.ttc",
        # 用户目录
        os.path.expanduser("~/.local/share/fonts/wqy-zenhei.ttc"),
        os.path.expanduser("~/.fonts/wqy-zenhei.ttc"),
    ]
    for _fp in _fallback_paths:
        if _try_register(_fp, "CJK"):
            FONT_NAME = "CJK"
            FONT_REGISTERED_PATH = _fp
            break

print(f"📝 export_pdf.py 字体: {FONT_NAME} ({FONT_REGISTERED_PATH or 'NO CJK FOUND, 用 Helvetica!'})", flush=True)

# ======================= 样式 =======================
styles = getSampleStyleSheet()
style_title = ParagraphStyle("Title", parent=styles["Heading1"], fontName=FONT_NAME, fontSize=18, alignment=TA_CENTER, spaceAfter=12, textColor=HexColor("#1a5276"))
style_h2 = ParagraphStyle("H2", parent=styles["Heading2"], fontName=FONT_NAME, fontSize=13, spaceAfter=8, spaceBefore=10, textColor=HexColor("#2c3e50"))
style_body = ParagraphStyle("Body", parent=styles["Normal"], fontName=FONT_NAME, fontSize=10.5, leading=16)
style_qnum = ParagraphStyle("QNum", parent=styles["Heading3"], fontName=FONT_NAME, fontSize=11, spaceAfter=4, textColor=HexColor("#c0392b"))
style_prompt = ParagraphStyle("Prompt", parent=styles["Normal"], fontName=FONT_NAME, fontSize=9, leading=14, leftIndent=10, textColor=HexColor("#7f8c8d"), backColor=HexColor("#f8f9fa"), borderPadding=6)


def _b64_to_image(b64_str, max_width=15*cm):
    """base64 → reportlab Image"""
    try:
        if "," in b64_str:
            b64_str = b64_str.split(",", 1)[1]
        raw = base64.b64decode(b64_str.strip())
        img = RLImage(BytesIO(raw))
        # 自适应宽度, 保持比例
        w, h = img.drawWidth, img.drawHeight
        if w > max_width:
            ratio = max_width / w
            img.drawWidth = max_width
            img.drawHeight = h * ratio
        return img
    except Exception as e:
        return Paragraph(f"(图片无法显示: {e})", style_body)


def build_pdf(data, output_path):
    paper_name = data.get("paper_name", "未命名试卷")
    subject = data.get("subject", "")
    questions = data.get("questions", [])

    doc = SimpleDocTemplate(output_path, pagesize=A4, leftMargin=2*cm, rightMargin=2*cm, topMargin=2*cm, bottomMargin=2*cm)
    story = []

    # === 封面 ===
    story.append(Spacer(1, 40))
    story.append(Paragraph(paper_name, style_title))
    if subject:
        story.append(Paragraph(f"科目: {subject}", ParagraphStyle("Sub", parent=style_body, alignment=TA_CENTER, textColor=HexColor("#7f8c8d"))))
    story.append(Spacer(1, 20))
    story.append(HRFlowable(width="60%", thickness=2, color=HexColor("#3498db")))
    story.append(Spacer(1, 20))
    story.append(Paragraph(f"共 {len(questions)} 道题目", ParagraphStyle("Cnt", parent=style_body, alignment=TA_CENTER, fontSize=11)))

    # === AI 提示词 ===
    story.append(Spacer(1, 30))
    story.append(Paragraph("🤖 给 AI 的提示词 (复制下面整段 + 上传此 PDF)", style_h2))
    prompt_text = f"""请根据这份试卷 PDF 中每道题的<strong>题干、选项和图片</strong>, 
为每道题生成: 
<br/>
1. <b>正确答案</b> (单选题填选项字母 A/B/C/D, 简答题写完整答案)
<br/>
2. <b>详细解析</b> (markdown 格式, 步骤清晰)
<br/>
3. <b>难度评分</b> (1-5, 1=最易, 5=最难)
<br/>
<br/>
<b>严格按以下 JSON 格式输出</b> (不要输出任何 JSON 外的文字):
<br/>
<pre>{{{{"answers": [
  {{"qid": 1, "answer": "C", "explanation": "解析 markdown...", "difficulty": 3}},
  {{"qid": 2, "answer": "B", "explanation": "解析 markdown...", "difficulty": 2}}
]}}}}</pre>
"""
    story.append(Paragraph(prompt_text, style_prompt))

    # === 题目 ===
    story.append(Spacer(1, 30))
    story.append(HRFlowable(width="100%", thickness=1, color=HexColor("#bdc3c7")))
    story.append(Paragraph("📝 题目 (含图片, 请连同 PDF 一起上传给 AI)", style_h2))

    for i, q in enumerate(questions):
        qnum = i + 1
        content = q.get("question_content", q.get("content", ""))
        qtype = q.get("question_type", "")
        options = q.get("question_options", q.get("options", []))
        images = q.get("question_images", q.get("images", []))
        # images 可能是 [base64_str] 或 [{base64: "..."}]
        img_b64_list = []
        for img in images:
            if isinstance(img, str):
                img_b64_list.append(img)
            elif isinstance(img, dict):
                img_b64_list.append(img.get("base64", ""))

        story.append(Paragraph(f"=== 第 {qnum} 题 ===", style_qnum))
        if qtype:
            story.append(Paragraph(f"<i>题型: {qtype}</i>", ParagraphStyle("QT", parent=style_body, textColor=HexColor("#7f8c8d"), fontSize=9)))
        if content:
            # HTML escape 防止 Paragraph 解析 < > 当标签
            safe_content = html.escape(content).replace("\n", "<br/>")
            story.append(Paragraph(safe_content, style_body))

        if options and len(options) > 0:
            story.append(Spacer(1, 4))
            opt_lines = []
            for oi, opt in enumerate(options):
                letter = chr(65 + oi)
                safe_opt = html.escape(opt)
                opt_lines.append(f"<b>{letter}.</b> {safe_opt}")
            story.append(Paragraph("<br/>".join(opt_lines), style_body))

        # 图片
        for b64 in img_b64_list:
            if b64:
                story.append(Spacer(1, 6))
                story.append(_b64_to_image(b64))
                story.append(Spacer(1, 4))

        story.append(Spacer(1, 12))
        story.append(HRFlowable(width="100%", thickness=0.5, color=HexColor("#ecf0f1")))

    doc.build(story)
    return os.path.getsize(output_path)


if __name__ == "__main__":
    if len(sys.argv) < 3:
        print("用法: python3 export_pdf.py input.json output.pdf")
        sys.exit(1)

    input_path = sys.argv[1]
    output_path = sys.argv[2]

    with open(input_path, "r", encoding="utf-8") as f:
        data = json.load(f)

    size = build_pdf(data, output_path)
    print(f"✅ PDF 生成: {output_path} ({size/1024:.1f} KB)")
