#!/usr/bin/env python3
"""Build a read-only, reviewable plan for product consolidation.

The plan deliberately contains no write operation. A later merge runner must
refuse plans unless every group is explicitly approved and has a redirect map.
"""

from __future__ import annotations

import argparse
import json
import re
from collections import defaultdict
from datetime import datetime, timezone
from pathlib import Path
from typing import Any


CYRILLIC = str.maketrans(
    {
        "а": "a", "б": "b", "в": "v", "г": "g", "д": "d", "е": "e", "ё": "e", "ж": "zh",
        "з": "z", "и": "i", "й": "y", "к": "k", "л": "l", "м": "m", "н": "n", "о": "o",
        "п": "p", "р": "r", "с": "s", "т": "t", "у": "u", "ф": "f", "х": "h", "ц": "ts",
        "ч": "ch", "ш": "sh", "щ": "sch", "ъ": "", "ы": "y", "ь": "", "э": "e", "ю": "yu", "я": "ya",
    }
)


def slugify(value: str) -> str:
    value = value.lower().translate(CYRILLIC)
    value = re.sub(r"[^a-z0-9]+", "-", value).strip("-")
    return re.sub(r"-+", "-", value)


def sangens_groups(products: list[dict[str, Any]]) -> list[dict[str, Any]]:
    groups: dict[str, list[dict[str, Any]]] = defaultdict(list)
    patterns = (
        (re.compile(r"\bSangens L\d+C Ceramic\b", re.I), "sangens-l-ceramic", "Sangens L Ceramic"),
        (re.compile(r"\bSangens LS\d+C Ceramic\b", re.I), "sangens-ls-ceramic", "Sangens LS Ceramic"),
        (re.compile(r"\bSangens W(12|20|30|40)[GBS] ", re.I), None, None),
    )
    for product in products:
        title = product["title"]
        for pattern, fixed_key, fixed_name in patterns:
            match = pattern.search(title)
            if not match:
                continue
            if fixed_key:
                groups[fixed_key].append(product)
            else:
                model = match.group(1)
                groups[f"sangens-w{model}"].append(product)
            break

    result: list[dict[str, Any]] = []
    for key, sources in sorted(groups.items()):
        if len(sources) < 2:
            continue
        if key.startswith("sangens-w"):
            target_name = f"Sangens W{key.removeprefix('sangens-w')}"
            variation_attributes = ["pa_finish"]
        else:
            target_name = "Sangens L Ceramic" if key == "sangens-l-ceramic" else "Sangens LS Ceramic"
            variation_attributes = ["pa_power"]
        result.append(
            {
                "key": key,
                "target_slug": key,
                "target_name": target_name,
                "brand": "Sangens",
                "kind": "variable_product",
                "source_ids": sorted(source["id"] for source in sources),
                "source_slugs": sorted(source["slug"] for source in sources),
                "redirects": {source["slug"]: key for source in sources},
                "variation_attributes": variation_attributes,
                "state": "needs-approval",
            }
        )
    return result


def exact_duplicate_groups(inventory: dict[str, Any]) -> list[dict[str, Any]]:
    result: list[dict[str, Any]] = []
    for group in inventory.get("exact_duplicate_groups", []):
        retain = group["retain_suggestion"]
        deletions = group["delete_candidates"]
        result.append(
            {
                "kind": "exact_duplicate",
                "title": group["title"],
                "retain_id": retain["id"],
                "retain_slug": retain["slug"],
                "delete_ids": [row["id"] for row in deletions],
                "delete_slugs": [row["slug"] for row in deletions],
                "redirects": {row["slug"]: retain["slug"] for row in deletions},
                "state": "needs-approval",
            }
        )
    return result


