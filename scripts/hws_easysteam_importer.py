#!/usr/bin/env python3
"""
EasySteam -> HWS import helper.

Default mode is safe: parse manufacturer pages and write a preview JSON.
It does not write to WooCommerce unless an explicit import mode is added later.
"""

from __future__ import annotations

import argparse
import itertools
import json
import re
import sys
import time
from dataclasses import dataclass, asdict
from pathlib import Path
from typing import Any
from urllib.parse import urljoin

import requests
from bs4 import BeautifulSoup, Tag


BASE_URL = "https://easysteam.ru"
GELENDZHIK_URL = f"{BASE_URL}/products/stoves/pechi/gelendzhik"


FILTER_RULES = {
    "Вид топлива": ("Тип топлива", {
        "Дрова Стандартная комплектация": "дрова",
        "Дрова": "дрова",
        "Подготовка под ГГУ": "подготовка под газ",
    }),
    "Марка стали": ("Марка стали", {}),
    "Вид кожуха": ("Материал кожуха", {
        "Жадеит (Цена по запросу)": "жадеит",
        "Жадеит (цена по запросу)": "жадеит",
    }),
    "Варианты кожуха": ("Материал кожуха", {
        "Жадеит (Цена по запросу)": "жадеит",
        "Жадеит (цена по запросу)": "жадеит",
    }),
    "Исполнение дверки": ("Сторона дверки", {}),
    "Варианты дверки": ("Сторона дверки", {}),
    "Боковой вход в каменку": ("Сторона входа в каменку", {}),
    "Боковое подключение дымохода": ("Сторона подключения дымохода", {}),
    "Защита топки": ("Защита топки", {
        "Защ. экраны": "защитные экраны",
        "Защитные экраны": "защитные экраны",
    }),
}


def clean_text(value: str) -> str:
    return re.sub(r"\s+", " ", value or "").strip()


def parse_int(value: str | None) -> int:
    if not value:
        return 0
    digits = re.sub(r"[^\d-]", "", value)
    return int(digits or 0)


def abs_url(value: str | None) -> str:
    if not value:
        return ""
    return urljoin(BASE_URL, value)


def normalize_option(group: str, value: str) -> tuple[str, str]:
    target, mapping = FILTER_RULES.get(group, (group, {}))
    normalized = mapping.get(value, value)

    if group == "Вид топлива":
        low = value.lower()
        if "газ" in low and "дров" in low:
            normalized = "газ + дрова"
        elif "газ" in low and "подготов" not in low:
            normalized = "газ"
        elif "подготов" in low:
            normalized = "подготовка под газ"
        elif "дров" in low:
            normalized = "дрова"

    if target.startswith("Сторона"):
        normalized = normalized.lower()

    if target == "Материал кожуха":
        normalized = re.sub(r"\s*\(.*?\)\s*", "", normalized).strip().lower()

    return target, normalized


def fallback_short_description(*sources: str, max_sentences: int = 2, max_chars: int = 420) -> str:
    for source in sources:
        text = clean_text(source)
        if not text:
            continue
        parts = [part.strip() for part in re.split(r"(?<=[.!?])\s+", text) if part.strip()]
        short = " ".join(parts[:max_sentences]).strip()
        if not short:
            short = text[:max_chars].rstrip(" ,;:")
        if len(short) > max_chars:
            short = short[:max_chars].rstrip(" ,;:") + "…"
        if short:
            return short
    return ""


@dataclass
class OptionValue:
    label: str
    normalized_attribute: str
    normalized_value: str
    api_id: str
    price_delta_rub: int
    image: str
    swatch_image: str
    additional_image: str
    checked: bool


@dataclass
class OptionGroup:
    name: str
    values: list[OptionValue]


@dataclass
class ProductPreview:
    source_url: str
    source_product_id: str
    title: str
    base_article: str
    base_price_rub: int
    base_image: str
    short_description: str
    long_description: str
    target_category: str
    product_type: str
    option_groups: list[OptionGroup]
    variants: list[dict[str, Any]]
    characteristics: dict[str, str]
    purpose_text: str
    advantage_text: str
    skipped_tabs: list[str]
    post_import_content_sources: list[str]


