#!/usr/bin/env python3
import argparse
import json
import re
from html import unescape
from pathlib import Path
from typing import Any
from urllib.parse import urljoin
from urllib.request import Request, urlopen


USER_AGENT = "Mozilla/5.0 (compatible; HWSImporter/1.0)"
FETCH_TIMEOUT = 12


def fetch_text(url: str) -> str:
    req = Request(url, headers={"User-Agent": USER_AGENT})
    with urlopen(req, timeout=FETCH_TIMEOUT) as resp:
        return resp.read().decode("utf-8", errors="replace")


def clean_html_text(value: str) -> str:
    text = re.sub(r"<br\s*/?>", "\n", value, flags=re.I)
    text = re.sub(r"<[^>]+>", " ", text)
    text = unescape(text)
    text = re.sub(r"[ \t\r\f\v]+", " ", text)
    text = re.sub(r"\n\s*", "\n", text)
    return text.strip()


def slugify(value: str) -> str:
    value = value.lower().strip()
    value = re.sub(r"[^a-z0-9а-яё]+", "-", value, flags=re.I)
    value = re.sub(r"-{2,}", "-", value)
    return value.strip("-")


def extract_first(pattern: str, text: str, flags: int = re.S | re.I) -> str | None:
    match = re.search(pattern, text, flags)
    return match.group(1).strip() if match else None


def extract_tab_pane(html: str, tab_id: str) -> str | None:
    pattern = rf'<div[^>]*class="tab-pane[^"]*"[^>]*id="{re.escape(tab_id)}"[^>]*>(?P<body>.*?)</div>\s*(?=<div[^>]*class="tab-pane|\Z)'
    match = re.search(pattern, html, re.S | re.I)
    return match.group("body") if match else None


def parse_series_intro(html: str) -> str | None:
    intro = extract_first(r'<h1[^>]*>.*?</h1>\s*<p[^>]*>(.*?)</p>', html)
    if intro:
        return intro
    return extract_first(
        r'<div[^>]*class="catalog-banner__title[^"]*"[^>]*>.*?</div>\s*(.*?)\s*(?=<a[^>]*>|<div[^>]*class="catalog-banner__links"|</div>)',
        html,
    )


def parse_series_cards(html: str, base_url: str) -> list[dict[str, Any]]:
    cards: list[dict[str, Any]] = []
    pattern = re.compile(
        r'<div class="product-card js-product">(?P<body>.*?)'
        r'<a href="(?P<href>https://easysteam\.ru/products/(?:show|product)/[^"]+)" class="product-card__button button">Подробнее</a>',
        re.S | re.I,
    )
    for match in pattern.finditer(html):
        body = match.group("body")
        href = match.group("href")
        title = extract_first(r'<div class="product-card__title">(.*?)</div>', body)
        subtitle = extract_first(r'<div class="product-card__text">(.*?)</div>', body)
        price_text = extract_first(r'<div class="product-card__price js-product-price">(.*?)</div>', body)
        image = extract_first(r'<img src="([^"]+)" class="product-card__image-inner"', body)
        options: list[dict[str, Any]] = []
        for idx, opt in enumerate(
            re.finditer(
                r'<div class="product-card-options__item[^"]*?(?P<active> is-active)?">.*?'
                r'data-image="(?P<image>[^"]*)".*?data-price="(?P<price>[^"]*)".*?'
                r'style="background-image: url\((?P<thumb>[^)]+)\)',
                body,
                re.S | re.I,
            )
        ):
            options.append(
                {
                    "name": None,
                    "thumb": opt.group("thumb").strip("'\""),
                    "image": urljoin(base_url, opt.group("image")),
                    "price_text": opt.group("price").strip(),
                    "is_default": bool(opt.group("active")),
                    "sort_order": idx,
                }
            )
        cards.append(
            {
                "source_url": href,
                "source_type": "show_page" if "/products/show/" in href else "product_page",
                "title": clean_html_text(title or ""),
                "subtitle": clean_html_text(subtitle or ""),
                "price_text": clean_html_text(price_text or ""),
                "image": urljoin(base_url, image) if image else None,
                "option_count": len(options),
                "options": options,
            }
        )
    deduped: dict[str, dict[str, Any]] = {}
    for card in cards:
        deduped[card["source_url"]] = card
    return list(deduped.values())


