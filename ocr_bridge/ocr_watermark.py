#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Conservative PDF object-layer cleanup before OCR."""

from __future__ import annotations

import io
import math
import os
import shutil
from collections import defaultdict
from pathlib import Path
from typing import Any

try:
    import pymupdf as fitz  # type: ignore
except ImportError:
    try:
        import fitz  # type: ignore
    except ImportError:
        fitz = None  # type: ignore

try:
    from PIL import Image, ImageStat  # type: ignore
except ImportError:
    Image = None  # type: ignore
    ImageStat = None  # type: ignore


_TEXT_REPEAT_RATIO = 0.60
_IMAGE_REPEAT_RATIO = 0.60


def _result(status: str, method: str = "none") -> dict[str, Any]:
    return {
        "status": status,
        "method": method,
        "removed_count": 0,
        "suspicious_count": 0,
        "warnings": [],
        "candidates": [],
    }


def _normalized_rect(rect: Any, page_rect: Any) -> tuple[float, float, float, float]:
    width = max(float(page_rect.width), 1.0)
    height = max(float(page_rect.height), 1.0)
    return (
        round((float(rect.x0) - page_rect.x0) / width, 3),
        round((float(rect.y0) - page_rect.y0) / height, 3),
        round((float(rect.x1) - page_rect.x0) / width, 3),
        round((float(rect.y1) - page_rect.y0) / height, 3),
    )


def _position_key(norm: tuple[float, float, float, float]) -> tuple[int, int, int, int]:
    return tuple(round(value / 0.025) for value in norm)  # type: ignore[return-value]


def _color_luminance(color: int) -> float:
    red = (color >> 16) & 255
    green = (color >> 8) & 255
    blue = color & 255
    return (0.2126 * red + 0.7152 * green + 0.0722 * blue) / 255.0


def _image_luminance(document: Any, xref: int, cache: dict[int, float | None]) -> float | None:
    if xref in cache:
        return cache[xref]
    if Image is None or ImageStat is None:
        cache[xref] = None
        return None
    try:
        payload = document.extract_image(xref).get("image", b"")
        with Image.open(io.BytesIO(payload)) as image:
            image.thumbnail((128, 128))
            rgb = image.convert("RGB")
            mean = ImageStat.Stat(rgb).mean
            luminance = (0.2126 * mean[0] + 0.7152 * mean[1] + 0.0722 * mean[2]) / 255.0
        cache[xref] = round(luminance, 4)
    except Exception:
        cache[xref] = None
    return cache[xref]


def _is_central_large(norm: tuple[float, float, float, float], kind: str) -> bool:
    x0, y0, x1, y1 = norm
    width = max(0.0, x1 - x0)
    height = max(0.0, y1 - y0)
    center_x = (x0 + x1) / 2
    center_y = (y0 + y1) / 2
    intersects_center = x0 < 0.68 and x1 > 0.32 and y0 < 0.72 and y1 > 0.28
    centered = 0.22 <= center_x <= 0.78 and 0.20 <= center_y <= 0.80
    if kind == "text":
        return intersects_center and centered and width >= 0.28 and height >= 0.018
    return intersects_center and centered and width >= 0.32 and height >= 0.20 and width * height >= 0.09


def _collect_candidates(document: Any) -> list[dict[str, Any]]:
    candidates: list[dict[str, Any]] = []
    image_luminance_cache: dict[int, float | None] = {}
    for page_index, page in enumerate(document):
        page_rect = page.rect
        raw = page.get_text("dict")
        for block in raw.get("blocks", []):
            if block.get("type") != 0:
                continue
            for line in block.get("lines", []):
                for span in line.get("spans", []):
                    text = " ".join(str(span.get("text", "")).split())
                    if len(text) < 3:
                        continue
                    rect = fitz.Rect(span.get("bbox", (0, 0, 0, 0)))
                    norm = _normalized_rect(rect, page_rect)
                    luminance = _color_luminance(int(span.get("color", 0)))
                    candidates.append({
                        "kind": "text", "page": page_index, "rect": rect,
                        "bbox": list(norm), "identity": text.casefold(),
                        "label": text[:120], "luminance": round(luminance, 4),
                        "central_large": _is_central_large(norm, "text"),
                    })

        for info in page.get_image_info(xrefs=True, hashes=True):
            xref = int(info.get("xref", 0) or 0)
            if xref <= 0:
                continue
            rect = fitz.Rect(info.get("bbox", (0, 0, 0, 0)))
            norm = _normalized_rect(rect, page_rect)
            digest = info.get("digest")
            identity = digest.hex() if isinstance(digest, bytes) else f"xref:{xref}"
            luminance = _image_luminance(document, xref, image_luminance_cache)
            candidates.append({
                "kind": "image", "page": page_index, "rect": rect,
                "bbox": list(norm), "identity": identity, "label": f"image:{xref}",
                "luminance": luminance, "central_large": _is_central_large(norm, "image"),
            })
    return candidates


