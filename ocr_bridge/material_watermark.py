#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""StudyScape material PDF two-stage watermark renderer."""

from __future__ import annotations

import hashlib
import io
import os
import shutil
import subprocess
import tempfile
from pathlib import Path
from typing import Any, Dict, Optional

RENDERER = "studyscape-material-watermark"
VERSION = "1"
PLATFORM_MARK = f"{RENDERER}/platform/{VERSION}"
USER_MARK = f"{RENDERER}/user/{VERSION}"

_MISSING_DEPENDENCIES: list[str] = []
try:
    import pymupdf as fitz  # type: ignore
except ImportError:
    try:
        import fitz  # type: ignore
    except ImportError:
        fitz = None  # type: ignore
        _MISSING_DEPENDENCIES.append("PyMuPDF")
try:
    import qrcode  # type: ignore
except ImportError:
    qrcode = None  # type: ignore
    _MISSING_DEPENDENCIES.append("qrcode[pil]")
try:
    from PIL import ImageFont  # type: ignore
except ImportError:
    ImageFont = None  # type: ignore
    _MISSING_DEPENDENCIES.append("Pillow")

_DEPENDENCY_ERROR: Optional[Exception] = (
    ImportError(", ".join(_MISSING_DEPENDENCIES)) if _MISSING_DEPENDENCIES else None
)


class WatermarkDependencyError(RuntimeError):
    """Raised when a rendering dependency is unavailable."""


class CJKFontNotFoundError(RuntimeError):
    """Raised when no usable Chinese font can be found."""


def _require_dependencies() -> None:
    if _DEPENDENCY_ERROR is not None:
        raise WatermarkDependencyError(
            f"PDF 水印依赖缺失: {', '.join(_MISSING_DEPENDENCIES)}。"
            "请安装 PyMuPDF、Pillow、qrcode[pil]。"
        ) from _DEPENDENCY_ERROR


def sha256_file(path: os.PathLike[str] | str, chunk_size: int = 1024 * 1024) -> str:
    digest = hashlib.sha256()
    with open(path, "rb") as stream:
        for chunk in iter(lambda: stream.read(chunk_size), b""):
            digest.update(chunk)
    return digest.hexdigest()


def validate_pdf(path: os.PathLike[str] | str) -> bool:
    """Return True only for a readable, non-empty PDF with at least one page."""
    _require_dependencies()
    try:
        pdf_path = Path(path)
        if not pdf_path.is_file() or pdf_path.stat().st_size == 0:
            return False
        with fitz.open(pdf_path) as document:
            if not document.is_pdf or document.page_count < 1 or document.needs_pass:
                return False
            for page in document:
                _ = page.rect
        return True
    except (OSError, RuntimeError, ValueError):
        return False


def _font_candidates() -> list[Path]:
    candidates: list[Path] = []
    configured = os.environ.get("STUDYSCAPE_CJK_FONT")
    if configured:
        candidates.append(Path(configured).expanduser())

    windir = Path(os.environ.get("WINDIR", "C:/Windows"))
    candidates.extend(
        windir / "Fonts" / name
        for name in (
            "msyh.ttc", "msyh.ttf", "simhei.ttf", "simsun.ttc",
            "Deng.ttf", "NotoSansCJK-Regular.ttc",
        )
    )
    candidates.extend(
        Path(path).expanduser()
        for path in (
            "/usr/share/fonts/opentype/noto/NotoSansCJK-Regular.ttc",
            "/usr/share/fonts/truetype/noto/NotoSansCJK-Regular.ttc",
            "/usr/share/fonts/opentype/noto/NotoSansCJKsc-Regular.otf",
            "/usr/share/fonts/opentype/source-han-sans/SourceHanSansSC-Regular.otf",
            "/usr/share/fonts/truetype/wqy/wqy-microhei.ttc",
            "/usr/share/fonts/truetype/wqy/wqy-zenhei.ttc",
            "~/.local/share/fonts/NotoSansCJK-Regular.ttc",
            "~/.fonts/NotoSansCJK-Regular.ttc",
        )
    )

    if os.name != "nt":
        try:
            result = subprocess.run(
                ["fc-list", ":lang=zh", "file"], capture_output=True,
                text=True, timeout=5, check=False,
            )
            for line in result.stdout.splitlines():
                path = line.split(":", 1)[0].strip()
                if path:
                    candidates.append(Path(path))
        except (OSError, subprocess.SubprocessError):
            pass
    return candidates