def parse_option_groups(html: str, base_url: str) -> list[dict[str, Any]]:
    groups: list[dict[str, Any]] = []
    for block in re.finditer(r'<div class="product__params-item" data-id="([^"]+)">(.*?)</div>\s*</div>\s*</div>', html, re.S | re.I):
        group_id = block.group(1)
        body = block.group(2)
        label = extract_first(r'<div\s+class="radio-group__title">(.*?)</div>', body)
        if not label:
            continue
        values: list[dict[str, Any]] = []
        item_pattern = re.compile(
            r'<div class="radio-group__item">.*?<input(?P<input>.*?)data-price="(?P<price>[^"]*)".*?'
            r'data-add-image="(?P<add_image>[^"]*)".*?'
            r'<img\s+src="(?P<thumb>[^"]+)"\s+alt="(?P<alt>[^"]*)".*?'
            r'<div\s+class="radio-group__item-text">(?P<text>.*?)</div>',
            re.S | re.I,
        )
        for idx, item in enumerate(item_pattern.finditer(body)):
            input_block = item.group("input")
            values.append(
                {
                    "name": clean_html_text(item.group("text")),
                    "delta_price": int(re.sub(r"[^\d-]", "", item.group("price") or "0") or "0"),
                    "image": urljoin(base_url, item.group("add_image")) if item.group("add_image") else None,
                    "thumb": item.group("thumb"),
                    "alt": clean_html_text(item.group("alt")),
                    "is_default": "checked" in input_block,
                    "sort_order": idx,
                }
            )
        if values:
            groups.append({"id": group_id, "name": clean_html_text(label), "values": values})
    return groups


def parse_specs_groups(html: str) -> list[dict[str, Any]]:
    result: list[dict[str, Any]] = []
    block = extract_tab_pane(html, "prod-technical-data") or html
    current_section = None
    section_pattern = re.compile(
        r'<div[^>]*class="product__description-title"[^>]*>(.*?)</div>(.*?)(?=<div[^>]*class="product__description-title"|$)',
        re.S | re.I,
    )
    for section_match in section_pattern.finditer(block):
        current_section = clean_html_text(section_match.group(1))
        section_body = section_match.group(2)
        for group_match in re.finditer(r'<p class="font-weight-bold text-dark">(.*?)</p>(.*?)(?=<p class="font-weight-bold text-dark">|$)', section_body, re.S | re.I):
            title = clean_html_text(group_match.group(1))
            rows: list[dict[str, str]] = []
            for row in re.finditer(r'<tr>\s*<td>(.*?)</td>\s*<td class="font-weight-bold">(.*?)</td>\s*</tr>', group_match.group(2), re.S | re.I):
                rows.append({"label": clean_html_text(row.group(1)), "value": clean_html_text(row.group(2))})
            if rows:
                result.append({"section": current_section, "title": title, "rows": rows})
    return result


def parse_documents(html: str, base_url: str) -> list[dict[str, str]]:
    docs: list[dict[str, str]] = []
    pattern = re.compile(
        r'<img class="document-card__img[^"]*" src="(?P<image>[^"]+)"\s+alt="(?P<alt>[^"]*)".*?'
        r'<a href="(?P<href>[^"]+)"\s+download=',
        re.S | re.I,
    )
    for match in pattern.finditer(html):
        docs.append(
            {
                "title": clean_html_text(match.group("alt")),
                "url": urljoin(base_url, match.group("href")),
                "preview": urljoin(base_url, match.group("image")),
            }
        )
    deduped: dict[str, dict[str, str]] = {}
    for doc in docs:
        deduped[doc["url"]] = doc
    return list(deduped.values())


