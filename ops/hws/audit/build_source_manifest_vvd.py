#!/usr/bin/env python3
from __future__ import annotations

import argparse
import json
import re
from datetime import datetime, timezone
from pathlib import Path
from typing import Any
from urllib.parse import urlparse


ROOT = Path(__file__).resolve().parents[3]
GENERATED_DIR = ROOT / "data" / "import" / "generated"
OUTPUT_PATH = ROOT / "data" / "audit" / "source" / "vvd.json"


def normalize_text(value: Any) -> str:
    if value is None:
        return ""
    return " ".join(str(value).split()).strip()


def vvd_build_sku(product: dict[str, Any]) -> str:
    offer = product.get("current_offer") or {}
    offer_id = normalize_text(str(offer.get("offer_id", "") or ""))
    url = str(product.get("source_url", "") or "")
    path = urlparse(url).path
    slug = path.rstrip("/").split("/")[-1] if path else ""
    if offer_id and slug:
        return "VVD-" + re.sub(r"[^A-Z0-9]+", "-", slug.upper()) + "-" + offer_id
    if offer_id:
        return "VVD-" + offer_id
    if slug:
        return "VVD-" + re.sub(r"[^A-Z0-9]+", "-", slug.upper())
    return ""


def target_primary_category(series_payload: dict[str, Any]) -> str:
    target = series_payload.get("target") or {}
    path = normalize_text(target.get("primaryCategoryPath"))
    if not path:
        return ""
    return path.split("/")[-1]


def pick_spec_value(specs: Any, labels: list[str]) -> str:
    wanted = {label.lower() for label in labels}
    if isinstance(specs, list):
        for row in specs:
            if not isinstance(row, dict):
                continue
            label = normalize_text(row.get("label") or row.get("name")).lower()
            if label in wanted:
                return normalize_text(row.get("value"))
    elif isinstance(specs, dict):
        for key, val in specs.items():
            if normalize_text(key).lower() in wanted:
                return normalize_text(val)
    return ""


def build_row(series_payload: dict[str, Any], product: dict[str, Any], wave_file: str) -> dict[str, Any]:
    offer = product.get("current_offer") or {}
    option_groups = product.get("option_groups") or []
    offers = product.get("offers") or []
    docs = product.get("documents") or []
    gallery = product.get("gallery") or []

    sku = vvd_build_sku(product)
    series_name = normalize_text(series_payload.get("seriesName"))
    product_name = normalize_text(offer.get("name") or product.get("title") or product.get("name"))
    short_description = normalize_text(product.get("short_description"))
    full_description = normalize_text(product.get("full_description_text") or product.get("full_description"))
    main_image = normalize_text(offer.get("primary_image_url") or (gallery[0].get("url") if gallery else ""))
    product_type = normalize_text((series_payload.get("target") or {}).get("productType"))
    specs = product.get("specs") or []
    power = pick_spec_value(specs, ["Номинальная потребляемая мощность", "Мощность", "Power"])
    category_path = normalize_text((series_payload.get("target") or {}).get("primaryCategoryPath"))
    price_text = normalize_text(offer.get("price_number_rub") or offer.get("price_text"))
    base_price_rub = 0
    if price_text:
        digits = re.sub(r"[^\d]", "", price_text)
        base_price_rub = int(digits) if digits.isdigit() and int(digits) > 0 else 0

    # VVD can be variable (has option_groups). Expected variation count =
    # product of all option-value counts across all option groups.
    expected_variation_count = 0
    if option_groups:
        total = 1
        valid = False
        for group in option_groups:
            if not isinstance(group, dict):
                continue
            values = group.get("values") or []
            if isinstance(values, list) and values:
                total *= len(values)
                valid = True
        if valid:
            expected_variation_count = total
    elif offers:
        expected_variation_count = len(offers)

    return {
        "brand": "vvd",
        "wave_file": wave_file,
        "source_url": normalize_text(product.get("source_url")),
        "source_name": product_name,
        "source_sku": sku,
        "source_series": series_name,
        "source_category": category_path,
        "source_category_slug": target_primary_category(series_payload),
        "target_primary_category": target_primary_category(series_payload),
        "product_type_expected": product_type,
        "source_power": power,
        "base_price_rub": base_price_rub,
        "price_on_request": False if base_price_rub > 0 else None,
        "has_image": bool(main_image),
        "main_image": main_image,
        "has_short_description": bool(short_description),
        "short_description": short_description,
        "has_full_description": bool(full_description),
        "full_description": full_description,
        "has_specs": bool(specs),
        "has_variants": bool(option_groups) or bool(offers),
        "variant_count_expected": expected_variation_count,
        "document_count": len(docs),
        "option_groups_count": len(option_groups),
        "attribute_values": dict(series_payload.get("defaultAttributes") or {}),
    }


def build_manifest_from_payloads(paths: list[Path]) -> dict[str, Any]:
    rows: list[dict[str, Any]] = []
    for path in paths:
        payload = json.loads(path.read_text(encoding="utf-8"))
        for series_payload in payload.get("series", []):
            for product in series_payload.get("products", []):
                if not isinstance(product, dict):
                    continue
                if normalize_text(product.get("error")):
                    continue
                row = build_row(series_payload, product, path.name)
                if row["source_sku"] and row["source_url"]:
                    rows.append(row)

    deduped: dict[str, dict[str, Any]] = {}
    for row in rows:
        key = row["source_sku"] or row["source_url"]
        deduped[key] = row

    final_rows = sorted(
        deduped.values(),
        key=lambda row: (row["source_series"], row["source_sku"], row["source_name"]),
    )

    return {
        "brand": {"name": "ВВД", "slug": "vvd"},
        "generated_at": datetime.now(timezone.utc).isoformat(),
        "source": "generated detailed wave payloads",
        "input_files": [path.name for path in paths],
        "total": len(final_rows),
        "products": final_rows,
    }


def main() -> None:
    parser = argparse.ArgumentParser()
    parser.add_argument(
        "--input-glob",
        default="hws-vvd-wave-*-electric-and-steam.json",
        help="glob inside data/import/generated (excludes smoke files by default)",
    )
    parser.add_argument(
        "--output",
        default=str(OUTPUT_PATH),
        help="output path",
    )
    args = parser.parse_args()

    paths = sorted(GENERATED_DIR.glob(args.input_glob))
    if not paths:
        raise SystemExit(f"no files matched: {GENERATED_DIR / args.input_glob}")

    manifest = build_manifest_from_payloads(paths)
    output_path = Path(args.output)
    output_path.parent.mkdir(parents=True, exist_ok=True)
    output_path.write_text(
        json.dumps(manifest, ensure_ascii=False, indent=2) + "\n",
        encoding="utf-8",
    )
    print(f"brand=vvd total={manifest['total']} output={output_path}")


if __name__ == "__main__":
    main()
