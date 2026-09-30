/**
 * Utilitare comune pentru testele e2e Natur.MD.
 *
 * Dacă domeniul local (ex. natur.ddev) nu e în /etc/hosts, cererile lui sunt
 * direcționate spre 127.0.0.1 (routerul ddev) atât în Node, cât și în Chrome.
 */
const dns = require('dns');

process.env.NODE_TLS_REJECT_UNAUTHORIZED = '0'; // certificat local ddev
const BASE = (process.env.BASE || 'https://natur.ddev').replace(/\/$/, '');
const HOST = new URL(BASE).hostname;

/* Instalat înainte de încărcarea Playwright, care își păstrează propria referință la dns.lookup. */
const lookup = dns.lookup;
dns.lookup = (host, opts, cb) => {
	if (typeof opts === 'function') [cb, opts] = [opts, {}];
	if (host !== HOST) return lookup(host, opts, cb);
	lookup(host, opts, (err, ...res) => {
		if (!err) return cb(null, ...res);
		return opts && opts.all ? cb(null, [{ address: '127.0.0.1', family: 4 }]) : cb(null, '127.0.0.1', 4);
	});
};
const lookupAsync = dns.promises.lookup;
dns.promises.lookup = (host, opts = {}) =>
	lookupAsync(host, opts).catch((err) => {
		if (host !== HOST) throw err;
		if (opts.family === 6) return opts.all ? [] : Promise.reject(err);
		return opts.all ? [{ address: '127.0.0.1', family: 4 }] : { address: '127.0.0.1', family: 4 };
	});
const { chromium } = require('playwright-core');

let failed = 0;
const ok = (cond, msg) => {
	console.log(`${cond ? '  ✔' : '  ✘'} ${msg}`);
	if (!cond) failed++;
	return cond;
};
const section = (t) => console.log(`\n▶ ${t}`);
const failures = () => failed;

async function mapHostIfNeeded() {
	try {
		await lookupAsync(HOST);
		return [];
	} catch {
		console.log(`(${HOST} nu e în /etc/hosts — folosesc 127.0.0.1)`);
		return [`--host-resolver-rules=MAP ${HOST} 127.0.0.1`];
	}
}

async function launch() {
	const args = await mapHostIfNeeded();
	return chromium.launch({ channel: 'chrome', args });
}

async function newPage(browser, opts = {}) {
	const ctx = await browser.newContext({ viewport: { width: 1366, height: 900 }, ignoreHTTPSErrors: true, locale: 'ro-RO', ...opts });
	const page = await ctx.newPage();
	page.jsErrors = [];
	page.on('pageerror', (e) => page.jsErrors.push(`${page.url()}: ${e.message}`));
	return page;
}

module.exports = { BASE, HOST, ok, section, failures, launch, newPage };