def slugify(value: str) -> str:
    mapping = {
        "а": "a", "б": "b", "в": "v", "г": "g", "д": "d", "е": "e", "ё": "e",
        "ж": "zh", "з": "z", "и": "i", "й": "y", "к": "k", "л": "l", "м": "m",
        "н": "n", "о": "o", "п": "p", "р": "r", "с": "s", "т": "t", "у": "u",
        "ф": "f", "х": "h", "ц": "c", "ч": "ch", "ш": "sh", "щ": "sch", "ъ": "",
        "ы": "y", "ь": "", "э": "e", "ю": "yu", "я": "ya",
    }
    value = clean_text(value).lower()
    value = "".join(mapping.get(ch, ch) for ch in value)
    value = re.sub(r"[^a-z0-9]+", "-", value)
    return value.strip("-")


def build_long_description(purpose_text: str, advantage_text: str) -> str:
    sections = []
    if clean_text(purpose_text):
        sections.append(f"Назначение\n\n{clean_text(purpose_text)}")
    if clean_text(advantage_text):
        sections.append(f"Преимущества\n\n{clean_text(advantage_text)}")
    return "\n\n".join(sections)


def is_importable_variant(variant: dict[str, Any]) -> bool:
    article = clean_text(str(variant.get("manufacturer_article", "")))
    return (
        variant.get("status") == "ok"
        and article != ""
        and article != "missing manufacturer article"
        and int(variant.get("price_rub") or 0) > 0
    )


