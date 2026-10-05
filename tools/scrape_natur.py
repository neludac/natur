#!/usr/bin/env python3
"""Extrage catalogul de pe vechiul site natur.md (PrestaShop) pentru importul în WooCommerce.

    python3 tools/scrape_natur.py        # tot catalogul
    python3 tools/scrape_natur.py 6      # eșantion: până la 6 produse aleatorii din fiecare categorie

Produsele se găsesc din listele categoriilor din meniu (toate paginile) și, pentru
catalogul complet, prin interogarea fiecărui ID de produs: așa apar și produsele
active din categorii ascunse în meniu (Miere, Apă, Condimente…), pe care vechiul
site le arăta doar în căutare. Datele ajung în tools/data/natur.json, fotografiile
(galerie + cele din descrieri) în tools/data/img/.

Funcțiile de extragere sunt folosite și de tools/verify_products.py.
"""
import hashlib
import html
import json
import random
import re
import sys
import time
import urllib.error
import urllib.request
from concurrent.futures import ThreadPoolExecutor
from pathlib import Path

BASE = "http://natur.md"
OUT = Path(__file__).parent / "data"
IMG = OUT / "img"
UA = {"User-Agent": "Mozilla/5.0 (Macintosh) natur-migration"}
ROOT_CAT = 2  # „Categorii”, rădăcina PrestaShop: nu e o categorie reală

# Categorii dezactivate în meniul vechi (numele nu apare nicăieri public, doar slug-ul).
HIDDEN_CATS = {
    "apa-voda": "Apă (Вода)",
    "cadou": "Cadou",
    "condimente-pryanosti": "Condimente (Пряности)",
    "fructe-uscate": "Fructe uscate",
    "miere-magiun-med-dzhemy": "Miere, magiun (Мед, джемы)",
    "muraturi-otet-bors-altele": "Murături, oțet, borș și altele",
}


def get(url, binary=False):
    for attempt in range(3):
        try:
            req = urllib.request.Request(url, headers=UA)
            with urllib.request.urlopen(req, timeout=30) as r:
                data = r.read()
            return data if binary else data.decode("utf-8", "ignore")
        except urllib.error.HTTPError as e:
            if e.code == 404:
                return None
            print(f"  ! {url}: {e}", file=sys.stderr)
        except Exception as e:  # noqa: BLE001
            print(f"  ! {url}: {e}", file=sys.stderr)
        time.sleep(1 + attempt)
    return None


def clean_text(s):
    s = re.sub(r"<[^>]+>", " ", s or "")
    return re.sub(r"\s+", " ", html.unescape(s).replace("\xa0", " ")).strip()


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


def raw_html(s):
    """Descrierea lungă cu tot marcajul (imagini, linkuri, culori); se curăță la import cu wp_kses_post."""
    s = re.sub(r"<(script|style)[^>]*>.*?</\1>", "", s or "", flags=re.S | re.I)
    return re.sub(r"<!--.*?-->", "", s, flags=re.S).strip()


def inner(h, start_pat, tag):
    """Conținutul primului element care se potrivește cu start_pat (echilibrat după `tag`)."""
    m = re.search(start_pat, h)
    if not m:
        return ""
    i, depth = m.end(), 1
    for t in re.finditer(rf"<(/?){tag}\b[^>]*>", h[i:]):
        depth += -1 if t.group(1) else 1
        if depth == 0:
            return h[i:i + t.start()]
    return h[i:]


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
    """Toate URL-urile de produs din categorie (toate paginile) și descrierea categoriei."""
    links, page, first = set(), 1, None
    while page < 50:
        ph = get(f"{cat['url']}?p={page}")
        if not ph:
            break
        first = first or ph
        i = ph.find('class="product_list')
        j = ph.find("pagination-content", i)
        found = set(re.findall(r'href="(http://natur\.md/[^"]+/\d+-[^"]+\.html)"', ph[i:j] if i > 0 else ""))
        if not found - links:
            break
        links |= found
        m = re.search(r"Afi\S+ \d+ - (\d+) din (\d+)", ph)  # „Afişate 16 - 30 din 39 produse”
        if not m or m.group(1) == m.group(2):
            break
        page += 1
    desc = ""
    m = re.search(r'<div class="cat_desc[^"]*">(.*?)</div>', first or "", re.S)
    if m:
        desc = clean_html(m.group(1))
    return sorted(links), desc


