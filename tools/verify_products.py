#!/usr/bin/env python3
"""Verifică dacă produsele de pe natur.md din lista de prețuri (tools/data/catalog.json) există pe
site-ul nou, cu toate detaliile (numele așteptat = titlul din listă).

    python3 tools/verify_products.py            # citește natur.md acum (~4 minute)
    python3 tools/verify_products.py --offline  # compară cu tools/data/natur.json (ultima extragere)
    WP="wp --path=/cale/site" python3 tools/verify_products.py   # alt site decât DDEV

Compară, pentru fiecare produs: nume, preț (și reducere), stoc, descrierea scurtă,
descrierea lungă (text + număr de imagini), fotografii, categorii, recenzii și produsele
recomandate; pentru fiecare categorie: existența, părintele și numărul de produse.
Iese cu codul 1 dacă găsește diferențe.
"""
import html
import json
import os
import re
import shlex
import subprocess
import sys
from collections import Counter, defaultdict
from pathlib import Path

sys.path.insert(0, str(Path(__file__).parent))
import scrape_natur  # noqa: E402

ROOT = Path(__file__).resolve().parent.parent

EXPORT_PHP = r"""<?php
global $wpdb;
$old_of = static fn( $t ) => (int) get_term_meta( $t, '_natur_old_id', true );
$out    = array( 'products' => array(), 'cats' => array() );
foreach ( get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false ) ) as $t ) {
	$out['cats'][] = array( 'id' => $t->term_id, 'old' => $old_of( $t->term_id ), 'name' => $t->name, 'parent_old' => $t->parent ? $old_of( $t->parent ) : 0 );
}
foreach ( get_posts( array( 'post_type' => 'product', 'post_status' => 'any', 'numberposts' => -1, 'fields' => 'ids' ) ) as $id ) {
	$p    = wc_get_product( $id );
	$imgs = array_values( array_filter( array_merge( array( $p->get_image_id() ), $p->get_gallery_image_ids() ) ) );
	$revs = array();
	foreach ( get_comments( array( 'post_id' => $id, 'type' => 'review', 'status' => 'approve' ) ) as $c ) {
		$revs[] = array( 'author' => $c->comment_author, 'date' => substr( $c->comment_date, 0, 10 ), 'text' => $c->comment_content );
	}
	$out['products'][] = array(
		'ID'      => $id,
		'old_id'  => (int) get_post_meta( $id, '_natur_old_id', true ),
		'status'  => get_post_status( $id ),
		'name'    => $p->get_name(),
		'regular' => $p->get_regular_price(),
		'sale'    => $p->get_sale_price(),
		'price'   => $p->get_price(),
		'stock'   => $p->get_stock_status(),
		'short'   => $p->get_short_description(),
		'desc'    => $p->get_description(),
		'cats'    => array_map( $old_of, $p->get_category_ids() ),
		'images'  => count( $imgs ),
		'missing_files' => count( array_filter( $imgs, static fn( $a ) => ! file_exists( (string) get_attached_file( $a ) ) ) ),
		'reviews' => $revs,
		'unit'    => implode( ', ', wc_get_product_terms( $id, 'pa_ambalare', array( 'fields' => 'names' ) ) ),
		'upsells' => array_map( static fn( $u ) => (int) get_post_meta( $u, '_natur_old_id', true ), array_merge( $p->get_upsell_ids(), $p->get_cross_sell_ids() ) ),
	);
}
echo wp_json_encode( $out, JSON_UNESCAPED_UNICODE );
"""


def norm(s):
    s = re.sub(r"<[^>]+>", " ", s or "")
    s = html.unescape(s).replace("\xa0", " ")
    s = s.translate(str.maketrans("şţŞŢ", "șțȘȚ"))
    return re.sub(r"\s+", " ", s).strip()


def export_new():
    cmd = shlex.split(os.environ.get("WP", "ddev wp")) + ["eval-file", "-"]
    r = subprocess.run(cmd, input=EXPORT_PHP, capture_output=True, text=True, cwd=ROOT)
    start = r.stdout.find("{")
    if r.returncode or start < 0:
        sys.exit(f"Exportul din WordPress a eșuat:\n{r.stderr or r.stdout}")
    return json.loads(r.stdout[start:])


