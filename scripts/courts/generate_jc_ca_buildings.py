#!/usr/bin/env python3
"""Generate data/jc_ca_buildings.txt from Judicial Council PDF extract text."""
from __future__ import annotations

import re
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]
OUT = ROOT / "data" / "jc_ca_buildings.txt"
PDF_URL = (
    "https://courts.ca.gov/system/files/solicitation-request-document/"
    "rfp-fs-sp-2019-03-jp-attachment-2-regional-building-list-addendum-1.pdf"
)

STREET_SUFFIX = (
    r"St\.?|Street|Ave\.?|Avenue|Blvd\.?|Boulevard|Drive|Dr\.?|Way|Road|Rd\.?|"
    r"Plaza|Pass|Pkwy\.?|Parkway|Highway|Hwy\.?|Route|Homestead|Mission|Grant|"
    r"Center|Union|Brown|Texas|Kansas|Church|Aguajito|Sonoma|Willow|Padre|Walnut|"
    r"Hill|Main|Broadway|Courthouse|Camino|Real|Boulevard"
)
STREET_RE = re.compile(STREET_SUFFIX, re.I)
STREET_BODY_RE = re.compile(
    rf"(\d[\w\s\.,#'\-/]*(?:{STREET_SUFFIX})[\w\s\.,#'\-/]*)",
    re.I,
)
DENSE_SEGMENT_RE = re.compile(
    r"(\d{2}-[A-Z0-9*]+)\s+(.+?)\s+(\d{5})\s+([A-Za-z .'\-]+)\s+(BANCRO|NCRO|SRO)\b"
)
ID_RE = re.compile(r"^\d{2}-[A-Z0-9*]+$")
ZIP_RE = re.compile(r"^\d{5}$")
COUNTY_RE = re.compile(r"^[A-Za-z .'\-]+$")
SKIP_NAME_RE = re.compile(
    r"\b(parking structure|parking lot|parking\b|payroll|finance-hr|ocit\b|"
    r"records center|file unit|storage\b|camp storage|modular \d|trailer\b|"
    r"jury assembly trailer|building record for|probation center|psychiatric|"
    r"juvenile hall|juvenile justice center|jury assembly bldg|administration bldg|"
    r"credit union|swing space|swing space modular)",
    re.I,
)


def looks_like_street(part: str) -> bool:
    if re.search(r"\d", part) and STREET_RE.search(part):
        return True
    return bool(re.match(r"^\d+\s+[NSEW]?\.?\s*[\w\s\.\-']{3,}$", part, re.I))


def normalize_id(raw: str) -> str | None:
    raw = raw.strip()
    if not ID_RE.match(raw):
        return None
    return raw.replace("*", "")


def useful(row: dict[str, str]) -> bool:
    if row["county"].lower() == "los angeles":
        return False
    if not ZIP_RE.match(row["zip"]):
        return False
    if not all(row[k].strip() for k in ("name", "street", "city")):
        return False
    if SKIP_NAME_RE.search(row["name"]):
        return False
    return True


def parse_markdown_row(line: str) -> dict[str, str] | None:
    if line.startswith("| ---") or not line.startswith("|"):
        return None
    parts = [p.strip() for p in line.split("|") if p.strip()]
    if not parts:
        return None
    jid = normalize_id(parts[0])
    if not jid:
        return None
    zip_idx = next((i for i, p in enumerate(parts) if ZIP_RE.match(p)), None)
    if zip_idx is None or zip_idx < 3:
        return None
    city = parts[zip_idx - 1]
    county = re.sub(r"\s*County\s*$", "", parts[zip_idx + 1], flags=re.I)
    if not city or not county or not COUNTY_RE.match(county):
        return None
    region = ""
    for part in parts[zip_idx + 2 :]:
        up = part.upper()
        if up in {"BANCRO", "NCRO", "SRO"}:
            region = up
            break
    name = street = ""
    for part in parts[1 : zip_idx - 1]:
        if part.upper() in {"BANCRO", "NCRO", "SRO"}:
            continue
        if not name and len(part) > 2 and not re.match(r"^\d+\s", part) and not looks_like_street(part):
            name = part
            continue
        if looks_like_street(part):
            street = part
    if not name or not street:
        return None
    return {
        "id": jid,
        "name": name,
        "street": street,
        "city": city,
        "zip": parts[zip_idx],
        "county": county,
        "region": region,
    }