def find_product_url(pid):
    """URL-ul public al unui produs activ după ID (PrestaShop redirecționează spre URL-ul canonic)."""
    url = f"{BASE}/index.php?controller=product&id_product={pid}"
    for attempt in range(3):
        try:
            with urllib.request.urlopen(urllib.request.Request(url, headers=UA), timeout=30) as r:
                body = r.read().decode("utf-8", "ignore")
                final = r.geturl()
            if 'id="product_page_product_id"' not in body:
                return None
            return final if re.search(r"/\d+-[^/]+\.html$", final) else url
        except urllib.error.HTTPError as e:
            if e.code == 404:
                return None
        except Exception:  # noqa: BLE001
            pass
        time.sleep(1 + attempt)
    return None


def parse_product(url):
    h = get(url)
    if not h:
        return None
    pid = int(re.search(r"/(\d+)-[^/]+\.html$", url).group(1))
    name = clean_text(inner(h, r'<h1 itemprop="name">', "h1"))
    price = re.search(r"var productPrice = ([\d.]+);", h)
    old = re.search(r"var productPriceWithoutReduction = ([\d.]+);", h)
    unit = re.search(r'<p class="unit-price">.*?</span>\s*(.*?)</p>', h, re.S)
    short = inner(h, r'<div id="short_description_content"[^>]*>', "div")
    long_html = raw_html(inner(h, r'<ul id="moreinfo"[^>]*>', "ul"))  # tabul „Detalii”
    offer = re.search(r'itemprop="offers"[^>]*>\s*<link itemprop="availability" href="http://schema\.org/(\w+)"', h)
    avail = re.search(r'id="availability_value"[^>]*>([^<]*)<', h)
    defcat = re.search(r'<body id="product" class="[^"]*\bcategory-(\d+) category-([a-z0-9-]+)', h)
    meta_desc = re.search(r'<meta name="description" content="([^"]*)"', h)
    # imagini: principala + miniaturile, toate cu slug-ul produsului
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
    # „Produse recomandate” (accesorii PrestaShop)
    accessories = []
    a = h.find(">Produse recomandate<")
    if a > 0:
        seg = h[a:h.find("</section>", a)]
        for x in re.findall(r'href="http://natur\.md/[^"]+/(\d+)-[^"]+\.html"', seg):
            if int(x) not in accessories:
                accessories.append(int(x))
    return {
        "id": pid,
        "url": url,
        "name": name,
        "price": float(price.group(1)) if price else 0.0,
        "regular_price": float(old.group(1)) if old else 0.0,
        "unit": clean_text(unit.group(1)) if unit else "",
        "short": clean_html(short),
        "description": long_html,
        "meta_description": clean_text(meta_desc.group(1)) if meta_desc else "",
        "images": images,
        "reviews": reviews,
        "in_stock": bool(offer) and offer.group(1) == "InStock",
        "availability": clean_text(avail.group(1)) if avail else "",
        "default_category": {"id": int(defcat.group(1)), "slug": defcat.group(2).strip("-")} if defcat else None,
        "accessories": accessories,
        "categories": [],
    }


def desc_image_urls(p):
    """Imaginile inserate în descrierea lungă (adrese absolute)."""
    out = []
    for src in re.findall(r'<img\b[^>]*\bsrc="([^"]+)"', p["description"], re.I):
        src = html.unescape(src)
        if src.startswith("/"):
            src = BASE + src
        if src not in out:
            out.append(src)
    return out


def desc_image_name(url):
    m = re.search(r"natur\.md/(\d+)-[a-z_]+/([^/?]+)$", url) or re.search(r"natur\.md/img/p/[\d/]+/(\d+)()\.jpg$", url)
    if m:
        return f"desc-{m.group(1)}-{m.group(2) or 'img.jpg'}"
    base = re.sub(r"[^a-zA-Z0-9._-]", "-", url.rsplit("/", 1)[-1].split("?")[0])[-60:] or "img.jpg"
    return f"desc-ext-{hashlib.sha1(url.encode()).hexdigest()[:10]}-{base}"


def download(url, dest):
    """Descarcă o imagine (o singură dată); http și https pentru natur.md."""
    if dest.exists():
        return True
    for u in dict.fromkeys([url, url.replace("https://natur.md", "http://natur.md")]):
        data = get(u, binary=True)
        if data and len(data) >= 500 and data[:4] != b"<!DO":
            dest.write_bytes(data)
            return True
    return False


def parse_cms(url):
    h = get(url)
    if not h:
        return None
    title = clean_text((re.search(r"<h1[^>]*>(.*?)</h1>", h, re.S) or [None, ""])[1])
    m = re.search(r'<div class="rte">(.*?)</div>\s*</div>', h, re.S)
    return {"url": url, "title": title, "html": clean_html(m.group(1)) if m else ""}


