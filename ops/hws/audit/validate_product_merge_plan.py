#!/usr/bin/env python3
"""Validate a product merge plan without modifying WooCommerce.

The report makes every source difference explicit. It never treats a matching
title as proof that a product may be deleted.
"""

from __future__ import annotations

import argparse
import json
from collections import Counter
from datetime import datetime, timezone
from pathlib import Path
from typing import Any


def values(products: list[dict[str, Any]], field: str) -> dict[str, list[int]]:
    grouped: dict[str, list[int]] = {}
    for product in products:
        value = json.dumps(product.get(field), ensure_ascii=False, sort_keys=True)
        grouped.setdefault(value, []).append(product["id"])
    return grouped


def attribute_differences(products: list[dict[str, Any]]) -> dict[str, dict[str, list[int]]]:
    keys = sorted({key for product in products for key in product["attributes"]})
    differences: dict[str, dict[str, list[int]]] = {}
    for key in keys:
        distinct: dict[str, list[int]] = {}
        for product in products:
            value = product["attributes"].get(key, {"values": []})["values"]
            distinct.setdefault(json.dumps(value, ensure_ascii=False), []).append(product["id"])
        if len(distinct) > 1:
            differences[key] = distinct
    return differences


def validate_variable_group(group: dict[str, Any], inventory: dict[int, dict[str, Any]]) -> dict[str, Any]:
    products = [inventory[product_id] for product_id in group["source_ids"]]
    missing = [product_id for product_id in group["source_ids"] if product_id not in inventory]
    sku_counts = Counter(product["sku"] for product in products)
    empty_fields = {
        field: [product["id"] for product in products if not product.get(field)]
        for field in ("sku", "price", "image_hashes", "description_hash", "short_description_hash")
    }
    empty_fields = {field: ids for field, ids in empty_fields.items() if ids}
    target_conflict = group["target_slug"] in {
        product["slug"] for product in inventory.values()
    }
    return {
        "key": group["key"],
        "brand": group["brand"],
        "source_count": len(products),
        "source_ids": group["source_ids"],
        "target_slug": group["target_slug"],
        "missing_source_ids": missing,
        "duplicate_skus": sorted(sku for sku, count in sku_counts.items() if sku and count > 1),
        "empty_required_fields": empty_fields,
        "target_slug_conflicts_with_current_product": target_conflict,
        "different_attributes": attribute_differences(products),
        "different_content": {
            field: values(products, field)
            for field in ("price", "description_hash", "short_description_hash", "image_hashes")
            if len(values(products, field)) > 1
        },
        "result": "needs-review",
    }


def validate_exact_duplicate_group(group: dict[str, Any], inventory: dict[int, dict[str, Any]]) -> dict[str, Any]:
    ids = [group["retain_id"], *group["delete_ids"]]
    products = [inventory[product_id] for product_id in ids if product_id in inventory]
    signatures = {product["content_signature"] for product in products}
    return {
        "title": group["title"],
        "source_ids": ids,
        "missing_source_ids": [product_id for product_id in ids if product_id not in inventory],
        "content_signature_count": len(signatures),
        "redirects": group["redirects"],
        "result": "verified-exact" if len(products) == len(ids) and len(signatures) == 1 else "rejected",
    }


def main() -> None:
    parser = argparse.ArgumentParser()
    parser.add_argument("--inventory", required=True, type=Path)
    parser.add_argument("--plan", required=True, type=Path)
    parser.add_argument("--output", required=True, type=Path)
    args = parser.parse_args()

    inventory_payload = json.loads(args.inventory.read_text(encoding="utf-8"))
    plan = json.loads(args.plan.read_text(encoding="utf-8"))
    inventory = {product["id"]: product for product in inventory_payload["products"]}
    variable_groups = [validate_variable_group(group, inventory) for group in plan["variable_groups"]]
    exact_groups = [validate_exact_duplicate_group(group, inventory) for group in plan["exact_duplicate_groups"]]
    report = {
        "generated_at": datetime.now(timezone.utc).isoformat(),
        "mode": "read-only dry-run; no WooCommerce write performed",
        "plan_generated_at": plan["generated_at"],
        "variable_groups": variable_groups,
        "exact_duplicate_groups": exact_groups,
        "summary": {
            "variable_groups": len(variable_groups),
            "variable_source_products": sum(group["source_count"] for group in variable_groups),
            "variable_groups_with_missing_sources": sum(bool(group["missing_source_ids"]) for group in variable_groups),
            "variable_groups_with_duplicate_skus": sum(bool(group["duplicate_skus"]) for group in variable_groups),
            "variable_groups_with_target_conflicts": sum(bool(group["target_slug_conflicts_with_current_product"]) for group in variable_groups),
            "verified_exact_duplicate_groups": sum(group["result"] == "verified-exact" for group in exact_groups),
            "rejected_exact_duplicate_groups": sum(group["result"] == "rejected" for group in exact_groups),
        },
    }
    args.output.parent.mkdir(parents=True, exist_ok=True)
    args.output.write_text(json.dumps(report, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    print(json.dumps(report["summary"], ensure_ascii=False))


if __name__ == "__main__":
    main()
