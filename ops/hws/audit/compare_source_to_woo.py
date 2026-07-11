#!/usr/bin/env python3
from __future__ import annotations

import argparse
import json
from datetime import datetime, timezone
from pathlib import Path
from typing import Any


ROOT = Path(__file__).resolve().parents[3]
SOURCE_DIR = ROOT / "data" / "audit" / "source"
WOO_DIR = ROOT / "data" / "audit" / "woo"
DIFF_DIR = ROOT / "data" / "audit" / "diff"


def load_json(path: Path) -> Any:
    return json.loads(path.read_text(encoding="utf-8"))


def normalize_text(value: Any) -> str:
    if value is None:
        return ""
    if isinstance(value, (int, float)):
        return str(value).strip()
    return " ".join(str(value).split()).strip()


def normalize_bool(value: Any) -> bool:
    if isinstance(value, bool):
        return value
    if value is None:
        return False
    text = normalize_text(value).lower()
    return text in {"1", "true", "yes", "y", "да", "present"}


def normalize_slug(value: Any) -> str:
    return normalize_text(value).lower().replace("ё", "е").replace(" ", "-")


def normalize_sku(value: Any) -> str:
    text = normalize_text(value)
    return text.upper().replace(" ", "")


def read_source_rows(path: Path) -> list[dict[str, Any]]:
    payload = load_json(path)
    if isinstance(payload, list):
        rows = payload
    elif isinstance(payload, dict):
        rows = payload.get("products") or payload.get("items") or payload.get("rows") or []
    else:
        rows = []
    return [row for row in rows if isinstance(row, dict)]


def read_woo_rows(path: Path) -> list[dict[str, Any]]:
    payload = load_json(path)
    if isinstance(payload, dict):
        rows = payload.get("products") or payload.get("items") or payload.get("rows") or []
    elif isinstance(payload, list):
        rows = payload
    else:
        rows = []
    return [row for row in rows if isinstance(row, dict)]


def source_sku(row: dict[str, Any]) -> str:
    for key in ("source_sku", "sku", "article", "id", "vendor_code"):
        value = normalize_sku(row.get(key))
        if value:
            return value
    return ""


def woo_sku(row: dict[str, Any]) -> str:
    return normalize_sku(row.get("sku"))


def source_category_slug(row: dict[str, Any]) -> str:
    candidates = [
        row.get("target_primary_category"),
        row.get("primary_category"),
        row.get("source_category_slug"),
        row.get("category_slug"),
        row.get("source_category"),
        row.get("category"),
    ]
    for value in candidates:
        text = normalize_text(value)
        if not text:
            continue
        if "/" in text:
            text = text.split("/")[-1]
        return normalize_slug(text)
    return ""


def source_has_image(row: dict[str, Any]) -> bool:
    if "has_image" in row:
        return normalize_bool(row.get("has_image"))
    images = row.get("images") or row.get("image_urls") or []
    if isinstance(images, list) and images:
        return True
    return bool(normalize_text(row.get("image")) or normalize_text(row.get("image_url")))


def source_has_short_description(row: dict[str, Any]) -> bool:
    if "has_short_description" in row:
        return normalize_bool(row.get("has_short_description"))
    return bool(normalize_text(row.get("short_description")))


def source_has_full_description(row: dict[str, Any]) -> bool:
    if "has_full_description" in row:
        return normalize_bool(row.get("has_full_description"))
    return bool(normalize_text(row.get("description") or row.get("full_description") or row.get("full_description_text")))


def source_attribute_keys(row: dict[str, Any]) -> set[str]:
    keys: set[str] = set()
    attrs = row.get("attributes")
    if isinstance(attrs, dict):
        keys.update(normalize_text(key) for key in attrs.keys() if normalize_text(key))
    default_attrs = row.get("defaultAttributes")
    if isinstance(default_attrs, dict):
        keys.update(normalize_text(key) for key in default_attrs.keys() if normalize_text(key))
    attribute_values = row.get("attribute_values")
    if isinstance(attribute_values, dict):
        keys.update(
            normalize_text(key)
            for key, val in attribute_values.items()
            if normalize_text(key) and normalize_text(val)
        )
    return {key for key in keys if key}


def source_expected_variation_count(row: dict[str, Any]) -> int:
    for key in ("variant_count_expected", "expected_variation_count", "variation_count_expected"):
        value = row.get(key)
        if isinstance(value, int):
            return value
        text = normalize_text(value)
        if text.isdigit():
            return int(text)
    option_groups = row.get("option_groups")
    if isinstance(option_groups, list) and option_groups:
        total = 1
        valid = False
        for group in option_groups:
            if not isinstance(group, dict):
                continue
            options = group.get("options")
            if isinstance(options, list) and options:
                total *= len(options)
                valid = True
        if valid:
            return total
    offers = row.get("offers")
    if isinstance(offers, list):
        return len(offers)
    return 0


def source_price_on_request(row: dict[str, Any]) -> bool:
    for key in ("price_on_request_expected", "price_on_request"):
        if key in row and row.get(key) is not None and normalize_text(row.get(key)) != "":
            return normalize_bool(row.get(key))
    price = normalize_text(row.get("price") or row.get("base_price") or row.get("base_price_rub"))
    if price == "":
        return True
    try:
        return float(price.replace(",", ".")) <= 0
    except ValueError:
        return False


