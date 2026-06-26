/**
 * Dynamic Post Grid + Filter — front-end behaviour.
 *
 * Vanilla JS, no jQuery. Everything is namespaced under window.DPG and scoped
 * per element instance. Uses event delegation so cards injected via AJAX need
 * no re-binding. Progressive enhancement: with JS off, the filter bar submits
 * as a normal GET form and pagination falls back to links.
 *
 * Version: 1.2.1
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
				if (e.target && e.target.matches('select[data-dpg-taxonomy]')) {
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
			var selects = this.form.querySelectorAll('select[data-dpg-taxonomy]');
			selects.forEach(function (sel) {
				var tax = sel.getAttribute('data-dpg-taxonomy');
				if (tax && sel.value) {
					filters[tax] = sel.value;
				}
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
			this.form.querySelectorAll('select[data-dpg-taxonomy]').forEach(function (sel) {
				sel.value = '';
			});
			var search = this.form.querySelector('[data-dpg-search]');
			if (search) {
				search.value = '';
			}
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
			params.set('dpg_' + tax, this.state.filters[tax]);
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
	function init() {
		var roots = document.querySelectorAll('.dpg-instance');
		roots.forEach(function (root) {
			if (root.__dpg) {
				return;
			}
			root.__dpg = new Instance(root);
		});
	}

	DPG.init = init;

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
