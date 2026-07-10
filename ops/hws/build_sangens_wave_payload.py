#!/usr/bin/env python3
"""
Build Sangens wave payload from product pages.

Reads a manifest JSON (data/import/hws-sangens-wave-1.json), fetches each
product URL, extracts specs/images/docs/description, and writes a generated
payload JSON ready for import_easysteam_wave.php.

Usage:
    python3 build_sangens_wave_payload.py <manifest.json> <output.json>

Dependencies: requests, beautifulsoup4
    pip install requests beautifulsoup4
"""

import datetime
import json
import re
import sys
import time
import urllib.parse
from typing import Optional

try:
    import requests
    from bs4 import BeautifulSoup
except ImportError:
    print("ERROR: pip install requests beautifulsoup4", file=sys.stderr)
    sys.exit(1)

HEADERS = {
    "User-Agent": "Mozilla/5.0 (compatible; HWS-Scraper/1.0; +https://creamui.ru)",
    "Accept-Language": "ru-RU,ru;q=0.9,en;q=0.5",
}
CRAWL_DELAY = 1.5  # seconds between requests

session = requests.Session()
session.headers.update(HEADERS)


def log(msg: str) -> None:
    print(msg, file=sys.stderr, flush=True)


def fetch_soup(url: str, retries: int = 3) -> Optional[BeautifulSoup]:
    for attempt in range(retries):
        try:
            r = session.get(url, timeout=30)
            r.raise_for_status()
            r.encoding = r.apparent_encoding or "utf-8"
            return BeautifulSoup(r.text, "html.parser")
        except Exception as exc:
            log(f"  WARN fetch attempt {attempt + 1}/{retries}: {exc}")
            if attempt < retries - 1:
                time.sleep(2**attempt)
    return None


# ---------------------------------------------------------------------------
# Extraction helpers
# ---------------------------------------------------------------------------

def extract_title(soup: BeautifulSoup) -> str:
    # h1 is cleanest on Sangens (og:title has " — Купить c доставкой по России" suffix)
    h1 = soup.find("h1")
    if h1:
        return h1.get_text(strip=True)
    og = soup.find("meta", property="og:title")
    if og and og.get("content"):
        title = og["content"].strip()
        # Strip common Russian e-commerce suffixes
        for suffix in [" — Купить", " - Купить", " | Купить", " – Купить"]:
            if suffix in title:
                title = title[:title.index(suffix)].strip()
        return title
    title_tag = soup.find("title")
    return title_tag.get_text(strip=True) if title_tag else ""


def extract_article(soup: BeautifulSoup) -> str:
    text = soup.get_text(" ", strip=True)
    # Sangens articles: S followed by 6 digits or G followed by 6 digits
    m = re.search(r'(?:Артикул|Article|SKU)[:\s]+([A-Z]\d{5,7})', text)
    if m:
        return m.group(1)
    # Fallback: find any standalone article-like code
    m = re.search(r'\b([SGC]\d{6})\b', text)
    return m.group(1) if m else ""


def extract_price_rub(soup: BeautifulSoup) -> int:
    # Look for Russian price patterns: "305 990 ₽" / "305,990 руб" / "305990"
    text = soup.get_text(" ", strip=True)
    # Pattern: digits (with spaces/commas as thousands sep) followed by ₽ or руб
    m = re.search(r'([\d][\d\s ,]{3,})\s*(?:₽|руб)', text)
    if m:
        raw = re.sub(r'[\s ,]', '', m.group(1))
        val = int(raw)
        if val > 1000:
            return val
    # Alternative: find meta product:price:amount
    price_meta = soup.find("meta", property="product:price:amount")
    if price_meta and price_meta.get("content"):
        try:
            return int(float(price_meta["content"]))
        except ValueError:
            pass
    return 0


