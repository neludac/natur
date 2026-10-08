#!/usr/bin/env node
/**
 * Test end-to-end al activității magazinului Natur.MD, ca în viața reală:
 *
 *   1. Admin: categorie nouă (creare / citire / editare), apoi 4 produse adăugate din editor:
 *      standard, cu reducere (preț vechi / preț nou, „-25%”), fără stoc, „În curând”.
 *   2. Vitrina: prețuri, insigne, produsele indisponibile nu pot fi puse în coș (nici prin URL / Store API).
 *   3. Client real, fără cont (magazinul nu are „Contul meu”): coș → comandă cu plata la livrare,
 *      cu e-mail opțional → e-mailuri de confirmare (Mailpit).
 *   4. Admin: comanda e procesată și finalizată, o rambursare parțială, o comandă anulată.
 *   5. Statistici (WooCommerce Analytics): venituri, comenzi, produse, categorii, clienți, stoc
 *      — diferențele față de înainte de test trebuie să corespundă exact activității de mai sus.
 *   6. Categoria e ștearsă (D din CRUD); la final — raportul întregii activități a magazinului.
 *
 *   cd tools/tests && npm install && npm run test:shop
 *
 * Fără ADMIN_PASS, testul creează (prin ddev wp) administratorul temporar e2e-admin.
 * Datele de test (produse/categorie „E2E …”, comenzi e2e-*@example.com)
 * se șterg cu `npm run cleanup`.
 */
const { execSync } = require('child_process');
const crypto = require('crypto');
const { BASE, HOST, ok, section, failures, launch, newPage } = require('./lib');

const RUN = Date.now().toString(36).slice(-5);
const MAILPIT = process.env.MAILPIT || (HOST.endsWith('.ddev') || HOST.endsWith('.ddev.site') ? `https://${HOST}:8026` : '');
let ADMIN_USER = process.env.ADMIN_USER || 'admin';
let ADMIN_PASS = process.env.ADMIN_PASS || '';

const lei = (n) => n.toFixed(2).replace('.', ',') + ' lei';
/* Vitrina (card, pagina produsului) afișează prețurile fără zecimale nule: „200 lei”; coșul și comenzile — „200,00 lei”. */
const leiV = (n) => (Number.isInteger(n) ? String(n) : n.toFixed(2).replace('.', ',')) + ' lei';
const text = (s) => (s || '').replace(/\s+/g, ' ').trim(); // \s include și &nbsp;
const BADGE = '.onsale, .ast-onsale-card';
const num = (s) => parseFloat(String(s).replace(/[^\d,.-]/g, '').replace(/\.(?=\d{3})/g, '').replace(',', '.'));
const soonDate = new Date(Date.now() + 14 * 864e5);
const soonISO = soonDate.toISOString().slice(0, 10);
const soonRo = new Intl.DateTimeFormat('ro-RO', { day: 'numeric', month: 'long', year: 'numeric', timeZone: 'UTC' }).format(soonDate);

/* Produsele create de test: preț, stare de stoc, reducere. */
const P = {
	std: { title: `E2E Brânză de vaci ${RUN}`, price: 100 },
	sale: { title: `E2E Miere de salcâm ${RUN}`, price: 200, sale: 150 },
	oos: { title: `E2E Ulei de cânepă ${RUN}`, price: 90, stock: 'outofstock' },
	soon: { title: `E2E Dulceață de gutui ${RUN}`, price: 70, stock: 'comingsoon', date: soonISO },
};
const cat = { name: `E2E Rafturile bunicii ${RUN}`, slug: `e2e-rafturi-${RUN}` };
const customer = { email: `e2e-client-${RUN}@example.com`, first: 'Maria', last: 'Testescu' };

/* ------------------------------------------------------------------ utilitare */

function ensureAdmin() {
	if (ADMIN_PASS) return;
	ADMIN_USER = 'e2e-admin';
	ADMIN_PASS = 'Ad!' + crypto.randomBytes(12).toString('base64url');
	const opts = { cwd: __dirname, stdio: 'pipe' };
	try {
		execSync(`ddev wp user update ${ADMIN_USER} --user_pass='${ADMIN_PASS}' --role=administrator`, opts);
	} catch {
		execSync(`ddev wp user create ${ADMIN_USER} e2e-admin@example.com --role=administrator --user_pass='${ADMIN_PASS}' --first_name=E2E --last_name=Admin`, opts);
	}
}

