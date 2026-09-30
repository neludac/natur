#!/usr/bin/env python3
"""Extrage conținut din vechiul site natur.md (PrestaShop) pentru importul în WooCommerce.

Pentru fiecare categorie alege aleatoriu până la PER_CAT produse și salvează
datele în tools/data/natur.json, iar imaginile în tools/data/img/.
"""
import html
import json
import random
import re
import sys
import time
import urllib.request
from pathlib import Path

BASE = "http://natur.md"
PER_CAT = int(sys.argv[1]) if len(sys.argv) > 1 else 6
OUT = Path(__file__).parent / "data"
IMG = OUT / "img"
UA = {"User-Agent": "Mozilla/5.0 (Macintosh) natur-migration"}
random.seed(2026)


def get(url, binary=False):
    for attempt in range(3):
        try:
            req = urllib.request.Request(url, headers=UA)
            with urllib.request.urlopen(req, timeout=30) as r:
                data = r.read()
            return data if binary else data.decode("utf-8", "ignore")
        except Exception as e:  # noqa: BLE001
            print(f"  ! {url}: {e}", file=sys.stderr)
            time.sleep(1 + attempt)
    return None


def clean_text(s):
    s = re.sub(r"<[^>]+>", " ", s or "")
    return re.sub(r"\s+", " ", html.unescape(s)).strip()


def clean_html(s):
    """Păstrează doar marcaj simplu (p, br, strong, em, ul, ol, li, h3, h4)."""
    s = s or ""
    s = re.sub(r"<(script|style)[^>]*>.*?</\1>", "", s, flags=re.S | re.I)
    s = re.sub(r"<(/?)(p|br|strong|b|em|i|ul|ol|li|h3|h4)\b[^>]*>", r"<\1\2>", s, flags=re.I)
    s = re.sub(r"<(?!/?(p|br|strong|b|em|i|ul|ol|li|h3|h4)\b)[^>]*>", "", s, flags=re.I)
    s = s.replace("&nbsp;", " ")
    s = re.sub(r"<p>\s*</p>", "", s)
    s = re.sub(r"[ \t]+", " ", s)
    return s.strip()


def parse_menu(home):
    """Reconstruiește arborele de categorii din meniul principal."""
    start = home.find('title="Categorii">Categorii</a>')
    seg = home[start:]
    tokens = re.finditer(r'<ul>|</ul>|<a href="http://natur\.md/(\d+)-([^"]+)"[^>]*>([^<]+)</a>', seg)
    cats, stack, last, depth = [], [], 0, 0
    for t in tokens:
        tok = t.group(0)
        if tok == "<ul>":
            depth += 1
            stack.append(last if depth > 1 else 0)
        elif tok == "</ul>":
            depth -= 1
            stack.pop()
            if depth == 0:
                break
        else:
            cid = int(t.group(1))
            cats.append({
                "id": cid,
                "slug": t.group(2).strip("-"),
                "name": html.unescape(t.group(3)).strip(),
                "parent": stack[-1] if stack else 0,
                "url": f"{BASE}/{cid}-{t.group(2)}",
            })
            last = cid
    return cats


def category_products(cat):
    links, page, h = set(), 1, None
    while page < 50:
        ph = get(f"{cat['url']}?p={page}")
        if not ph:
            break
        h = h or ph
        i = ph.find('class="product_list')
        j = ph.find("pagination-content", i)
        found = set(re.findall(r'href="(http://natur\.md/[^"]+/\d+-[^"]+\.html)"', ph[i:j] if i > 0 else ""))
        if not found - links:
            break
        links |= found
        m = re.search(r"Afişate \d+ - (\d+) din (\d+)", ph)
        if not m or m.group(1) == m.group(2):
            break
        page += 1
    links = sorted(links)
    if not h:
        return [], ""
    desc = ""
    m = re.search(r'<div class="cat_desc[^"]*">(.*?)</div>', h, re.S)
    if m:
        desc = clean_html(m.group(1))
    return links, desc