def extract_specs(soup: BeautifulSoup) -> list[dict]:
    rows = []

    # Sangens uses .product-specs-modal__info-attrs-item divs:
    #   <div class="product-specs-modal__info-attrs-item">
    #     Мощность -<span>6</span>
    #   </div>
    attr_items = soup.select(".product-specs-modal__info-attrs-item")
    for item in attr_items:
        span = item.find("span")
        if not span:
            continue
        value = span.get_text(strip=True)
        # Label = full text minus the span text, strip trailing " -"
        full_text = item.get_text(separator=" ", strip=True)
        label = full_text.replace(value, "").strip().rstrip("-").strip()
        if label and value:
            rows.append({"label": label, "value": value})

    if rows:
        return rows

    # Fallback: standard table th/td rows
    for table in soup.find_all("table"):
        for tr in table.find_all("tr"):
            cells = tr.find_all(["th", "td"])
            if len(cells) >= 2:
                label = cells[0].get_text(separator=" ", strip=True)
                value = cells[1].get_text(separator=" ", strip=True)
                if label and value and label != value:
                    rows.append({"label": label, "value": value})

    # Fallback: dl/dt/dd
    if not rows:
        for dl in soup.find_all("dl"):
            for dt, dd in zip(dl.find_all("dt"), dl.find_all("dd")):
                label = dt.get_text(strip=True)
                value = dd.get_text(strip=True)
                if label and value:
                    rows.append({"label": label, "value": value})

    return rows


def extract_images(soup: BeautifulSoup, base_url: str) -> tuple[str, list[str]]:
    seen: set[str] = set()
    images: list[str] = []

    def add(src: str) -> None:
        if not src or src.startswith("data:"):
            return
        full = urllib.parse.urljoin(base_url, src)
        # Skip thumbnails (WordPress generates -WxH suffixed copies)
        if re.search(r'-\d+x\d+\.\w+$', full):
            return
        if "wp-content/uploads" not in full:
            return
        if full not in seen:
            seen.add(full)
            images.append(full)

    # Sangens uses Splide slider for the product gallery
    splide = soup.select_one(".splide__list")
    if splide:
        for img in splide.find_all("img"):
            add(img.get("src") or img.get("data-splide-lazy") or "")
        # data-splide-lazy on <img> inside splide
        for img in splide.find_all("img", attrs={"data-splide-lazy": True}):
            add(img["data-splide-lazy"])

    # Fallback: common gallery containers
    if not images:
        for sel in [
            ".product-gallery img",
            ".woocommerce-product-gallery img",
            "[class*='gallery'] img",
            "[class*='slider'] img",
        ]:
            for img in soup.select(sel):
                add(img.get("src") or img.get("data-src") or img.get("data-lazy-src") or "")

    # Last resort: any wp-content/uploads image without thumbnail suffix
    if not images:
        for img in soup.find_all("img"):
            src = img.get("src") or img.get("data-src") or ""
            add(src)

    main = images[0] if images else ""
    gallery = images[1:] if len(images) > 1 else []
    return main, gallery


def extract_documents(soup: BeautifulSoup, base_url: str) -> list[dict]:
    docs: list[dict] = []
    seen: set[str] = set()
    for a in soup.find_all("a", href=True):
        href = a["href"]
        if ".pdf" not in href.lower():
            continue
        full = urllib.parse.urljoin(base_url, href)
        if full in seen:
            continue
        seen.add(full)
        name = a.get_text(strip=True)
        # Use filename from URL when link text is trivial ("pdf", "PDF", empty, 1-3 chars)
        if not name or len(name) <= 3 or name.lower() == "pdf":
            filename = urllib.parse.urlparse(full).path.split("/")[-1]
            filename = re.sub(r'\.pdf+$', '', filename, flags=re.IGNORECASE)
            filename = re.sub(r'[_-]+', ' ', filename).strip()
            name = filename or "Инструкция"
        docs.append({"name": name, "url": full})
    return docs