async function login(p, user, pass) {
	await p.goto(BASE + '/wp-login.php');
	await p.fill('#user_login', user);
	await p.fill('#user_pass', pass);
	await Promise.all([p.waitForNavigation(), p.click('#wp-submit')]);
}

/** Caută în Mailpit ultimul e-mail către `to` cu subiectul potrivit. */
async function waitMail(to, subject, timeout = 30000) {
	if (!MAILPIT) return null;
	const end = Date.now() + timeout;
	while (Date.now() < end) {
		const r = await fetch(`${MAILPIT}/api/v1/search?query=${encodeURIComponent(`to:"${to}"`)}`);
		const m = ((await r.json()).messages || []).find((x) => subject.test(x.Subject));
		if (m) return (await fetch(`${MAILPIT}/api/v1/message/${m.ID}`)).json();
		await new Promise((res) => setTimeout(res, 1000));
	}
	return null;
}

/** Cereri REST autentificate (cookie-urile paginii + nonce). */
async function restClient(p) {
	const nonce = await (await p.request.get(`${BASE}/wp-admin/admin-ajax.php?action=rest-nonce`)).text();
	return async (path) => {
		const sep = path.includes('?') ? '&' : '?';
		const r = await p.request.get(`${BASE}/wp-json/${path}${sep}force_cache_refresh=true`, { headers: { 'X-WP-Nonce': nonce } });
		if (!r.ok()) throw new Error(`REST ${path}: ${r.status()} ${(await r.text()).slice(0, 200)}`);
		return r.json();
	};
}

const RANGE = 'after=2020-01-01T00:00:00&before=2035-12-31T23:59:59';
async function revenueTotals(rest) {
	return (await rest(`wc-analytics/reports/revenue/stats?interval=year&${RANGE}`)).totals;
}

/* ------------------------------------------------------------------ 1. Categorii (C, R, U) */

async function testCategoryCreateUpdate(admin) {
	section('Categorii de produse: creare, citire, editare');
	const p = admin;
	await p.goto(`${BASE}/wp-admin/edit-tags.php?taxonomy=product_cat&post_type=product`, { waitUntil: 'networkidle' });
	await p.fill('#tag-name', cat.name + ' (ciornă)');
	await p.fill('#tag-slug', cat.slug + '-ciorna');
	await p.fill('#tag-description', 'Categorie creată de testul automat.');
	await p.click('#submit');
	const row = p.locator('#the-list tr', { hasText: cat.name + ' (ciornă)' });
	await row.first().waitFor({ timeout: 15000 });
	cat.id = Number((await row.first().getAttribute('id')).replace('tag-', ''));
	ok(cat.id > 0, `categoria creată în admin (ID ${cat.id})`);

	let r = await p.goto(`${BASE}/categorie/${cat.slug}-ciorna/`, { waitUntil: 'networkidle' });
	ok(r.status() === 200 && text(await p.innerText('h1')).includes('(ciornă)'), 'pagina categoriei există pe site');

	await p.goto(`${BASE}/wp-admin/term.php?taxonomy=product_cat&tag_ID=${cat.id}&post_type=product`, { waitUntil: 'networkidle' });
	await p.fill('#name', cat.name);
	await p.fill('#slug', cat.slug);
	await p.fill('#description', 'Dulcețuri, miere și uleiuri ca la bunica.');
	await Promise.all([p.waitForNavigation({ waitUntil: 'networkidle' }), p.click('#edittag input[type="submit"].button-primary')]);
	ok(/actualizat|updated/i.test(await p.locator('#message').first().innerText().catch(() => '')), 'categoria editată (nume, slug, descriere)');

	r = await p.goto(`${BASE}/categorie/${cat.slug}/`, { waitUntil: 'networkidle' });
	ok(r.status() === 200 && text(await p.innerText('h1')) === cat.name, 'noul nume și URL apar pe site');
	ok((await p.innerText('main')).includes('ca la bunica'), 'descrierea categoriei afișată');
	r = await p.goto(`${BASE}/categorie/${cat.slug}-ciorna/`);
	ok(r.status() === 404 || !r.url().includes('-ciorna'), `vechiul URL nu mai e activ (${r.status()})`);
}

/* ------------------------------------------------------------------ 2. Produse din editorul admin */

