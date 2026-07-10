#!/usr/bin/env python3
import argparse
import json
import re
from html import unescape
from pathlib import Path
from typing import Any
from urllib.parse import urljoin
from urllib.request import Request, urlopen


USER_AGENT = "Mozilla/5.0 (compatible; HWSVVDImporter/1.0)"
FETCH_TIMEOUT = 15


def fetch_text(url: str) -> str:
    req = Request(url, headers={"User-Agent": USER_AGENT})
    with urlopen(req, timeout=FETCH_TIMEOUT) as resp:
        return resp.read().decode("utf-8", errors="replace")


def extract_first(pattern: str, text: str, flags: int = re.S | re.I) -> str | None:
    match = re.search(pattern, text, flags)
    return match.group(1).strip() if match else None


def clean_html_text(value: str) -> str:
    text = re.sub(r"<br\s*/?>", "\n", value, flags=re.I)
    text = re.sub(r"</p\s*>", "\n\n", text, flags=re.I)
    text = re.sub(r"</div\s*>", "\n", text, flags=re.I)
    text = re.sub(r"<[^>]+>", " ", text)
    text = unescape(text)
    text = text.replace("\xa0", " ")
    text = re.sub(r"[ \t\r\f\v]+", " ", text)
    text = re.sub(r"\n[ \t]+", "\n", text)
    text = re.sub(r"\n{3,}", "\n\n", text)
    return text.strip()


def strip_styles(html: str) -> str:
    return re.sub(r'\sstyle="[^"]*"', "", html, flags=re.I)


def extract_div_block(html: str, start_pattern: str) -> str | None:
    match = re.search(start_pattern, html, re.S | re.I)
    if not match:
        return None
    start = match.start()
    open_pos = html.find(">", match.start())
    if open_pos == -1:
        return None
    pos = open_pos + 1
    depth = 1
    token_pattern = re.compile(r"<div\b|</div>", re.I)
    for token in token_pattern.finditer(html, pos):
        token_text = token.group(0).lower()
        if token_text.startswith("</div"):
            depth -= 1
            if depth == 0:
                return html[start : token.end()]
        else:
            depth += 1
    return None


def parse_breadcrumbs(html: str) -> list[str]:
    return [
        clean_html_text(match.group(1))
        for match in re.finditer(r'<span itemprop="name" class="breadcrumbs__item-name[^"]*">(.*?)</span>', html, re.S | re.I)
        if clean_html_text(match.group(1))
    ]


def parse_series_intro(html: str) -> str | None:
    meta = extract_first(r'<meta name="description" content="([^"]+)"', html)
    if meta:
        return clean_html_text(meta)
    return None


def parse_series_cards(html: str, base_url: str) -> list[dict[str, Any]]:
    cards: list[dict[str, Any]] = []
    pattern = re.compile(
        r'<div class="catalog-block__wrapper.*?>(?P<body>.*?)</div>\s*</div>\s*</div>\s*(?=<div class="catalog-block__wrapper|\Z)',
        re.S | re.I,
    )
    for match in pattern.finditer(html):
        body = match.group("body")
        href = extract_first(r'<a href="([^"]+)" class="image-list__link">', body)
        title = extract_first(r'<a href="[^"]+" class="dark_link switcher-title js-popup-title"><span>(.*?)</span></a>', body)
        section = extract_first(r'<div class="catalog-block__info-section[^"]*">(.*?)</div>', body)
        price = extract_first(r'<span class="price__new-val[^"]*">(.*?)<', body)
        image = extract_first(r'<img[^>]+(?:src|data-src)="([^"]+)"', body)
        if not href or not title:
            continue
        cards.append(
            {
                "source_url": urljoin(base_url, href),
                "title": clean_html_text(title),
                "section": clean_html_text(section or ""),
                "price_text": clean_html_text(price or ""),
                "image": urljoin(base_url, image) if image else None,
            }
        )
    deduped: dict[str, dict[str, Any]] = {}
    for card in cards:
        deduped[card["source_url"]] = card
    return list(deduped.values())


def parse_gallery(html: str, base_url: str) -> list[dict[str, Any]]:
    gallery: list[dict[str, Any]] = []
    pattern = re.compile(
        r'<a href="(?P<href>/upload/[^"]+)"[^>]+class="catalog-detail__gallery__link[^"]*"[^>]+title="(?P<title>[^"]*)"',
        re.S | re.I,
    )
    for idx, match in enumerate(pattern.finditer(html), start=1):
        gallery.append(
            {
                "sort": idx,
                "url": urljoin(base_url, match.group("href")),
                "title": clean_html_text(match.group("title")),
            }
        )
    deduped: dict[str, dict[str, Any]] = {}
    for item in gallery:
        deduped[item["url"]] = item
    return list(deduped.values())


def extract_tab(html: str, tab_id: str) -> str | None:
    return extract_div_block(
        html,
        rf'<div class="tab-pane(?: active| hidden)?(?: [^"]*)?" id="{re.escape(tab_id)}">',
    )