def source_groups(
    brand: str,
    products_by_sku: dict[str, dict[str, Any]],
    source_rows: list[dict[str, Any]],
    excluded_ids: set[int],
) -> list[dict[str, Any]]:
    grouped: dict[str, list[dict[str, Any]]] = defaultdict(list)
    labels: dict[str, str] = {}
    for source in source_rows:
        product = products_by_sku.get(str(source.get("source_sku", "")))
        if not product or product["id"] in excluded_ids:
            continue
        if brand == "easysteam":
            label = str(source.get("source_series", "")).strip()
            name = str(source.get("source_name", ""))
            if label in {"Анапа", "Сочи", "Геленджик"}:
                label = f"{label} М2" if re.search(r"\bМ2\b", name) else label
            elif label == "Южная":
                match = re.search(r"\bЮжная\s+(\d+)\b", name)
                label = f"Южная {match.group(1)}" if match else label
        else:
            label = str(source.get("eos_family", "")).strip()
        if not label:
            continue
        key = slugify(label)
        grouped[key].append(product)
        labels[key] = label

    result: list[dict[str, Any]] = []
    for key, sources in sorted(grouped.items()):
        source_ids = sorted({source["id"] for source in sources})
        if len(source_ids) < 2:
            continue
        label = labels[key]
        result.append(
            {
                "key": f"{brand}-{key}",
                "target_slug": f"{brand}-{key}",
                "target_name": label,
                "brand": "EasySteam" if brand == "easysteam" else "EOS",
                "kind": "variable_product",
                "source_ids": source_ids,
                "source_slugs": sorted({source["slug"] for source in sources}),
                "redirects": {
                    source["slug"]: f"{brand}-{key}"
                    for source in sorted(sources, key=lambda item: item["slug"])
                },
                "variation_attributes": "requires per-group source-attribute validation",
                "state": "needs-review",
            }
        )
    return result


def main() -> None:
    parser = argparse.ArgumentParser()
    parser.add_argument("--inventory", required=True, type=Path)
    parser.add_argument("--output", required=True, type=Path)
    args = parser.parse_args()

    inventory = json.loads(args.inventory.read_text(encoding="utf-8"))
    products = [
        product
        for product in inventory["products"]
        if product["type"] == "simple" and product["status"] == "publish"
    ]
    sangens = [product for product in products if product["slug"].startswith("sangens-")]
    duplicate_groups = exact_duplicate_groups(inventory)
    duplicate_ids = {product_id for group in duplicate_groups for product_id in group["delete_ids"]}
    products_by_sku = {product["sku"]: product for product in products if product["sku"]}
    easysteam_source = json.loads((Path(__file__).resolve().parents[3] / "data/audit/source/easysteam.json").read_text(encoding="utf-8"))["products"]
    eos_source = json.loads((Path(__file__).resolve().parents[3] / "data/audit/source/eos.json").read_text(encoding="utf-8"))["products"]
    variable_groups = (
        sangens_groups([product for product in sangens if product["id"] not in duplicate_ids])
        + source_groups("easysteam", products_by_sku, easysteam_source, duplicate_ids)
        + source_groups("eos", products_by_sku, eos_source, duplicate_ids)
    )
    source_redirects = {
        old_slug: target_slug
        for group in variable_groups
        for old_slug, target_slug in group["redirects"].items()
    }
    for group in duplicate_groups:
        target_slug = source_redirects.get(group["retain_slug"], group["retain_slug"])
        group["final_target_slug"] = target_slug
        group["redirects"] = {
            old_slug: target_slug for old_slug in group["delete_slugs"]
        }
    planned_ids = {product_id for group in variable_groups for product_id in group["source_ids"]}
    planned_ids.update(duplicate_ids)
    unassigned = [product for product in products if product["id"] not in planned_ids]
    plan = {
        "generated_at": datetime.now(timezone.utc).isoformat(),
        "mode": "read-only plan; not executable",
        "inventory_generated_at": inventory.get("generated_at"),
        "required_before_execution": [
            "approved group-by-group manifest",
            "redirect map for every old URL",
            "fresh backup",
            "per-group dry-run and production verification",
        ],
        "variable_groups": variable_groups,
        "exact_duplicate_groups": duplicate_groups,
        "manual_review": [
            {
                "id": product["id"],
                "slug": product["slug"],
                "sku": product["sku"],
                "title": product["title"],
                "reason": "not included in an approved brand merge group",
            }
            for product in unassigned
        ],
        "summary": {
            "sangens_variable_groups": len(
                [group for group in variable_groups if group["brand"] == "Sangens"]
            ),
            "sangens_source_products": sum(
                len(group["source_ids"])
                for group in variable_groups
                if group["brand"] == "Sangens"
            ),
            "variable_groups": len(variable_groups),
            "variable_source_products": sum(len(group["source_ids"]) for group in variable_groups),
            "exact_duplicate_groups": len(duplicate_groups),
            "exact_duplicate_urls": sum(len(group["delete_ids"]) for group in duplicate_groups),
            "manual_review_products": len(unassigned),
        },
    }
    args.output.parent.mkdir(parents=True, exist_ok=True)
    args.output.write_text(json.dumps(plan, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    print(json.dumps(plan["summary"], ensure_ascii=False))


if __name__ == "__main__":
    main()
