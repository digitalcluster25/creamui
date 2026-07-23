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
    variable_groups = sangens_groups(sangens)
    duplicate_groups = exact_duplicate_groups(inventory)
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
        "summary": {
            "sangens_variable_groups": len(variable_groups),
            "sangens_source_products": sum(len(group["source_ids"]) for group in variable_groups),
            "exact_duplicate_groups": len(duplicate_groups),
            "exact_duplicate_urls": sum(len(group["delete_ids"]) for group in duplicate_groups),
        },
    }
    args.output.parent.mkdir(parents=True, exist_ok=True)
    args.output.write_text(json.dumps(plan, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    print(json.dumps(plan["summary"], ensure_ascii=False))


if __name__ == "__main__":
    main()