def parse_full_description_html(html: str) -> str | None:
    desc_tab = extract_tab(html, "desc")
    if not desc_tab:
        return None
    block = extract_first(r'<div class="content catalog-detail__detailtext"[^>]*>(.*?)</div>', desc_tab)
    if not block:
        return None
    return strip_styles(block.strip())


def parse_short_description(html: str) -> str | None:
    preview = extract_first(r'<div class="catalog-detail__previewtext"[^>]*>.*?<div class="text-block[^"]*">(.*?)</div>', html)
    if preview:
        return clean_html_text(preview)
    meta = extract_first(r'<meta property="og:description" content="([^"]+)"', html)
    return clean_html_text(meta) if meta else None


def parse_specs_summary(html: str) -> list[dict[str, str]]:
    specs: list[dict[str, str]] = []
    pattern = re.compile(
        r'<div class="properties__item[^"]*">.*?<div class="properties__title[^"]*">(.*?)</div>.*?<div class="properties__value[^"]*">(.*?)</div>.*?</div>',
        re.S | re.I,
    )
    for match in pattern.finditer(html):
        name = clean_html_text(match.group(1))
        value = clean_html_text(match.group(2))
        if name and value:
            specs.append({"name": name, "value": value})
    return specs


def parse_specs_full(html: str) -> list[dict[str, str]]:
    char_tab = extract_tab(html, "char")
    if not char_tab:
        return []
    specs: list[dict[str, str]] = []
    pattern = re.compile(
        r'<div class="properties-group__item[^"]*">.*?<span itemprop="name" class="properties-group__name">(.*?)</span>.*?<div class="properties-group__value[^"]*" itemprop="value">\s*(.*?)\s*</div>.*?</div>\s*</div>',
        re.S | re.I,
    )
    for match in pattern.finditer(char_tab):
        name = clean_html_text(match.group(1))
        value = clean_html_text(match.group(2))
        if "#PROP_TITLE#" in name or "#PROP_VALUE#" in value:
            continue
        if name and value:
            specs.append({"name": name, "value": value})
    return specs


def parse_documents(html: str, base_url: str) -> list[dict[str, str]]:
    docs_tab = extract_tab(html, "docs")
    if not docs_tab:
        return []
    docs: list[dict[str, str]] = []
    pattern = re.compile(
        r'<a href="(?P<href>/upload/[^"]+)" target="_blank" class="doc-list-inner__name[^"]*" title="(?P<title>[^"]+)">\s*(?P<text>.*?)\s*</a>.*?<div class="doc-list-inner__label">(?P<size>.*?)</div>',
        re.S | re.I,
    )
    for match in pattern.finditer(docs_tab):
        title = clean_html_text(match.group("title") or match.group("text"))
        docs.append(
            {
                "url": urljoin(base_url, match.group("href")),
                "title": title,
                "size": clean_html_text(match.group("size")),
            }
        )
    deduped: dict[str, dict[str, str]] = {}
    for doc in docs:
        deduped[doc["url"]] = doc
    return list(deduped.values())


def parse_videos(html: str) -> list[dict[str, Any]]:
    video_tab = extract_tab(html, "video")
    if not video_tab:
        return []
    videos = []
    for idx, match in enumerate(re.finditer(r'<iframe[^>]+src="([^"]+)"', video_tab, re.S | re.I), start=1):
        videos.append({"sort": idx, "url": match.group(1).strip()})
    return videos


def parse_option_groups(html: str) -> list[dict[str, Any]]:
    groups: list[dict[str, Any]] = []
    for match in re.finditer(r'<div class="line-block__item sku-props__inner"[^>]*data-id="(?P<id>\d+)">', html, re.S | re.I):
        block = extract_div_block(html[match.start() :], r'<div class="line-block__item sku-props__inner"[^>]*data-id="\d+">')
        if not block:
            continue
        body = block
        title = extract_first(r'<div class="sku-props__title[^"]*">(.*?)<span class="sku-props__js-size">', body)
        selected = extract_first(r'<span class="sku-props__js-size">(.*?)</span>', body)
        values = []
        for idx, item in enumerate(
            re.finditer(
                r'<div class="(?P<class>sku-props__value[^"]*)" data-onevalue="(?P<id>\d+)" data-title="(?P<title>[^"]+)">(?P<label>.*?)</div>',
                body,
                re.S | re.I,
            ),
            start=1,
        ):
            values.append(
                {
                    "id": item.group("id"),
                    "name": clean_html_text(item.group("title") or item.group("label")),
                    "is_default": "sku-props__value--active" in item.group("class"),
                    "sort": idx,
                }
            )
        if values:
            groups.append(
                {
                    "id": match.group("id"),
                    "name": clean_html_text(title or ""),
                    "selected": clean_html_text(selected or ""),
                    "values": values,
                }
            )
    return groups