def scrape_catalog(per_cat=0, log=print):
    """Categoriile și produsele (cu toate detaliile) de pe natur.md. per_cat > 0 = eșantion."""
    home = get(BASE + "/")
    cats = parse_menu(home)
    log(f"{len(cats)} categorii în meniu")

    urls, cat_of = {}, {}
    for c in cats:
        links, c["description"] = category_products(c)
        c["total_on_old_site"] = len(links)
        pick = random.sample(links, min(per_cat, len(links))) if per_cat else links
        log(f"- {c['name']}: {len(links)} produse" + (f", aleg {len(pick)}" if per_cat else ""))
        for url in pick:
            pid = int(re.search(r"/(\d+)-[^/]+\.html$", url).group(1))
            urls.setdefault(pid, url)
            cat_of.setdefault(pid, [])
            if c["id"] not in cat_of[pid]:
                cat_of[pid].append(c["id"])

    if not per_cat:
        # Produse active care nu apar în nicio categorie din meniu.
        top = max(urls) + 300
        with ThreadPoolExecutor(6) as ex:
            found = dict(zip(range(1, top), ex.map(lambda i: None if i in urls else find_product_url(i), range(1, top))))
        extra = {i: u for i, u in found.items() if u}
        log(f"{len(extra)} produse active în afara categoriilor din meniu: {sorted(extra)}")
        urls.update(extra)

    with ThreadPoolExecutor(4) as ex:
        parsed = list(ex.map(parse_product, urls.values()))
    products = {}
    for url, p in zip(urls.values(), parsed):
        if not p or not p["name"]:
            log(f"  ! nu am putut citi {url}")
            continue
        p["categories"] = list(cat_of.get(p["id"], []))
        products[p["id"]] = p

    # Categoria implicită a produsului face parte mereu din categoriile lui (inclusiv cele ascunse).
    known = {c["id"] for c in cats}
    for p in products.values():
        d = p["default_category"]
        if not d or d["id"] == ROOT_CAT:
            continue
        if d["id"] not in known:
            cats.append({
                "id": d["id"], "slug": d["slug"], "name": HIDDEN_CATS.get(d["slug"], d["slug"].replace("-", " ").capitalize()),
                "parent": 0, "url": f"{BASE}/{d['id']}-{d['slug']}", "description": "", "hidden": True,
            })
            known.add(d["id"])
        if d["id"] not in p["categories"]:
            p["categories"].append(d["id"])
    for c in cats:
        if c.get("hidden"):
            c["total_on_old_site"] = sum(1 for p in products.values() if c["id"] in p["categories"])
    return cats, products


def main():
    per_cat = int(sys.argv[1]) if len(sys.argv) > 1 else 0
    random.seed(2026)
    OUT.mkdir(exist_ok=True)
    IMG.mkdir(exist_ok=True)
    cats, products = scrape_catalog(per_cat)

    home = get(BASE + "/")
    footer = {}
    m = re.search(r'id="block_contact_infos".*?</section>', home, re.S)
    if m:
        footer["lines"] = [clean_text(x) for x in re.findall(r"<li>(.*?)</li>", m.group(0), re.S)]

    def fetch_media(p):
        local = []
        for n, u in enumerate(p["images"]):
            fn = f"{p['id']}-{n}-{u.rsplit('/', 1)[1]}"
            if download(u, IMG / fn):
                local.append(fn)
        p["local_images"] = local
        p["desc_images"] = {}
        for u in desc_image_urls(p):
            fn = desc_image_name(u)
            if download(u, IMG / fn):
                p["desc_images"][u] = fn
            else:
                print(f"  ! imagine din descriere indisponibilă ({p['id']}): {u}", file=sys.stderr)

    with ThreadPoolExecutor(4) as ex:
        list(ex.map(fetch_media, products.values()))

    cms = [c for c in (parse_cms(f"{BASE}/content/1-conditii"), parse_cms(f"{BASE}/content/2-povestea-noastra")) if c]
    data = {"categories": cats, "products": list(products.values()), "cms": cms, "footer": footer}
    (OUT / "natur.json").write_text(json.dumps(data, ensure_ascii=False, indent=1))
    n_img = sum(len(p["local_images"]) for p in products.values())
    n_desc = len({f for p in products.values() for f in p["desc_images"].values()})
    print(f"Salvat: {len(cats)} categorii, {len(products)} produse, {n_img} fotografii, {n_desc} imagini din descrieri, {len(cms)} pagini")


if __name__ == "__main__":
    main()
