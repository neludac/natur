#!/usr/bin/env node
/**
 * Test end-to-end Natur.MD (Playwright + Google Chrome instalat local).
 *
 *   cd tools/tests && npm install && BASE=https://natur.ddev ADMIN_PASS='…' npm test
 *
 * Creează 3 comenzi de test (nume „Test E2E”) și un client de test;
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
	ok((await p.$$('.nt-recipe .nt-recipe__img')).length >= 4 && (await p.$$('.nt-recipe__prod .nt-add')).length >= 4, 'rețete video afișate, cu produsul din rețetă');
	// Linkurile demo duc la profil; un reel încorporat trebuie să se deschidă în fereastră.
	await p.$eval('.nt-recipe__link', (a) => a.setAttribute('data-nt-embed', 'about:blank'));
	await p.click('.nt-recipe__link');
	ok(await p.evaluate(() => document.querySelector('.nt-reel').open && !!document.querySelector('.nt-reel iframe') && !!document.querySelector('.nt-reel .nt-recipe__prod')), 'rețeta video se deschide în fereastră, cu produsul');
	await p.keyboard.press('Escape');
	const closed = await p.waitForFunction(() => !document.querySelector('.nt-reel').open && !document.querySelector('[data-nt-reel-player]').children.length, null, { timeout: 5000 }).catch(() => null);
	ok(!!closed, 'Esc închide fereastra și oprește clipul');
	// Subsol: contact, linkurile principale, rețelele sociale și, obligatoriu, politicile de confidențialitate și cookies.
	const foot = await p.$eval('footer.nt-footer', (f) => ({
		contact: !!f.querySelector('.nt-footer__contact a[href^="tel:"]') && !!f.querySelector('.nt-footer__contact a[href^="mailto:"]'),
		social: f.querySelectorAll('.nt-social a[target="_blank"]').length,
		links: f.querySelectorAll('.nt-footer__col li a').length,
		legal: [...f.querySelectorAll('.nt-footer__legal a')].map((a) => new URL(a.href).pathname),
	}));
	ok(foot.contact && foot.social >= 2 && foot.links >= 6, `subsol: telefon, e-mail, ${foot.social} rețele sociale, ${foot.links} linkuri`);
	ok(foot.legal.includes('/politica-de-confidentialitate/') && foot.legal.includes('/politica-de-cookies/'), 'subsol: Politica de confidențialitate și Politica de cookies');
	for (const path of foot.legal) ok((await p.request.get(BASE + path)).status() === 200, `${path} răspunde 200`);
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

/**
 * Comanda într-un singur pas: coșul duce direct la comandă, câmpurile vizibile: telefon, „Unde livrăm?”,
 * nume, adresă. `email` (opțional) se completează în „Adaugă un comentariu sau e-mail”.
 */