def find_cjk_font() -> str:
    """Find a CJK font usable by both Pillow and PyMuPDF."""
    _require_dependencies()
    seen: set[str] = set()
    for candidate in _font_candidates():
        key = os.path.normcase(str(candidate))
        if key in seen or not candidate.is_file():
            continue
        seen.add(key)
        try:
            ImageFont.truetype(str(candidate), 16)
            fitz.Font(fontfile=str(candidate))
            return str(candidate)
        except (OSError, RuntimeError, ValueError):
            continue
    raise CJKFontNotFoundError(
        "未找到可用中文字体。请安装 Noto Sans CJK/思源黑体/微软雅黑，"
        "或设置 STUDYSCAPE_CJK_FONT 指向 .ttf/.ttc/.otf 文件。"
    )


def _parse_tags(metadata: Dict[str, Any]) -> Dict[str, str]:
    tags: Dict[str, str] = {}
    for item in str(metadata.get("keywords", "")).split(";"):
        if "=" in item:
            key, value = item.split("=", 1)
            tags[key] = value
    return tags


def _safe_tag(value: Any) -> str:
    return str(value).replace(";", " ").replace("=", " ").replace("\n", " ").strip()


def _metadata_keywords(**values: Any) -> str:
    return ";".join(f"{key}={_safe_tag(value)}" for key, value in values.items())


def _atomic_temp(output_path: Path) -> str:
    output_path.parent.mkdir(parents=True, exist_ok=True)
    handle, path = tempfile.mkstemp(
        prefix=f".{output_path.name}.", suffix=".tmp", dir=str(output_path.parent)
    )
    os.close(handle)
    return path


def _atomic_copy(source_path: Path, output_path: Path) -> str:
    if source_path.resolve() == output_path.resolve():
        return str(output_path)
    temp_path = _atomic_temp(output_path)
    try:
        shutil.copyfile(source_path, temp_path)
        os.replace(temp_path, output_path)
    finally:
        if os.path.exists(temp_path):
            os.unlink(temp_path)
    return str(output_path)


def _insert_textbox(page: Any, rect: Any, text: str, font_path: str, fontsize: float,
                    color: tuple[float, float, float], align: int = 0,
                    opacity: float = 1.0, rotate: int = 0) -> None:
    result = page.insert_textbox(
        rect, text, fontname="studyscape-cjk", fontfile=font_path,
        fontsize=fontsize, color=color, align=align, fill_opacity=opacity,
        rotate=rotate, overlay=True,
    )
    if result < 0:
        raise RuntimeError(f"PDF 页面文字区域空间不足: {text[:30]}")


def _qr_png(payload: str) -> bytes:
    qr = qrcode.QRCode(version=None, box_size=5, border=1)
    qr.add_data(payload)
    qr.make(fit=True)
    image = qr.make_image(fill_color="black", back_color="white").convert("RGB")
    buffer = io.BytesIO()
    image.save(buffer, format="PNG")
    return buffer.getvalue()


def _draw_centered_page(page: Any, title: str, subtitle: str, font_path: str,
                        accent: tuple[float, float, float]) -> None:
    rect = page.rect
    page.draw_rect(rect, color=None, fill=(0.97, 0.98, 0.99), overlay=True)
    page.draw_rect(fitz.Rect(0, 0, rect.width, 14), color=None, fill=accent, overlay=True)
    _insert_textbox(page, fitz.Rect(52, rect.height * 0.29, rect.width - 52, rect.height * 0.48),
                    title, font_path, 28, (0.10, 0.14, 0.18), 1)
    _insert_textbox(page, fitz.Rect(65, rect.height * 0.52, rect.width - 65, rect.height * 0.65),
                    subtitle, font_path, 12, (0.36, 0.41, 0.46), 1)


