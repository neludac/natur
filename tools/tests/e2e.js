#!/usr/bin/env node
/**
 * Test end-to-end Natur.MD (Playwright + Google Chrome instalat local).
 *
 *   cd tools/tests && npm install && BASE=https://natur.ddev ADMIN_PASS='…' npm test
 *
 * Creează 2 comenzi de test (e-mail e2e-*@example.com) și un client de test;
 * se șterg cu `npm run cleanup` (rulează ddev wp).
 */
const { BASE, ok, section, failures, launch, newPage } = require('./lib');

const text = (s) => (s || '').replace(/\s+/g, ' ').trim();

const ADMIN_USER = process.env.ADMIN_USER || 'admin';
const ADMIN_PASS = process.env.ADMIN_PASS || '';

async function testHome(b) {
	section('Prima pagină');
	const p = await newPage(b);
	const r = await p.goto(BASE + '/', { waitUntil: 'networkidle' });
	ok(r.status() === 200, 'status 200');
	ok(await p.isVisible('h1'), 'titlul hero vizibil');
	ok((await p.$$('ul.products li.product')).length >= 8, 'produse recomandate afișate');
	ok((await p.$$('.nt-cat')).length >= 4, 'categorii afișate');
	ok(await p.isVisible('.natur-embed .natur-spin'), 'widget 360° pe prima pagină');
	ok(p.jsErrors.length === 0, 'fără erori JS ' + p.jsErrors.join(' | '));
	await p.context().close();
}

async function testTheme(b) {
	section('Tema: panou categorii, căutare live, coș lateral');
	const p = await newPage(b);
	await p.goto(BASE + '/', { waitUntil: 'networkidle' });
	await p.hover('.nt-nav__item.has-mega > .nt-nav__link');
	await p.waitForTimeout(500);
	ok((await p.$$eval('.nt-mega__cat', (els) => els.filter((e) => e.offsetParent).length)) >= 4, 'panoul „Magazin” arată categoriile cu imagini');
	await p.mouse.move(10, 600);

	await p.keyboard.press('/');
	await p.keyboard.type('prepel');
	await p.waitForSelector('.nt-sres a', { timeout: 10000 }).catch(() => null);
	ok((await p.$$('.nt-sres a')).length > 0, 'căutarea live găsește produse în timp ce scrii');
	await p.keyboard.press('Escape');
	await p.waitForTimeout(600);
	ok(await p.isHidden('#nt-search'), 'Esc închide căutarea');

	const add = p.locator('.nt-ptabs__panel:not([hidden]) .nt-add.ajax_add_to_cart').first();
	await add.scrollIntoViewIfNeeded();
	await add.click();
	await p.waitForSelector('.nt-toast', { timeout: 10000 }).catch(() => null);
	ok(await p.isVisible('.nt-toast'), 'notificarea „Adăugat în coș” apare');
	await p.waitForFunction(() => document.querySelector('.nt-cartbtn .nt-cart-count').textContent.trim() === '1', null, { timeout: 10000 }).catch(() => null);
	ok(text(await p.innerText('.nt-cartbtn .nt-cart-count')) === '1', 'contorul coșului din antet: 1');

	await p.click('.nt-cartbtn');
	await p.waitForSelector('#nt-cart.is-open .nt-mc__item', { timeout: 10000 }).catch(() => null);
	ok((await p.$$('#nt-cart .nt-mc__item')).length === 1, 'coșul lateral se deschide cu produsul adăugat');
	await p.click('#nt-cart [data-nt-mc-qty] button[data-step="1"]');
	await p.waitForFunction(() => document.querySelector('.nt-cartbtn .nt-cart-count').textContent.trim() === '2', null, { timeout: 15000 }).catch(() => null);
	ok(text(await p.innerText('#nt-cart .nt-qty__val')) === '2' && text(await p.innerText('.nt-cartbtn .nt-cart-count')) === '2', 'butonul „+” din coșul lateral mărește cantitatea');
	await p.click('#nt-cart .nt-mc__remove');
	await p.waitForSelector('#nt-cart .nt-mc__empty', { timeout: 15000 }).catch(() => null);
	ok(await p.isVisible('#nt-cart .nt-mc__empty'), 'produsul se scoate din coș; apare mesajul „Coșul tău e gol”');
	ok(p.jsErrors.length === 0, 'fără erori JS ' + p.jsErrors.join(' | '));
	await p.context().close();
}