def _classify_candidates(candidates: list[dict[str, Any]], page_count: int) -> list[dict[str, Any]]:
    groups: dict[tuple[Any, ...], list[dict[str, Any]]] = defaultdict(list)
    for candidate in candidates:
        norm = tuple(candidate["bbox"])
        groups[(candidate["kind"], candidate["identity"], _position_key(norm))].append(candidate)

    minimum_text_pages = max(2, math.ceil(page_count * _TEXT_REPEAT_RATIO))
    minimum_image_pages = max(2, math.ceil(page_count * _IMAGE_REPEAT_RATIO))
    output: list[dict[str, Any]] = []
    for group in groups.values():
        pages = sorted({item["page"] for item in group})
        kind = group[0]["kind"]
        minimum_pages = minimum_text_pages if kind == "text" else minimum_image_pages
        repeated = len(pages) >= minimum_pages
        for item in group:
            luminance = item["luminance"]
            light = luminance is not None and luminance >= (0.72 if kind == "text" else 0.78)
            overlaps_dark_text = False
            if kind == "text" and repeated and item["central_large"] and light:
                item_rect = item["rect"]
                overlaps_dark_text = any(
                    other is not item
                    and other["page"] == item["page"]
                    and other["kind"] == "text"
                    and other.get("luminance", 1.0) < 0.60
                    and item_rect.intersects(other["rect"])
                    for other in candidates
                )
            # Image objects can be legitimate shared figures or page templates.
            # Without reliable layer/opacity metadata they are review-only and are
            # never physically redacted before OCR.
            high_confidence = (kind == "text" and repeated and item["central_large"]
                               and light and not overlaps_dark_text)
            suspicious = repeated and (item["central_large"] or light)
            item["overlaps_dark_text"] = overlaps_dark_text
            item["decision"] = "remove" if high_confidence else ("suspicious" if suspicious else "keep")
            item["repeat_pages"] = pages
            output.append(item)
    return output


def _public_candidate(candidate: dict[str, Any]) -> dict[str, Any]:
    return {
        "type": candidate["kind"],
        "page": candidate["page"],
        "bbox": candidate["bbox"],
        "label": candidate["label"],
        "luminance": candidate["luminance"],
        "repeat_pages": candidate["repeat_pages"],
        "decision": candidate["decision"],
        "overlaps_dark_text": candidate.get("overlaps_dark_text", False),
    }


def _apply_removals(document: Any, candidates: list[dict[str, Any]]) -> None:
    by_page: dict[int, dict[str, list[Any]]] = defaultdict(lambda: {"text": [], "image": []})
    for candidate in candidates:
        if candidate["decision"] == "remove":
            by_page[candidate["page"]][candidate["kind"]].append(candidate["rect"])

    for page_index, kinds in by_page.items():
        page = document[page_index]
        for rect in kinds["text"]:
            page.add_redact_annot(rect, fill=False, cross_out=False)
        if kinds["text"]:
            page.apply_redactions(images=fitz.PDF_REDACT_IMAGE_NONE, graphics=fitz.PDF_REDACT_LINE_ART_NONE,
                                  text=fitz.PDF_REDACT_TEXT_REMOVE)
        for rect in kinds["image"]:
            page.add_redact_annot(rect, fill=False, cross_out=False)
        if kinds["image"]:
            page.apply_redactions(images=fitz.PDF_REDACT_IMAGE_REMOVE, graphics=fitz.PDF_REDACT_LINE_ART_NONE,
                                  text=fitz.PDF_REDACT_TEXT_NONE)


def prepare_ocr_input(source_path: str, output_path: str) -> dict[str, Any]:
    """Create a sanitized PDF copy and return JSON-serializable risk metadata."""
    result = _result("not_applicable")
    source = Path(source_path)
    output = Path(output_path)
    if source.suffix.lower() != ".pdf":
        return result
    if fitz is None:
        result.update(status="fallback", method="original")
        result["warnings"].append("PyMuPDF unavailable; OCR uses the original PDF")
        return result
    if source.resolve() == output.resolve():
        result.update(status="fallback", method="original")
        result["warnings"].append("Sanitized output must differ from source")
        return result

    try:
        with fitz.open(source) as document:
            if not document.is_pdf or document.needs_pass or document.page_count < 1:
                raise ValueError("unreadable or encrypted PDF")
            candidates = _classify_candidates(_collect_candidates(document), document.page_count)
            removed = [item for item in candidates if item["decision"] == "remove"]
            suspicious = [item for item in candidates if item["decision"] == "suspicious"]
            reported = removed + suspicious
            result.update(
                status="cleaned" if removed else "unchanged",
                method="pymupdf_object_redaction" if removed else "analysis_only",
                removed_count=len(removed),
                suspicious_count=len(suspicious),
                candidates=[_public_candidate(item) for item in reported],
            )
            output.parent.mkdir(parents=True, exist_ok=True)
            if removed:
                _apply_removals(document, candidates)
                document.save(output, garbage=4, deflate=True, clean=True)
            else:
                shutil.copyfile(source, output)

        with fitz.open(output) as verified:
            if not verified.is_pdf or verified.needs_pass or verified.page_count < 1:
                raise ValueError("sanitized PDF validation failed")
    except Exception as exc:
        try:
            if output.exists():
                output.unlink()
        except OSError:
            pass
        result.update(status="fallback", method="original", removed_count=0)
        result["warnings"].append(f"PDF cleanup skipped: {type(exc).__name__}: {exc}")
    return result


__all__ = ["prepare_ocr_input"]