def parse_purpose_description(html: str) -> str | None:
    block = extract_tab_pane(html, "prod-purpose")
    if not block:
        return None
    paragraphs = [
        clean_html_text(match.group(1))
        for match in re.finditer(r"<div[^>]*>(.*?)</div>", block, re.S | re.I)
        if clean_html_text(match.group(1))
    ]
    if not paragraphs:
        return None
    return "\n\n".join(paragraphs)


def parse_advantages_description(html: str) -> str | None:
    block = extract_tab_pane(html, "prod-advantage")
    if not block:
        return None
    items: list[str] = []
    pattern = re.compile(
        r'<div[^>]*class="row\s+mb-2"[^>]*>.*?'
        r'<div[^>]*class="col-md-3\s+col-sm\s+font-weight-bold"[^>]*>(.*?)</div>.*?'
        r'<div[^>]*class="col-md-3\s+col-sm"[^>]*>(.*?)</div>.*?'
        r"</div>",
        re.S | re.I,
    )
    for match in pattern.finditer(block):
        title = clean_html_text(match.group(1))
        text = clean_html_text(match.group(2))
        if title and text:
            items.append(f"{title}. {text}")
        elif text:
            items.append(text)
    if not items:
        return None
    return "\n\n".join(items)


def parse_short_description(html: str) -> str | None:
    short_description = extract_first(
        r'<div[^>]*id="prod-technical-data"[^>]*>.*?'
        r'<div[^>]*class="col-12\s+col-lg-5\s+col-xl-6"[^>]*>\s*'
        r'<div[^>]*class="product__description"[^>]*>\s*'
        r'<div[^>]*class="product__description-title"[^>]*>\s*Описание\s*</div>\s*'
        r'<div[^>]*>(.*?)</div>',
        html,
    )
    return clean_html_text(short_description) if short_description else None


def parse_description(html: str) -> str | None:
    composed_parts = [
        part
        for part in [
            parse_purpose_description(html),
            parse_advantages_description(html),
        ]
        if part
    ]
    if composed_parts:
        return "\n\n".join(composed_parts)
    for block in filter(
        None,
        [
            extract_tab_pane(html, "prod-description"),
            extract_tab_pane(html, "prod-technical-data"),
        ],
    ):
        description = extract_first(
            r'<div[^>]*class="product__description-title"[^>]*>\s*Описание\s*</div>\s*<div[^>]*>(.*?)</div>',
            block,
        )
        if description:
            return description
    return None


def find_spec_value(specs_groups: list[dict[str, Any]], label: str) -> str | None:
    for group in specs_groups:
        for row in group.get("rows", []):
            if clean_html_text(row.get("label", "")) == label:
                value = clean_html_text(row.get("value", ""))
                if value:
                    return value
    return None


def short_description_from_description(description: str | None) -> str | None:
    if not description:
        return None
    text = clean_html_text(description)
    if not text:
        return None
    sentences = re.split(r"(?<=[.!?])\s+", text)
    snippet = " ".join(sentence.strip() for sentence in sentences[:2] if sentence.strip())
    return snippet or text


def hydrate_short_descriptions(products: list[dict[str, Any]]) -> None:
    short_by_model: dict[str, str] = {}
    for product in products:
        model = find_spec_value(product.get("specs_groups", []), "Модель")
        short_description = product.get("short_description")
        if model and short_description and model not in short_by_model:
            short_by_model[model] = short_description

    for product in products:
        if product.get("short_description"):
            continue
        model = find_spec_value(product.get("specs_groups", []), "Модель")
        if model and short_by_model.get(model):
            product["short_description"] = short_by_model[model]
            continue
        fallback = short_description_from_description(product.get("description"))
        if fallback:
            product["short_description"] = fallback