async function testProductMedia(b, mobile) {
	section(`Pagina produsului: foto / video / 360° (${mobile ? 'mobil' : 'desktop'})`);
	const p = await newPage(b, mobile ? { viewport: { width: 390, height: 844 }, hasTouch: true, isMobile: true } : {});
	await p.goto(BASE + '/produs/unt-topit-ghee/', { waitUntil: 'networkidle' });
	const btns = await p.$$eval('.natur-media__btn', (bs) => bs.map((x) => x.dataset.show).join(','));
	ok(btns === 'photos,video,spin', 'butoane Foto / Video / 360°');
	await p.click('[data-show="spin"]');
	await p.waitForSelector('.natur-spin.is-ready', { timeout: 20000 });
	ok(await p.isHidden('.woocommerce-product-gallery'), '360°: galeria foto ascunsă, vizualizator încărcat');
	await p.click('.natur-spin [data-act="play"]');
	const bb = await (await p.$('.natur-spin')).boundingBox();
	const before = await p.$eval('.natur-spin__img', (i) => i.src);
	await p.mouse.move(bb.x + bb.width * 0.3, bb.y + bb.height / 2);
	await p.mouse.down();
	await p.mouse.move(bb.x + bb.width * 0.7, bb.y + bb.height / 2, { steps: 10 });
	await p.mouse.up();
	ok(before !== (await p.$eval('.natur-spin__img', (i) => i.src)), '360°: tragerea rotește produsul');
	await p.click('[data-show="video"]');
	await p.$eval('.natur-video video', (v) => { v.muted = true; return v.play(); });
	await p.waitForTimeout(1500);
	ok((await p.$eval('.natur-video video', (v) => v.currentTime)) > 0.3, 'video: redare pornită');
	await p.click('[data-show="photos"]');
	ok(await p.isVisible('.woocommerce-product-gallery'), 'revenire la galeria foto');
	ok(await p.$eval('.natur-video video', (v) => v.paused), 'video oprit la schimbarea tabului');
	ok(p.jsErrors.length === 0, 'fără erori JS ' + p.jsErrors.join(' | '));
	await p.context().close();
}

async function placeOrder(b, items, expect, tag) {
	const p = await newPage(b);
	for (const [slug, qty] of items) {
		await p.goto(`${BASE}/produs/${slug}/`, { waitUntil: 'networkidle' });
		await p.fill('input.qty', String(qty));
		await Promise.all([p.waitForNavigation({ waitUntil: 'networkidle' }), p.click('button[name="add-to-cart"]')]);
	}
	await p.goto(`${BASE}/finalizare-comanda/`, { waitUntil: 'networkidle' });
	await p.fill('#email', `e2e-${tag}-${Date.now()}@example.com`);
	await p.fill('#shipping-first_name', 'Test');
	await p.fill('#shipping-last_name', 'E2E');
	await p.fill('#shipping-address_1', 'str. Ștefan cel Mare 1');
	await p.fill('#shipping-city', 'Chișinău');
	await p.fill('#shipping-phone', '069123456');
	await p.waitForTimeout(2500);
	const rates = await p.innerText('.wp-block-woocommerce-checkout-shipping-methods-block');
	ok(expect.test(rates), `livrare corectă (${rates.replace(/\s+/g, ' ').trim().slice(0, 70)})`);
	await p.click('.wc-block-components-checkout-place-order-button');
	await p.waitForURL(/order-received|comanda-primita/, { timeout: 30000 });
	ok(/Mulțumim|Mulţumim|primit/i.test(await p.innerText('main')), 'pagina de confirmare a comenzii');
	ok(p.jsErrors.length === 0, 'fără erori JS ' + p.jsErrors.join(' | '));
	await p.context().close();
}

async function testCheckout(b) {
	section('Comandă sub 500 lei (livrare 50 lei)');
	await placeOrder(b, [['oua-de-prepelita', 2]], /50,00/, 'mic');
	section('Comandă peste 500 lei (livrare gratuită)');
	await placeOrder(b, [['unt-topit-ghee', 1], ['oua-de-prepelita', 2]], /gratuit/i, 'mare');
}