def parse_product(url):
    h = get(url)
    if not h:
        return None
    pid = int(re.search(r"/(\d+)-[^/]+\.html$", url).group(1))
    name = clean_text((re.search(r'<h1 itemprop="name">(.*?)</h1>', h, re.S) or [None, ""])[1])
    price = re.search(r"var productPrice = ([\d.]+);", h)
    old = re.search(r"var productPriceWithoutReduction = ([\d.]+);", h)
    unit = re.search(r'<p class="unit-price">.*?</span>\s*(.*?)</p>', h, re.S)
    short = re.search(r'id="short_description_content"[^>]*>(.*?)</div>', h, re.S)
    long_html = ""
    for sec in re.finditer(r'<section class="page-product-box">(.*?)</section>', h, re.S):
        body = sec.group(1)
        if "product_comments_block_tab" in body or "Accesorii" in body:
            continue
        m = re.search(r'<div class="rte">(.*?)</div>\s*$', body, re.S) or re.search(r'<div class="rte">(.*)', body, re.S)
        if m:
            long_html += clean_html(m.group(1))
    # imagini: thickbox cu slug-ul imaginii principale
    big = re.search(r'id="bigpic"[^>]*src="http://natur\.md/(\d+)-large_default/([^"]+)"', h)
    images = []
    if big:
        main_id, slug = big.group(1), big.group(2)
        ids = [main_id] + re.findall(r"http://natur\.md/(\d+)-thickbox_default/" + re.escape(slug), h)
        seen = []
        for i in ids:
            if i not in seen:
                seen.append(i)
        images = [f"{BASE}/{i}-thickbox_default/{slug}" for i in seen]
    reviews = []
    for r in re.finditer(r'<div class="comment row"(.*?)itemprop="reviewBody">(.*?)</p>', h, re.S):
        head = r.group(1)
        rating = re.search(r'itemprop="ratingValue" content = "(\d+)"', head)
        author = re.search(r'itemprop="author">(.*?)</strong>', head, re.S)
        date = re.search(r'itemprop="datePublished" content="([\d-]+)"', head)
        reviews.append({
            "author": clean_text(author.group(1)) if author else "Client",
            "rating": int(rating.group(1)) if rating else 5,
            "date": date.group(1) if date else "",
            "text": clean_text(r.group(2)),
        })
    in_stock = "schema.org/InStock" in h
    return {
        "id": pid,
        "url": url,
        "name": name,
        "price": float(price.group(1)) if price else 0.0,
        "regular_price": float(old.group(1)) if old else 0.0,
        "unit": clean_text(unit.group(1)) if unit else "",
        "short": clean_html(short.group(1)) if short else "",
        "description": long_html,
        "images": images,
        "reviews": reviews,
        "in_stock": in_stock,
        "categories": [],
    }


def parse_cms(url):
    h = get(url)
    if not h:
        return None
    title = clean_text((re.search(r"<h1[^>]*>(.*?)</h1>", h, re.S) or [None, ""])[1])
    m = re.search(r'<div class="rte">(.*?)</div>\s*</div>', h, re.S)
    return {"url": url, "title": title, "html": clean_html(m.group(1)) if m else ""}


def main():
    OUT.mkdir(exist_ok=True)
    IMG.mkdir(exist_ok=True)
    home = get(BASE + "/")
    cats = parse_menu(home)
    print(f"{len(cats)} categorii")

    footer = {}
    m = re.search(r'id="block_contact_infos".*?</section>', home, re.S)
    if m:
        blk = m.group(0)
        footer["lines"] = [clean_text(x) for x in re.findall(r"<li>(.*?)</li>", blk, re.S)]

    products = {}
    for c in cats:
        links, c["description"] = category_products(c)
        c["total_on_old_site"] = len(links)
        pick = random.sample(links, min(PER_CAT, len(links)))
        print(f"- {c['name']}: {len(links)} produse, aleg {len(pick)}")
        for url in pick:
            pid = int(re.search(r"/(\d+)-[^/]+\.html$", url).group(1))
            if pid not in products:
                p = parse_product(url)
                if not p or not p["name"]:
                    continue
                products[pid] = p
            if c["id"] not in products[pid]["categories"]:
                products[pid]["categories"].append(c["id"])

    for p in products.values():
        local = []
        for n, u in enumerate(p["images"]):
            fn = f"{p['id']}-{n}-{u.rsplit('/', 1)[1]}"
            dest = IMG / fn
            if not dest.exists():
                data = get(u, binary=True)
                if not data or len(data) < 500:
                    continue
                dest.write_bytes(data)
            local.append(fn)
        p["local_images"] = local

    cms = [c for c in (parse_cms(f"{BASE}/content/1-conditii"), parse_cms(f"{BASE}/content/2-povestea-noastra")) if c]
    data = {"categories": cats, "products": list(products.values()), "cms": cms, "footer": footer}
    (OUT / "natur.json").write_text(json.dumps(data, ensure_ascii=False, indent=1))
    print(f"Salvat: {len(cats)} categorii, {len(products)} produse, {len(cms)} pagini")


if __name__ == "__main__":
    main()