class EasySteamParser:
    def __init__(self, delay: float = 0.15) -> None:
        self.session = requests.Session()
        self.session.headers.update({
            "User-Agent": "HWS Store importer preview (+https://hws.shopping)",
            "Accept": "text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
        })
        self.delay = delay

    def get_soup(self, url: str) -> BeautifulSoup:
        res = self.session.get(url, timeout=30)
        res.raise_for_status()
        return BeautifulSoup(res.text, "html.parser")

    def collect_product_links(self, category_url: str) -> list[str]:
        soup = self.get_soup(category_url)
        links: list[str] = []
        for a in soup.select("a[href*='/products/show/'], a[href*='/products/product/']"):
            text = clean_text(a.get_text(" ", strip=True))
            href = a.get("href")
            if href and text == "Подробнее":
                full = abs_url(href)
                if full not in links:
                    links.append(full)
        return links

    def parse_product(self, url: str) -> ProductPreview:
        soup = self.get_soup(url)
        token_el = soup.select_one('meta[name="csrf-token"]')
        csrf = token_el.get("content", "") if token_el else ""

        title = clean_text(soup.select_one("h1.product__title").get_text(" ", strip=True))
        article_el = soup.select_one(".js-article")
        base_article = clean_text(article_el.get("data-product-offer") or article_el.get_text(" ", strip=True))
        base_article = re.sub(r"\D+", "", base_article)

        button = soup.select_one(".js-btn-item-cart-add")
        source_product_id = button.get("data-product-id", "") if button else ""
        base_price_rub = parse_int(button.get("data-product-offer-price") if button else "")

        img = soup.select_one(".js-product-main-image")
        base_image = abs_url(img.get("src")) if img else f"{BASE_URL}/images/offers/{base_article}.jpg"

        purpose_text = self.extract_tab_text(soup, "#prod-purpose")
        advantage_text = self.extract_tab_text(soup, "#prod-advantage")
        short_description = self.extract_description_tab(soup)
        short_description = short_description or fallback_short_description(purpose_text, advantage_text)
        long_description = build_long_description(purpose_text, advantage_text)
        characteristics = self.extract_characteristics(soup)
        option_groups = self.extract_option_groups(soup)
        variants = self.resolve_variants(url, csrf, source_product_id, base_image, option_groups)

        return ProductPreview(
            source_url=url,
            source_product_id=source_product_id,
            title=title,
            base_article=base_article,
            base_price_rub=base_price_rub,
            base_image=base_image,
            short_description=short_description,
            long_description=long_description,
            target_category="Печи для русской бани",
            product_type="variable" if option_groups else "simple",
            option_groups=option_groups,
            variants=variants,
            characteristics=characteristics,
            purpose_text=purpose_text,
            advantage_text=advantage_text,
            skipped_tabs=[
                "Печь в разрезе",
                "Схема работы печи",
                "Документация",
                "Видео",
                "Для проектов",
            ],
            post_import_content_sources=[
                "Назначение",
                "Преимущества",
            ],
        )

    def extract_description_tab(self, soup: BeautifulSoup) -> str:
        pane = soup.select_one("#prod-technical-data")
        if not pane:
            return ""

        for title in pane.select(".product__description-title"):
            if clean_text(title.get_text(" ", strip=True)) != "Описание":
                continue
            for sibling in title.next_siblings:
                if not isinstance(sibling, Tag):
                    continue
                text = clean_text(sibling.get_text(" ", strip=True))
                if text:
                    return text

        candidates: list[str] = []
        for p in pane.select("p"):
            text = clean_text(p.get_text(" ", strip=True))
            if not text:
                continue
            if text in {"Информация о товаре", "Основная информация", "Характеристики", "Общие характеристики"}:
                continue
            if len(text) >= 80:
                candidates.append(text)
        return candidates[0] if candidates else ""

    def extract_tab_text(self, soup: BeautifulSoup, selector: str) -> str:
        pane = soup.select_one(selector)
        if not pane:
            return ""
        text = clean_text(pane.get_text(" ", strip=True))
        return text

    def extract_characteristics(self, soup: BeautifulSoup) -> dict[str, str]:
        result: dict[str, str] = {}
        for table in soup.select("table"):
            for row in table.select("tr"):
                cells = [clean_text(c.get_text(" ", strip=True)) for c in row.select("th,td")]
                if len(cells) >= 2 and cells[0] and cells[1]:
                    result[cells[0]] = cells[1]

        if result:
            return result

        labels = [
            "Тип продукта", "Модель", "Версия", "Назначение",
            "Максимальный объем парной", "Минимальный объем парной",
            "Диаметр дымохода", "Защита топки", "Тип каменки", "Парогенератор",
            "Время вывода на режим русской бани (зима / лето)",
            "Материал (печь)", "Толщина материала (корпус печи)",
            "Толщина материала (корпус закрытой каменки)",
            "Толщина материала (элементы жесткости)",
            "Материал (дверка)", "Материал (колосниковая решетка)",
            "Стандартная комплектация", "Расход дров", "Максимальная длина поленьев",
            "Комплектация с САБК-50", "Расход газа (САБК)",
            "Комплектация с ГГУ-60", "Расход газа (ГГУ)",
            "Размеры ( Ш x Г x В )", "Масса печи без учёта закладки камней и шамота",
            "Масса закладываемых камней в закрытую каменку",
            "Размер стекла на топочной дверце (Ш х В)",
        ]
        text = "\n".join(soup.get_text("\n", strip=True).splitlines())
        lines = [clean_text(x) for x in text.splitlines() if clean_text(x)]
        for i, line in enumerate(lines[:-1]):
            if line in labels:
                result[line] = lines[i + 1]
        return result

    def extract_option_groups(self, soup: BeautifulSoup) -> list[OptionGroup]:
        groups: list[OptionGroup] = []
        for item in soup.select(".product__params-item"):
            title_el = item.select_one(".radio-group__title")
            if not title_el:
                continue
            name = clean_text(title_el.get_text(" ", strip=True))
            values: list[OptionValue] = []
            for option in item.select(".radio-group__item"):
                input_el = option.select_one("input.js-product-param")
                label_el = option.select_one(".js-radio-group__label")
                text_el = option.select_one(".radio-group__item-text")
                if not input_el or not label_el or not text_el:
                    continue
                label = clean_text(text_el.get_text(" ", strip=True))
                normalized_attribute, normalized_value = normalize_option(name, label)
                values.append(OptionValue(
                    label=label,
                    normalized_attribute=normalized_attribute,
                    normalized_value=normalized_value,
                    api_id=label_el.get("data-id", ""),
                    price_delta_rub=parse_int(input_el.get("data-price")),
                    image=abs_url(input_el.get("data-image")),
                    swatch_image=abs_url((option.select_one("img") or {}).get("src")),
                    additional_image=abs_url(input_el.get("data-add-image")),
                    checked=input_el.has_attr("checked"),
                ))
            if values:
                groups.append(OptionGroup(name=name, values=values))
        return groups

    def resolve_variants(
        self,
        product_url: str,
        csrf: str,
        source_product_id: str,
        base_image: str,
        option_groups: list[OptionGroup],
    ) -> list[dict[str, Any]]:
        if not option_groups:
            return []

        headers = {
            "X-CSRF-TOKEN": csrf,
            "X-Requested-With": "XMLHttpRequest",
            "Accept": "application/json, text/plain, */*",
            "Referer": product_url,
        }
        variants: list[dict[str, Any]] = []
        for combo in itertools.product(*[g.values for g in option_groups]):
            param_list = ",".join(v.api_id for v in combo)
            article = ""
            price = 0
            error = ""
            try:
                res = self.session.post(
                    f"{BASE_URL}/product/article",
                    data={"product": source_product_id, "param-list": param_list},
                    headers=headers,
                    timeout=30,
                )
                if res.status_code == 200:
                    payload = json.loads(res.json() if isinstance(res.json(), str) else res.text)
                    article = str(payload.get("article", ""))
                    price = parse_int(str(payload.get("price", "")))
                else:
                    error = f"article api status {res.status_code}"
            except Exception as exc:  # noqa: BLE001
                error = str(exc)

            image = ""
            for option in combo:
                if option.image:
                    image = option.image
            if article and not image:
                image = f"{BASE_URL}/images/offers/{article}.jpg"
            if not image:
                image = base_image

            attrs = {
                option.normalized_attribute: option.normalized_value
                for option in combo
            }
            source_options = {
                group.name: option.label
                for group, option in zip(option_groups, combo, strict=True)
            }
            variants.append({
                "manufacturer_article": article or "missing manufacturer article",
                "price_rub": price,
                "image": image,
                "source_options": source_options,
                "normalized_attributes": attrs,
                "api_param_list": param_list,
                "status": "ok" if article and price else "needs_review",
                "error": error,
            })
            time.sleep(self.delay)
        return variants