async function testForms(b) {
	section('Pagina de contact, căutare, magazin, cont');
	const p = await newPage(b);
	await p.context().grantPermissions(['clipboard-read', 'clipboard-write'], { origin: BASE });
	await p.goto(BASE + '/contact/', { waitUntil: 'networkidle' });
	ok(!(await p.$('.entry-content form')), 'pagina de contact nu are formular');
	ok(await p.isVisible('.nt-cb--phone a.nt-cb__phone[href^="tel:"]'), 'telefonul e afișat ca link de apel');
	ok(await p.isVisible('.nt-cb--mail a[href^="mailto:"]') && await p.isVisible('.nt-cb--chat a[href*="m.me"]'), 'e-mailul și Messenger sunt afișate');
	await p.click('.nt-cb--phone [data-nt-copy]');
	await p.waitForSelector('.nt-cb--phone [data-nt-copy].is-copied', { timeout: 5000 }).catch(() => {});
	const copied = await p.evaluate(() => navigator.clipboard.readText().catch(() => ''));
	ok(copied === await p.getAttribute('.nt-cb--phone [data-nt-copy]', 'data-nt-copy') && /Copiat/.test(await p.innerText('.nt-cb--phone [data-nt-copy]')), `„Copiază numărul” copiază telefonul (${copied})`);
	ok(await p.isVisible('.nt-map'), 'harta livrărilor e afișată');
	const faq = await p.$$('.nt-faq details');
	await p.click('.nt-faq details:first-of-type summary');
	await p.waitForSelector('.nt-faq details[open] p', { state: 'visible', timeout: 3000 }).catch(() => {}); // deschidere animată
	ok(faq.length >= 5 && await p.isVisible('.nt-faq details[open] p'), `întrebările frecvente se deschid (${faq.length})`);
	ok(!(await p.$('.nt-help')), 'fără banda „Preferi să comanzi la telefon?” pe pagina de contact');

	await p.goto(BASE + '/?s=prepeli&post_type=product', { waitUntil: 'networkidle' });
	ok((await p.$$('ul.products li.product')).length > 0, 'căutarea găsește produse');
	await p.goto(BASE + '/magazin/', { waitUntil: 'networkidle' });
	ok((await p.$$('ul.products li.product')).length === 12, 'magazinul afișează 12 produse pe pagină');
	await p.goto(BASE + '/categorie/carne/', { waitUntil: 'networkidle' });
	ok((await p.$$('ul.products li.product')).length > 0, 'pagina de categorie listează produse');

	await p.goto(BASE + '/contul-meu/', { waitUntil: 'networkidle' });
	await p.fill('#reg_email', `e2e-client-${Date.now()}@example.com`);
	await Promise.all([p.waitForNavigation({ waitUntil: 'networkidle' }), p.click('button[name="register"]')]);
	ok(await p.isVisible('.woocommerce-MyAccount-navigation'), 'înregistrare cont nou și autentificare');
	ok(p.jsErrors.length === 0, 'fără erori JS ' + p.jsErrors.join(' | '));
	await p.context().close();
}

async function testRedirects() {
	section('Redirecționări de la URL-urile vechi natur.md');
	const cases = {
		'/17-lactate-molochnye': '/categorie/lactate/',
		'/oua-yajca/58-oua-de-prepelita.html': '/produs/oua-de-prepelita/',
		'/content/1-conditii': '/livrare-si-plata/',
		'/content/2-povestea-noastra': '/despre-noi/',
		'/quick-order': '/finalizare-comanda/',
	};
	for (const [from, to] of Object.entries(cases)) {
		const r = await fetch(BASE + from, { redirect: 'manual' });
		ok(r.status === 301 && (r.headers.get('location') || '').endsWith(to), `${from} → ${to} (${r.status})`);
	}
}

