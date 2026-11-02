(function () {
	'use strict';

	var cfg = window.MedusaA11y || {};
	var L = cfg.labels || {};
	var root = document.documentElement;
	var reader = null;
	var moved = [];
	var headingCount = 0;

	var SKIP = ['SCRIPT', 'STYLE', 'NOSCRIPT', 'TEMPLATE', 'SVG', 'CANVAS', 'BUTTON', 'INPUT', 'SELECT', 'TEXTAREA', 'OBJECT', 'EMBED', 'AUDIO', 'SOURCE', 'TRACK', 'LINK', 'META', 'DIALOG', 'MAP', 'AREA'];
	var KEEP = ['H1', 'H2', 'H3', 'H4', 'H5', 'H6', 'P', 'UL', 'OL', 'LI', 'DL', 'DT', 'DD', 'BLOCKQUOTE', 'TABLE', 'CAPTION', 'THEAD', 'TBODY', 'TFOOT', 'TR', 'TH', 'TD', 'STRONG', 'B', 'EM', 'I', 'BR', 'CODE', 'PRE', 'ABBR', 'SMALL', 'SUB', 'SUP', 'MARK', 'S', 'DEL', 'INS', 'Q', 'CITE', 'TIME', 'ADDRESS', 'FIGURE', 'FIGCAPTION', 'HR', 'DETAILS', 'SUMMARY'];
	var INLINE = ['A', 'STRONG', 'B', 'EM', 'I', 'BR', 'CODE', 'ABBR', 'SMALL', 'SUB', 'SUP', 'MARK', 'S', 'DEL', 'INS', 'Q', 'CITE', 'TIME', 'SPAN', 'IMG', 'LABEL', 'FONT', 'U'];
	var PHRASING = ['P', 'H1', 'H2', 'H3', 'H4', 'H5', 'H6', 'A', 'SUMMARY', 'CAPTION', 'DT', 'FIGCAPTION', 'STRONG', 'B', 'EM', 'I', 'CODE', 'ABBR', 'SMALL', 'SUB', 'SUP', 'MARK', 'S', 'DEL', 'INS', 'Q', 'CITE', 'TIME', 'PRE', 'ADDRESS'];
	var ATTRS = ['colspan', 'rowspan', 'scope', 'headers', 'datetime', 'title', 'start', 'reversed', 'type', 'cite'];
	var MEDIA = 'img, video, iframe, form, a[href], .wpcf7';

	function el(tag, text, cls) {
		var e = document.createElement(tag);
		if (text) {
			e.textContent = text;
		}
		if (cls) {
			e.className = cls;
		}
		return e;
	}

	function visible(node) {
		if (node.hidden || node.getAttribute('aria-hidden') === 'true') {
			return false;
		}
		if (typeof node.checkVisibility === 'function') {
			return node.checkVisibility({ visibilityProperty: true });
		}
		var style = window.getComputedStyle(node);
		return style.display !== 'none' && style.visibility !== 'hidden';
	}

	function isOurs(node) {
		return node.id === 'mat-widget' || node.id === 'mat-reader' || (node.classList && node.classList.contains('mat-filters'));
	}

	function localHref(a) {
		var raw = a.getAttribute('href') || '';
		if (raw.charAt(0) === '#' && raw.length > 1) {
			return '#mat-r-' + raw.slice(1);
		}
		if (a.pathname === window.location.pathname && a.host === window.location.host && a.hash) {
			return '#mat-r-' + a.hash.slice(1);
		}
		return a.href;
	}

	function newTab(link) {
		link.target = '_blank';
		link.rel = 'noopener';
		link.appendChild(el('span', ' ' + (L.newTab || '(opens in a new tab)'), 'mat-sr'));
	}

	function textOf(node) {
		return (node.textContent || '').replace(/\s+/g, ' ').trim();
	}

	function hasContent(node) {
		return textOf(node) !== '' || !!node.querySelector(MEDIA);
	}

	function moveLive(node, out) {
		var placeholder = document.createElement('span');
		placeholder.hidden = true;
		node.parentNode.insertBefore(placeholder, node);
		moved.push({ node: node, placeholder: placeholder });
		out.appendChild(node);
	}

	function image(node) {
		var alt = (node.getAttribute('alt') || '').trim();
		if (!alt) {
			return null;
		}
		var img = document.createElement('img');
		img.src = node.currentSrc || node.getAttribute('data-src') || node.getAttribute('data-lazy-src') || node.src;
		img.alt = alt;
		img.loading = 'lazy';
		img.decoding = 'async';
		if (node.getAttribute('width') && node.getAttribute('height')) {
			img.width = parseInt(node.getAttribute('width'), 10);
			img.height = parseInt(node.getAttribute('height'), 10);
		}
		return img;
	}

	function link(node, force) {
		var href = node.getAttribute('href');
		if (!href || href.indexOf('javascript:') === 0) {
			var span = document.createDocumentFragment();
			convert(node, span, force, true);
			return span;
		}
		var a = document.createElement('a');
		a.href = localHref(node);
		convert(node, a, force, true);
		if (!hasContent(a)) {
			var label = node.getAttribute('aria-label') || node.getAttribute('title');
			if (!label) {
				return null;
			}
			a.textContent = label;
		}
		if (node.target === '_blank') {
			newTab(a);
		}
		return a;
	}

	function frame(node) {
		var src = node.getAttribute('src') || node.getAttribute('data-src') || '';
		if (!src || src.indexOf('about:') === 0) {
			return null;
		}
		var label = L.embed || 'Open embedded content';
		if (/google\.[a-z.]+\/maps|maps\.google|openstreetmap/i.test(src)) {
			label = L.map || 'Open the map';
		} else if (/youtube|youtu\.be|vimeo|wistia/i.test(src)) {
			label = L.video || 'Watch the video';
		}
		var title = (node.getAttribute('title') || '').trim();
		var p = el('p');
		var a = el('a', title ? label + ': ' + title : label);
		a.href = src.replace('youtube.com/embed/', 'youtube.com/watch?v=').replace('youtube-nocookie.com/embed/', 'youtube.com/watch?v=');
		newTab(a);
		p.appendChild(a);
		return p;
	}

	function video(node) {
		if (node.closest('.wp-block-cover') || (node.autoplay && node.muted && node.loop)) {
			return null;
		}
		var v = document.createElement('video');
		v.controls = true;
		v.preload = 'metadata';
		if (node.currentSrc || node.getAttribute('src')) {
			v.src = node.currentSrc || node.getAttribute('src');
		}
		if (node.poster) {
			v.poster = node.poster;
		}
		Array.prototype.forEach.call(node.querySelectorAll('track'), function (t) {
			v.appendChild(t.cloneNode(false));
		});
		return v;
	}

	function wrapRuns(frag, out) {
		var run = null;
		Array.prototype.slice.call(frag.childNodes).forEach(function (child) {
			var inline = child.nodeType === 3 || INLINE.indexOf(child.nodeName) > -1;
			if (inline) {
				if (!run) {
					run = el('p');
				}
				run.appendChild(child);
				return;
			}
			if (run) {
				if (hasContent(run)) {
					out.appendChild(run);
				}
				run = null;
			}
			out.appendChild(child);
		});
		if (run && hasContent(run)) {
			out.appendChild(run);
		}
	}

	function anchor(node, out) {
		if (node.id) {
			var a = document.createElement('span');
			a.id = 'mat-r-' + node.id;
			out.appendChild(a);
		}
	}

	function convert(source, out, force, phrasing) {
		Array.prototype.slice.call(source.childNodes).forEach(function (node) {
			if (node.nodeType === 3) {
				out.appendChild(document.createTextNode(node.nodeValue));
				return;
			}
			if (node.nodeType !== 1 || isOurs(node)) {
				return;
			}
			var tag = node.nodeName.toUpperCase();
			if (SKIP.indexOf(tag) > -1 || tag === 'SVG') {
				return;
			}
			if (!force && !visible(node)) {
				return;
			}
			if (tag === 'FORM' || node.classList.contains('wpcf7') || node.classList.contains('wp-block-search')) {
				if (!phrasing) {
					moveLive(node, out);
				}
				return;
			}
			if (tag === 'IMG') {
				var img = image(node);
				if (img) {
					out.appendChild(img);
				}
				return;
			}
			if (tag === 'A') {
				var a = link(node, force);
				if (a) {
					out.appendChild(a);
				}
				return;
			}
			if (tag === 'IFRAME') {
				var f = frame(node);
				if (f && !phrasing) {
					out.appendChild(f);
				}
				return;
			}
			if (tag === 'VIDEO') {
				var v = video(node);
				if (v && !phrasing) {
					out.appendChild(v);
				}
				return;
			}
			if (tag === 'I' && /icon|fa-|dashicons/.test(node.className)) {
				return;
			}
			if (KEEP.indexOf(tag) > -1) {
				if (phrasing && PHRASING.indexOf(tag) === -1 && INLINE.indexOf(tag) === -1) {
					convert(node, out, force, true);
					return;
				}
				var copy = document.createElement(tag);
				ATTRS.forEach(function (name) {
					if (node.hasAttribute(name)) {
						copy.setAttribute(name, node.getAttribute(name));
					}
				});
				if (node.id) {
					copy.id = 'mat-r-' + node.id;
				}
				if (tag === 'DETAILS') {
					copy.open = true;
				}
				var inner = PHRASING.indexOf(tag) > -1 || INLINE.indexOf(tag) > -1;
				convert(node, copy, force || tag === 'DETAILS', inner);
				if (tag === 'BR' || tag === 'HR' || tag === 'TD' || tag === 'TH' || hasContent(copy)) {
					out.appendChild(copy);
				}
				return;
			}
			anchor(node, out);
			if (phrasing || INLINE.indexOf(tag) > -1) {
				convert(node, out, force, phrasing);
				return;
			}
			var frag = document.createDocumentFragment();
			convert(node, frag, force, false);
			wrapRuns(frag, out);
		});
	}

	function topLevel(selector) {
		return Array.prototype.filter.call(document.querySelectorAll(selector), function (node) {
			return !node.closest('#mat-reader') && !node.closest('main, [role="main"]') && (!node.parentElement || !node.parentElement.closest(selector));
		});
	}

	function navItems(list, seen) {
		var ul = el('ul');
		Array.prototype.forEach.call(list.children, function (item) {
			if (item.nodeName !== 'LI') {
				return;
			}
			var a = Array.prototype.filter.call(item.querySelectorAll('a[href]'), function (x) {
				return x.closest('li') === item;
			})[0];
			var sub = Array.prototype.filter.call(item.querySelectorAll('ul'), function (x) {
				return x.parentElement.closest('li') === item;
			})[0];
			var label = a ? textOf(a) || a.getAttribute('aria-label') : '';
			if (!a || !label) {
				return;
			}
			var li = el('li');
			var link = el('a', label);
			link.href = a.href;
			seen[a.href + '|' + label] = true;
			li.appendChild(link);
			if (sub) {
				var child = navItems(sub, seen);
				if (child.children.length) {
					li.appendChild(child);
				}
			}
			ul.appendChild(li);
		});
		return ul;
	}

	function buildNav() {
		var seen = {};
		var lists = [];
		var headers = topLevel('header, [role="banner"]');
		var navs = [];
		headers.forEach(function (h) {
			Array.prototype.forEach.call(h.querySelectorAll('nav'), function (n) {
				navs.push(n);
			});
		});
		if (!navs.length) {
			navs = topLevel('nav').filter(function (n) {
				return !n.closest('footer, [role="contentinfo"]');
			}).slice(0, 1);
		}
		navs.forEach(function (n) {
			var list = n.querySelector('ul');
			if (list) {
				var ul = navItems(list, seen);
				if (ul.children.length) {
					lists.push(ul);
				}
			}
		});
		var extra = el('ul');
		headers.forEach(function (h) {
			Array.prototype.forEach.call(h.querySelectorAll('a[href]'), function (a) {
				var label = textOf(a) || a.getAttribute('aria-label') || '';
				if (!label || a.closest('nav') || a.closest('.wp-block-site-logo, .wp-block-site-title, .custom-logo-link') || a.rel === 'home' || seen[a.href + '|' + label]) {
					return;
				}
				seen[a.href + '|' + label] = true;
				var li = el('li');
				var link = el('a', label);
				link.href = a.href;
				li.appendChild(link);
				extra.appendChild(li);
			});
		});
		if (extra.children.length) {
			lists.push(extra);
		}
		if (!lists.length) {
			return null;
		}
		var nav = el('nav', '', 'mat-reader-nav');
		nav.setAttribute('aria-labelledby', 'mat-reader-menu-title');
		var title = el('h2', L.menu || 'Menu');
		title.id = 'mat-reader-menu-title';
		nav.appendChild(title);
		lists.forEach(function (ul) {
			nav.appendChild(ul);
		});
		return nav;
	}

	function source() {
		var main = Array.prototype.filter.call(document.querySelectorAll('main, [role="main"]'), function (m) {
			return !m.closest('#mat-reader') && (!m.parentElement || !m.parentElement.closest('main, [role="main"]'));
		})[0];
		if (main) {
			return [main];
		}
		var holder = document.querySelector('.wp-site-blocks') || document.body;
		return Array.prototype.filter.call(holder.children, function (c) {
			return !isOurs(c) && !c.matches('header, footer, nav, [role="banner"], [role="contentinfo"], [role="navigation"], #wpadminbar, script, style');
		});
	}

	function clean(container) {
		var nodes = container.querySelectorAll('p, li, ul, ol, dl, h1, h2, h3, h4, h5, h6, blockquote, figure, figcaption, strong, em, b, i, span:not([id]), small');
		Array.prototype.slice.call(nodes).reverse().forEach(function (n) {
			if (n.closest('form, .wpcf7, .wp-block-search')) {
				return;
			}
			if (!hasContent(n)) {
				n.remove();
			}
		});
	}

	function pageTitle() {
		var t = document.title || '';
		var parts = t.split(/\s[-|\u2013\u2014\u2022\u00b7]\s/);
		return (parts[0] || t || cfg.site || '').trim();
	}

	function contents(main) {
		var heads = main.querySelectorAll('h2');
		if (heads.length < 3) {
			return null;
		}
		var nav = el('nav', '', 'mat-reader-contents');
		nav.setAttribute('aria-labelledby', 'mat-reader-contents-title');
		var title = el('h2', L.contents || 'On this page');
		title.id = 'mat-reader-contents-title';
		nav.appendChild(title);
		var ul = el('ul');
		Array.prototype.forEach.call(heads, function (h) {
			if (!h.id) {
				headingCount += 1;
				h.id = 'mat-r-h-' + headingCount;
			}
			var li = el('li');
			var a = el('a', textOf(h));
			a.href = '#' + h.id;
			li.appendChild(a);
			ul.appendChild(li);
		});
		nav.appendChild(ul);
		return nav;
	}

	function build() {
		var wrap = el('div', '', 'mat-reader');
		wrap.id = 'mat-reader';
		wrap.setAttribute('data-nosnippet', '');

		var skip = el('a', L.skip || 'Skip to content', 'mat-reader-skip');
		skip.href = '#mat-reader-main';
		wrap.appendChild(skip);

		var header = el('header', '', 'mat-reader-header');
		var site = el('p', '', 'mat-reader-site');
		var home = el('a', cfg.site || window.location.hostname);
		home.href = cfg.home || '/';
		site.appendChild(home);
		header.appendChild(site);
		var exit = el('button', L.exit || 'Exit accessible mode', 'mat-reader-exit');
		exit.type = 'button';
		exit.addEventListener('click', function () {
			if (typeof api.onExit === 'function') {
				api.onExit();
			} else {
				api.off();
			}
		});
		header.appendChild(exit);
		header.appendChild(el('p', L.notice || '', 'mat-reader-notice'));
		wrap.appendChild(header);

		var nav = buildNav();
		if (nav) {
			wrap.appendChild(nav);
		}

		var main = el('main', '', 'mat-reader-main');
		main.id = 'mat-reader-main';
		main.tabIndex = -1;
		source().forEach(function (node) {
			var frag = document.createDocumentFragment();
			anchor(node, frag);
			convert(node, frag, false, false);
			wrapRuns(frag, main);
		});
		clean(main);
		var h1 = main.querySelector('h1');
		if (!h1) {
			h1 = el('h1', pageTitle());
			main.insertBefore(h1, main.firstChild);
		}
		h1.tabIndex = -1;
		var list = contents(main);
		if (list) {
			h1.parentNode.insertBefore(list, h1.nextSibling);
		}
		wrap.appendChild(main);

		var footers = topLevel('footer, [role="contentinfo"]');
		if (footers.length) {
			var footer = el('footer', '', 'mat-reader-footer');
			footer.appendChild(el('h2', L.footer || 'Site information'));
			footers.forEach(function (f) {
				var frag = document.createDocumentFragment();
				convert(f, frag, false, false);
				wrapRuns(frag, footer);
			});
			clean(footer);
			wrap.appendChild(footer);
		}
		return wrap;
	}

	var api = {
		active: function () {
			return !!reader;
		},
		on: function (focus) {
			if (reader) {
				return;
			}
			try {
				reader = build();
			} catch (e) {
				reader = null;
				root.removeAttribute('data-mat-reader');
				return;
			}
			var widget = document.getElementById('mat-widget');
			document.body.insertBefore(reader, widget || null);
			root.setAttribute('data-mat-reader', 'on');
			if (focus) {
				window.scrollTo(0, 0);
				var target = reader.querySelector('#mat-reader-main h1') || reader.querySelector('#mat-reader-main');
				target.focus();
			} else if (window.location.hash) {
				var hashed = document.getElementById('mat-r-' + window.location.hash.slice(1));
				if (hashed) {
					hashed.scrollIntoView();
				}
			}
		},
		off: function () {
			moved.forEach(function (m) {
				if (m.placeholder.parentNode) {
					m.placeholder.parentNode.replaceChild(m.node, m.placeholder);
				}
			});
			moved = [];
			if (reader) {
				reader.remove();
				reader = null;
			}
			root.removeAttribute('data-mat-reader');
		},
		onExit: null
	};

	window.MedusaA11yReader = api;
})();