def main():
    if "--offline" in sys.argv:
        data = json.loads((ROOT / "tools/data/natur.json").read_text())
        cats, products = data["categories"], {p["id"]: p for p in data["products"]}
        print(f"natur.md (extragerea din tools/data/natur.json): {len(products)} produse")
    else:
        print("Citesc natur.md…", flush=True)
        cats, products = scrape_natur.scrape_catalog(0, log=lambda *_: None)
        print(f"natur.md acum: {len(products)} produse, {len(cats)} categorii")

    # Catalogul = lista de prețuri (tools/data/catalog.json): doar produsele de acolo, cu titlul de acolo.
    catalog = {c["old_id"]: c["title"] for c in json.loads((ROOT / "tools/data/catalog.json").read_text())["products"]}
    products = {pid: {**o, "name": catalog[pid], "accessories": [a for a in o.get("accessories", []) if a in catalog]}
                for pid, o in products.items() if pid in catalog}
    print(f"din lista de prețuri: {len(products)} produse")

    site = export_new()
    new = {p["old_id"]: p for p in site["products"] if p["old_id"]}
    print(f"site nou: {len(site['products'])} produse ({len(new)} preluate de pe natur.md)\n")

    issues, fields = defaultdict(list), Counter()

    def bad(key, field, msg):
        issues[key].append(f"{field}: {msg}")
        fields[field] += 1

    for pid, o in sorted(products.items()):
        n = new.get(pid)
        key = f"#{pid} {o['name']}"
        if not n:
            bad(key, "lipsește", o["url"])
            continue
        if n["status"] != "publish":
            bad(key, "status", n["status"])
        if norm(o["name"]) != norm(n["name"]):
            bad(key, "nume", f"{o['name']!r} ≠ {n['name']!r}")
        regular = max(o["regular_price"], o["price"])
        if float(n["price"] or 0) != o["price"] or float(n["regular"] or 0) != regular:
            bad(key, "preț", f"{o['price']} (fără reducere {regular}) ≠ {n['price']} (regular {n['regular']})")
        if (n["stock"] == "instock") != o["in_stock"]:
            bad(key, "stoc", f"{'în stoc' if o['in_stock'] else o.get('availability') or 'stoc epuizat'} ≠ {n['stock']}")
        # Ambalarea (pa_ambalare) vine acum din lista de prețuri, nu de pe natur.md.
        if norm(o["short"]) != norm(n["short"]):
            bad(key, "descriere scurtă", f"{norm(o['short'])[:120]!r} ≠ {norm(n['short'])[:120]!r}")
        if norm(o["description"]) and norm(o["description"]) not in norm(n["desc"]):
            bad(key, "descriere lungă", f"textul de pe natur.md ({len(norm(o['description']))} caractere) nu apare în descriere")
        old_imgs = len(re.findall(r"<img\b", o["description"], re.I))
        new_imgs = len(re.findall(r"<img\b", n["desc"], re.I))
        if new_imgs < old_imgs:
            bad(key, "imagini în descriere", f"{old_imgs} pe natur.md, {new_imgs} pe site")
        if len(o["images"]) != n["images"]:
            bad(key, "fotografii", f"{len(o['images'])} pe natur.md, {n['images']} pe site")
        if n["missing_files"]:
            bad(key, "fotografii", f"{n['missing_files']} fișiere lipsă pe disc")
        want_cats, have_cats = set(o["categories"]), set(n["cats"]) - {0}
        if want_cats != have_cats:
            bad(key, "categorii", f"lipsă {sorted(want_cats - have_cats)}, în plus {sorted(have_cats - want_cats)} (ID-uri natur.md)")
        orev = sorted((r["author"], r["date"], norm(r["text"])) for r in o["reviews"])
        nrev = sorted((r["author"], r["date"], norm(r["text"])) for r in n["reviews"])
        if orev != nrev:
            bad(key, "recenzii", f"{len(orev)} pe natur.md, {len(nrev)} pe site" + (" (text diferit)" if len(orev) == len(nrev) else ""))
        if set(o.get("accessories", [])) - set(n["upsells"]):
            bad(key, "produse recomandate", f"lipsă {sorted(set(o['accessories']) - set(n['upsells']))}")

    # Categorii: toate cele cu produse, cu același părinte și același număr de produse.
    by_old = {c["old"]: c for c in site["cats"] if c["old"]}
    count_old = Counter(c for p in products.values() for c in p["categories"])
    count_new = Counter(c for p in site["products"] if p["old_id"] in products for c in p["cats"])
    for c in cats:
        if not count_old[c["id"]]:
            continue
        key = f"categoria {c['id']} {c['name']}"
        t = by_old.get(c["id"])
        if not t:
            bad(key, "categorie lipsă", c["url"])
            continue
        if t["parent_old"] != c["parent"]:
            bad(key, "părinte categorie", f"{c['parent']} ≠ {t['parent_old']}")
        if count_old[c["id"]] != count_new[c["id"]]:
            bad(key, "produse în categorie", f"{count_old[c['id']]} pe natur.md, {count_new[c['id']]} pe site")
    dupes = [o for o, k in Counter(c["old"] for c in site["cats"] if c["old"]).items() if k > 1]
    if dupes:
        bad("categorii", "categorii duplicate", f"ID-uri natur.md {sorted(dupes)}")

    extra = [p for p in site["products"] if p["old_id"] and p["old_id"] not in products]
    for key in issues:
        print(key)
        for i in issues[key]:
            print("   -", i)
    ok = sum(1 for pid, o in products.items() if f"#{pid} {o['name']}" not in issues)
    print(f"\nProduse identice: {ok}/{len(products)}")
    if extra:
        print("Pe site, dar nu (mai) există pe natur.md:", ", ".join(f"#{p['old_id']} {p['name']}" for p in extra))
    if fields:
        print("Diferențe:", ", ".join(f"{f}: {k}" for f, k in fields.most_common()))
        sys.exit(1)
    print("Toate produsele din lista de prețuri și categoriile lor sunt pe site, cu toate detaliile.")


if __name__ == "__main__":
    main()