def extract_descriptions(soup: BeautifulSoup) -> tuple[str, str]:
    """Returns (full_description, short_description)."""
    # Short: og:description
    short = ""
    og = soup.find("meta", property="og:description")
    if og and og.get("content"):
        short = og["content"].strip()[:500]

    # Full: largest text block
    full = ""
    candidates = []
    for sel in [
        ".entry-content",
        ".product-description",
        "[class*='description']",
        ".content",
        "article",
        "main",
    ]:
        elem = soup.select_one(sel)
        if elem:
            text = elem.get_text(separator=" ", strip=True)
            candidates.append(text)

    if candidates:
        full = max(candidates, key=len)[:4000]

    if not short and full:
        short = full[:300]

    return full, short


# ---------------------------------------------------------------------------
# Product scraping
# ---------------------------------------------------------------------------

def scrape_product(url: str) -> dict:
    log(f"    GET {url}")
    soup = fetch_soup(url)
    if not soup:
        return {"source_url": url, "error": "fetch failed"}

    title = extract_title(soup)
    article = extract_article(soup)
    price_rub = extract_price_rub(soup)
    spec_rows = extract_specs(soup)
    main_image, gallery = extract_images(soup, url)
    documents = extract_documents(soup, url)
    full_desc, short_desc = extract_descriptions(soup)

    specs_groups = []
    if spec_rows:
        specs_groups = [{"title": "Характеристики", "rows": spec_rows}]

    result: dict = {
        "title": title,
        "article": article,
        "base_price_rub": price_rub,
        "source_url": url,
        "main_image": main_image,
        "images": gallery,
        "documents": documents,
        "description": full_desc,
        "short_description": short_desc,
        "specs_groups": specs_groups,
        "option_groups": [],
    }

    status = "OK" if article else "WARN:no-article"
    if not price_rub:
        status = "WARN:no-price" if article else "WARN:no-article,no-price"
    log(f"      → {status} | article={article} | price={price_rub} | specs={len(spec_rows)}")
    return result


# ---------------------------------------------------------------------------
# Main
# ---------------------------------------------------------------------------

def main() -> None:
    if len(sys.argv) < 3:
        print(f"Usage: {sys.argv[0]} <manifest.json> <output.json>", file=sys.stderr)
        sys.exit(1)

    manifest_path = sys.argv[1]
    output_path = sys.argv[2]

    with open(manifest_path, "r", encoding="utf-8") as f:
        manifest = json.load(f)

    output: dict = {
        "supplier": manifest.get("supplier"),
        "wave": manifest.get("wave"),
        "brand": manifest.get("brand"),
        "generated_at": datetime.datetime.utcnow().strftime("%Y-%m-%dT%H:%M:%SZ"),
        "series": [],
    }

    total_products = 0
    total_errors = 0

    for series in manifest.get("series", []):
        series_name = series["seriesName"]
        log(f"\nSeries: {series_name}")

        series_out: dict = {
            "seriesName": series_name,
            "seriesSlug": series.get("seriesSlug", ""),
            "seriesIntro": series.get("seriesIntro", ""),
            "target": series["target"],
            "defaultAttributes": series.get("defaultAttributes", {}),
            "products": [],
        }

        for stub in series.get("products", []):
            url = stub.get("source_url", "")
            if not url:
                log("  SKIP: no source_url")
                continue

            product = scrape_product(url)

            # Merge any manifest-level overrides (e.g. article, title)
            for k, v in stub.items():
                if k != "source_url" and k not in product:
                    product[k] = v

            series_out["products"].append(product)
            total_products += 1
            if product.get("error"):
                total_errors += 1

            time.sleep(CRAWL_DELAY)

        output["series"].append(series_out)

    with open(output_path, "w", encoding="utf-8") as f:
        json.dump(output, f, ensure_ascii=False, indent=2)

    log(f"\nDone. products={total_products} errors={total_errors} → {output_path}")


if __name__ == "__main__":
    main()
