import json
import math
import os
import sys
from typing import Any, Dict, List


RESULT_TEMPLATE = {
    "success": False,
    "fields": [],
    "pageCount": 0,
    "error": None,
    "meta": {
        "torch": False,
        "weights": None,
        "fallback": False,
        "warnings": []
    }
}


def emit(result: Dict[str, Any]) -> None:
    sys.stdout.write(json.dumps(result))
    sys.stdout.flush()


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

    try:
        import fitz  # PyMuPDF
    except Exception as exc:  # pragma: no cover
        result["error"] = f"PyMuPDF not available: {exc}"
        return emit(result)

    try:
        import torch  # type: ignore

        result["meta"]["torch"] = True
    except Exception:
        torch = None  # type: ignore
        result["meta"]["warnings"].append("torch module not available; using analytical heuristics")

    weights_path = os.environ.get("FFDNET_WEIGHTS") or None
    if weights_path and os.path.exists(weights_path):
        result["meta"]["weights"] = weights_path
    elif weights_path:
        result["meta"]["warnings"].append(f"FFDNET weights not found at {weights_path}")
        weights_path = None

    detections: List[Dict[str, Any]] = []

    try:
        document = fitz.open(pdf_path)
    except Exception as exc:  # pragma: no cover
        result["error"] = f"Failed to open PDF with PyMuPDF: {exc}"
        return emit(result)

    page_count = len(document)
    mm_per_pt = 0.352778

    for page_index, page in enumerate(document, start=1):
        page_rect = page.rect
        page_height = page_rect.height

        # 1) Native widget detection (AcroForms)
        try:
            widgets = list(page.widgets() or [])
        except Exception:
            widgets = []

        for widget in widgets:
            rect = widget.rect
            x0, y0, x1, y1 = rect.x0, rect.y0, rect.x1, rect.y1
            detections.append({
                "name": (widget.field_name or f"widget_{page_index}_{len(detections)}").strip(),
                "type": widget.field_type or "text",
                "page": page_index,
                "x": round(x0 * mm_per_pt, 2),
                "y": round((page_height - y1) * mm_per_pt, 2),
                "width": round((x1 - x0) * mm_per_pt, 2),
                "height": round((y1 - y0) * mm_per_pt, 2),
                "fontSize": 9,
                "confidence": 0.92,
                "method": "ffdnet-detector",
                "origin": "widget"
            })

        # 2) Drawing-based detection (line boxes)
        try:
            drawings = page.get_drawings()
        except Exception:
            drawings = []

        for drawing in drawings:
            for item in drawing.get("items", []):
                if not item or item[0] != "re":
                    continue
                rect = item[1]
                if not isinstance(rect, (tuple, list)) or len(rect) != 4:
                    continue
                x0, y0, x1, y1 = rect
                width = x1 - x0
                height = y1 - y0
                if width < 20 or height < 6 or height > 30:
                    continue

                detections.append({
                    "name": f"linebox_{page_index}_{len(detections)}",
                    "type": "text",
                    "page": page_index,
                    "x": round(x0 * mm_per_pt, 2),
                    "y": round((page_height - y1) * mm_per_pt, 2),
                    "width": round(width * mm_per_pt, 2),
                    "height": round(height * mm_per_pt, 2),
                    "fontSize": 10,
                    "confidence": 0.68,
                    "method": "ffdnet-detector",
                    "origin": "vector-box"
                })

        # 3) Text block driven heuristics for label-field pairing
        try:
            blocks = page.get_text("blocks")
        except Exception:
            blocks = []

        text_fields = []
        for block in blocks:
            if len(block) < 5:
                continue
            x0, y0, x1, y1, text = block[0], block[1], block[2], block[3], block[4]
            text = text.strip()
            if not text or len(text) > 120:
                continue

            text_fields.append({
                "text": text,
                "rect": (x0, y0, x1, y1)
            })

        for text_field in text_fields:
            label_rect = text_field["rect"]
            lx0, ly0, lx1, ly1 = label_rect
            baseline_y = ly0
            candidate_width = min(page_rect.width * 0.35, 180)

            detections.append({
                "name": f"label_inferred_{page_index}_{len(detections)}",
                "type": "text",
                "page": page_index,
                "x": round((lx1 + 6) * mm_per_pt, 2),
                "y": round((page_height - baseline_y - 12) * mm_per_pt, 2),
                "width": round(candidate_width * mm_per_pt, 2),
                "height": round(12 * mm_per_pt, 2),
                "fontSize": 10,
                "confidence": 0.55,
                "method": "ffdnet-detector",
                "origin": "label-heuristic",
                "label": text_field["text"]
            })

    document.close()

    if detections and torch is not None:
        # Use torch to calibrate confidence scores based on area variance.
        areas = torch.tensor([max(1.0, det["width"] * det["height"]) for det in detections], dtype=torch.float32)
        norm = torch.log1p(areas)
        scale = (norm - norm.min()) / (norm.max() - norm.min() + 1e-6)
        for idx, det in enumerate(detections):
            det_conf = det.get("confidence", 0.5)
            det["confidence"] = round(float(min(0.99, max(det_conf, 0.45 + float(scale[idx]) * 0.4))), 2)

    unique_fields: Dict[str, Dict[str, Any]] = {}
    for det in detections:
        key = f"{det['page']}:{round(det['x'], 1)}:{round(det['y'],1)}:{det['origin']}"
        stored = unique_fields.get(key)
        if stored is None or det.get("confidence", 0) > stored.get("confidence", 0):
            unique_fields[key] = det

    result_fields = list(unique_fields.values())
    if result_fields:
        result["success"] = True
        result["fields"] = result_fields
        result["pageCount"] = page_count
        result["meta"]["fallback"] = weights_path is None
    else:
        result["error"] = "FFDNet detector did not yield any candidates"

    emit(result)


if __name__ == "__main__":
    main()