def parse_dense_line(line: str) -> list[dict[str, str]]:
    if line.startswith("|") or not re.search(r"\b(BANCRO|NCRO|SRO)\b", line):
        return []
    rows: list[dict[str, str]] = []
    for m in DENSE_SEGMENT_RE.finditer(line):
        jid = normalize_id(m.group(1))
        if not jid:
            continue
        mid = m.group(2).strip()
        zipcode = m.group(3)
        county = re.sub(r"\s*County\s*$", "", m.group(4).strip(), flags=re.I)
        region = m.group(5).upper()
        street_match = STREET_BODY_RE.search(mid)
        if not street_match:
            continue
        street = street_match.group(1).strip()
        name = mid[: street_match.start()].strip()
        city = mid[street_match.end() :].strip()
        if not name or not city or not street:
            continue
        rows.append(
            {
                "id": jid,
                "name": name,
                "street": street,
                "city": city,
                "zip": zipcode,
                "county": county,
                "region": region,
            }
        )
    return rows


def validate_with_parser_line(line: str) -> bool:
    """Mirror jc_ca_parse_building_line acceptance."""
    line = line.strip()
    if not line.startswith("|"):
        return False
    parts = [p.strip() for p in line.split("|") if p.strip()]
    zip_idx = next((i for i, p in enumerate(parts) if ZIP_RE.match(p)), None)
    if zip_idx is None or zip_idx < 2:
        return False
    city = parts[zip_idx - 1]
    county = parts[zip_idx + 1] if zip_idx + 1 < len(parts) else ""
    if not city or not county or not COUNTY_RE.match(county):
        return False
    name = street = ""
    for part in parts[1 : zip_idx - 1]:
        if part.upper() in {"BANCRO", "NCRO", "SRO"}:
            continue
        if not name and len(part) > 2 and not re.match(r"^\d+\s", part) and not looks_like_street(part):
            name = part
            continue
        if looks_like_street(part):
            street = part
    return bool(name and street)


def extract(text: str) -> list[dict[str, str]]:
    rows: list[dict[str, str]] = []
    seen: set[str] = set()
    for line in text.splitlines():
        line = line.strip()
        if not line:
            continue
        parsed_rows = [parse_markdown_row(line)] if line.startswith("|") else parse_dense_line(line)
        for row in parsed_rows:
            if row is None:
                continue
            if not useful(row):
                continue
            key = "|".join(
                row[k].lower()
                for k in ("id", "name", "street", "city")
            )
            if key in seen:
                continue
            seen.add(key)
            rows.append(row)
    rows.sort(key=lambda r: (r["county"].lower(), r["name"].lower()))
    return rows


def main() -> int:
    from_text = ""
    args = sys.argv[1:]
    for i, arg in enumerate(args):
        if arg.startswith("--from-text="):
            from_text = arg.split("=", 1)[1]
        elif arg == "--from-text" and i + 1 < len(args):
            from_text = args[i + 1]
    if not from_text:
        print("Usage: generate_jc_ca_buildings.py --from-text=path", file=sys.stderr)
        return 1
    text = Path(from_text).read_text(encoding="utf-8", errors="replace")
    rows = extract(text)
    if not rows:
        print("No rows extracted", file=sys.stderr)
        return 1
    lines = [
        "# Judicial Council of California — Regional Building List",
        f"# Source: {PDF_URL}",
        "# Generated from Judicial Council Attachment 2 (RFP-FS-SP-2019-03-JP)",
        "# Los Angeles County locations excluded (lacourt.ca.gov provides dept/room data).",
        "# Format: | JCC ID | Facility Name | Address | City | Zip | County | Region |",
        "",
    ]
    skipped = 0
    for row in rows:
        region = row["region"] or "CA"
        pipe = (
            f"| {row['id']} | {row['name']} | {row['street']} | {row['city']} | "
            f"{row['zip']} | {row['county']} | {region} |"
        )
        if validate_with_parser_line(pipe):
            lines.append(pipe)
        else:
            skipped += 1
    OUT.parent.mkdir(parents=True, exist_ok=True)
    OUT.write_text("\n".join(lines) + "\n", encoding="utf-8")
    print(f"Extracted {len(rows)} rows, wrote {len(lines) - 7} valid lines ({skipped} skipped by parser check) -> {OUT}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