def parse_current_offer(html: str, source_url: str, gallery: list[dict[str, Any]], full_specs: list[dict[str, str]]) -> dict[str, Any]:
    current_title = extract_first(r'<h1 id="pagetitle"[^>]*>(.*?)</h1>', html) or ""
    price_text = clean_html_text(extract_first(r'<span class="price__new-val[^"]*">\s*(.*?)<meta', html) or "")
    price_number = re.sub(r"[^\d]", "", price_text)
    offer_id = extract_first(r'data-offer-id="(\d+)"', html) or ""
    primary_image = gallery[0]["url"] if gallery else None
    offer: dict[str, Any] = {
        "offer_id": offer_id,
        "name": clean_html_text(current_title),
        "price_text": price_text,
        "price_number_rub": price_number or None,
        "currency": "RUB" if price_text else None,
        "detail_page_url": source_url,
        "primary_image_url": primary_image,
    }
    for spec in full_specs:
        key = re.sub(r"[^a-z0-9]+", "_", spec["name"].lower()).strip("_")
        offer[key] = spec["value"]
    return offer


def dedupe_specs(summary_specs: list[dict[str, str]], full_specs: list[dict[str, str]]) -> list[dict[str, str]]:
    merged: list[dict[str, str]] = []
    seen: set[tuple[str, str]] = set()
    for spec in summary_specs + full_specs:
        key = (spec["name"], spec["value"])
        if key in seen:
            continue
        seen.add(key)
        merged.append(spec)
    return merged


def parse_product_page(url: str) -> dict[str, Any]:
    html = fetch_text(url)
    base_url = "https://vvd.su"
    title = clean_html_text(extract_first(r'<h1 id="pagetitle"[^>]*>(.*?)</h1>', html) or "")
    breadcrumbs = parse_breadcrumbs(html)
    short_description = parse_short_description(html)
    full_description_html = parse_full_description_html(html)
    full_description_text = clean_html_text(full_description_html or "")
    gallery = parse_gallery(html, base_url)
    summary_specs = parse_specs_summary(html)
    full_specs = parse_specs_full(html)
    specs = dedupe_specs(summary_specs, full_specs)
    documents = parse_documents(html, base_url)
    videos = parse_videos(html)
    option_groups = parse_option_groups(html)
    current_offer = parse_current_offer(html, url, gallery, full_specs)
    return {
        "source_url": url,
        "title": title,
        "breadcrumbs": breadcrumbs,
        "short_description": short_description,
        "full_description_html": full_description_html,
        "full_description_text": full_description_text,
        "current_offer": current_offer,
        "specs": specs,
        "gallery": gallery,
        "documents": documents,
        "videos": videos,
        "option_groups": option_groups,
    }


def merge_card_fallbacks(product: dict[str, Any], card_index: dict[str, dict[str, Any]]) -> dict[str, Any]:
    card = card_index.get(product.get("source_url", ""))
    if not card:
        return product
    if not product.get("title") and card.get("title"):
        product["title"] = card["title"]
    if not product.get("short_description") and card.get("section"):
        product["short_description"] = card["section"]
    if not product.get("gallery") and card.get("image"):
        product["gallery"] = [{"sort": 1, "url": card["image"], "title": card.get("title", "")}]
    return product


def build_series_payload(series_manifest: dict[str, Any], max_products_per_series: int | None) -> dict[str, Any]:
    series_url = series_manifest["source"]["seriesPageUrl"]
    html = fetch_text(series_url)
    cards = parse_series_cards(html, "https://vvd.su")
    series_intro = parse_series_intro(html)
    detailed_products = []
    card_index = {card["source_url"]: card for card in cards}
    urls = [card["source_url"] for card in cards]
    if max_products_per_series is not None:
        urls = urls[:max_products_per_series]
    for url in urls:
        try:
            product = parse_product_page(url)
            product = merge_card_fallbacks(product, card_index)
        except Exception as exc:
            product = {"source_url": url, "error": str(exc)}
        detailed_products.append(product)
    return {
        "seriesName": series_manifest["seriesName"],
        "seriesSlug": series_manifest["seriesSlug"],
        "seriesPageUrl": series_url,
        "seriesIntro": series_intro,
        "target": series_manifest["target"],
        "defaultAttributes": series_manifest["defaultAttributes"],
        "cards": cards,
        "products": detailed_products,
    }


def main() -> None:
    parser = argparse.ArgumentParser()
    parser.add_argument("--manifest", required=True)
    parser.add_argument("--output", required=True)
    parser.add_argument("--max-products-per-series", type=int, default=None)
    args = parser.parse_args()

    manifest = json.loads(Path(args.manifest).read_text())
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
    output_path = Path(args.output)
    output_path.parent.mkdir(parents=True, exist_ok=True)
    output_path.write_text(json.dumps(payload, ensure_ascii=False, indent=2))
    print(output_path)


if __name__ == "__main__":
    main()
