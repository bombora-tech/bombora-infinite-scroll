/*!
 * Bombora Infinite Scroll 1.0.5 – Bombora group s.r.o.
 * Automatické donačítání produktů: Bombora B2B (.zgb-rows), Bricks (.infinite-list), WooCommerce (ul.products).
 * Klasické stránkování zůstává v HTML (skryté) pro roboty a prohlížeče bez JS.
 */
(function () {
	'use strict';

	var C = window.BomboraIS || {};
	var T = C.t || {
		shown: 'Products shown: <b>%n</b> · page %p of %m', shownN: 'Products shown: <b>%n</b>',
		done: 'All products shown (<b>%n</b>)', more: 'Load more products', err: 'Loading failed – try again'
	};
	var AUTO = typeof C.autoPages === 'number' ? C.autoPages : 4;
	var MARGIN = (C.rootMargin || 700) + 'px';

	var PRESETS = [
		{ name: 'zgb', list: '.zgb-archive .zgb-rows', item: 'article.zgb-row', next: '.zgb-pag a.next', hide: '.zgb-pag', anchor: '.zgb-pag', pos: 'after' },
		{ name: 'bricks', list: '.infinite-list', item: '.infinite-post', next: '.infinite-pagination a.next, .brxe-pagination a.next', hide: '.infinite-pagination, .brxe-pagination, .infinite-load-more, .zg-lm-count', anchor: '.infinite-load-more', pos: 'before' },
		{ name: 'woo', list: 'ul.products', item: 'li.product', next: '.woocommerce-pagination a.next', hide: '.woocommerce-pagination', anchor: '.woocommerce-pagination', pos: 'after' }
	];

	function qs(s, c) { try { return s ? (c || document).querySelector(s) : null; } catch (e) { return null; } }
	function qsa(s, c) { try { return s ? [].slice.call((c || document).querySelectorAll(s)) : []; } catch (e) { return []; } }

	function pickPreset() {
		if (C.list && C.item && qs(C.list)) {
			return { name: 'custom', list: C.list, item: C.item, next: C.next || PRESETS[1].next + ', ' + PRESETS[0].next + ', ' + PRESETS[2].next, hide: C.hide || '', anchor: '', pos: 'after' };
		}
		for (var i = 0; i < PRESETS.length; i++) {
			if (qs(PRESETS[i].list)) {
				var p = Object.assign({}, PRESETS[i]);
				if (C.hide) { p.hide += ', ' + C.hide; }
				if (C.next) { p.next = C.next; }
				return p;
			}
		}
		return null;
	}

	function init() {
		if (window.__bomboraIS) { return; }
		var P = pickPreset();
		if (!P) { return; }
		var list = qs(P.list);
		if (!list) { return; }
		window.__bomboraIS = true;

		qsa(P.hide).forEach(function (el) { el.classList.add('bis-hidden'); });

		var nextA = qs(P.next);
		if (!nextA || !nextA.href) { return; }

		/* --- stav --- */
		var nextUrl = nextA.href, busy = false, autoLoaded = 0;
		var startPage = parseInt((location.pathname.match(/\/page\/(\d+)\/?$/) || [0, 1])[1], 10) || 1;
		var page = startPage;
		var maxPages = 0;
		qsa('.page-numbers', nextA.closest('nav, ul, .bricks-pagination, .brxe-pagination, .zgb-pag, .woocommerce-pagination') || document).forEach(function (el) {
			var v = parseInt(el.textContent.replace(/\D/g, ''), 10);
			if (v > maxPages) { maxPages = v; }
		});
		var KEY = 'bis:' + location.pathname + location.search;

		function items(root) { return qsa(P.item, root || list).filter(function (el) { return !el.classList.contains('bis-skel'); }); }
		function keyOf(el) {
			var a = el.querySelector('a[href]');
			if (!a) { return null; }
			try { return new URL(a.getAttribute('href'), location.href).pathname; } catch (e) { return a.getAttribute('href'); }
		}
		var seen = {};
		items().forEach(function (el) { var k = keyOf(el); if (k) { seen[k] = 1; } });

		/* --- UI --- */
		var box = document.createElement('div');
		box.className = 'bis';
		box.setAttribute('aria-live', 'polite');
		box.innerHTML = '<div class="bis-txt"></div><div class="bis-bar" aria-hidden="true"><span></span></div>' +
			'<button type="button" class="bis-btn"><span class="bis-spin" aria-hidden="true"></span><span class="bis-lbl"></span></button>';
		var anchor = qs(P.anchor);
		if (anchor && anchor.parentNode) {
			anchor.parentNode.insertBefore(box, P.pos === 'before' ? anchor : anchor.nextSibling);
		} else {
			list.parentNode.insertBefore(box, list.nextSibling);
		}
		var txt = box.querySelector('.bis-txt'), bar = box.querySelector('.bis-bar span'),
			btn = box.querySelector('.bis-btn'), lbl = box.querySelector('.bis-lbl');

		function render(err) {
			var n = items().length;
			if (!nextUrl) {
				txt.innerHTML = T.done.replace('%n', n);
				bar.style.width = '100%';
				box.classList.add('is-done');
				btn.hidden = true;
				return;
			}
			var mp = Math.max(maxPages, page + 1);
			txt.innerHTML = (maxPages ? T.shown : T.shownN).replace('%n', n).replace('%p', page).replace('%m', mp);
			bar.style.width = Math.round(page / mp * 100) + '%';
			lbl.textContent = err ? T.err : T.more;
			btn.hidden = false;
		}

		/* --- skeleton --- */
		function skel(on) {
			if (!on) { qsa('.bis-skel', list).forEach(function (s) { s.parentNode.removeChild(s); }); return; }
			var first = items()[0];
			if (!first) { return; }
			var rowMode = first.getBoundingClientRect().width > list.getBoundingClientRect().width * 0.6;
			var cols = 1;
			if (!rowMode) {
				var top = first.offsetTop;
				cols = items().filter(function (el) { return el.offsetTop === top; }).length || 1;
			}
			var count = rowMode ? 3 : cols;
			for (var i = 0; i < count; i++) {
				var s = document.createElement(first.tagName === 'LI' ? 'li' : 'div');
				s.className = first.className + ' bis-skel' + (rowMode ? ' bis-row' : '');
				s.setAttribute('aria-hidden', 'true');
				if (rowMode) {
					var h = '';
					for (var j = 0; j < Math.max(2, first.children.length); j++) {
						h += j === 0 ? '<span><i class="bis-sk-img"></i></span>' : (j === 1 ? '<span><i style="width:70%"></i><i class="bis-sk-s" style="width:35%"></i></span>' : '<span><i></i></span>');
					}
					s.innerHTML = h;
				} else {
					s.innerHTML = '<i class="bis-sk-img"></i><i></i><i class="bis-sk-s"></i>';
				}
				list.appendChild(s);
			}
		}

		/* --- re-inicializace po vložení --- */
		function fixLazy(added) {
			added.forEach(function (el) {
				qsa('img[data-src], source[data-srcset], img[data-srcset]', el).forEach(function (img) {
					if (img.dataset.srcset) { img.srcset = img.dataset.srcset; img.removeAttribute('data-srcset'); }
					if (img.dataset.sizes) { img.sizes = img.dataset.sizes; }
					if (img.dataset.src) { img.src = img.dataset.src; img.removeAttribute('data-src'); }
					img.classList.remove('perfmatters-lazy', 'bricks-lazy-hidden', 'lazyload');
					img.classList.add('pmloaded');
				});
				qsa('[data-bg]', el).forEach(function (b) {
					var v = b.getAttribute('data-bg');
					b.style.backgroundImage = /^url\(/.test(v) ? v : 'url("' + v + '")';
					b.removeAttribute('data-bg');
				});
			});
		}
		function reinit(added) {
			fixLazy(added);
			added.forEach(function (el) {
				qsa('.zgb-add input[type=number]', el).forEach(function (i) { i.dispatchEvent(new Event('input', { bubbles: true })); });
			});
			/* Bricks deklaruje tyto funkce jako globální const (nejsou na window) – volat holým jménem */
			/* global bricksWooQuantityTriggersFn, bricksWooLoopQtyListenerFn, bricksWooAjaxAddToCartFn, bricksLazyLoad */
			try { if (typeof bricksWooQuantityTriggersFn !== 'undefined') { bricksWooQuantityTriggersFn.run(); } } catch (e) {}
			try { if (typeof bricksWooLoopQtyListenerFn !== 'undefined') { bricksWooLoopQtyListenerFn.run(); } } catch (e) {}
			try { if (typeof bricksWooAjaxAddToCartFn !== 'undefined') { bricksWooAjaxAddToCartFn.run(); } } catch (e) {}
			try { if (typeof bricksLazyLoad === 'function') { bricksLazyLoad(); } } catch (e) {}
			try { if (window.jQuery) { window.jQuery(document.body).trigger('post-load'); } } catch (e) {}
			try {
				document.dispatchEvent(new CustomEvent('zg:loadmore', { detail: { page: page } }));
				document.dispatchEvent(new CustomEvent('bombora:infinite-loaded', { detail: { page: page, items: added } }));
			} catch (e) {}
		}
		function track(method) {
			var data = { page_number: page, items_shown: items().length, page_path: location.pathname, load_method: method };
			try {
				if (typeof window.gtag === 'function') { window.gtag('event', 'load_more', data); }
				else if (window.dataLayer) { window.dataLayer.push(Object.assign({ event: 'load_more' }, data)); }
			} catch (e) {}
		}

		/* --- načtení další strany --- */
		function load(method) {
			if (busy || !nextUrl) { return Promise.resolve(false); }
			busy = true;
			box.classList.add('is-loading');
			btn.setAttribute('aria-busy', 'true');
			skel(true);
			return fetch(nextUrl, { credentials: 'same-origin' })
				.then(function (r) { if (!r.ok) { throw new Error(r.status); } return r.text(); })
				.then(function (html) {
					var doc = new DOMParser().parseFromString(html, 'text/html');
					var src = doc.querySelector(P.list) || doc;
					skel(false);
					var frag = document.createDocumentFragment(), added = [];
					items(src).forEach(function (el) {
						var k = keyOf(el);
						if (k && seen[k]) { return; }
						if (k) { seen[k] = 1; }
						var node = document.importNode(el, true);
						node.classList.add('bis-new');
						frag.appendChild(node);
						added.push(node);
					});
					list.appendChild(frag);
					var nx = doc.querySelector(P.next);
					nextUrl = nx && nx.getAttribute('href') ? new URL(nx.getAttribute('href'), location.href).href : null;
					page++;
					reinit(added);
					if (method) { track(method); }
					return true;
				})
				.catch(function () { skel(false); render(true); return false; })
				.then(function (ok) {
					busy = false;
					box.classList.remove('is-loading');
					btn.removeAttribute('aria-busy');
					if (ok) { render(); }
					return ok;
				});
		}

		btn.addEventListener('click', function (e) { e.preventDefault(); load('button'); });

		var io = null;
		if ('IntersectionObserver' in window && AUTO > 0) {
			io = new IntersectionObserver(function (en) {
				if (!en[0].isIntersecting || autoLoaded >= AUTO || !nextUrl || busy) { return; }
				autoLoaded++;
				load('auto').then(function (ok) { if (ok && nextUrl && autoLoaded < AUTO) { io.unobserve(box); io.observe(box); } });
			}, { rootMargin: '0px 0px ' + MARGIN + ' 0px' });
		}

		/* --- návrat z detailu produktu: obnovit stránky a pozici --- */
		function saveState() {
			try {
				if (page > startPage) { sessionStorage.setItem(KEY, JSON.stringify({ p: page - startPage + 1, y: window.scrollY, t: Date.now() })); }
				else { sessionStorage.removeItem(KEY); }
			} catch (e) {}
		}
		list.addEventListener('click', function (e) { if (e.target.closest && e.target.closest('a[href]')) { saveState(); } });
		window.addEventListener('pagehide', saveState);

		render();

		var saved = null;
		try { saved = JSON.parse(sessionStorage.getItem(KEY) || 'null'); } catch (e) {}
		var nav = (performance.getEntriesByType && performance.getEntriesByType('navigation')[0]) || {};
		if (saved && saved.p > 1 && nav.type === 'back_forward' && Date.now() - saved.t < 1800000) {
			if ('scrollRestoration' in history) { history.scrollRestoration = 'manual'; }
			var need = saved.p - 1;
			(function step() {
				if (need-- > 0 && nextUrl) {
					load('').then(function (ok) { if (ok) { step(); } else { finish(); } });
				} else { finish(); }
			})();
		} else {
			finish(true);
		}
		function finish(fresh) {
			if (!fresh && saved) { window.scrollTo(0, saved.y); }
			if (io && nextUrl) { io.observe(box); }
		}
	}

	if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', init); } else { init(); }
})();