def _draw_platform_body(page: Any, font_path: str, qr_png: bytes, title: str) -> None:
    rect = page.rect
    watermark = "学境 · StudyScape"
    for y in range(90, max(91, int(rect.height - 70)), 145):
        offset = 25 if (y // 145) % 2 else 0
        for x in range(-20 + offset, max(1, int(rect.width)), 220):
            _insert_textbox(page, fitz.Rect(x, y, x + 190, y + 30), watermark,
                            font_path, 13, (0.50, 0.53, 0.56), 1, 0.13, 0)

    footer_top = max(0, rect.height - 34)
    page.draw_rect(fitz.Rect(0, footer_top, rect.width, rect.height),
                   color=None, fill=(1, 1, 1), fill_opacity=0.92, overlay=True)
    _insert_textbox(page, fitz.Rect(24, footer_top + 8, rect.width - 58, rect.height - 5),
                    f"© StudyScape 学境 · {title}", font_path, 7.5,
                    (0.28, 0.31, 0.34), 0)
    page.insert_image(fitz.Rect(rect.width - 31, footer_top + 3, rect.width - 5, rect.height - 5),
                      stream=qr_png, overlay=True)


def _save_atomic(document: Any, output_path: Path) -> str:
    temp_path = _atomic_temp(output_path)
    try:
        document.save(temp_path, garbage=4, deflate=True, clean=True)
        if not validate_pdf(temp_path):
            raise RuntimeError("渲染结果不是有效 PDF，已取消发布。")
        os.replace(temp_path, output_path)
    finally:
        if os.path.exists(temp_path):
            os.unlink(temp_path)
    return str(output_path)


def render_platform_watermark(source_path: os.PathLike[str] | str,
                              output_path: os.PathLike[str] | str,
                              title: str) -> str:
    """Add platform pages and watermarks without modifying the source PDF."""
    _require_dependencies()
    source = Path(source_path)
    output = Path(output_path)
    if not validate_pdf(source):
        raise ValueError(f"输入不是有效 PDF: {source}")
    source_hash = sha256_file(source)
    clean_title = str(title).strip() or "学习资料"

    with fitz.open(source) as existing:
        source_tags = _parse_tags(existing.metadata)
        if source_tags.get("renderer") == PLATFORM_MARK:
            return _atomic_copy(source, output)

    if source.resolve() == output.resolve():
        raise ValueError("输出路径不能与输入路径相同；渲染器绝不原地修改输入 PDF。")

    if validate_pdf(output):
        with fitz.open(output) as cached:
            tags = _parse_tags(cached.metadata)
            if (tags.get("renderer") == PLATFORM_MARK and
                    tags.get("source_sha256") == source_hash and
                    tags.get("title") == _safe_tag(clean_title)):
                return str(output)

    font_path = find_cjk_font()
    input_hash_before = sha256_file(source)
    with fitz.open(source) as original, fitz.open() as result:
        page_rect = original[0].rect
        cover = result.new_page(width=page_rect.width, height=page_rect.height)
        _draw_centered_page(cover, clean_title, "StudyScape 学境 · 学习资料", font_path,
                            (0.10, 0.47, 0.55))
        result.insert_pdf(original)
        qr_png = _qr_png(f"StudyScape|{clean_title}|{source_hash[:16]}")
        for page_number in range(1, original.page_count + 1):
            _draw_platform_body(result[page_number], font_path, qr_png, clean_title)

        statement = result.new_page(width=page_rect.width, height=page_rect.height)
        _draw_centered_page(
            statement, "版权与使用声明",
            "本资料仅供合法获得授权的用户学习使用。\n请勿擅自复制、传播、出售或用于其他商业用途。",
            font_path, (0.84, 0.55, 0.15),
        )
        back = result.new_page(width=page_rect.width, height=page_rect.height)
        _draw_centered_page(back, "学境 · StudyScape", "让每一次学习都有迹可循", font_path,
                            (0.10, 0.47, 0.55))

        metadata = dict(original.metadata)
        metadata.update({
            "title": clean_title,
            "producer": f"{RENDERER} {VERSION}",
            "creator": "StudyScape",
            "keywords": _metadata_keywords(
                renderer=PLATFORM_MARK, version=VERSION, body_start=1,
                body_count=original.page_count, source_sha256=source_hash,
                title=clean_title,
            ),
        })
        result.set_metadata(metadata)
        published = _save_atomic(result, output)

    if sha256_file(source) != input_hash_before:
        raise RuntimeError("输入 PDF 在渲染期间发生变化。")
    return published


def render_user_watermark(source_path: os.PathLike[str] | str,
                          output_path: os.PathLike[str] | str,
                          username: str, user_id: str | int) -> str:
    """Add a user-specific mark to platform PDF body pages."""
    _require_dependencies()
    source = Path(source_path)
    output = Path(output_path)
    if not validate_pdf(source):
        raise ValueError(f"输入不是有效 PDF: {source}")
    clean_username = str(username).strip()
    clean_user_id = str(user_id).strip()
    if not clean_username or not clean_user_id:
        raise ValueError("username 和 user_id 不能为空。")

    source_hash = sha256_file(source)
    with fitz.open(source) as original:
        source_tags = _parse_tags(original.metadata)
        if source_tags.get("renderer") != PLATFORM_MARK:
            raise ValueError("用户阶段输入必须是当前版本的平台水印 PDF。")
        body_start = int(source_tags.get("body_start", "-1"))
        body_count = int(source_tags.get("body_count", "0"))
        if body_start < 0 or body_count < 1 or body_start + body_count > original.page_count:
            raise ValueError("平台 PDF metadata 中的正文页范围无效。")

    if source.resolve() == output.resolve():
        raise ValueError("输出路径不能与输入路径相同；渲染器绝不原地修改输入 PDF。")

    if validate_pdf(output):
        with fitz.open(output) as cached:
            tags = _parse_tags(cached.metadata)
            if (tags.get("renderer") == USER_MARK and
                    tags.get("platform_sha256") == source_hash and
                    tags.get("username") == _safe_tag(clean_username) and
                    tags.get("user_id") == _safe_tag(clean_user_id)):
                return str(output)

    font_path = find_cjk_font()
    input_hash_before = sha256_file(source)
    with fitz.open(source) as original, fitz.open() as result:
        result.insert_pdf(original)
        label = f"仅限用户 {clean_username} 使用 · UID {clean_user_id}"
        for page_number in range(body_start, body_start + body_count):
            page = result[page_number]
            rect = page.rect
            width = min(rect.width - 48, max(245, len(label) * 11))
            box = fitz.Rect((rect.width - width) / 2, 16, (rect.width + width) / 2, 48)
            page.draw_rect(box, color=(0.55, 0.16, 0.16), fill=(1.0, 0.97, 0.97),
                           width=0.6, fill_opacity=0.88, overlay=True)
            _insert_textbox(page, fitz.Rect(box.x0 + 7, box.y0 + 7, box.x1 - 7, box.y1 - 5),
                            label, font_path, 9, (0.55, 0.10, 0.10), 1)

        metadata = dict(original.metadata)
        metadata.update({
            "producer": f"{RENDERER} {VERSION}",
            "keywords": _metadata_keywords(
                renderer=USER_MARK, version=VERSION, body_start=body_start,
                body_count=body_count, platform_sha256=source_hash,
                username=clean_username, user_id=clean_user_id,
            ),
        })
        result.set_metadata(metadata)
        published = _save_atomic(result, output)

    if sha256_file(source) != input_hash_before:
        raise RuntimeError("输入 PDF 在渲染期间发生变化。")
    return published


# Stable, concise aliases for callers that name stages directly.
platform_watermark = render_platform_watermark
user_watermark = render_user_watermark


__all__ = [
    "CJKFontNotFoundError", "PLATFORM_MARK", "RENDERER", "USER_MARK", "VERSION",
    "WatermarkDependencyError", "find_cjk_font", "platform_watermark",
    "render_platform_watermark", "render_user_watermark", "sha256_file",
    "user_watermark", "validate_pdf",
]