async function placeOrder(b, items, expect, { email = '', other = false } = {}) {
	const p = await newPage(b);
	for (const [slug, qty] of items) {
		await p.goto(`${BASE}/produs/${slug}/`, { waitUntil: 'networkidle' });
		await p.fill('input.qty', String(qty));
		await Promise.all([p.waitForNavigation({ waitUntil: 'networkidle' }), p.click('button[name="add-to-cart"]')]);
	}
	await p.goto(`${BASE}/cos/`, { waitUntil: 'networkidle' });
	ok(/\/finalizare-comanda\/$/.test(p.url()), 'coșul cu produse duce direct la comandă');
	const fields = await p.$$eval('form.checkout input:not([type="hidden"]):not([type="radio"]), form.checkout textarea, form.checkout select', (els) => els.filter((e) => e.checkVisibility() && !e.closest('.nt-hp')).map((e) => e.name));
	ok(fields.join() === 'billing_phone,billing_first_name,billing_address_1', `doar 3 câmpuri de completat: ${fields.join(', ')}`);
	ok(!(await p.$('input[name="terms"], #createaccount, .woocommerce-form-login-toggle')), 'fără bifă de termeni, cont sau autentificare');
	await p.fill('#billing_first_name', 'Test E2E');
	await p.fill('#billing_phone', '069123456');
	if (other) await p.click('label[for="billing_state_other"]');
	await p.fill('#billing_address_1', other ? 's. Bubuieci, str. Livezilor 3' : 'str. Ștefan cel Mare 1');
	if (email) {
		await p.click('.nt-ck__more summary');
		await p.fill('#billing_email', email);
	}
	await p.waitForTimeout(2500);
	const rates = text(await p.innerText('.nt-ck-ship'));
	ok(expect.test(rates), `livrare corectă (${rates.slice(0, 80)})`);
	const total = text(await p.innerText('.nt-ck-sum__total dd'));
	ok(text(await p.innerText('#place_order')).endsWith(total), `butonul arată totalul: „${text(await p.innerText('#place_order'))}”`);
	await p.click('#place_order');
	await p.waitForURL(/order-received|comanda-primita/, { timeout: 30000 });
	const thanks = text(await p.innerText('main'));
	ok(/Mulțumim, Test!/.test(thanks) && thanks.includes('069123456') && thanks.includes(total), 'confirmare: nume, telefonul la care sunăm și suma de plată');
	ok(p.jsErrors.length === 0, 'fără erori JS ' + p.jsErrors.join(' | '));
	await p.context().close();
}

/*
 * Protecția ascunsă anti-bot (inc/checkout-guard.php): aceleași date trimise direct (fără browser), cu capcana completată,
 * prea repede sau prin Store API sunt refuzate; formularul completat de un om (testele de mai sus) trece.
 */
async function testGuard(p) {
	const trap = await p.$eval('.nt-hp input', (i) => ({ name: i.name, tab: i.tabIndex, hidden: i.closest('[aria-hidden="true"]') !== null, x: i.getBoundingClientRect().right }));
	ok(trap.tab === -1 && trap.hidden && trap.x < 0, 'câmpul-capcană e ascuns (în afara ecranului, fără Tab, ascuns cititoarelor de ecran)');
	const form = async (patch) =>
		p.evaluate(async (patch) => {
			const fd = new FormData(document.querySelector('form.checkout'));
			fd.set('billing_first_name', 'Bot E2E');
			fd.set('billing_phone', '069123456');
			for (const [k, v] of Object.entries(patch)) fd.set(k, v);
			const r = await fetch('/?wc-ajax=checkout', { method: 'POST', body: new URLSearchParams(fd), credentials: 'same-origin' });
			return r.json();
		}, patch);
	const msg = (r) => text(r.messages || '').replace(/<[^>]+>/g, ' ');
	const token = await p.$eval('input[data-nt-hc]', (i) => i.dataset.ntHc);
	let r = await form({ nt_hc: '' });
	ok(r.result === 'failure' && /Reîncarcă/.test(msg(r)), `fără dovada din browser → refuzată: „${msg(r).slice(0, 70)}”`);
	r = await form({ nt_hc: `v1.${token}.5.3.20.0.0.9000.6000`, nt_website: 'https://spam.example' });
	ok(r.result === 'failure' && /Reîncarcă/.test(msg(r)), 'capcana completată → refuzată');
	r = await form({ nt_hc: `v1.${token.replace(/^\d+/, (t) => t - 1)}.5.3.20.0.0.9000.6000` });
	ok(r.result === 'failure', 'semnătura falsificată → refuzată');
	const now = Math.floor(Date.now() / 1000);
	r = await form({ nt_hc: `v1.${now}.${token.split('.')[1]}.5.3.20.0.0.900.600` });
	ok(r.result === 'failure', 'jeton reconstruit cu ora curentă → refuzat');
	const api = await p.request.post(`${BASE}/wp-json/wc/store/v1/checkout`, { data: {}, failOnStatusCode: false });
	ok(api.status() === 403, `comanda prin Store API → ${api.status()}`);
}

