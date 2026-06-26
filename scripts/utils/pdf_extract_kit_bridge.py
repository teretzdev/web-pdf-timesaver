import json
import math
import os
import sys
from typing import Any, Dict, List, Tuple


RESULT_TEMPLATE = {
    "success": False,
    "fields": [],
    "pageCount": 0,
    "error": None,
    "meta": {
        "kit": False,
        "usedFallback": False,
        "warnings": []
    }
}


def emit(result: Dict[str, Any]) -> None:
    sys.stdout.write(json.dumps(result))
    sys.stdout.flush()


def to_mm(value: float) -> float:
    return round(value * 0.352778, 2)


def detect_with_pdf_extract_kit(pdf_path: str) -> Tuple[List[Dict[str, Any]], List[str]]:
    warnings: List[str] = []
    detections: List[Dict[str, Any]] = []
    try:
        from pdf_extract_kit.form import FormExtractor  # type: ignore
    except Exception as exc:  # pragma: no cover
        warnings.append(f"pdf_extract_kit import failed: {exc}")
        return detections, warnings

    weights = os.environ.get("PDF_EXTRACT_KIT_MODEL")
    try:
        extractor = FormExtractor(weights_path=weights) if weights else FormExtractor()
        results = extractor.process(pdf_path)
        for field in results.get("fields", []):
            detections.append({
                "name": field.get("name") or f"pek_field_{len(detections)}",
                "type": field.get("type") or "text",
                "page": field.get("page", 1),
                "x": to_mm(field.get("x", 0)),
                "y": to_mm(field.get("y", 0)),
                "width": to_mm(field.get("width", 60)),
                "height": to_mm(field.get("height", 12)),
                "fontSize": 10,
                "confidence": min(0.95, float(field.get("score", 0.82))),
                "method": "pdf-extract-kit-wrapper",
                "origin": "pdf-extract-kit"
            })
    except Exception as exc:  # pragma: no cover
        warnings.append(f"pdf_extract_kit inference failed: {exc}")

    return detections, warnings


def detect_with_fallback(pdf_path: str) -> Tuple[List[Dict[str, Any]], List[str]]:
    warnings: List[str] = []
    detections: List[Dict[str, Any]] = []

    try:
        import fitz  # type: ignore
    except Exception as exc:  # pragma: no cover
        warnings.append(f"PyMuPDF import failed: {exc}")
        return detections, warnings

    try:
        document = fitz.open(pdf_path)
    except Exception as exc:  # pragma: no cover
        warnings.append(f"PyMuPDF failed to open PDF: {exc}")
        return detections, warnings

    for page_index, page in enumerate(document, start=1):
        page_height = page.rect.height

        try:
            drawings = page.get_drawings()
        except Exception:
            drawings = []

        for drawing in drawings:
            border = drawing.get("rect")
            if border:
                x0, y0, x1, y1 = border
                width = x1 - x0
                height = y1 - y0
                if width > 25 and 8 < height < 40:
                    detections.append({
                        "name": f"pek_rect_{page_index}_{len(detections)}",
                        "type": "text",
                        "page": page_index,
                        "x": to_mm(x0),
                        "y": to_mm(page_height - y1),
                        "width": to_mm(width),
                        "height": to_mm(height),
                        "fontSize": 10,
                        "confidence": 0.6,
                        "method": "pdf-extract-kit-wrapper",
                        "origin": "vector-outline"
                    })

        try:
            blocks = page.get_text("blocks")
        except Exception:
            blocks = []

        for block in blocks:
            if len(block) < 5:
                continue
            x0, y0, x1, y1, text = block[0], block[1], block[2], block[3], block[4]
            text = (text or "").strip()
            if not text or len(text) > 100:
                continue

            width = min(200, (page.rect.width - x1) * 0.6)
            detections.append({
                "name": f"pek_label_{page_index}_{len(detections)}",
                "type": "text",
                "page": page_index,
                "x": to_mm(x1 + 8),
                "y": to_mm(page_height - y1 - 14),
                "width": to_mm(max(width, 50)),
                "height": to_mm(14),
                "fontSize": 10,
                "confidence": 0.5,
                "method": "pdf-extract-kit-wrapper",
                "origin": "label-proximity",
                "label": text
            })

    document.close()
    return detections, warnings


def main() -> None:
    result = RESULT_TEMPLATE.copy()
    result["meta"] = RESULT_TEMPLATE["meta"].copy()

    if len(sys.argv) < 2:
        result["error"] = "Missing PDF path"
        return emit(result)

    pdf_path = sys.argv[1]
    if not os.path.exists(pdf_path):
        result["error"] = f"PDF not found: {pdf_path}"
        return emit(result)

    detections, warnings = detect_with_pdf_extract_kit(pdf_path)
    if detections:
        result["success"] = True
        result["fields"] = detections
        result["pageCount"] = max((field.get("page", 1) for field in detections), default=1)
        result["meta"]["kit"] = True
        if warnings:
            result["meta"]["warnings"].extend(warnings)
        return emit(result)

    if warnings:
        result["meta"]["warnings"].extend(warnings)

    fallback_detections, fallback_warnings = detect_with_fallback(pdf_path)
    if fallback_detections:
        result["success"] = True
        result["fields"] = fallback_detections
        result["pageCount"] = max((field.get("page", 1) for field in fallback_detections), default=1)
        result["meta"]["usedFallback"] = True
        if fallback_warnings:
            result["meta"]["warnings"].extend(fallback_warnings)
        return emit(result)

    result["error"] = "PDF-Extract-Kit bridge could not detect fields"
    if fallback_warnings:
        result["meta"]["warnings"].extend(fallback_warnings)
    emit(result)


if __name__ == "__main__":
    main()


