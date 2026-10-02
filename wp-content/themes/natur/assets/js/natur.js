/**
 * Natur.MD — comportamentul temei (fără dependențe; jQuery doar pentru evenimentele WooCommerce).
 */
(function () {
	'use strict';

	var NT = window.NT || {};
	var doc = document;
	var root = doc.documentElement;
	var body = doc.body;
	var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
	var $ = function (s, c) { return (c || doc).querySelector(s); };
	var $$ = function (s, c) { return Array.prototype.slice.call((c || doc).querySelectorAll(s)); };
	var jq = window.jQuery;

	function esc(s) {
		return String(s).replace(/[&<>"']/g, function (c) {
			return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
		});
	}
	function decode(html) {
		var t = doc.createElement('textarea');
		t.innerHTML = html;
		return t.value;
	}
	/* 460 → „460 lei”, 47.5 → „47,50 lei”, 1200 → „1 200 lei” */
	function money(v) {
		var n = Number(v);
		var s = (Number.isInteger(n) ? String(n) : n.toFixed(2).replace('.', ','));
		return s.replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + ' lei';
	}

	/* ------------------------------------------------------------ Antet: stare la derulare, bara mobilă */
	var header = $('[data-nt-header]');
	var tabbar = $('.nt-tabbar');
	var lastY = window.scrollY;
	var ticking = false;
	function onScroll() {
		var y = window.scrollY;
		if (header) header.classList.toggle('is-scrolled', y > 24);
		if (tabbar) {
			if (y > lastY + 6 && y > 240) tabbar.classList.add('is-hidden');
			else if (y < lastY - 6 || y < 240) tabbar.classList.remove('is-hidden');
		}
		lastY = y;
		ticking = false;
	}
	window.addEventListener('scroll', function () {
		if (!ticking) { ticking = true; requestAnimationFrame(onScroll); }
	}, { passive: true });
	onScroll();

	/* ------------------------------------------------------------ Bara de anunțuri (mesaje care se schimbă) */
	var rot = $('[data-nt-rotator]');
	if (rot && !reduced) {
		var msgs = $$('p', rot);
		var cur = Math.max(0, msgs.findIndex(function (m) { return m.classList.contains('is-active'); }));
		var paused = false;
		rot.addEventListener('mouseenter', function () { paused = true; });
		rot.addEventListener('mouseleave', function () { paused = false; });
		if (msgs.length > 1) {
			setInterval(function () {
				if (paused || doc.hidden) return;
				var prev = msgs[cur];
				cur = (cur + 1) % msgs.length;
				prev.classList.remove('is-active');
				prev.classList.add('is-leaving');
				setTimeout(function () { prev.classList.remove('is-leaving'); }, 700);
				msgs[cur].classList.add('is-active');
			}, 4200);
		}
	}

	/* ------------------------------------------------------------ Panouri: căutare, meniu, coș */
	var openPanel = null;
	var lastFocus = null;

	function lockScroll(on) {
		if (on) {
			var sb = window.innerWidth - root.clientWidth;
			root.style.overflow = 'hidden';
			if (sb > 0) body.style.paddingRight = sb + 'px';
		} else {
			root.style.overflow = '';
			body.style.paddingRight = '';
		}
	}
	function setExpanded(id, val) {
		$$('[aria-controls="' + id + '"]').forEach(function (b) { b.setAttribute('aria-expanded', val ? 'true' : 'false'); });
	}
	function open(panel) {
		if (!panel) return;
		if (openPanel && openPanel !== panel) close(true);
		lastFocus = doc.activeElement;
		panel.hidden = false;
		panel.offsetHeight; // reflow pentru tranziție
		panel.classList.add('is-open');
		openPanel = panel;
		setExpanded(panel.id, true);
		lockScroll(true);
		// Doar căutarea primește focus în câmp (pe telefon, meniul nu deschide tastatura).
		var focusEl = (panel.id === 'nt-search' && panel.querySelector('input[type="search"]')) || panel.querySelector('[data-nt-close]:not(.nt-scrim)') || panel;
		focusEl.focus({ preventScroll: true }); // imediat: literele tastate după „/” nu se pierd
	}
	function close(silent) {
		var panel = openPanel;
		if (!panel) return;
		panel.classList.remove('is-open');
		setExpanded(panel.id, false);
		openPanel = null;
		var done = function () { if (!panel.classList.contains('is-open')) panel.hidden = true; };
		reduced ? done() : setTimeout(done, 480);
		if (!silent) {
			lockScroll(false);
			if (lastFocus && lastFocus.focus) lastFocus.focus({ preventScroll: true });
		}
	}
	var noDrawer = body.classList.contains('woocommerce-cart') || body.classList.contains('woocommerce-checkout');
	doc.addEventListener('click', function (e) {
		var opener = e.target.closest('[data-nt-open]');
		if (opener) {
			var name = opener.getAttribute('data-nt-open');
			if (name === 'cart' && noDrawer) return; // pe Coș / Finalizare linkul duce la pagina coșului
			var panel = doc.getElementById('nt-' + name);
			if (panel) {
				e.preventDefault();
				panel === openPanel ? close() : open(panel);
			}
			return;
		}
		if (e.target.closest('[data-nt-close]')) { e.preventDefault(); close(); }
	});
	doc.addEventListener('keydown', function (e) {
		if (e.key === 'Escape') {
			if (openPanel) { close(); return; }
			$$('.nt-nav__item.is-open').forEach(closeSub);
		}
		/* „/” deschide căutarea (când nu se scrie într-un câmp) */
		if (e.key === '/' && !openPanel && !/^(INPUT|TEXTAREA|SELECT)$/.test(doc.activeElement.tagName) && !doc.activeElement.isContentEditable) {
			e.preventDefault();
			open(doc.getElementById('nt-search'));
		}
		/* Focusul rămâne în panoul deschis */
		if (e.key === 'Tab' && openPanel) {
			var f = $$('a[href], button:not([disabled]), input:not([type="hidden"]), select, textarea, summary, [tabindex]:not([tabindex="-1"])', openPanel)
				.filter(function (el) { return el.offsetParent !== null && !el.classList.contains('nt-scrim'); });
			if (!f.length) return;
			var first = f[0], last = f[f.length - 1];
			if (e.shiftKey && doc.activeElement === first) { e.preventDefault(); last.focus(); }
			else if (!e.shiftKey && doc.activeElement === last) { e.preventDefault(); first.focus(); }
		}
	});

	/* ------------------------------------------------------------ Submeniuri (tastatură / atingere) */
	function closeSub(li) {
		li.classList.remove('is-open');
		var b = $('.nt-nav__toggle', li);
		if (b) b.setAttribute('aria-expanded', 'false');
	}
	$$('.nt-nav__toggle').forEach(function (btn) {
		btn.addEventListener('click', function (e) {
			e.preventDefault();
			var li = btn.closest('.nt-nav__item');
			var isOpen = li.classList.contains('is-open');
			$$('.nt-nav__item.is-open').forEach(closeSub);
			if (!isOpen) { li.classList.add('is-open'); btn.setAttribute('aria-expanded', 'true'); }
		});
	});
	doc.addEventListener('click', function (e) {
		if (!e.target.closest('.nt-nav__item')) $$('.nt-nav__item.is-open').forEach(closeSub);
	});
	/* Pe ecrane tactile, primul tap pe „Magazin” deschide panoul de categorii */
	$$('.nt-nav__item.has-sub > .nt-nav__link').forEach(function (a) {
		a.addEventListener('touchend', function (e) {
			var li = a.closest('.nt-nav__item');
			if (!li.classList.contains('is-open')) {
				e.preventDefault();
				$$('.nt-nav__item.is-open').forEach(closeSub);
				li.classList.add('is-open');
			}
		});
	});

	/* ------------------------------------------------------------ Căutare live (Store API WooCommerce) */
	var sPanel = doc.getElementById('nt-search');
	if (sPanel) {
		var input = $('.nt-search__input', sPanel);
		var out = $('#nt-search-results', sPanel);
		var popular = $('[data-nt-popular]', sPanel);
		var timer = null;
		var ctrl = null;
		var active = -1;

		var highlight = function (name, q) {
			var safe = esc(name);
			var words = q.trim().split(/\s+/).filter(function (w) { return w.length > 1; }).map(function (w) { return w.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'); });
			if (!words.length) return safe;
			return safe.replace(new RegExp('(' + words.join('|') + ')', 'gi'), '<mark>$1</mark>');
		};
		var render = function (items, total, q) {
			active = -1;
			if (!items.length) {
				out.innerHTML = '<div class="nt-sres__empty"><strong>Nimic pentru „' + esc(q) + '”</strong>Încearcă un cuvânt mai scurt sau una dintre căutările populare.</div>';
				popular.hidden = false;
				return;
			}
			popular.hidden = true;
			var html = '<ul class="nt-sres">';
			items.forEach(function (p) {
				var img = p.images && p.images[0] ? (p.images[0].thumbnail || p.images[0].src) : '';
				var minor = p.prices ? Number(p.prices.currency_minor_unit || 0) : 0;
				var price = p.prices && p.prices.price ? money(Number(p.prices.price) / Math.pow(10, minor)) : '';
				html += '<li><a href="' + esc(p.permalink) + '">' + (img ? '<img src="' + esc(img) + '" alt="" loading="lazy">' : '<img alt="">') +
					'<span><span class="nt-sres__name">' + highlight(decode(p.name), q) + '</span><span class="nt-sres__price">' + price + '</span></span></a></li>';
			});
			html += '</ul><div class="nt-sres__foot"><span>' + total + (total === 1 ? ' produs găsit' : ' produse găsite') + '</span>' +
				'<a class="nt-link-arrow" href="' + esc(NT.home + '?s=' + encodeURIComponent(q) + '&post_type=product') + '">Vezi toate rezultatele <svg class="nt-i" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a></div>';
			out.innerHTML = html;
		};
		var search = function (q) {
			if (ctrl) ctrl.abort();
			if (q.trim().length < 2) { out.innerHTML = ''; popular.hidden = false; return; }
			ctrl = 'AbortController' in window ? new AbortController() : null;
			out.innerHTML = '<ul class="nt-sres"><li class="nt-sres__skeleton"></li><li class="nt-sres__skeleton"></li><li class="nt-sres__skeleton"></li></ul>';
			fetch(NT.storeApi + 'products?per_page=6&search=' + encodeURIComponent(q), { signal: ctrl ? ctrl.signal : undefined, credentials: 'same-origin' })
				.then(function (r) { return r.json().then(function (j) { return { items: j, total: parseInt(r.headers.get('X-WP-Total') || j.length, 10) }; }); })
				.then(function (res) { if (Array.isArray(res.items)) render(res.items, res.total, q); })
				.catch(function (err) { if (err.name !== 'AbortError') out.innerHTML = ''; });
		};
		input.addEventListener('input', function () {
			clearTimeout(timer);
			timer = setTimeout(function () { search(input.value); }, 220);
		});
		input.addEventListener('keydown', function (e) {
			var links = $$('.nt-sres a', out);
			if (!links.length) return;
			if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
				e.preventDefault();
				active = (active + (e.key === 'ArrowDown' ? 1 : -1) + links.length) % links.length;
				links.forEach(function (l, i) { l.classList.toggle('is-active', i === active); });
				links[active].scrollIntoView({ block: 'nearest' });
			} else if (e.key === 'Enter' && active > -1) {
				e.preventDefault();
				window.location.href = links[active].href;
			}
		});
		/* Căutările populare completează câmpul și caută pe loc */
		$$('[data-nt-q]', sPanel).forEach(function (chip) {
			chip.addEventListener('click', function (e) {
				e.preventDefault();
				input.value = chip.getAttribute('data-nt-q');
				search(input.value);
				input.focus();
			});
		});
	}

	/* ------------------------------------------------------------ Apariție la derulare */
	var revealSel = [
		'.nt-sec__head', '.nt-cat', '.nt-step', '.nt-ptabs', '.nt-spot', '.nt-sart', '.nt-story__copy',
		'.nt-review', '.nt-recipe', '.nt-help','.nt-footer__grid > *', 'ul.products > li.product', '.nt-ahero', '.nt-catchips',
		'.woocommerce-tabs', 'section.related', '.nt.page:not(.home) .entry-content > *', '.nt-cb', '.nt-faq details'
	].join(',');
	if ('IntersectionObserver' in window && !reduced) {
		var io = new IntersectionObserver(function (entries) {
			entries.forEach(function (en) {
				if (en.isIntersecting) {
					en.target.classList.add('is-in');
					io.unobserve(en.target);
					if (en.target.querySelector('[data-nt-count]')) countUp(en.target);
				}
			});
		}, { rootMargin: '0px 0px -8% 0px', threshold: .08 });
		var vh = window.innerHeight;
		var groups = new Map();
		$$(revealSel).forEach(function (el) {
			if (el.getBoundingClientRect().top < vh * .92) return; // vizibil deja: fără animație
			var parent = el.parentElement;
			var idx = groups.get(parent) || 0;
			groups.set(parent, idx + 1);
			el.style.setProperty('--d', (Math.min(idx, 6) % 4) * 0.07 + 's');
			el.setAttribute('data-reveal', '');
			io.observe(el);
		});
	}
	/* Cifrele din „Povestea noastră” cresc de la 0 */
	function countUp(scope) {
		$$('[data-nt-count]', scope).forEach(function (el) {
			var to = parseInt(el.getAttribute('data-nt-count'), 10);
			var t0 = null;
			var step = function (t) {
				if (!t0) t0 = t;
				var p = Math.min(1, (t - t0) / 1300);
				el.textContent = Math.round(to * (1 - Math.pow(1 - p, 3)));
				if (p < 1) requestAnimationFrame(step);
			};
			requestAnimationFrame(step);
		});
	}

	/* ------------------------------------------------------------ Hero: colaj care urmărește cursorul */
	var hart = $('.nt-hart');
	if (hart && !reduced && window.matchMedia('(hover: hover)').matches) {
		var hero = hart.closest('.nt-hero') || hart;
		var raf = null;
		hero.addEventListener('pointermove', function (e) {
			if (raf) return;
			raf = requestAnimationFrame(function () {
				var r = hero.getBoundingClientRect();
				hart.style.setProperty('--mx', ((e.clientX - r.left) / r.width - .5).toFixed(3));
				hart.style.setProperty('--my', ((e.clientY - r.top) / r.height - .5).toFixed(3));
				raf = null;
			});
		});
		hero.addEventListener('pointerleave', function () { hart.style.setProperty('--mx', 0); hart.style.setProperty('--my', 0); });
	}

	/* ------------------------------------------------------------ File de produse */
	$$('[data-nt-tabs]').forEach(function (wrap) {
		var tabs = $$('[role="tab"]', wrap);
		var select = function (tab, focus) {
			tabs.forEach(function (t) {
				var on = t === tab;
				t.setAttribute('aria-selected', on ? 'true' : 'false');
				t.tabIndex = on ? 0 : -1;
				var panel = doc.getElementById(t.getAttribute('aria-controls'));
				if (!panel) return;
				panel.hidden = !on;
				if (on) {
					panel.classList.remove('is-entering');
					panel.offsetHeight;
					panel.classList.add('is-entering');
					$$('[data-reveal]', panel).forEach(function (el) { el.classList.add('is-in'); });
				}
			});
			if (focus) tab.focus();
			tab.scrollIntoView({ block: 'nearest', inline: 'nearest', behavior: reduced ? 'auto' : 'smooth' });
		};
		tabs.forEach(function (t, i) {
			t.addEventListener('click', function () { select(t); });
			t.addEventListener('keydown', function (e) {
				if (e.key === 'ArrowRight' || e.key === 'ArrowLeft') {
					e.preventDefault();
					select(tabs[(i + (e.key === 'ArrowRight' ? 1 : -1) + tabs.length) % tabs.length], true);
				}
			});
		});
	});

	/* ------------------------------------------------------------ Carusel recenzii */
	$$('[data-nt-carousel]').forEach(function (c) {
		var track = $('.nt-reviews__track', c);
		var btns = $$('[data-dir]', c);
		var update = function () {
			var max = track.scrollWidth - track.clientWidth - 4;
			btns[0].disabled = track.scrollLeft <= 4;
			btns[1].disabled = track.scrollLeft >= max;
		};
		btns.forEach(function (b) {
			b.addEventListener('click', function () {
				var card = track.firstElementChild;
				var w = card ? card.getBoundingClientRect().width + 20 : track.clientWidth;
				track.scrollBy({ left: w * Number(b.getAttribute('data-dir')), behavior: reduced ? 'auto' : 'smooth' });
			});
		});
		track.addEventListener('scroll', function () { requestAnimationFrame(update); }, { passive: true });
		window.addEventListener('resize', update);
		update();
	});

	/* ------------------------------------------------------------ Rețete video: clipul rulează într-o fereastră */
	$$('[data-nt-recipes]').forEach(function (wrap) {
		var dlg = $('[data-nt-reel]', wrap);
		if (!dlg || typeof dlg.showModal !== 'function') return; // fără <dialog>: linkul se deschide normal
		var player = $('[data-nt-reel-player]', dlg);
		var more = $('[data-nt-reel-more]', dlg);
		var opener = null;
		wrap.addEventListener('click', function (e) {
			var a = e.target.closest('.nt-recipe__link[data-nt-video], .nt-recipe__link[data-nt-embed]');
			if (!a || e.metaKey || e.ctrlKey || e.shiftKey) return;
			e.preventDefault();
			opener = a;
			var card = a.closest('.nt-recipe');
			var title = $('.nt-recipe__title', card).innerText.replace(/\s+/g, ' ').trim();
			var video = a.getAttribute('data-nt-video');
			var media = doc.createElement(video ? 'video' : 'iframe');
			if (video) {
				media.src = video;
				media.controls = media.autoplay = media.playsInline = true;
			} else {
				media.src = a.getAttribute('data-nt-embed');
				media.title = title;
				media.allow = 'autoplay; encrypted-media; fullscreen; picture-in-picture';
				media.allowFullscreen = true;
			}
			player.replaceChildren(media);
			$('[data-nt-reel-title]', dlg).textContent = title;
			var prod = $('.nt-recipe__prod', card);
			$('[data-nt-reel-prod]', dlg).replaceChildren(prod ? prod.cloneNode(true) : '');
			var label = a.getAttribute('data-nt-more');
			more.hidden = !label;
			if (label) {
				more.href = a.getAttribute('data-nt-link');
				more.firstChild.nodeValue = label + ' ';
			}
			dlg.showModal();
			lockScroll(true);
		});
		dlg.addEventListener('close', function () {
			player.replaceChildren(); // oprește clipul
			lockScroll(false);
			if (opener) opener.focus({ preventScroll: true });
		});
		dlg.addEventListener('click', function (e) {
			if (e.target === dlg || e.target.closest('[data-nt-reel-close]')) dlg.close();
		});
	});

	/* ------------------------------------------------------------ Mergi sus */
	$$('[data-nt-totop]').forEach(function (b) {
		b.addEventListener('click', function () { window.scrollTo({ top: 0, behavior: reduced ? 'auto' : 'smooth' }); });
	});

	/* ------------------------------------------------------------ Pagina produsului: cantitate − / + */
	var qtyIcon = function (d) {
		return '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="' + d + '"/></svg>';
	};
	$$('form.cart .quantity').forEach(function (q) {
		var inp = $('input.qty', q);
		if (!inp || inp.type === 'hidden' || q.classList.contains('nt-qty')) return;
		q.classList.add('nt-qty', 'nt-qty--lg');
		var mk = function (dir, label, d) {
			var b = doc.createElement('button');
			b.type = 'button';
			b.setAttribute('aria-label', label);
			b.innerHTML = qtyIcon(d);
			b.addEventListener('click', function () {
				var step = parseFloat(inp.step) || 1;
				var min = parseFloat(inp.min) || 1;
				var max = parseFloat(inp.max) || Infinity;
				var v = Math.min(max, Math.max(min, (parseFloat(inp.value) || min) + dir * step));
				inp.value = v;
				inp.dispatchEvent(new Event('change', { bubbles: true }));
				sync();
			});
			return b;
		};
		var minus = mk(-1, 'Scade cantitatea', 'M5 12h14');
		var plus = mk(1, 'Crește cantitatea', 'M12 5v14M5 12h14');
		var sync = function () {
			var v = parseFloat(inp.value) || 1;
			minus.disabled = v <= (parseFloat(inp.min) || 1);
			plus.disabled = inp.max !== '' && v >= parseFloat(inp.max);
		};
		inp.parentNode.insertBefore(minus, inp);
		inp.parentNode.insertBefore(plus, inp.nextSibling);
		inp.addEventListener('input', sync);
		sync();
	});

	/* ------------------------------------------------------------ Bara „Adaugă în coș” lipită (mobil) */
	var satc = $('[data-nt-satc]');
	var mainBtn = $('form.cart .single_add_to_cart_button');
	if (satc && mainBtn && 'IntersectionObserver' in window) {
		new IntersectionObserver(function (en) {
			var e = en[0];
			var show = !e.isIntersecting && e.boundingClientRect.top < 0;
			satc.classList.toggle('is-visible', show);
			satc.setAttribute('aria-hidden', show ? 'false' : 'true');
			$('[data-nt-satc-btn]', satc).tabIndex = show ? 0 : -1;
		}).observe(mainBtn);
		$('[data-nt-satc-btn]', satc).addEventListener('click', function () { mainBtn.click(); });
	}

	/* ------------------------------------------------------------ Coș: notificare, contor, cantități în coșul lateral */
	var toasts = $('.nt-toasts');
	function toast(name, img) {
		if (!toasts) return;
		var t = doc.createElement('div');
		t.className = 'nt-toast';
		t.setAttribute('role', 'status');
		t.innerHTML = (img ? '<img src="' + esc(img) + '" alt="">' : '<span class="nt-toast__ico">✓</span>') +
			'<span class="nt-toast__txt"><strong>Adăugat în coș</strong><span>' + esc(name || '') + '</span></span>' +
			'<a class="nt-btn nt-btn--leaf nt-btn--sm" href="' + esc(NT.cartUrl || '#') + '" data-nt-open="cart" aria-controls="nt-cart">Vezi coșul</a>';
		toasts.appendChild(t);
		while (toasts.children.length > 2) toasts.removeChild(toasts.firstChild);
		setTimeout(function () {
			t.classList.add('is-out');
			setTimeout(function () { t.remove(); }, 320);
		}, 3800);
	}
	function bump() {
		$$('.nt-cartbtn, .nt-tabbar__ico').forEach(function (el) {
			el.classList.remove('is-bump');
			el.offsetWidth;
			el.classList.add('is-bump');
		});
	}

	if (jq) {
		jq(doc.body).on('added_to_cart', function (e, fragments, hash, $btn) {
			var b = $btn && $btn[0];
			toast(b ? b.getAttribute('data-nt-name') : '', b ? b.getAttribute('data-nt-img') : '');
			setTimeout(bump, 60);
			var label = b && b.querySelector('.nt-add__label');
			if (label) label.textContent = 'Adăugat';
			if (b) setTimeout(function () { b.classList.remove('added'); if (label) label.textContent = 'Adaugă'; }, 2400);
		});
		jq(doc.body).on('removed_from_cart', bump);
	}

	/* Cantitate − / + în coșul lateral (Store API; nonce-ul se ia proaspăt din răspunsul GET /cart) */
	doc.addEventListener('click', function (e) {
		var btn = e.target.closest('[data-nt-mc-qty] button');
		if (!btn) return;
		var item = btn.closest('.nt-mc__item');
		var val = $('.nt-qty__val', item);
		var qty = Math.max(1, (parseInt(val.textContent, 10) || 1) + Number(btn.getAttribute('data-step')));
		item.classList.add('is-busy');
		val.textContent = qty;
		fetch(NT.storeApi + 'cart', { credentials: 'same-origin' })
			.then(function (r) { return r.headers.get('Nonce'); })
			.then(function (nonce) {
				return fetch(NT.storeApi + 'cart/update-item', {
					method: 'POST',
					credentials: 'same-origin',
					headers: { 'Content-Type': 'application/json', Nonce: nonce || '' },
					body: JSON.stringify({ key: item.getAttribute('data-key'), quantity: qty })
				});
			})
			.then(function () {
				if (jq) jq(doc.body).trigger('wc_fragment_refresh');
				bump();
			})
			.catch(function () { item.classList.remove('is-busy'); });
	});

	/* ------------------------------------------------------------ Contact: „Copiază” telefonul / e-mailul */
	function copyText(text) {
		if (navigator.clipboard && window.isSecureContext) return navigator.clipboard.writeText(text);
		return new Promise(function (resolve, reject) {
			var t = doc.createElement('textarea');
			t.value = text;
			t.setAttribute('readonly', '');
			t.style.cssText = 'position:fixed;opacity:0';
			body.appendChild(t);
			t.select();
			var ok = doc.execCommand('copy');
			t.remove();
			if (ok) resolve(); else reject();
		});
	}
	doc.addEventListener('click', function (e) {
		var btn = e.target.closest('[data-nt-copy]');
		if (!btn) return;
		var label = $('.nt-copy__label', btn);
		if (!btn.hasAttribute('data-label')) btn.setAttribute('data-label', label.textContent);
		copyText(btn.getAttribute('data-nt-copy')).then(function () {
			btn.classList.add('is-copied');
			label.textContent = 'Copiat!';
			clearTimeout(btn.ntTimer);
			btn.ntTimer = setTimeout(function () {
				btn.classList.remove('is-copied');
				label.textContent = btn.getAttribute('data-label');
			}, 2200);
		}, function () {});
	});
})();