async function testCheckout(b) {
	section('Comandă sub 500 lei, doar cu nume și telefon (livrare 50 lei)');
	await placeOrder(b, [['oua-de-prepelita', 2]], /curier.*50 lei/i);
	section('Comandă peste 500 lei, cu e-mail (livrare gratuită)');
	await placeOrder(b, [['unt-topit-ghee', 1], ['oua-de-prepelita', 2]], /gratuit/i, { email: `e2e-mare-${Date.now()}@example.com` });
	section('Comandă în altă localitate (Poșta Moldovei)');
	await placeOrder(b, [['oua-de-prepelita', 1]], /Poșta Moldovei.*50 lei/i, { other: true, email: `e2e-posta-${Date.now()}@example.com` });

	section('„Comandă acum” și validarea formularului');
	const p = await newPage(b);
	await p.goto(`${BASE}/produs/unt-topit-ghee/`, { waitUntil: 'networkidle' });
	await Promise.all([p.waitForNavigation({ waitUntil: 'networkidle' }), p.click('.nt-buynow')]);
	ok(/\/finalizare-comanda\/$/.test(p.url()) && (await p.$$('.nt-ck-item')).length === 1, '„Comandă acum” deschide comanda cu produsul');
	ok(!(await p.$('.woocommerce-message')), 'fără mesajul „adăugat în coș” deasupra formularului');
	await p.click('[data-nt-ck-qty] button[data-step="1"]');
	await p.waitForFunction(() => /2/.test(document.querySelector('.nt-cart-count').getAttribute('data-count')), null, { timeout: 15000 }).catch(() => null);
	await p.waitForFunction(() => /920/.test(document.querySelector('.nt-ck-sum__total dd').textContent), null, { timeout: 15000 }).catch(() => null);
	ok(text(await p.innerText('.nt-ck-sum__total dd')) === '920 lei', `„+” pe pagina comenzii: total ${text(await p.innerText('.nt-ck-sum__total dd'))}`);
	await p.fill('#billing_first_name', 'Test');
	await p.fill('#billing_phone', '0691');
	await p.fill('#billing_address_1', 'str. Test 1');
	await p.click('#place_order');
	await p.waitForSelector('.woocommerce-NoticeGroup-checkout', { timeout: 15000 }).catch(() => null);
	ok(/telefon/i.test(text(await p.innerText('.woocommerce-NoticeGroup-checkout').catch(() => ''))), 'telefonul incomplet e refuzat');
	await testGuard(p);
	await Promise.all([p.waitForNavigation({ waitUntil: 'networkidle', timeout: 20000 }), p.click('.nt-ck-item__remove')]);
	ok(/\/cos\/$/.test(p.url()) && /gol/i.test(text(await p.innerText('main'))), 'după ștergerea ultimului produs: „Coșul e gol”');
	ok(p.jsErrors.length === 0, 'fără erori JS ' + p.jsErrors.join(' | '));
	await p.context().close();
}

async function testForms(b) {
	section('Pagina de contact, căutare, magazin, fără cont de client');
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

	// Magazinul nu are „Contul meu”: se comandă fără cont, iar /contul-meu/ duce la prima pagină.
	ok(!(await p.$('a[href*="contul-meu"], .nt-account')), 'fără link spre „Contul meu” în antet, meniu sau subsol');
	await p.goto(BASE + '/contul-meu/', { waitUntil: 'networkidle' });
	ok(new URL(p.url()).pathname === '/', `/contul-meu/ redirecționează spre prima pagină (${new URL(p.url()).pathname})`);
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
		'/my-account': '/',
		'/authentication': '/',
	};
	for (const [from, to] of Object.entries(cases)) {
		const r = await fetch(BASE + from, { redirect: 'manual' });
		ok(r.status === 301 && new URL(r.headers.get('location') || '/x', BASE).pathname === to, `${from} → ${to} (${r.status})`);
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
	ok(seen.size > 30 && bad.length === 0, `${seen.size} URL-uri verificate ${bad.join(', ')}`);
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
