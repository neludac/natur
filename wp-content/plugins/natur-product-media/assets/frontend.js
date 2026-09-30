/* Natur – Video & 360°: galerie produs + vizualizator 360° (fără dependențe) */
(function () {
	'use strict';

	/* ------------------------------------------------------------ 360° */
	function Spin(el) {
		this.el = el;
		this.img = el.querySelector('.natur-spin__img');
		this.bar = el.querySelector('.natur-spin__progress span');
		this.frames = JSON.parse(el.getAttribute('data-frames') || '[]');
		this.n = this.frames.length;
		this.index = 0;
		this.timer = null;
		this.started = false;
		this.autostart = el.getAttribute('data-autostart') === '1';
		this.bind();
	}

	Spin.prototype.start = function () {
		if (this.started || !this.n) return;
		this.started = true;
		var self = this, done = 0;
		this.el.classList.add('is-loading');
		this.cache = this.frames.map(function (src) {
			var im = new Image();
			im.onload = im.onerror = function () {
				done++;
				self.bar.style.width = Math.round(done / self.n * 100) + '%';
				if (done === self.n) {
					self.el.classList.remove('is-loading');
					self.el.classList.add('is-ready');
					self.play();
				}
			};
			im.decoding = 'async';
			im.src = src;
			return im;
		});
	};

	Spin.prototype.show = function (i) {
		this.index = ((i % this.n) + this.n) % this.n;
		this.img.src = this.frames[this.index];
	};

	Spin.prototype.play = function () {
		if (this.timer || !this.el.classList.contains('is-ready')) return;
		var self = this, delay = Math.max(40, Math.round(3600 / this.n));
		this.el.classList.add('is-playing');
		this.timer = setInterval(function () { self.show(self.index + 1); }, delay);
	};

	Spin.prototype.pause = function () {
		clearInterval(this.timer);
		this.timer = null;
		this.el.classList.remove('is-playing');
	};

	Spin.prototype.bind = function () {
		var self = this, el = this.el, startX = 0, startIndex = 0, dragging = false;

		el.addEventListener('pointerdown', function (e) {
			if (e.target.closest('button') || !el.classList.contains('is-ready')) return;
			dragging = true;
			startX = e.clientX;
			startIndex = self.index;
			self.pause();
			el.classList.add('is-dragging', 'was-used');
			el.setPointerCapture(e.pointerId);
		});
		el.addEventListener('pointermove', function (e) {
			if (!dragging) return;
			var step = Math.max(4, el.clientWidth / self.n);
			self.show(startIndex - Math.round((e.clientX - startX) / step));
		});
		['pointerup', 'pointercancel'].forEach(function (t) {
			el.addEventListener(t, function () {
				dragging = false;
				el.classList.remove('is-dragging');
			});
		});

		el.addEventListener('keydown', function (e) {
			if (e.key === 'ArrowLeft' || e.key === 'ArrowRight') {
				e.preventDefault();
				self.pause();
				self.show(self.index + (e.key === 'ArrowLeft' ? 1 : -1));
			}
		});

		el.querySelectorAll('[data-act]').forEach(function (btn) {
			btn.addEventListener('click', function () {
				el.classList.add('was-used');
				switch (btn.getAttribute('data-act')) {
					case 'prev': self.pause(); self.show(self.index + 1); break;
					case 'next': self.pause(); self.show(self.index - 1); break;
					case 'play': self.timer ? self.pause() : self.play(); break;
					case 'full':
						if (document.fullscreenElement) document.exitFullscreen();
						else if (el.requestFullscreen) el.requestFullscreen();
						else if (el.webkitRequestFullscreen) el.webkitRequestFullscreen();
						break;
				}
			});
		});

		// Oprește rotația când vizualizatorul nu e pe ecran.
		if ('IntersectionObserver' in window) {
			new IntersectionObserver(function (entries) {
				entries.forEach(function (en) {
					if (en.isIntersecting && self.autostart) self.start();
					if (!en.isIntersecting) self.pause();
				});
			}, { threshold: 0.2 }).observe(el);
		} else if (this.autostart) {
			this.start();
		}
	};

	function makeSpin(el) {
		if (!el.naturSpin) el.naturSpin = new Spin(el);
		return el.naturSpin;
	}

	/* ------------------------------------------------------------ Video */
	function loadVideo(item) {
		var iframe = item.querySelector('iframe[data-src]');
		if (iframe && iframe.getAttribute('src') !== iframe.getAttribute('data-src')) {
			iframe.setAttribute('src', iframe.getAttribute('data-src'));
		}
	}

	function stopVideos(root) {
		root.querySelectorAll('video').forEach(function (v) { v.pause(); });
		root.querySelectorAll('iframe[data-src]').forEach(function (f) { f.removeAttribute('src'); });
	}

	function initVideo(box, autoload) {
		if (box.naturVideo) return;
		box.naturVideo = true;
		var items = box.querySelectorAll('.natur-video__item');
		var picks = box.querySelectorAll('.natur-video__pick');
		picks.forEach(function (btn) {
			btn.addEventListener('click', function () {
				var idx = +btn.getAttribute('data-index');
				stopVideos(box);
				items.forEach(function (it, i) { it.hidden = i !== idx; });
				picks.forEach(function (p) { p.classList.toggle('is-active', p === btn); });
				loadVideo(items[idx]);
			});
		});
		if (autoload && items[0]) loadVideo(items[0]);
	}

	/* ------------------------------------------------ Galerie produs */
	function initGallery(wrap) {
		var gallery = wrap.querySelector('.woocommerce-product-gallery');
		var stage = wrap.querySelector('.natur-media__stage');
		var panels = wrap.querySelectorAll('.natur-media__panel');
		var buttons = wrap.querySelectorAll('.natur-media__btn');
		var spinEl = wrap.querySelector('.natur-spin');
		var spin = spinEl ? makeSpin(spinEl) : null;
		var videoBox = wrap.querySelector('.natur-video');
		if (videoBox) initVideo(videoBox, false);

		buttons.forEach(function (btn) {
			btn.addEventListener('click', function () {
				var what = btn.getAttribute('data-show');
				buttons.forEach(function (b) {
					var on = b === btn;
					b.classList.toggle('is-active', on);
					b.setAttribute('aria-selected', on ? 'true' : 'false');
				});
				if (spin) spin.pause();
				if (videoBox) stopVideos(videoBox);

				if (what === 'photos') {
					stage.hidden = true;
					if (gallery) gallery.style.display = '';
					window.dispatchEvent(new Event('resize')); // reîmprospătează flexslider
					return;
				}
				if (gallery) gallery.style.display = 'none';
				stage.hidden = false;
				panels.forEach(function (p) { p.hidden = p.getAttribute('data-panel') !== what; });
				if (what === 'spin' && spin) {
					spin.started ? spin.play() : spin.start();
					spinEl.focus({ preventScroll: true });
				}
				if (what === 'video' && videoBox) {
					var visible = videoBox.querySelector('.natur-video__item:not([hidden])');
					if (visible) loadVideo(visible);
				}
			});
		});
	}

	function init() {
		document.querySelectorAll('[data-natur-media]').forEach(initGallery);
		document.querySelectorAll('.natur-embed .natur-spin').forEach(makeSpin);
		document.querySelectorAll('.natur-embed[data-natur-autoload] .natur-video').forEach(function (b) { initVideo(b, true); });
	}

	if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
	else init();

	// Elementor editor: re-inițializează widgetul la randare.
	window.addEventListener('elementor/frontend/init', function () {
		if (!window.elementorFrontend) return;
		window.elementorFrontend.hooks.addAction('frontend/element_ready/natur_product_media.default', function ($scope) {
			var root = $scope[0];
			root.querySelectorAll('.natur-spin').forEach(makeSpin);
			root.querySelectorAll('.natur-embed[data-natur-autoload] .natur-video').forEach(function (b) { initVideo(b, true); });
		});
	});
})();
