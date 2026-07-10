#!/usr/bin/env python3
"""
Build EOS wave payload from series pages.

EOS product pages list multiple power variants (6 kW, 9 kW, etc.) each with
a unique item number. This scraper expands each series page into one product
per variant and stores shared specs/images/docs on each.

Reads: data/import/hws-eos-wave-1.json  (manifest)
Writes: data/import/generated/hws-eos-wave-1-detailed.json

Usage:
    python3 build_eos_wave_payload.py <manifest.json> <output.json>

Dependencies: requests beautifulsoup4
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

BASE_URL = "https://www.eos-sauna.com"
HEADERS = {
    "User-Agent": "Mozilla/5.0 (compatible; HWS-Scraper/1.0)",
    "Accept-Language": "en-US,en;q=0.9",
}
CRAWL_DELAY = 1.5

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
            log(f"  WARN fetch attempt {attempt + 1}/{retries} {url}: {exc}")
            if attempt < retries - 1:
                time.sleep(2**attempt)
    return None


# ---------------------------------------------------------------------------
# Extraction helpers
# ---------------------------------------------------------------------------

def extract_h1(soup: BeautifulSoup) -> str:
    h1 = soup.find("h1")
    return h1.get_text(strip=True) if h1 else ""


def extract_variants(soup: BeautifulSoup) -> list[dict]:
    """
    Find 'Power | Item no.' tables on EOS series pages.
    EOS uses two patterns:
      A) <thead><tr><th>Power</th><th>Item no.</th></tr></thead>
      B) First <tr> has <td>Power</td><td>Item no.</td> (no th)
    Multiple such tables may exist per page (sub-models). Collect all.
    Returns list of {"power_kw": "6,0 kW", "item_no": "907685"}
    """
    seen_item_nos: set[str] = set()
    variants = []

    def looks_like_variant_table(table) -> bool:
        all_text = table.get_text(" ").lower()
        return "item no" in all_text or "artikel" in all_text

    for table in soup.find_all("table", class_="contenttable"):
        if not looks_like_variant_table(table):
            continue
        for tr in table.find_all("tr"):
            cells = tr.find_all(["td", "th"])
            ncells = len(cells)
            if ncells < 1:
                continue

            cell_texts = [c.get_text(" ", strip=True).replace("\xa0", " ").strip() for c in cells]

            # Skip header row
            if "power" in cell_texts[0].lower() or "leistung" in cell_texts[0].lower():
                continue
            if "item no" in cell_texts[0].lower() or "artikel" in cell_texts[0].lower():
                continue

            if ncells == 1:
                # Single-column table: just "943077" (e.g. EOS Watermill large)
                power_raw = ""
                item_no_raw = cell_texts[0]
                variant_name = ""
            elif ncells >= 3 and re.search(r'\d{5,}', cell_texts[2]):
                # 3-col: Power | Model/Style | Item no.
                power_raw = cell_texts[0]
                item_no_raw = cell_texts[2]
                variant_name = cell_texts[1]
            else:
                # 2-col: Power | Item no. (may have trailing color text)
                power_raw = cell_texts[0]
                item_no_raw = cell_texts[1]
                variant_name = ""

            # Extract the item number token
            # Handles "945650   Stainless steel" and "942444A   Anthracite"
            m = re.search(r'\b([A-Z]?\d{5,}[A-Z]?)\b', item_no_raw, re.IGNORECASE)
            if not m:
                continue
            item_no = m.group(1)

            if item_no in seen_item_nos:
                continue
            seen_item_nos.add(item_no)

            variants.append({
                "power_kw": power_raw,
                "item_no": item_no,
                "variant_name": variant_name,
            })

    return variants


def normalize_power(power_raw: str) -> str:
    """Normalize '6,0 kW' → '6.0 kW' → '6.0'"""
    # EOS uses comma as decimal separator: "6,0 kW"
    m = re.search(r'([\d,\.]+)\s*kW', power_raw, re.IGNORECASE)
    if m:
        val = m.group(1).replace(",", ".")
        return val
    return power_raw.replace(",", ".").strip()


def extract_specs(soup: BeautifulSoup) -> list[dict]:
    """
    EOS specs are in the first .contenttable that is NOT the variants table.
    Rows: <td><b>Label</b></td><td>Value</td>
    """
    rows = []
    for table in soup.find_all("table", class_="contenttable"):
        # Skip if it looks like the variants table (has th with "item" or "power")
        headers = [th.get_text(strip=True).lower() for th in table.find_all("th")]
        if any("item" in h or "artikel" in h for h in headers):
            continue
        # Skip tables with only 1 column
        for tr in table.find_all("tr"):
            cells = tr.find_all(["td", "th"])
            if len(cells) >= 2:
                label = cells[0].get_text(strip=True)
                value = cells[1].get_text(separator=" ", strip=True)
                # Clean up non-breaking spaces
                label = label.replace("\xa0", " ").strip()
                value = value.replace("\xa0", " ").strip()
                if label and value and label != value:
                    rows.append({"label": label, "value": value})
    return rows


def extract_images(soup: BeautifulSoup, page_url: str) -> tuple[str, list[str]]:
    """
    EOS main image: from .head picture element.
    Gallery: first few images from [class*='ce-'] that are product images.
    Filter out nav icons, PDF thumbnails, social media, accessories.
    """
    SKIP_PATTERNS = [
        r'/eos-navi-icons/',
        r'/Produktuebersicht/',
        r'eos-logo',
        r'flag',
        r'PDF_Download',
        r'facebook',
        r'instagram',
        r'linkedin',
        r'/pics/',
        # Known accessory items that appear in related-products sidebar
        r'csm_944421_',
        r'csm_Econ_',
        r'csm_Compact_',
        r'csm_EmoStyle',
        r'csm_U-Command',
        r'csm_EmoTouch',
        r'csm_LSG_',
    ]

    def is_product_image(src: str) -> bool:
        if "fileadmin" not in src:
            return False
        for pat in SKIP_PATTERNS:
            if re.search(pat, src, re.IGNORECASE):
                return False
        return True

    seen: set[str] = set()
    images: list[str] = []

    def add(src: str) -> None:
        if not src or src.startswith("data:"):
            return
        full = urllib.parse.urljoin(page_url, src)
        if not is_product_image(full):
            return
        if full not in seen:
            seen.add(full)
            images.append(full)

    # 1. Header image (main product shot)
    head = soup.select_one(".head picture")
    if head:
        for source in head.find_all("source"):
            src = source.get("srcset", "").split(" ")[0]
            add(src)
        img = head.find("img")
        if img:
            add(img.get("src", "") or img.get("data-src", ""))

    # 2. Content gallery (ce- elements, capped to avoid sidebar bleed)
    gallery_cap = 8
    for elem in soup.select('[class*="ce-"]'):
        if len(images) >= gallery_cap + 1:
            break
        for img in elem.find_all("img"):
            src = img.get("src") or img.get("data-src") or ""
            add(src)

    main = images[0] if images else ""
    gallery = images[1:] if len(images) > 1 else []
    return main, gallery


def extract_documents(soup: BeautifulSoup, page_url: str) -> list[dict]:
    docs: list[dict] = []
    seen: set[str] = set()
    for a in soup.find_all("a", href=True):
        href = a["href"]
        if ".pdf" not in href.lower():
            continue
        full = urllib.parse.urljoin(page_url, href)
        if full in seen:
            continue
        seen.add(full)
        name = a.get_text(strip=True)
        if not name or len(name) <= 3:
            # Build name from URL path
            path = urllib.parse.urlparse(full).path
            filename = path.split("/")[-1]
            filename = re.sub(r'\.pdf+$', '', filename, flags=re.IGNORECASE)
            filename = re.sub(r'[_-]+', ' ', filename).strip()
            name = filename or "Document"
        docs.append({"name": name, "url": full})
    return docs


def extract_description(soup: BeautifulSoup) -> tuple[str, str]:
    # EOS pages have text in .bodytext or general article content
    full = ""
    for sel in [".bodytext", ".news-text-wrap", "article", "[class*='content']", "main"]:
        elem = soup.select_one(sel)
        if elem:
            text = elem.get_text(separator=" ", strip=True)
            text = re.sub(r'\s+', ' ', text).strip()
            if len(text) > 100:
                full = text[:4000]
                break

    og = soup.find("meta", property="og:description")
    short = og["content"].strip()[:500] if og and og.get("content") else ""
    if not short and full:
        short = full[:300]
    return full, short


# ---------------------------------------------------------------------------
# Scrape one series page → list of products
# ---------------------------------------------------------------------------

def scrape_series_page(url: str, range_name: str) -> list[dict]:
    log(f"  GET {url}")
    soup = fetch_soup(url)
    if not soup:
        return [{"source_url": url, "error": "fetch failed", "article": "", "title": ""}]

    family_name = extract_h1(soup)
    variants = extract_variants(soup)
    spec_rows = extract_specs(soup)
    main_image, gallery = extract_images(soup, url)
    documents = extract_documents(soup, url)
    full_desc, short_desc = extract_description(soup)

    specs_groups = [{"title": "Technical specifications", "rows": spec_rows}] if spec_rows else []

    if not variants:
        # Single-variant product (no power table)
        log(f"    → single variant | family={family_name!r} | specs={len(spec_rows)}")
        return [{
            "title": family_name,
            "article": "",  # no item number extractable without variant table
            "base_price_rub": 0,
            "source_url": url,
            "main_image": main_image,
            "images": gallery,
            "documents": documents,
            "description": full_desc,
            "short_description": short_desc,
            "specs_groups": specs_groups,
            "option_groups": [],
            "eos_range": range_name,
            "eos_family": family_name,
        }]

    log(f"    → {len(variants)} variants | family={family_name!r} | specs={len(spec_rows)}")

    products = []
    for v in variants:
        power_raw = v["power_kw"]
        power_raw = v["power_kw"]
        power_norm = normalize_power(power_raw) if power_raw else ""
        item_no = v["item_no"]
        variant_name = v.get("variant_name", "").strip()
        if power_norm:
            title = f"{family_name} {power_norm} kW"
        else:
            title = family_name
        if variant_name:
            title = f"{title} {variant_name}"

        # Use Russian label so the PHP importer's hws_find_spec_value picks it up for pa_power
        power_spec = {"label": "Мощность", "value": power_raw}
        variant_specs = [power_spec] + spec_rows
        variant_specs_groups = [{"title": "Technical specifications", "rows": variant_specs}]

        products.append({
            "title": title,
            "article": item_no,
            "base_price_rub": 0,
            "source_url": url,
            "main_image": main_image,
            "images": gallery,
            "documents": documents,
            "description": full_desc,
            "short_description": short_desc,
            "specs_groups": variant_specs_groups,
            "option_groups": [],
            "eos_range": range_name,
            "eos_family": family_name,
            "eos_power_kw": power_norm,
        })

    return products


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

    # EOS manifest uses "ranges" not "series" at the top level
    output: dict = {
        "supplier": manifest.get("supplier"),
        "wave": manifest.get("wave"),
        "brand": manifest.get("brand"),
        "generated_at": datetime.datetime.utcnow().strftime("%Y-%m-%dT%H:%M:%SZ"),
        "series": [],
    }

    total_products = 0
    total_errors = 0

    for eos_range in manifest.get("ranges", []):
        range_name = eos_range["rangeName"]
        log(f"\nRange: {range_name}")

        range_out: dict = {
            "seriesName": f"EOS {range_name}",
            "seriesSlug": f"eos-{range_name.lower().replace(' ', '-')}",
            "seriesIntro": "",
            "target": eos_range["target"],
            "defaultAttributes": eos_range.get("defaultAttributes", {}),
            "products": [],
        }

        for series_stub in eos_range.get("series", []):
            url = series_stub.get("source_url", "")
            if not url:
                continue

            products = scrape_series_page(url, range_name)
            for p in products:
                range_out["products"].append(p)
                total_products += 1
                if p.get("error"):
                    total_errors += 1

            time.sleep(CRAWL_DELAY)

        output["series"].append(range_out)

    with open(output_path, "w", encoding="utf-8") as f:
        json.dump(output, f, ensure_ascii=False, indent=2)

    log(f"\nDone. products={total_products} errors={total_errors} → {output_path}")


if __name__ == "__main__":
    main()