def build_preview_data(products: list[ProductPreview]) -> dict[str, Any]:
    return {
        "source": "easysteam.ru",
        "category": "Геленджик",
        "generated_at": time.strftime("%Y-%m-%dT%H:%M:%S%z"),
        "mode": "preview_only",
        "products_count": len(products),
        "variants_count": sum(len(p.variants) for p in products),
        "products": [asdict(p) for p in products],
    }


def build_import_payload(products: list[ProductPreview]) -> dict[str, Any]:
    filter_index: dict[str, list[str]] = {}
    payload_products: list[dict[str, Any]] = []

    for product in products:
        importable_variants = [variant for variant in product.variants if is_importable_variant(variant)]
        skipped_variants = [variant for variant in product.variants if not is_importable_variant(variant)]
        default_variant = next(iter(importable_variants), None)
        parent_attributes: dict[str, list[str]] = {}

        for variant in importable_variants:
            for attr_name, attr_value in variant["normalized_attributes"].items():
                parent_attributes.setdefault(attr_name, [])
                if attr_value not in parent_attributes[attr_name]:
                    parent_attributes[attr_name].append(attr_value)
                filter_index.setdefault(attr_name, [])
                if attr_value not in filter_index[attr_name]:
                    filter_index[attr_name].append(attr_value)

        payload_products.append({
            "brand": "EasySteam",
            "series": "Геленджик",
            "source_url": product.source_url,
            "source_product_id": product.source_product_id,
            "slug": slugify(f"easysteam-gelendzhik-{product.title}"),
            "title": product.title,
            "target_category": product.target_category,
            "product_type": product.product_type,
            "base_article": product.base_article,
            "base_price_rub": product.base_price_rub,
            "base_image": product.base_image,
            "short_description": product.short_description,
            "long_description": product.long_description,
            "raw_tabs": {
                "purpose": product.purpose_text,
                "advantage": product.advantage_text,
            },
            "characteristics": product.characteristics,
            "option_groups": [asdict(group) for group in product.option_groups],
            "parent_attributes": parent_attributes,
            "default_variant_article": default_variant["manufacturer_article"] if default_variant else "",
            "default_variant_attributes": default_variant["normalized_attributes"] if default_variant else {},
            "variants": importable_variants,
            "skipped_variants": skipped_variants,
            "skipped_tabs": product.skipped_tabs,
            "post_import_content_sources": product.post_import_content_sources,
        })

    return {
        "supplier": "easysteam",
        "brand": {
            "name": "EasySteam",
            "slug": "easysteam",
        },
        "scope": {
            "category": "Печи для русской бани",
            "category_slug": "russian-bath-stoves",
            "series": "Геленджик",
            "manufacturer_category_url": GELENDZHIK_URL,
        },
        "generated_at": time.strftime("%Y-%m-%dT%H:%M:%S%z"),
        "mode": "prepared_for_import",
        "currency": "RUB",
        "parser_contract": {
            "product_write_mode": "upsert_only",
            "variation_write_mode": "upsert_only",
            "delete_missing_variations": False,
            "media_import_mode": "separate_phase",
            "description_policy": {
                "short_description": "import from manufacturer tab 'Описание' without rewrite",
                "long_description": "compose from manufacturer tabs 'Назначение' and 'Преимущества' without importing skipped tabs",
            },
        },
        "filter_index": filter_index,
        "products_count": len(payload_products),
        "variants_count": sum(len(product["variants"]) for product in payload_products),
        "skipped_variants_count": sum(len(product["skipped_variants"]) for product in payload_products),
        "products": payload_products,
    }