def parse_product_page(url: str) -> dict[str, Any]:
    html = fetch_text(url)
    base_url = "https://easysteam.ru"
    title = extract_first(r'<h1 class="product__title[^"]*">(.*?)</h1>', html)
    article = extract_first(r'data-product-offer="([^"]+)"', html)
    main_image = extract_first(r'<a target="_blank" class="js-product-main-image-wrap" href="([^"]+)"', html)
    base_price = extract_first(r'data-base-price="([^"]+)"', html)
    series_url = extract_first(r'<a href="https://easysteam\.ru(/products/stoves/pechi/[^"]+|/products/category/[^"]+)" itemprop="item"><span itemprop="name">', html)
    short_description = parse_short_description(html)
    description = parse_description(html)
    specs_groups = parse_specs_groups(html)
    option_groups = parse_option_groups(html, base_url)
    documents = parse_documents(html, base_url)
    return {
        "source_url": url,
        "title": clean_html_text(title or ""),
        "article": article,
        "series_url": urljoin(base_url, series_url) if series_url else None,
        "main_image": urljoin(base_url, main_image) if main_image else None,
        "base_price_rub": int(re.sub(r"[^\d]", "", base_price or "0") or "0"),
        "short_description": short_description,
        "description": clean_html_text(description or "") if description else None,
        "specs_groups": specs_groups,
        "option_groups": option_groups,
        "documents": documents,
    }


def build_series_payload(series_manifest: dict[str, Any], max_products_per_series: int | None) -> dict[str, Any]:
    series_url = series_manifest["source"]["seriesPageUrl"]
    html = fetch_text(series_url)
    intro = parse_series_intro(html)
    cards = parse_series_cards(html, "https://easysteam.ru")
    detailed_products = []
    seen = set()
    seed_urls = list(series_manifest["source"].get("seedSkuUrls", []))
    for card in cards:
        seed_urls.append(card["source_url"])
    if max_products_per_series is not None:
        seed_urls = seed_urls[:max_products_per_series]
    for url in seed_urls:
        if url in seen:
            continue
        seen.add(url)
        try:
            product = parse_product_page(url)
        except Exception as exc:
            product = {"source_url": url, "error": str(exc)}
        detailed_products.append(product)
    hydrate_short_descriptions(detailed_products)
    return {
        "seriesName": series_manifest["seriesName"],
        "seriesSlug": series_manifest["seriesSlug"],
        "seriesPageUrl": series_url,
        "seriesIntro": clean_html_text(intro or "") if intro else None,
        "target": series_manifest["target"],
        "defaultAttributes": series_manifest["defaultAttributes"],
        "docCandidates": series_manifest["source"].get("docCandidates", []),
        "cards": cards,
        "products": detailed_products,
    }


def main() -> None:
    parser = argparse.ArgumentParser()
    parser.add_argument("--manifest", required=True)
    parser.add_argument("--output", required=True)
    parser.add_argument("--max-products-per-series", type=int, default=None)
    args = parser.parse_args()

    manifest_path = Path(args.manifest)
    output_path = Path(args.output)
    manifest = json.loads(manifest_path.read_text())

    payload = {
        "supplier": manifest["supplier"],
        "wave": manifest["wave"],
        "updated": manifest["updated"],
        "sourceRoot": manifest["sourceRoot"],
        "buildOptions": {
            "maxProductsPerSeries": args.max_products_per_series,
            "fetchTimeoutSeconds": FETCH_TIMEOUT,
        },
        "brand": manifest["brand"],
        "scope": manifest["scope"],
        "parserContract": manifest["parserContract"],
        "series": [],
    }
    for series_manifest in manifest["series"]:
        print(f"series: {series_manifest['seriesName']}", flush=True)
        payload["series"].append(build_series_payload(series_manifest, args.max_products_per_series))

    output_path.parent.mkdir(parents=True, exist_ok=True)
    output_path.write_text(json.dumps(payload, ensure_ascii=False, indent=2))
    print(output_path)


if __name__ == "__main__":
    main()