async function createProduct(p, prod) {
	await p.goto(`${BASE}/wp-admin/post-new.php?post_type=product`, { waitUntil: 'networkidle' });
	await p.fill('#title', prod.title);
	await p.evaluate((html) => {
		if (window.tinymce && tinymce.get('content')) tinymce.get('content').setContent(html);
		else document.querySelector('#content').value = html;
	}, `<p>${prod.title} — produs natural de la gospodari din Moldova.</p>`);
	await p.fill('#_regular_price', String(prod.price));
	if (prod.stock) {
		await p.click('.inventory_options a');
		await p.selectOption('#_stock_status', prod.stock);
		if (prod.date) {
			ok(await p.isVisible('#_natur_available_from'), 'câmpul „Disponibil din” apare pentru „În curând”');
			await p.fill('#_natur_available_from', prod.date);
		}
	}
	await p.locator('#product_catchecklist label', { hasText: cat.name }).locator('input').check();
	await p.click('#publish');
	await p.waitForURL(/post\.php\?post=\d+&action=edit/, { timeout: 30000 });
	await p.waitForLoadState('networkidle');
	prod.id = Number(new URL(p.url()).searchParams.get('post'));
	prod.url = await p.getAttribute('#sample-permalink a', 'href');
	ok(prod.id > 0 && /\/produs\//.test(prod.url), `„${prod.title}” publicat (ID ${prod.id})`);
}

async function testAddProducts(admin) {
	section('Adăugare produse din panoul de administrare');
	// Reducerea se aplică ulterior, ca editare (testDiscount).
	for (const prod of Object.values(P)) await createProduct(admin, prod);

	await admin.goto(`${BASE}/wp-admin/edit.php?post_type=product&product_cat=${cat.slug}`, { waitUntil: 'networkidle' });
	const list = text(await admin.innerText('#the-list'));
	ok(Object.values(P).every((x) => list.includes(x.title)), 'toate cele 4 produse în lista de produse, filtrată pe categorie');
	ok(list.includes('Stoc epuizat') && list.includes(`În curând · disponibil din ${soonRo}`), `coloana Stoc: „Stoc epuizat” și „În curând · disponibil din ${soonRo}”`) || console.log('    ' + list.slice(0, 400));
}

async function testDiscount(admin, shopper) {
	section('Reducere: preț vechi / preț nou');
	const s = P.sale;
	await shopper.goto(s.url, { waitUntil: 'networkidle' });
	ok(text(await shopper.innerText('.summary .price')) === leiV(s.price) && !(await shopper.locator(BADGE).count()), `înainte de reducere: ${leiV(s.price)}, fără insignă`);

	await admin.goto(`${BASE}/wp-admin/post.php?post=${s.id}&action=edit`, { waitUntil: 'networkidle' });
	await admin.fill('#_sale_price', String(s.sale));
	await Promise.all([admin.waitForNavigation({ waitUntil: 'networkidle' }), admin.click('#publish')]);
	ok(/actualizat|updated/i.test(await admin.innerText('#message')), 'prețul redus salvat din editor');

	await shopper.goto(s.url, { waitUntil: 'networkidle' });
	const del = text(await shopper.innerText('.summary .price del'));
	const ins = text(await shopper.innerText('.summary .price ins'));
	ok(del === leiV(s.price), `preț vechi tăiat: ${del}`);
	ok(ins === leiV(s.sale), `preț nou: ${ins}`);
	ok(await shopper.$eval('.summary .price del', (e) => getComputedStyle(e).textDecorationLine.includes('line-through')), 'prețul vechi e vizual tăiat');
	const badge = text(await shopper.locator(BADGE).first().innerText());
	ok(badge === '-25%' && (await shopper.locator(BADGE).first().isVisible()), `insigna de reducere pe pagina produsului: ${badge}`);

	await shopper.goto(`${BASE}/categorie/${cat.slug}/`, { waitUntil: 'networkidle' });
	const card = shopper.locator('li.product', { hasText: s.title });
	ok(text(await card.locator('.price del').innerText()) === leiV(s.price) && text(await card.locator('.price ins').innerText()) === leiV(s.sale), 'pe cardul din categorie: preț vechi + preț nou');
	ok(text(await card.locator(BADGE).first().innerText()) === '-25%', 'insigna „-25%” pe cardul din categorie');
	await shopper.goto(`${BASE}/magazin/?orderby=date`, { waitUntil: 'networkidle' });
	ok(text(await shopper.locator('li.product', { hasText: s.title }).locator(BADGE).first().innerText()) === '-25%', 'insigna apare și în magazin');
}

async function storeCart(p) {
	const r = await p.request.get(`${BASE}/wp-json/wc/store/v1/cart`);
	return { cart: await r.json(), nonce: r.headers().nonce };
}

async function testUnavailable(shopper) {
	section('Produse fără stoc și „În curând”');
	const cases = [
		[P.oos, 'Stoc epuizat', 'out-of-stock'],
		[P.soon, `În curând · disponibil din ${soonRo}`, 'coming-soon'],
	];
	for (const [prod, label, cls] of cases) {
		await shopper.goto(prod.url, { waitUntil: 'networkidle' });
		ok(text(await shopper.innerText(`.summary .stock.${cls}`).catch(() => '')) === label, `${prod.title}: „${label}”`);
		ok(!(await shopper.isVisible('form.cart button[name="add-to-cart"], .single_add_to_cart_button')), 'fără buton „Adaugă în coș”');
		ok(text(await shopper.innerText('.summary .price')) === leiV(prod.price), `prețul rămâne vizibil (${leiV(prod.price)})`);
		const ld = await shopper.evaluate(() => [...document.querySelectorAll('script[type="application/ld+json"]')].map((s) => s.textContent).join(''));
		const schema = prod === P.soon ? 'PreOrder' : 'OutOfStock';
		ok(ld.includes(`schema.org/${schema}`), `date structurate Google: ${schema}`);

		await shopper.goto(`${BASE}/?add-to-cart=${prod.id}`, { waitUntil: 'networkidle' });
		const { cart, nonce } = await storeCart(shopper);
		ok(cart.items_count === 0, 'adăugarea prin link ?add-to-cart e refuzată');
		const api = await shopper.request.post(`${BASE}/wp-json/wc/store/v1/cart/add-item`, { headers: { Nonce: nonce }, data: { id: prod.id, quantity: 1 } });
		ok(api.status() >= 400, `adăugarea prin Store API e refuzată (${api.status()})`);
	}

	await shopper.goto(`${BASE}/categorie/${cat.slug}/`, { waitUntil: 'networkidle' });
	const cards = shopper.locator('ul.products li.product');
	ok((await cards.count()) === 4, 'categoria listează toate cele 4 produse (inclusiv cele indisponibile)');
	ok(text(await cards.filter({ hasText: P.oos.title }).locator('.nt-badge--oos').textContent()) === 'Stoc epuizat', 'card: insigna „Stoc epuizat”');
	ok(text(await cards.filter({ hasText: P.soon.title }).locator('.natur-soon-badge').textContent()) === 'În curând', 'card: insigna „În curând”');
	ok(!(await cards.filter({ hasText: P.soon.title }).locator('.add_to_cart_button').count()), 'card „În curând”: fără adăugare rapidă în coș');
	ok(shopper.jsErrors.length === 0, 'fără erori JS ' + shopper.jsErrors.join(' | '));
}

/* ------------------------------------------------------------------ 3. Client real */

async function addToCart(p, prod, qty) {
	await p.goto(prod.url, { waitUntil: 'networkidle' });
	await p.fill('input.qty', String(qty));
	await Promise.all([p.waitForNavigation({ waitUntil: 'networkidle' }), p.click('button[name="add-to-cart"]')]);
}

/* Comanda într-un singur pas, fără cont: nume, telefon, adresă și e-mailul opțional (pentru confirmări). */
async function checkout(p) {
	await p.goto(`${BASE}/finalizare-comanda/`, { waitUntil: 'networkidle' });
	await p.fill('#billing_first_name', `${customer.first} ${customer.last}`);
	await p.fill('#billing_phone', '069123456');
	await p.check('#billing_state_city');
	await p.fill('#billing_address_1', 'bd. Dacia 27, ap. 5');
	if (!(await p.isVisible('#billing_email'))) await p.click('.nt-ck__more summary');
	await p.fill('#billing_email', customer.email);
	await p.waitForTimeout(2500);
	const pay = text(await p.innerText('#payment .wc_payment_methods'));
	ok(/Plata la livrare/.test(pay), `metoda de plată: „${pay.slice(0, 60)}”`);
	const total = text(await p.innerText('.nt-ck-sum__total dd'));
	await p.click('#place_order');
	await p.waitForURL(/order-received|comanda-primita/, { timeout: 30000 });
	await p.waitForLoadState('networkidle');
	const id = Number((p.url().match(/(?:order-received|comanda-primita)\/(\d+)/) || [])[1]);
	return { id, total };
}

async function testCustomerOrder(p) {
	section('Client fără cont: coș și comandă cu plata la livrare');
	await p.goto(`${BASE}/categorie/${cat.slug}/`, { waitUntil: 'networkidle' });
	await addToCart(p, P.std, 1);
	await addToCart(p, P.sale, 2);
	await p.goto(`${BASE}/cos/`, { waitUntil: 'networkidle' });
	ok(/\/finalizare-comanda\/$/.test(p.url()), 'coșul duce direct la pagina de comandă');
	const cartTxt = text(await p.innerText('.nt-ck-sum'));
	ok(cartTxt.includes(P.std.title) && cartTxt.includes(P.sale.title), 'ambele produse în comandă');
	ok(cartTxt.includes(leiV(P.sale.sale)) && text(await p.innerText('.nt-ck-item del')).includes(leiV(P.sale.price)), 'comanda arată prețul redus (și pe cel vechi tăiat)');
	const { cart } = await storeCart(p);
	ok(cart.items_count === 3 && Number(cart.totals.total_items) / 100 === 400, `subtotal coș: ${Number(cart.totals.total_items) / 100} lei (100 + 2 × 150)`);

	const o = await checkout(p);
	P.order = o;
	ok(o.id > 0, `comanda plasată: #${o.id}`);
	ok(o.total === leiV(450), `total cu livrare 50 lei: ${o.total}`);
	const conf = text(await p.innerText('main'));
	ok(/Mulțumim|Mulţumim/i.test(conf) && conf.includes(String(o.id)), 'pagina de confirmare cu numărul comenzii');
	ok(/Plata la livrare/.test(conf), 'confirmarea menționează plata la livrare');

	const mail = await waitMail(customer.email, /primit/i);
	if (MAILPIT) ok(!!mail && mail.Text.includes(String(o.id)), `e-mail de confirmare către client: „${mail && mail.Subject}”`);
	const adminMail = MAILPIT && (await waitMail('', new RegExp(`(nou|new).*#?${o.id}|#?${o.id}.*(nou|new)`, 'i'), 15000).catch(() => null));
	if (MAILPIT) ok(!!adminMail, `e-mail „comandă nouă” către magazin: „${adminMail && adminMail.Subject}”`);

	section('Client: a doua comandă (va fi anulată de magazin)');
	await addToCart(p, P.std, 1);
	P.order2 = await checkout(p);
	ok(P.order2.id > 0 && P.order2.total === leiV(150), `comanda #${P.order2.id}: ${P.order2.total}`);
	ok(p.jsErrors.length === 0, 'fără erori JS ' + p.jsErrors.join(' | '));
}

/* ------------------------------------------------------------------ 4. Procesarea comenzilor în admin */

async function setOrderStatus(p, id, status) {
	await p.goto(`${BASE}/wp-admin/admin.php?page=wc-orders&action=edit&id=${id}`, { waitUntil: 'networkidle' });
	await p.evaluate((s) => jQuery('#order_status').val(s).trigger('change'), status);
	await Promise.all([p.waitForNavigation({ waitUntil: 'networkidle' }), p.click('button[name="save"].save_order')]);
}

async function testAdminOrders(admin) {
	section('Magazin: procesarea comenzii');
	const p = admin;
	const o = P.order;
	await p.goto(`${BASE}/wp-admin/admin.php?page=wc-orders`, { waitUntil: 'networkidle' });
	ok(text(await p.innerText('#the-list')).includes(`#${o.id}`), 'comanda apare în lista de comenzi');
	await p.goto(`${BASE}/wp-admin/admin.php?page=wc-orders&action=edit&id=${o.id}`, { waitUntil: 'networkidle' });
	const info = text(await p.innerText('#order_data'));
	ok(/Plata la livrare/.test(info), 'plata: Plata la livrare');
	ok(info.includes('bd. Dacia 27') && info.includes('069123456'), 'adresa și telefonul clientului');
	const items = text(await p.innerText('#order_line_items'));
	ok(items.includes(P.std.title) && items.includes(P.sale.title) && items.includes(lei(300)), 'produsele și prețurile din comandă (2 × 150 = 300 lei)');
	ok(text(await p.innerText('.wc-order-totals')).includes(lei(450)), 'total comandă 450 lei');

	await setOrderStatus(p, o.id, 'wc-completed');
	ok((await p.$eval('#order_status', (s) => s.value)) === 'wc-completed', 'comanda marcată „Finalizată” (livrată și achitată)');
	const done = await waitMail(customer.email, /pe drum|finalizat|complet|livrat/i);
	if (MAILPIT) ok(!!done, `e-mail „comandă finalizată” către client: „${done && done.Subject}”`);

	section('Magazin: rambursare parțială (1 × miere returnată)');
	await p.click('button.refund-items');
	const line = p.locator('#order_line_items tr.item', { hasText: P.sale.title });
	await line.locator('input.refund_order_item_qty').fill('1');
	await line.locator('input.refund_order_item_qty').dispatchEvent('change');
	await p.waitForFunction(() => document.querySelector('#refund_amount').value !== '' && parseFloat(document.querySelector('#refund_amount').value.replace(',', '.')) > 0);
	await p.fill('#refund_reason', 'Borcan returnat (test e2e)');
	p.once('dialog', (d) => d.accept());
	await Promise.all([p.waitForNavigation({ waitUntil: 'networkidle', timeout: 30000 }), p.click('button.do-manual-refund')]);
	const totals = text(await p.innerText('.wc-order-totals-items, .wc-order-data-row'));
	ok(totals.includes(lei(150)) && /Rambursat|Refunded/i.test(totals), 'rambursare 150 lei înregistrată');
	const refundMail = await waitMail(customer.email, /rambursat/i);
	if (MAILPIT) ok(!!refundMail, `e-mail de rambursare către client: „${refundMail && refundMail.Subject}”`);

	section('Magazin: anularea celei de-a doua comenzi');
	await setOrderStatus(p, P.order2.id, 'wc-cancelled');
	ok((await p.$eval('#order_status', (s) => s.value)) === 'wc-cancelled', `comanda #${P.order2.id} anulată`);

	const errs = p.jsErrors.filter((e) => !/ResizeObserver/.test(e));
	ok(errs.length === 0, 'fără erori JS în admin ' + errs.join(' | '));
}

/* ------------------------------------------------------------------ 5. Statistici */

async function testAnalytics(admin, before) {
	section('Statistici (WooCommerce Analytics)');
	const rest = await restClient(admin);

	// Importul în statistici rulează prin Action Scheduler; așteptăm până apare comanda.
	let orders = [];
	for (let i = 0; i < 40; i++) {
		orders = await rest(`wc-analytics/reports/orders?per_page=100&extended_info=true&${RANGE}`);
		const imported = orders.find((x) => x.order_id === P.order.id);
		if (imported && imported.status === 'completed' && orders.some((x) => x.net_total < 0)) break;
		try { execSync('ddev wp action-scheduler run --group=wc-admin-data --batch-size=50', { cwd: __dirname, stdio: 'pipe' }); } catch { /* fără ddev: doar așteptăm cron-ul */ }
		await new Promise((r) => setTimeout(r, 1500));
	}
	const row = orders.find((x) => x.order_id === P.order.id);
	ok(!!row, `comanda #${P.order.id} în raportul de comenzi`);
	if (row) {
		ok(row.status === 'completed', `status în statistici: ${row.status}`);
		ok(row.num_items_sold === 3 && row.net_total === 400, `articole vândute: ${row.num_items_sold}, venit net ${row.net_total} lei`);
		const refund = orders.find((x) => x.order_id !== P.order.id && x.net_total < 0 && x.status === 'completed');
		ok(refund && refund.net_total === -150 && refund.num_items_sold === -1, `rambursarea apare ca rând separat: #${refund && refund.order_id}, ${refund && refund.net_total} lei`);
		ok(row.customer_type === 'new', `tip client: ${row.customer_type}`);
	}
	ok(!orders.some((x) => x.order_id === P.order2.id), 'comanda anulată e exclusă din statistici');

	const after = await revenueTotals(rest);
	const d = (k) => Math.round((after[k] - before[k]) * 100) / 100;
	console.log(`    Δ venituri: comenzi ${d('orders_count')}, vânzări brute ${d('gross_sales')}, rambursări ${d('refunds')}, venit net ${d('net_revenue')}, livrare ${d('shipping')}, total ${d('total_sales')}`);
	ok(d('orders_count') === 1, 'Venituri: +1 comandă (cea anulată nu se numără)');
	ok(d('gross_sales') === 400, 'Venituri: vânzări brute +400 lei');
	ok(d('refunds') === 150, 'Venituri: rambursări +150 lei');
	ok(d('net_revenue') === 250, 'Venituri: venit net +250 lei');
	ok(d('shipping') === 50, 'Venituri: livrare +50 lei');
	ok(d('total_sales') === 300, 'Venituri: total vânzări +300 lei (250 + 50 livrare)');

	const prods = await rest(`wc-analytics/reports/products?products=${P.std.id},${P.sale.id}&extended_info=true&${RANGE}`);
	const ps = Object.fromEntries(prods.map((x) => [x.product_id, x]));
	ok(ps[P.std.id] && ps[P.std.id].items_sold === 1 && ps[P.std.id].net_revenue === 100, `Produse: ${P.std.title} — 1 buc., 100 lei`);
	ok(ps[P.sale.id] && ps[P.sale.id].items_sold === 1 && ps[P.sale.id].net_revenue === 150, `Produse: ${P.sale.title} — 2 buc. − 1 returnată = 1 buc., 150 lei`);

	const cats = await rest(`wc-analytics/reports/categories?categories=${cat.id}&${RANGE}`);
	ok(cats[0] && cats[0].items_sold === 2 && cats[0].net_revenue === 250, `Categorii: ${cat.name} — ${cats[0] && cats[0].items_sold} buc., ${cats[0] && cats[0].net_revenue} lei, ${cats[0] && cats[0].orders_count} comenzi/rambursări`);

	const custs = await rest(`wc-analytics/reports/customers?per_page=100&search=${encodeURIComponent(customer.last)}&${RANGE}`);
	const c = custs.find((x) => x.email === customer.email);
	ok(c && c.orders_count === 1 && c.total_spend === 300 && !c.user_id, `Clienți: ${c && c.name} — ${c && c.orders_count} comandă, ${c && c.total_spend} lei cheltuiți (450 − 150 rambursat), fără cont`);

	const stock = await rest(`wc-analytics/reports/stock?type=outofstock&per_page=100`);
	ok(stock.some((x) => x.id === P.oos.id), 'Stoc: produsul epuizat apare în raportul „Stoc epuizat”');
	const allStock = await rest(`wc-analytics/reports/stock?per_page=100&search=${encodeURIComponent('E2E')}`).catch(() => []);
	const soon = allStock.find((x) => x.id === P.soon.id);
	if (soon) ok(soon.stock_status === 'comingsoon', 'Stoc: produsul „În curând” are starea proprie');

	await admin.goto(`${BASE}/wp-admin/admin.php?page=wc-admin&path=%2Fanalytics%2Frevenue&period=year&compare=previous_year`, { waitUntil: 'networkidle' });
	await admin.waitForSelector('.woocommerce-summary', { timeout: 60000 });
	await admin.waitForSelector('.woocommerce-table__table tbody tr', { timeout: 60000 });
	ok(!(await admin.isVisible('.woocommerce-analytics__error, .components-notice.is-error')), 'pagina Analiză → Venituri se încarcă fără erori');
	await admin.goto(`${BASE}/wp-admin/admin.php?page=wc-admin&path=%2Fanalytics%2Fproducts&period=year`, { waitUntil: 'networkidle' });
	await admin.locator('.woocommerce-table__table', { hasText: P.sale.title }).waitFor({ timeout: 60000 }).then(() => ok(true, 'Analiză → Produse afișează produsele vândute'), () => ok(false, 'Analiză → Produse afișează produsele vândute'));
	return rest;
}

/* ------------------------------------------------------------------ 6. Ștergere categorie + raport */

async function testCategoryDelete(admin) {
	section('Categorii de produse: ștergere');
	const p = admin;
	await p.goto(`${BASE}/wp-admin/term.php?taxonomy=product_cat&tag_ID=${cat.id}&post_type=product`, { waitUntil: 'networkidle' });
	p.once('dialog', (d) => d.accept());
	await Promise.all([p.waitForNavigation({ waitUntil: 'networkidle' }), p.click('#delete-link a.delete')]);
	await p.goto(`${BASE}/wp-admin/edit-tags.php?taxonomy=product_cat&post_type=product&s=${encodeURIComponent(cat.name)}`, { waitUntil: 'networkidle' });
	ok(!text(await p.innerText('#the-list')).includes(cat.name), 'categoria nu mai apare în admin');
	const r = await p.goto(`${BASE}/categorie/${cat.slug}/`);
	ok(r.status() === 404, `pagina categoriei returnează 404 (${r.status()})`);
	const r2 = await p.goto(P.std.url, { waitUntil: 'networkidle' });
	const meta = text(await p.innerText('.product_meta').catch(() => ''));
	ok(r2.status() === 200 && /Diverse/.test(meta), `produsele rămân, mutate în categoria implicită (${r2.status()}, „${meta}”)`);
}

async function report(rest) {
	section('Raport: întreaga activitate a magazinului (de la început)');
	const t = await revenueTotals(rest);
	const os = (await rest(`wc-analytics/reports/orders/stats?${RANGE}`)).totals;
	const top = await rest(`wc-analytics/reports/products?orderby=items_sold&order=desc&per_page=5&extended_info=true&${RANGE}`);
	const cats = await rest(`wc-analytics/reports/categories?orderby=net_revenue&order=desc&per_page=5&extended_info=true&${RANGE}`);
	const cust = (await rest(`wc-analytics/reports/customers/stats?${RANGE}`)).totals;
	const stock = (await rest('wc-analytics/reports/stock/stats')).totals;
	const all = await rest(`wc-analytics/reports/orders?per_page=100&${RANGE}`);
	const ro = { completed: 'finalizate', processing: 'în procesare', 'on-hold': 'în așteptare', refunded: 'rambursate integral' };
	const byStatus = all.reduce((a, o) => {
		const k = o.net_total < 0 ? 'rambursări' : ro[o.status] || o.status;
		a[k] = (a[k] || 0) + 1;
		return a;
	}, {});
	const f = (n) => lei(Number(n || 0));
	console.log(`    Comenzi: ${t.orders_count} (${Object.entries(byStatus).map(([k, v]) => `${k}: ${v}`).join(', ')}) · articole vândute: ${os.num_items_sold}`);
	console.log(`    Vânzări brute ${f(t.gross_sales)} · rambursări ${f(t.refunds)} · reduceri cupon ${f(t.coupons)} · venit net ${f(t.net_revenue)} · livrare ${f(t.shipping)} · total ${f(t.total_sales)}`);
	console.log(`    Valoare medie comandă: ${f(os.avg_order_value)} · clienți: ${cust.customers_count} · cheltuială medie/client: ${f(cust.avg_total_spend)}`);
	console.log(`    Top produse: ${top.map((x) => `${x.extended_info.name} (${x.items_sold} buc., ${f(x.net_revenue)})`).join('; ') || '—'}`);
	console.log(`    Top categorii: ${cats.map((x) => `${x.extended_info.name} (${x.items_sold} buc., ${f(x.net_revenue)})`).join('; ') || '—'}`);
	console.log(`    Stoc: ${stock.products} produse · în stoc ${stock.instock} · epuizat ${stock.outofstock} · precomandă ${stock.onbackorder} · stoc redus ${stock.lowstock}`);
	ok(t.orders_count >= 1 && stock.products > 0, 'raportul general se generează');
}

/* ------------------------------------------------------------------ */

(async () => {
	console.log(`Testare ${BASE} (rulare ${RUN})`);
	ensureAdmin();
	const b = await launch();
	try {
		const admin = await newPage(b, { viewport: { width: 1440, height: 900 } });
		await login(admin, ADMIN_USER, ADMIN_PASS);
		ok(admin.url().includes('/wp-admin'), `autentificare admin (${ADMIN_USER})`);
		const before = await revenueTotals(await restClient(admin));

		const shopper = await newPage(b);
		await testCategoryCreateUpdate(admin);
		await testAddProducts(admin);
		await testDiscount(admin, shopper);
		await testUnavailable(shopper);
		await testCustomerOrder(await newPage(b));
		await testAdminOrders(admin);
		const rest = await testAnalytics(admin, before);
		await testCategoryDelete(admin);
		await report(rest);
	} catch (e) {
		ok(false, 'excepție: ' + e.stack);
		const dir = require('os').tmpdir();
		for (const [i, pg] of b.contexts().flatMap((c) => c.pages()).entries()) {
			await pg.screenshot({ path: `${dir}/natur-e2e-${RUN}-${i}.png`, fullPage: true }).catch(() => {});
			console.log(`    captură: ${dir}/natur-e2e-${RUN}-${i}.png (${pg.url()})`);
		}
	}
	await b.close();
	const failed = failures();
	console.log(failed ? `\n✘ ${failed} verificări eșuate` : '\n✔ Toate verificările au trecut');
	console.log('Datele de test rămân pentru inspecție; ștergere: npm run cleanup');
	process.exit(failed ? 1 : 0);
})();