def write_outputs(products: list[ProductPreview], output: Path, payload_output: Path | None = None) -> None:
    output.parent.mkdir(parents=True, exist_ok=True)
    data = build_preview_data(products)
    output.write_text(json.dumps(data, ensure_ascii=False, indent=2), encoding="utf-8")

    csv_path = output.with_suffix(".variants.csv")
    rows = ["parent_title,article,price_rub,image,source_options,normalized_attributes,status"]
    for product in products:
        for variant in product.variants:
            rows.append(",".join([
                json.dumps(product.title, ensure_ascii=False),
                json.dumps(variant["manufacturer_article"], ensure_ascii=False),
                str(variant["price_rub"]),
                json.dumps(variant["image"], ensure_ascii=False),
                json.dumps(variant["source_options"], ensure_ascii=False),
                json.dumps(variant["normalized_attributes"], ensure_ascii=False),
                json.dumps(variant["status"], ensure_ascii=False),
            ]))
    csv_path.write_text("\n".join(rows) + "\n", encoding="utf-8")

    if payload_output:
        payload_output.parent.mkdir(parents=True, exist_ok=True)
        payload_output.write_text(
            json.dumps(build_import_payload(products), ensure_ascii=False, indent=2),
            encoding="utf-8",
        )


def main(argv: list[str]) -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--category-url", default=GELENDZHIK_URL)
    parser.add_argument("--product-url", action="append", default=[])
    parser.add_argument("--limit", type=int, default=0)
    parser.add_argument("--delay", type=float, default=0.15)
    parser.add_argument("--output", default="data/import/easysteam/gelendzhik-preview.json")
    parser.add_argument("--payload-output", default="data/import/easysteam/gelendzhik-import-payload.json")
    args = parser.parse_args(argv)

    client = EasySteamParser(delay=args.delay)
    links = args.product_url or client.collect_product_links(args.category_url)
    if args.limit:
        links = links[:args.limit]
    if not links:
        print("No product links found", file=sys.stderr)
        return 2

    products: list[ProductPreview] = []
    for index, link in enumerate(links, start=1):
        print(f"[{index}/{len(links)}] {link}", file=sys.stderr)
        products.append(client.parse_product(link))

    output = Path(args.output)
    payload_output = Path(args.payload_output) if args.payload_output else None
    write_outputs(products, output, payload_output)
    print(output)
    print(output.with_suffix(".variants.csv"))
    if payload_output:
        print(payload_output)
    return 0


if __name__ == "__main__":
    raise SystemExit(main(sys.argv[1:]))
