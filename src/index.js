/**
 *
 * Index (JS)
 *
 * @author Takuto Yanagida
 * @version 2022-03-18
 *
 */


window.NT = window['NT'] || {};


(function (NS) {

	const AJAX_API = 'index.php';

	function query(url, callback, args = {}) {
		let   query   = args.query    ? args.query    : {};
		let   filter  = args.filter   ? args.filter   : {};
		const option  = args.option   ? args.option   : {};
		let   baseUrl = args.base_url ? args.base_url : false;

		query  = Object.assign(parseQueryString('id'), query);
		filter = Object.assign({ date: 'year' }, filter);

		url += (url.endsWith('/') ? '' : '/') + AJAX_API;
		if (!baseUrl) baseUrl = window.location.origin + window.location.pathname;

		const msg = {
			query : query,
			filter: filter,
			option: option
		};
		if (msg.query['id']) {
			_createViewSingle(url, callback, msg, baseUrl);
		} else {
			_createViewArchive(url, callback, msg, baseUrl);
		}
	}

	function queryRecentPosts(url, callback, args = {}) {
		const count   = args.count    ? args.count    : 10;
		const query   = args.query    ? args.query    : {};
		const option  = args.option   ? args.option   : {};
		let   baseUrl = args.base_url ? args.base_url : null;

		url += (url.endsWith('/') ? '' : '/') + AJAX_API;
		if (!baseUrl) {
			baseUrl = window.location.origin + window.location.pathname;
		}
		if (Array.isArray(query)) {
			for (const q of query) {
				if (!q['per_page']) {
					q['per_page'] = count;
				}
			}
		} else {
			if (!query['per_page']) {
				query['per_page'] = count;
			}
		}
		const msg = {
			query : query,
			filter: {},
			option: option
		};
		_createViewArchive(url, callback, msg, baseUrl, count);
	}


	// -------------------------------------------------------------------------


	function _createViewArchive(url, callback, msg, baseUrl, count = -1) {
		sendRequest(url, msg, (res) => {
			if (!res || res.status !== 'success') res.posts = [];
			const df = (msg.option && msg.option.date_format) ? msg.option.date_format : null;

			const view = {};
			view.posts = _processPostsForView(res.posts, df, baseUrl);
			view.navigation = {};
			view.navigation.pagination = _createPaginationView(msg, res.page_count, baseUrl);
			view.filter = _createFilterView(msg, res, baseUrl);

			if (0 < count && count < view.posts.length) {
				view.posts.length = count;
			}
			callback(view);
		});
	}

	function _createViewSingle(url, callback, msg, baseUrl) {
		sendRequest(url, msg, (res) => {
			if (!res || res.status !== 'success') res.post = null;
			const df = (msg.option && msg.option.date_format) ? msg.option.date_format : null;

			const view = {};
			[view.post] = _processPostsForView([res.post], df, baseUrl);
			view.navigation = {};
			view.navigation.post_navigation = _createPostNavigationView(msg, res.adjacent_post, baseUrl);
			view.filter = _createFilterView(msg, res, baseUrl);

			callback(view);
		});
	}


	// -------------------------------------------------------------------------


	function _processPostsForView(items, dateFormat, baseUrl) {
		for (const p of items) {
			if (!p) continue;
			if (p['taxonomy']) {
				const tax = Object.entries(p['taxonomy']);
				for (const [tax_slug, terms] of tax) {
					const a = {};
					for (const t of terms) a[t.slug] = true;
					p['taxonomy']['$' + tax_slug] = a;
				}
			}
			p.url = baseUrl + '?' + encodeQueryParam(p.id);
			if (dateFormat) {
				p['date']     = formatDate(p['date'], dateFormat);
				p['modified'] = formatDate(p['modified'], dateFormat);
			}
			if (p['meta']) {
				for (const [key, val] of Object.entries(p['meta'])) {
					if (key.includes('@')) continue;
					if (p['meta'][key + '@type'] === 'date') {
						val = formatDate(val, dateFormat);
					}
					if (p['meta'][key + '@type'] === 'date-range') {
						val['from'] = val['from'] !== undefined ? formatDate(val['from'], dateFormat) : '';
						val['to']   = val['to']   !== undefined ? formatDate(val['to'], dateFormat) : '';
					}
					p['meta'][key] = val;
				}
			}
			if (p['class']) {
				const cs = p['class'].join(' ');
				p['class@joined'] = cs;
			}
		}
		return items;
	}

	function _createPaginationView(msg, pageCount, baseUrl) {
		const c = (msg.query && msg.query.page) ? parseInt(msg.query.page, 10) : 1;
		const cur = Math.max(1, Math.min(c, pageCount));
		const pages = [];
		for (let i = 1; i <= pageCount; i += 1) {
			const url = createCanonicalUrl(baseUrl, msg.query, { page: i });
			const p = { label: i, url: url };
			if (i === cur) p['is_selected'] = true;
			pages.push(p);
		}
		if (pages.length === 1) return null;
		return {
			previous: ((1 < cur) ? pages[cur - 2].url : ''),
			next    : ((cur < pageCount) ? pages[cur].url : ''),
			pages   : pages
		};
	}

	function _createPostNavigationView(msg, adjacentPosts, baseUrl) {
		const df = (msg.option && msg.option.date_format) ? msg.option.date_format : null;
		const ps = _processPostsForView([adjacentPosts.previous, adjacentPosts.next], df, baseUrl);
		return {
			previous: ps[0],
			next    : ps[1],
		};
	}


	// -------------------------------------------------------------------------


	function _createFilterView(msg, res, baseUrl) {
		const v = {};
		if (res.date) {
			const des = Object.entries(res.date);
			v.date = _createDateFilterView(msg, des[0][0], des[0][1], baseUrl);
		}
		v.taxonomy = {};
		if (res.taxonomy) {
			for (const [tax, terms] of Object.entries(res.taxonomy)) {
				Object.assign(v.taxonomy, _createTaxonomyFilterView(msg, tax, terms, baseUrl));
			}
		}
		v.search = {
			keyword: (msg.query && msg.query.search) ? msg.query.search : ''
		};
		return v;
	}

	function _createDateFilterView(msg, type, dates, baseUrl) {
		const cur = (msg.query && msg.query.date) ? msg.query.date : '';
		let df = '';
		if (msg.filter && msg.filter.date_format) {
			df = msg.filter.date_format;
		} else {
			switch (type) {
				case 'year':  df = 'yyyy'; break;
				case 'month': df = 'yyyy-MM'; break;
				case 'day':   df = 'yyyy-MM-dd'; break;
			}
		}
		const as = [];
		for (const d of dates) {
			const label = _formatDateLabel('' + d.slug, df);
			const url   = createCanonicalUrl(baseUrl, { date: d.slug });
			const p     = { label, url };
			if (d.slug == cur /* == */) p['is_selected'] = true;
			as.push(p);
		}
		return { [type]: as };
	}

	function _formatDateLabel(slug, df) {
		const y = 3 < slug.length ? slug.substring(0, 4) : '1970';
		const m = 5 < slug.length ? slug.substring(4, 2) : '01';
		const d = 7 < slug.length ? slug.substring(6, 2) : '01';
		return formatDate(`${y}-${m}-${d}`, df);
	}

	function _createTaxonomyFilterView(msg, tax, terms, baseUrl) {
		const cur = (msg.query && msg.query[tax]) ? msg.query[tax] : '';
		const as = [];
		for (const t of terms) {
			const label = t.label;
			const url   = createCanonicalUrl(baseUrl, { [tax]: t.slug });
			const p     = { label, url };
			if (t.slug === cur) p['is_selected'] = true;
			as.push(p);
		}
		return { [tax]: as };
	}


	// -------------------------------------------------------------------------


	function renderTemplate(tmplSel, view) {
		function isEmptyArray(a) { return (Array.isArray(a) && a.length === 0); }

		const ts = document.querySelectorAll(tmplSel);
		for (const tmpl of ts) {
			const sec = tmpl.dataset.section;
			if (sec && 0 < sec.length) {
				const k = sec.substring(1);
				if (sec[0] === '#') {
					if (view[k] === undefined ||  isEmptyArray(view[k]) || !view[k]) {
						tmpl.parentElement.removeChild(tmpl);
						continue;
					}
				}
				if (sec[0] === '^') {
					if (view[k] !== undefined && !isEmptyArray(view[k]) &&  view[k]) {
						tmpl.parentElement.removeChild(tmpl);
						continue;
					}
				}
			}
			const frag = _createRenderedFragment(tmpl, view);
			const app = tmpl.dataset.append ? document.querySelector(tmpl.dataset.append) : null;
			const rep = tmpl.dataset.replace ? document.querySelector(tmpl.dataset.replace) : null;
			if (app) {
				app.appendChild(frag);
			} else if (rep) {
				rep.textContent = null;
				rep.appendChild(frag);
			} else {
				const tar = tmpl.parentElement;
				if (tar) tar.replaceChild(frag, tmpl);
			}
		}
	}

	function _createRenderedFragment(tmpl, view) {
		const frag = document.createDocumentFragment();
		const t = document.createElement('div');
		t.innerHTML = Mustache.render(tmpl.innerHTML, view);

		const cs = [].slice.call(t.childNodes, 0);
		for (const c of cs) {
			frag.appendChild(c);
		}
		return frag;
	}


	// -------------------------------------------------------------------------


	function parseQueryString(defaultKey) {
		const regex = /([^&=]+)=?([^&]*)/g;
		const str = window.location.search.substring(1);

		let m;
		const ps = {};
		while (m = regex.exec(str)) ps[decodeQueryParam(m[1])] = decodeQueryParam(m[2]);

		let defaultVal = '';
		for (const [key, val] of Object.entries(ps)) {
			if (defaultKey && !val && !str.includes(key + '=')) {
				defaultVal = key;
			}
		}
		if (defaultVal) ps[defaultKey] = defaultVal;

		return ps;
	}

	function createQueryString(params) {
		const kvs = [];
		params = Array.isArray(params) ? params : Object.entries(params);
		for (const p of params) {
			const _key = encodeQueryParam(p[0]);
			let v = p[1];
			if (v.constructor.name === 'Array' || v.constructor.name === 'Object') {
				v = JSON.stringify(v);
			}
			const _val = encodeQueryParam(v);
			kvs.push(_key + '=' + _val);
		}
		return kvs.join('&');
	}

	function createCanonicalUrl(baseUrl, ps, overwrite = []) {
		const cq = createCanonicalQuery(ps, overwrite);
		return baseUrl + (cq.length ? ('?' + cq) : '');
	}

	function createCanonicalQuery(ps, overwrite = []) {
		ps = Object.assign({}, ps, overwrite);
		const qs = [];
		if (ps['id'])       qs.push(['id',       ps.id      ]);
		if (ps['type'])     qs.push(['type',     ps.type    ]);
		if (ps['date'])     qs.push(['date',     ps.date    ]);
		if (ps['search'])   qs.push(['search',   ps.search  ]);
		if (ps['per_page']) qs.push(['per_page', ps.per_page]);

		const keys = ['id', 'type', 'date', 'search', 'per_page', 'page', 'taxonomy'];
		for (const [tax, v] of Object.entries(ps)) {  // taxonomy
			if (keys.includes(tax)) continue;
			const ts = Array.isArray(v) ? v.join(',') : v;
			if (ts.length === 0) continue;
			qs.push([tax, ts]);
		}
		if (ps['page'] && 1 < ps.page) qs.push(['page', ps.page]);
		return createQueryString(qs);
	}


	// -------------------------------------------------------------------------


	function sendRequest(url, params, callback) {
		const xhr = new XMLHttpRequest();
		xhr.open('POST', url);
		xhr.onload = () => {
			const json = JSON.parse(xhr.response);
			callback(json);
		};
		xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
		xhr.send(createQueryString(params));
	}

	function encodeQueryParam(str) {
		return encodeURIComponent(str).replace(/[!'()*]/g, (c) => {
			return '%' + c.charCodeAt(0).toString(16);
		});
	}

	function decodeQueryParam(str) {
		return decodeURIComponent(str.replace(/\+/g, ' '));
	}

	function escapeHtml(str) {
		return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
	}

	function unescapeHtml(str) {
		str = str.replace(/<\w+(\s+("[^"]*"|'[^']*'|[^>])+)?(\/)?>|<\/\w+>/gi, '');
		return str.replace(/&lt;/g, '<').replace(/&gt;/g, '>').replace(/&amp;/g, '&');
	}

	function formatDate(d, format) {
		format = format.replaceAll('Y', 'y');  // Convert moment.js to luxon.
		return luxon.DateTime.fromSQL(d).toFormat(format);
	}


	// -------------------------------------------------------------------------


	NS.query             = query;
	NS.queryRecentPosts  = queryRecentPosts;

	NS.renderTemplate    = renderTemplate;
	NS.sendRequest       = sendRequest;
	NS.parseQueryString  = parseQueryString;
	NS.createQueryString = createQueryString;
	NS.escapeHtml        = escapeHtml;
	NS.unescapeHtml      = unescapeHtml;

})(window.NT);
