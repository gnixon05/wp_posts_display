/**
 * Dynamic Post Grid + Filter — front-end behaviour.
 *
 * Vanilla JS, no jQuery. Everything is namespaced under window.DPG and scoped
 * per element instance. Uses event delegation so cards injected via AJAX need
 * no re-binding. Progressive enhancement: with JS off, the filter bar submits
 * as a normal GET form and pagination falls back to links.
 *
 * Version: 1.4.1
 */
(function () {
	'use strict';

	if (typeof window.DPG_Data === 'undefined') {
		return;
	}

	var DPG = window.DPG = window.DPG || {};
	var DATA = window.DPG_Data;

	/* --------------------------------------------------------------- *
	 * Helpers
	 * --------------------------------------------------------------- */
	function debounce(fn, wait) {
		var t;
		return function () {
			var ctx = this;
			var args = arguments;
			clearTimeout(t);
			t = setTimeout(function () {
				fn.apply(ctx, args);
			}, wait);
		};
	}

	function postForm(params) {
		var body = new URLSearchParams();
		Object.keys(params).forEach(function (k) {
			body.append(k, params[k]);
		});
		return fetch(DATA.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: body.toString()
		}).then(function (r) {
			return r.json();
		});
	}

	/* --------------------------------------------------------------- *
	 * Instance controller
	 * --------------------------------------------------------------- */
	function Instance(root) {
		this.root = root;
		this.id = root.getAttribute('data-dpg-instance') || '';
		this.config = root.getAttribute('data-dpg-config') || '{}';
		this.nonce = root.getAttribute('data-dpg-nonce') || DATA.nonce;
		this.applyMode = root.getAttribute('data-dpg-apply') || 'live';

		this.results = root.querySelector('[data-dpg-results]');
		this.form = root.querySelector('[data-dpg-filter]');
		this.loadmoreWrap = root.querySelector('[data-dpg-loadmore]');

		this.state = {
			filters: {},
			s: '',
			page: 1,
			loading: false
		};

		this.bind();
		this.observeInfinite();
	}

	Instance.prototype.bind = function () {
		var self = this;

		// Filter bar interactions.
		if (this.form) {
			// Block native submit; we handle it (GET fallback kicks in only w/o JS).
			this.form.addEventListener('submit', function (e) {
				e.preventDefault();
				self.applyFilters();
			});

			this.form.addEventListener('change', function (e) {
				if (e.target && e.target.matches('input[data-dpg-taxonomy]')) {
					var ms = e.target.closest('[data-dpg-ms]');
					self.updateSummary(ms);
					// Single-select (radio): the choice is made, so close the panel.
					if (ms && e.target.type === 'radio') {
						ms.open = false;
					}
					if (self.applyMode === 'live') {
						self.applyFilters();
					}
				}
			});

			var search = this.form.querySelector('[data-dpg-search]');
			if (search) {
				var run = debounce(function () {
					if (self.applyMode === 'live') {
						self.applyFilters();
					}
				}, 400);
				search.addEventListener('input', run);
			}

			this.form.addEventListener('click', function (e) {
				if (e.target && e.target.closest('[data-dpg-reset]')) {
					e.preventDefault();
					self.reset();
				}
			});

			// Mouse-wheel scroll for the dropdown panels. Some themes hijack the
			// global wheel event (smooth-scroll libraries), so scroll manually and
			// stop propagation to keep the panel scrollable on hover.
			this.form.querySelectorAll('.dpg-ms-panel').forEach(function (panel) {
				panel.addEventListener('wheel', function (e) {
					if (panel.scrollHeight <= panel.clientHeight) {
						return;
					}
					panel.scrollTop += e.deltaY;
					e.preventDefault();
					e.stopPropagation();
				}, { passive: false });
			});
		}

		// Load more (delegated on the wrap so it survives nothing here, but the
		// button itself is stable).
		if (this.loadmoreWrap) {
			this.loadmoreWrap.addEventListener('click', function (e) {
				if (e.target && e.target.closest('.dpg-loadmore-btn')) {
					e.preventDefault();
					self.loadMore();
				}
			});
			this.state.page = parseInt(this.loadmoreWrap.getAttribute('data-dpg-page'), 10) || 1;
		}
	};

	Instance.prototype.collectFilters = function () {
		var filters = {};
		var s = '';
		if (this.form) {
			// Gather every checked term (checkbox or radio), grouped by taxonomy.
			// The single-select "All" radio has an empty value and is skipped.
			var boxes = this.form.querySelectorAll('input[data-dpg-taxonomy]:checked');
			boxes.forEach(function (box) {
				var tax = box.getAttribute('data-dpg-taxonomy');
				if (!tax || box.value === '') {
					return;
				}
				if (!filters[tax]) {
					filters[tax] = [];
				}
				filters[tax].push(box.value);
			});
			var search = this.form.querySelector('[data-dpg-search]');
			if (search) {
				s = search.value || '';
			}
		}
		this.state.filters = filters;
		this.state.s = s;
		return { filters: filters, s: s };
	};

	/* Update a multi-select's summary label ("All" / term name / "N selected"). */
	Instance.prototype.updateSummary = function (ms) {
		if (!ms) {
			return;
		}
		var valueEl = ms.querySelector('[data-dpg-ms-value]');
		if (!valueEl) {
			return;
		}
		// Count only real selections (skip the single-select "All" radio).
		var checked = Array.prototype.slice.call(
			ms.querySelectorAll('input[data-dpg-taxonomy]:checked')
		).filter(function (b) { return b.value !== ''; });
		var n = checked.length;
		var text;
		if (n === 0) {
			text = DATA.i18n.all;
		} else if (n === 1) {
			var lbl = checked[0].parentNode.querySelector('.dpg-ms-option-label');
			text = lbl ? lbl.textContent.trim() : DATA.i18n.all;
		} else {
			text = (DATA.i18n.selected || '%d selected').replace('%d', n);
		}
		valueEl.textContent = text;
		ms.classList.toggle('has-selection', n > 0);
	};

	Instance.prototype.updateAllSummaries = function () {
		var self = this;
		if (!this.form) {
			return;
		}
		this.form.querySelectorAll('[data-dpg-ms]').forEach(function (ms) {
			self.updateSummary(ms);
		});
	};

	Instance.prototype.applyFilters = function () {
		var self = this;
		var picked = this.collectFilters();

		this.setLoading(true);
		this.syncUrl();

		postForm({
			action: 'dpg_filter',
			nonce: this.nonce,
			config: this.config,
			filters: JSON.stringify(picked.filters),
			s: picked.s
		}).then(function (res) {
			if (res && res.success) {
				self.results.innerHTML = res.data.html;
				capAllPills(self.results);
				self.state.page = 1;
				self.refreshLoadMore(res.data.max_pages, 1);
			} else {
				self.showError();
			}
		}).catch(function () {
			self.showError();
		}).then(function () {
			self.setLoading(false);
		});
	};

	Instance.prototype.loadMore = function () {
		if (this.state.loading) {
			return;
		}
		var self = this;
		var btn = this.loadmoreWrap ? this.loadmoreWrap.querySelector('.dpg-loadmore-btn') : null;
		var next = this.state.page + 1;

		this.state.loading = true;
		if (btn) {
			btn.classList.add('is-loading');
		}

		postForm({
			action: 'dpg_load_more',
			nonce: this.nonce,
			config: this.config,
			filters: JSON.stringify(this.state.filters),
			s: this.state.s,
			paged: next
		}).then(function (res) {
			if (res && res.success) {
				var grid = self.results.querySelector('[data-dpg-grid]');
				if (grid && res.data.html) {
					grid.insertAdjacentHTML('beforeend', res.data.html);
					capAllPills(grid);
				}
				self.state.page = res.data.page || next;
				if (res.data.done) {
					self.hideLoadMore();
				}
			} else {
				self.showError();
			}
		}).catch(function () {
			self.showError();
		}).then(function () {
			self.state.loading = false;
			if (btn) {
				btn.classList.remove('is-loading');
			}
		});
	};

	Instance.prototype.reset = function () {
		if (this.form) {
			this.form.querySelectorAll('input[data-dpg-taxonomy]').forEach(function (box) {
				// Radios reset to their "All" (empty-value) option; checkboxes clear.
				box.checked = ( 'radio' === box.type && '' === box.value );
			});
			var search = this.form.querySelector('[data-dpg-search]');
			if (search) {
				search.value = '';
			}
			this.updateAllSummaries();
			// Close any open dropdowns.
			this.form.querySelectorAll('[data-dpg-ms][open]').forEach(function (ms) {
				ms.open = false;
			});
		}
		this.applyFilters();
	};

	Instance.prototype.observeInfinite = function () {
		if (!this.loadmoreWrap) {
			return;
		}
		if (this.loadmoreWrap.getAttribute('data-dpg-infinite') !== '1') {
			return;
		}
		if (!('IntersectionObserver' in window)) {
			return;
		}
		var self = this;
		this.io = new IntersectionObserver(function (entries) {
			entries.forEach(function (entry) {
				if (entry.isIntersecting) {
					var btn = self.loadmoreWrap.querySelector('.dpg-loadmore-btn');
					if (btn && !btn.hidden) {
						self.loadMore();
					}
				}
			});
		}, { rootMargin: '200px' });
		this.io.observe(this.loadmoreWrap);
	};

	/* ---- UI state ---- */
	Instance.prototype.setLoading = function (on) {
		if (!this.results) {
			return;
		}
		this.results.classList.toggle('is-loading', !!on);
	};

	Instance.prototype.showError = function () {
		if (this.results) {
			this.results.innerHTML = '<div class="dpg-empty" role="status">' + DATA.i18n.error + '</div>';
		}
	};

	Instance.prototype.refreshLoadMore = function (maxPages, page) {
		if (!this.loadmoreWrap) {
			return;
		}
		var btn = this.loadmoreWrap.querySelector('.dpg-loadmore-btn');
		this.loadmoreWrap.setAttribute('data-dpg-max', maxPages);
		this.loadmoreWrap.setAttribute('data-dpg-page', page);
		if (btn) {
			if (page >= maxPages) {
				btn.hidden = true;
			} else {
				btn.hidden = false;
			}
		}
	};

	Instance.prototype.hideLoadMore = function () {
		if (!this.loadmoreWrap) {
			return;
		}
		var btn = this.loadmoreWrap.querySelector('.dpg-loadmore-btn');
		if (btn) {
			btn.hidden = true;
		}
	};

	/* ---- URL sync (shareable / back-button friendly) ---- */
	Instance.prototype.syncUrl = function () {
		if (!window.history || !window.history.replaceState) {
			return;
		}
		var url = new URL(window.location.href);
		var params = url.searchParams;

		// Clear our namespaced params first.
		Array.from(params.keys()).forEach(function (key) {
			if (key.indexOf('dpg_') === 0) {
				params.delete(key);
			}
		});

		Object.keys(this.state.filters).forEach(function (tax) {
			// Append one dpg_{tax}[] entry per selected term (array-friendly).
			(this.state.filters[tax] || []).forEach(function (val) {
				params.append('dpg_' + tax + '[]', val);
			});
		}, this);

		if (this.state.s) {
			params.set('dpg_s', this.state.s);
		}

		var qs = params.toString();
		var newUrl = url.pathname + (qs ? '?' + qs : '') + url.hash;
		window.history.replaceState({ dpg: true }, '', newUrl);
	};

	/* --------------------------------------------------------------- *
	 * Boot
	 * --------------------------------------------------------------- */
	// Close any open multi-select dropdown when clicking outside it (bound once).
	function bindGlobalClose() {
		if (DPG._msCloseBound) {
			return;
		}
		DPG._msCloseBound = true;
		document.addEventListener('click', function (e) {
			document.querySelectorAll('.dpg-ms[open]').forEach(function (ms) {
				if (!ms.contains(e.target)) {
					ms.open = false;
				}
			});
		});
	}

	/* ---- Taxonomy pills: cap to N rows, manage the "+N more" toggle ---- */

	function ensureMoreButton(wrap) {
		var btn = wrap.querySelector('.dpg-pill-more');
		if (!btn) {
			btn = document.createElement('button');
			btn.type = 'button';
			btn.className = 'dpg-pill-more';
			wrap.appendChild(btn);
		}
		return btn;
	}

	// Measure the rendered pills and hide everything past `data-pill-rows` rows,
	// then set the "+N more" count. Runs on load, resize and after AJAX updates.
	function capPillRows(tax) {
		if (!tax) {
			return;
		}
		var rows = parseInt(tax.getAttribute('data-pill-rows'), 10);
		var wrap = tax.querySelector('.dpg-tax-pills');
		if (!rows || rows < 1 || !wrap) {
			return;
		}
		var pills = Array.prototype.slice.call(wrap.querySelectorAll('.dpg-tax-pill'));
		if (!pills.length) {
			return;
		}
		var moreTpl = tax.getAttribute('data-more-tpl') || '+%d more';
		var lessLabel = tax.getAttribute('data-less-label') || 'Show less';
		var btn = wrap.querySelector('.dpg-pill-more');

		// Fresh measurement: clear previous row-hides.
		pills.forEach(function (p) { p.classList.remove('dpg-tax-pill--rowhide'); });

		// Pills hidden by the optional server-side count cap.
		var extraCount = pills.filter(function (p) {
			return p.classList.contains('dpg-tax-pill--extra');
		}).length;

		// Expanded: show everything, button becomes "Show less".
		if (tax.classList.contains('is-expanded')) {
			if (btn) {
				btn.hidden = false;
				btn.textContent = lessLabel;
			}
			return;
		}

		// Measure only the pills that are not server-hidden.
		var visible = pills.filter(function (p) {
			return !p.classList.contains('dpg-tax-pill--extra');
		});
		if (!visible.length) {
			if (btn) {
				btn.hidden = extraCount <= 0;
				if (extraCount > 0) { btn.textContent = moreTpl.replace('%d', extraCount); }
			}
			return;
		}

		// Hide the button during measurement so it doesn't add a row.
		if (btn) { btn.hidden = true; }

		var tops = [];
		visible.forEach(function (p) {
			if (tops.indexOf(p.offsetTop) === -1) { tops.push(p.offsetTop); }
		});
		tops.sort(function (a, b) { return a - b; });
		var allowed = tops.slice(0, rows);

		var hidden = 0;
		visible.forEach(function (p) {
			if (allowed.indexOf(p.offsetTop) === -1) {
				p.classList.add('dpg-tax-pill--rowhide');
				hidden++;
			}
		});

		var total = hidden + extraCount;
		if (total <= 0) {
			if (btn) { btn.hidden = true; }
			return;
		}

		btn = ensureMoreButton(wrap);
		btn.hidden = false;
		btn.textContent = moreTpl.replace('%d', total);

		// The button itself may wrap to a new row; hide trailing pills until it fits.
		var kept = visible.filter(function (p) { return !p.classList.contains('dpg-tax-pill--rowhide'); });
		var guard = 0;
		while (kept.length && guard < 200) {
			var lastTop = kept[kept.length - 1].offsetTop;
			if (btn.offsetTop <= lastTop) {
				break;
			}
			var last = kept.pop();
			last.classList.add('dpg-tax-pill--rowhide');
			total++;
			btn.textContent = moreTpl.replace('%d', total);
			guard++;
		}
	}

	function capAllPills(root) {
		(root || document).querySelectorAll('.dpg-tax').forEach(function (tax) {
			capPillRows(tax);
		});
	}
	DPG.capAllPills = capAllPills;

	// Delegated "+N more" / "Show less" toggle (bound once; survives AJAX cards).
	function bindPillToggle() {
		if (DPG._pillBound) {
			return;
		}
		DPG._pillBound = true;
		document.addEventListener('click', function (e) {
			var btn = e.target && e.target.closest ? e.target.closest('.dpg-pill-more') : null;
			if (!btn) {
				return;
			}
			e.preventDefault();
			var tax = btn.closest('.dpg-tax');
			if (!tax) {
				return;
			}
			tax.classList.toggle('is-expanded');
			capPillRows(tax);
		});

		// Re-measure on resize (debounced) and once fonts/layout settle.
		var onResize = debounce(function () { capAllPills(document); }, 150);
		window.addEventListener('resize', onResize);
		window.addEventListener('load', function () { capAllPills(document); });
	}

	function init() {
		bindGlobalClose();
		bindPillToggle();
		var roots = document.querySelectorAll('.dpg-instance');
		roots.forEach(function (root) {
			if (root.__dpg) {
				return;
			}
			root.__dpg = new Instance(root);
		});
		capAllPills(document);
	}

	DPG.init = init;

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