def detect_broken_matches(source_row: dict[str, Any], woo_row: dict[str, Any]) -> list[str]:
    reasons: list[str] = []

    if source_has_image(source_row) and int(woo_row.get("image_count") or 0) <= 0:
        reasons.append("missing_image")

    if source_has_short_description(source_row) and not normalize_bool(woo_row.get("has_short_description")):
        reasons.append("missing_short_description")

    if source_has_full_description(source_row) and not normalize_bool(woo_row.get("has_full_description")):
        reasons.append("missing_full_description")

    source_category = source_category_slug(source_row)
    woo_primary = normalize_slug(woo_row.get("primary_category"))
    woo_categories = {normalize_slug(slug) for slug in woo_row.get("category_slugs") or [] if normalize_slug(slug)}
    if source_category and source_category != woo_primary and source_category not in woo_categories:
        reasons.append("wrong_category")

    expected_attributes = source_attribute_keys(source_row)
    actual_attributes = {normalize_text(key) for key in woo_row.get("attribute_keys") or [] if normalize_text(key)}
    if expected_attributes and not expected_attributes.issubset(actual_attributes):
        reasons.append("missing_attributes")

    expected_variations = source_expected_variation_count(source_row)
    actual_variations = int(woo_row.get("variation_count") or 0)
    if expected_variations > 0 and actual_variations != expected_variations:
        reasons.append("variation_issue")

    source_por = source_price_on_request(source_row)
    woo_por = normalize_bool(woo_row.get("price_on_request"))
    woo_has_price = bool(normalize_text(woo_row.get("price") or woo_row.get("regular_price")))
    if source_por != woo_por:
        reasons.append("price_issue")
    elif not source_por and not woo_has_price:
        reasons.append("price_issue")

    return reasons


def build_diff_report(brand: str) -> dict[str, Any]:
    source_path = SOURCE_DIR / f"{brand}.json"
    woo_path = WOO_DIR / f"{brand}.json"

    if not source_path.exists():
        raise FileNotFoundError(f"source manifest not found: {source_path}")
    if not woo_path.exists():
        raise FileNotFoundError(f"woo snapshot not found: {woo_path}")

    source_rows = read_source_rows(source_path)
    woo_rows = read_woo_rows(woo_path)

    source_by_sku: dict[str, dict[str, Any]] = {}
    source_duplicates: list[dict[str, Any]] = []
    for row in source_rows:
        sku = source_sku(row)
        if not sku:
            continue
        if sku in source_by_sku:
            source_duplicates.append({"sku": sku, "source_name": normalize_text(row.get("source_name") or row.get("name"))})
            continue
        source_by_sku[sku] = row

    woo_by_sku: dict[str, dict[str, Any]] = {}
    woo_duplicates: list[dict[str, Any]] = []
    for row in woo_rows:
        sku = woo_sku(row)
        if not sku:
            continue
        if sku in woo_by_sku:
            woo_duplicates.append({"sku": sku, "product_id": row.get("product_id"), "slug": row.get("slug")})
            continue
        woo_by_sku[sku] = row

    matched: list[dict[str, Any]] = []
    matched_but_broken: list[dict[str, Any]] = []
    source_only: list[dict[str, Any]] = []
    woo_only: list[dict[str, Any]] = []

    for sku, source_row in sorted(source_by_sku.items()):
        woo_row = woo_by_sku.get(sku)
        if woo_row is None:
            source_only.append(
                {
                    "sku": sku,
                    "source_name": normalize_text(source_row.get("source_name") or source_row.get("name")),
                    "source_url": normalize_text(source_row.get("source_url") or source_row.get("url")),
                }
            )
            continue

        reasons = detect_broken_matches(source_row, woo_row)
        summary = {
            "sku": sku,
            "source_name": normalize_text(source_row.get("source_name") or source_row.get("name")),
            "woo_product_id": woo_row.get("product_id"),
            "woo_slug": woo_row.get("slug"),
        }
        if reasons:
            matched_but_broken.append({**summary, "reasons": reasons})
        else:
            matched.append(summary)

    for sku, woo_row in sorted(woo_by_sku.items()):
        if sku in source_by_sku:
            continue
        woo_only.append(
            {
                "sku": sku,
                "woo_product_id": woo_row.get("product_id"),
                "woo_slug": woo_row.get("slug"),
                "title": normalize_text(woo_row.get("title")),
            }
        )

    return {
        "brand": brand,
        "generated_at": datetime.now(timezone.utc).isoformat(),
        "source_total_rows": len(source_rows),
        "woo_total_rows": len(woo_rows),
        "source_total_unique_skus": len(source_by_sku),
        "woo_total_unique_skus": len(woo_by_sku),
        "summary": {
            "matched": len(matched),
            "matched_but_broken": len(matched_but_broken),
            "source_only": len(source_only),
            "woo_only": len(woo_only),
            "source_duplicates": len(source_duplicates),
            "woo_duplicates": len(woo_duplicates),
        },
        "matched": matched,
        "matched_but_broken": matched_but_broken,
        "source_only": source_only,
        "woo_only": woo_only,
        "source_duplicates": source_duplicates,
        "woo_duplicates": woo_duplicates,
    }


def main() -> None:
    parser = argparse.ArgumentParser()
    parser.add_argument("--brand", required=True, help="brand slug, e.g. easysteam")
    args = parser.parse_args()

    report = build_diff_report(normalize_slug(args.brand))
    DIFF_DIR.mkdir(parents=True, exist_ok=True)
    output_path = DIFF_DIR / f"{normalize_slug(args.brand)}.json"
    output_path.write_text(
        json.dumps(report, ensure_ascii=False, indent=2) + "\n",
        encoding="utf-8",
    )
    print(
        f"brand={report['brand']} matched={report['summary']['matched']} "
        f"broken={report['summary']['matched_but_broken']} source_only={report['summary']['source_only']} "
        f"woo_only={report['summary']['woo_only']} output={output_path}"
    )


if __name__ == "__main__":
    main()