async function testCrawl() {
	section('Toate paginile de produs / categorie răspund 200');
	const seen = new Set();
	const html = async (u) => (await fetch(u)).text();
	for (let n = 1; n < 30; n++) {
		const r = await fetch(`${BASE}/magazin/page/${n}/`);
		if (r.status !== 200) break;
		for (const m of (await r.text()).matchAll(/href="([^"]+\/(?:produs|categorie)\/[^"#?]+)"/g)) seen.add(m[1]);
	}
	for (const m of (await html(BASE + '/')).matchAll(/href="(https?:\/\/[^"]+\/(?:despre-noi|livrare-si-plata|contact|termeni-si-conditii|politica-de-retur|politica-de-confidentialitate)\/)"/g)) seen.add(m[1]);
	const bad = [];
	await Promise.all([...seen].map(async (u) => { const r = await fetch(u); if (r.status !== 200) bad.push(`${r.status} ${u}`); }));
	ok(seen.size > 50 && bad.length === 0, `${seen.size} URL-uri verificate ${bad.join(', ')}`);
}

async function testMobile(b) {
	section('Mobil');
	const p = await newPage(b, { viewport: { width: 390, height: 844 }, hasTouch: true, isMobile: true });
	for (const path of ['/', '/magazin/', '/produs/unt-topit-ghee/', '/finalizare-comanda/', '/contact/']) {
		await p.goto(BASE + path, { waitUntil: 'networkidle' });
		const overflow = await p.evaluate(() => document.documentElement.scrollWidth - window.innerWidth);
		ok(overflow <= 0, `${path}: fără derulare orizontală`);
	}
	await p.goto(BASE + '/', { waitUntil: 'networkidle' });
	await p.click('.nt-burger');
	ok(await p.isVisible('#nt-menu .nt-mnav >> text=Magazin'), 'meniul mobil se deschide');
	ok(p.jsErrors.length === 0, 'fără erori JS ' + p.jsErrors.join(' | '));
	await p.context().close();
}

async function testAdmin(b) {
	section('Administrare');
	if (!ADMIN_PASS) {
		console.log('  – sărit (setați ADMIN_PASS)');
		return;
	}
	const p = await newPage(b, { viewport: { width: 1440, height: 900 } });
	await p.goto(BASE + '/wp-login.php');
	await p.fill('#user_login', ADMIN_USER);
	await p.fill('#user_pass', ADMIN_PASS);
	await Promise.all([p.waitForNavigation(), p.click('#wp-submit')]);
	ok(p.url().includes('/wp-admin'), 'autentificare admin');
	await p.goto(BASE + '/wp-admin/edit.php?post_type=product', { waitUntil: 'networkidle' });
	const edit = await p.$eval('#the-list .row-title', (a) => a.href);
	await p.goto(edit, { waitUntil: 'networkidle' });
	ok(await p.isVisible('#natur-product-media'), 'metabox „Video & prezentare 360°” în editorul produsului');
	await p.click('.natur-pm-pick-frames');
	await p.waitForSelector('.media-modal', { state: 'visible', timeout: 15000 });
	ok(true, 'biblioteca Media se deschide pentru cadrele 360°');
	await p.keyboard.press('Escape');
	const home = ((await (await fetch(BASE + '/')).text()).match(/page-id-(\d+)/) || [])[1];
	if (home) {
		await p.goto(`${BASE}/wp-admin/post.php?post=${home}&action=elementor`, { waitUntil: 'load', timeout: 90000 });
		await p.frameLocator('#elementor-preview-iframe').locator('.elementor-widget-natur_product_media').first().waitFor({ timeout: 60000 });
		ok(true, 'editorul Elementor încarcă prima pagină');
	}
	ok(p.jsErrors.filter((e) => !/ResizeObserver/.test(e)).length === 0, 'fără erori JS în admin');
	await p.context().close();
}

(async () => {
	console.log(`Testare ${BASE}`);
	const b = await launch();
	try {
		await testHome(b);
		await testTheme(b);
		await testProductMedia(b, false);
		await testProductMedia(b, true);
		await testCheckout(b);
		await testForms(b);
		await testRedirects();
		await testCrawl();
		await testMobile(b);
		await testAdmin(b);
	} catch (e) {
		ok(false, 'excepție: ' + e.message);
	}
	await b.close();
	const failed = failures();
	console.log(failed ? `\n✘ ${failed} verificări eșuate` : '\n✔ Toate verificările au trecut');
	process.exit(failed ? 1 : 0);
})();
