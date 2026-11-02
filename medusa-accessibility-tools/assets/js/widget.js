(function () {
	'use strict';

	var KEY = 'medusaA11y';
	var MODES = ['protanopia', 'deuteranopia', 'tritanopia'];

	function read() {
		try {
			var s = JSON.parse(window.localStorage.getItem(KEY) || '{}');
			return s && typeof s === 'object' ? s : {};
		} catch (e) {
			return {};
		}
	}

	function write(state) {
		try {
			window.localStorage.setItem(KEY, JSON.stringify(state));
		} catch (e) {}
	}

	function init() {
		var widget = document.getElementById('mat-widget');
		if (!widget) {
			return;
		}
		var root = document.documentElement;
		var toggle = document.getElementById('mat-toggle');
		var panel = document.getElementById('mat-panel');
		var close = document.getElementById('mat-close');
		var value = document.getElementById('mat-text-value');
		var select = document.getElementById('mat-cb');
		var reset = document.getElementById('mat-reset');
		var steps = widget.querySelectorAll('[data-mat-step]');
		var switches = widget.querySelectorAll('[data-mat-toggle]');
		var sizes = (window.MedusaA11y && window.MedusaA11y.steps) || [100, 112.5, 125, 137.5, 150];
		var state = read();
		var reader = window.MedusaA11yReader || null;

		function clean() {
			var text = parseInt(state.text, 10);
			state.text = text > 0 && text < sizes.length ? text : 0;
			state.font = !!state.font;
			state.invert = !!state.invert;
			state.cb = MODES.indexOf(state.cb) > -1 ? state.cb : '';
			state.reader = !!state.reader;
		}

		function attr(name, on, val) {
			if (on) {
				root.setAttribute(name, val === undefined ? '' : val);
			} else {
				root.removeAttribute(name);
			}
		}

		function apply(focus) {
			clean();
			if (reader) {
				if (state.reader && !reader.active()) {
					reader.on(focus);
				} else if (!state.reader && reader.active()) {
					reader.off();
				}
			}
			attr('data-mat-text', state.text > 0, String(state.text));
			attr('data-mat-font', state.font, 'dyslexic');
			attr('data-mat-invert', state.invert);
			attr('data-mat-cb', !!state.cb, state.cb);
			value.textContent = sizes[state.text] + '%';
			steps[0].disabled = state.text === 0;
			steps[1].disabled = state.text === sizes.length - 1;
			Array.prototype.forEach.call(switches, function (button) {
				button.setAttribute('aria-checked', state[button.getAttribute('data-mat-toggle')] ? 'true' : 'false');
			});
			select.value = state.cb;
		}

		function change(focus) {
			apply(focus);
			var saved = {};
			if (state.text) {
				saved.text = state.text;
			}
			if (state.font) {
				saved.font = true;
			}
			if (state.invert) {
				saved.invert = true;
			}
			if (state.cb) {
				saved.cb = state.cb;
			}
			if (state.reader) {
				saved.reader = true;
			}
			write(saved);
		}

		function open() {
			panel.hidden = false;
			toggle.setAttribute('aria-expanded', 'true');
			close.focus();
		}

		function shut(focus) {
			panel.hidden = true;
			toggle.setAttribute('aria-expanded', 'false');
			if (focus) {
				toggle.focus();
			}
		}

		toggle.addEventListener('click', function () {
			if (panel.hidden) {
				open();
			} else {
				shut(false);
			}
		});
		close.addEventListener('click', function () {
			shut(true);
		});
		document.addEventListener('keydown', function (e) {
			if (e.key === 'Escape' && !panel.hidden) {
				shut(true);
			}
		});
		document.addEventListener('click', function (e) {
			if (!panel.hidden && !widget.contains(e.target)) {
				shut(false);
			}
		});

		Array.prototype.forEach.call(steps, function (button) {
			button.addEventListener('click', function () {
				state.text = Math.max(0, Math.min(sizes.length - 1, state.text + parseInt(button.getAttribute('data-mat-step'), 10)));
				change();
			});
		});
		Array.prototype.forEach.call(switches, function (button) {
			button.addEventListener('click', function () {
				var key = button.getAttribute('data-mat-toggle');
				state[key] = !state[key];
				if (key === 'reader') {
					shut(false);
				}
				change(key === 'reader' && state.reader);
				if (key === 'reader' && !state.reader) {
					toggle.focus();
				}
			});
		});
		select.addEventListener('change', function () {
			state.cb = select.value;
			change();
		});
		reset.addEventListener('click', function () {
			state = {};
			change();
		});

		if (reader) {
			reader.onExit = function () {
				state.reader = false;
				change();
				window.scrollTo(0, 0);
				if (toggle.offsetParent) {
					toggle.focus();
				}
			};
		}

		apply(false);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
