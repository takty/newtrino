/**
 *
 * View (JS)
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @version 2020-07-09
 *
 */


window.NT = window['NT'] || {};


(function (NS) {

	const AJAX_API = 'core/view-ajax.php';

	function query(url, callback, args = {}) {
		let filter = { date: 'year', taxonomy: ['category'] };
		if (args.filter) filter = Object.assign(filter, args.filter);

		const option  = args.option   ? args.option   : {};
		let   baseUrl = args.base_url ? args.base_url : false;

		url += (url.endsWith('/') ? '' : '/') + AJAX_API;
		if (!baseUrl) baseUrl = window.location.origin + window.location.pathname;

		const msg = {
			query : parseQueryString('id'),
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
		const option  = args.option   ? args.option   : {};
		const count   = args.count    ? args.count    : 10;
		let   baseUrl = args.base_url ? args.base_url : false;

		url += (url.endsWith('/') ? '' : '/') + AJAX_API;
		if (!baseUrl) baseUrl = window.location.origin + window.location.pathname;

		const msg = {
			query:  { per_page: count },
			filter: {},
			option: option
		};
		_createViewArchive(url, callback, msg, baseUrl);
	}


	// -------------------------------------------------------------------------


	function _createViewArchive(url, callback, msg, baseUrl) {
		sendRequest(url, msg, (res) => {
			if (!res || res.status !== 'success') res.posts = [];
			const df = (msg.option && msg.option.date_format) ? msg.option.date_format : null;

			const view = {};
			view.posts = _processPostsForView(res.posts, df, baseUrl);
			view.navigation = {};
			view.navigation.pagination = _createPaginationView(msg, res.page_count, baseUrl);
			view.filter = _createFilterView(msg, res, baseUrl);

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
		for (let i = 0; i < items.length; i += 1) {
			const p = items[i];
			if (!p) continue;
			if (p['taxonomy']) {
				const tax = Object.entries(p['taxonomy']);
				for (let i = 0; i < tax.length; i += 1) {
					const [tax_slug, terms] = tax[i];
					const a = {};
					for (let j = 0; j < terms.length; j += 1) a[terms[j].slug] = true;
					p['taxonomy']['$' + tax_slug] = a;
				}
			}
			p.url = baseUrl + '?' + encodeQueryParam(p.id);
			if (dateFormat) {
				p['date']     = moment(p['date']).format(dateFormat);
				p['modified'] = moment(p['modified']).format(dateFormat);
			}
			if (p['meta']) {
				const meta = Object.entries(p['meta']);
				for (let i = 0; i < meta.length; i += 1) {
					const [key, val] = meta[i];
					if (key.indexOf('@') === -1) continue;
					if (!p['meta'] && p['meta'][key + '@type']) continue;
					if (p['meta'][key + '@type'] === 'date-range') {
						val[0] = moment(val[0]).format(dateFormat);
						val[1] = moment(val[1]).format(dateFormat);
					}
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
		const cur = (msg.query && msg.query.page) ? Math.max(1, Math.min(msg.query.page, pageCount)) : 1;
		const pages = [];
		for (let i = 1; i <= pageCount; i += 1) {
			const cq = _createCanonicalQuery(msg.query, { page: i });
			const url = baseUrl + (cq.length ? ('?' + cq) : '');
			const p = { label: i, url: url };
			if (i === cur) p['is_selected'] = true;
			pages.push(p);
		}
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
			const tes = Object.entries(res.taxonomy);
			for (let i = 0; i < tes.length; i += 1) {
				Object.assign(v.taxonomy, _createTaxonomyFilterView(msg, tes[i][0], tes[i][1], baseUrl));
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
				case 'year':  df = 'Y'; break;
				case 'month': df = 'Y-m'; break;
				case 'day':   df = 'Y-m-d'; break;
			}
		}
		const as = [];
		for (let i = 0; i < dates.length; i += 1) {
			const cq = _createCanonicalQuery({ date: dates[i].slug });
			const url = baseUrl + (cq.length ? ('?' + cq) : '');
			const label = _format_date_label('' + dates[i].slug, df);
			const p = { label: label, url: url };
			if (dates[i].slug == cur /* == */) p['is_selected'] = true;
			as.push(p);
		}
		return { [type]: as };
	}

	function _format_date_label( slug, df ) {
		let y = slug.substring(0, 4);
		let m = slug.substring(4, 2);
		let d = slug.substring(6, 2);
		if (!y) y = '1970';
		if (!m) m = '01';
		if (!d) d = '01';
		const date = new Date(parseInt(y), parseInt(m), parseInt(d));
		return moment(date).format(df);
	}

	function _createTaxonomyFilterView(msg, tax, terms, baseUrl) {
		const cur = (msg.query && msg.query[tax]) ? msg.query[tax] : '';
		const as = [];
		for (let i = 0; i < terms.length; i += 1) {
			const cq = _createCanonicalQuery({ [tax]: terms[i].slug });
			const url = baseUrl + (cq.length ? ('?' + cq) : '');
			const p = { label: terms[i].label, url: url };
			if (terms[i].slug === cur) p['is_selected'] = true;
			as.push(p);
		}
		return { [tax]: as };
	}


	// -------------------------------------------------------------------------


	function _createCanonicalQuery(ps, overwrite = []) {
		ps = Object.assign({}, ps, overwrite);
		const qs = [];
		if (ps['id']) {
			qs.push(['id', ps.id]);
		} else if (ps['date']) {
			qs.push(['date', ps.date]);
		} else if (ps['search']) {
			qs.push(['search', ps.search]);
		} else {  // taxonomy
			for (let tax in ps) {
				if (tax === 'id' || tax === 'date' || tax === 'search' || tax === 'page') continue;
				const ts = Array.isArray(ps[tax]) ? ps[tax].join(',') : ps[tax];
				qs.push([tax, ts]);
			}
		}
		if (ps['page']) {
			if (1 < ps.page) qs.push('page=' + ps.page);
		}
		return createQueryString(qs);
	}


	// -------------------------------------------------------------------------


	function renderTemplate(tmplSel, view) {
		function isEmptyArray(a) { return (Array.isArray(a) && a.length === 0); }

		const ts = document.querySelectorAll(tmplSel);
		for (let i = 0; i < ts.length; i += 1) {
			const tmpl = ts[i];
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
		for (let c of cs) {
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

		const es = Object.entries(ps);
		let defaultVal = '';
		for (let i = 0; i < es.length; ++i) {
			if (!es[i][1]) defaultVal = es[i][0];
		}
		if (defaultVal) ps[defaultKey] = defaultVal;

		return ps;
	}

	function createQueryString(params) {
		const kvs = [];
		if (Array.isArray(params)) {
			for (let i = 0; i < params.length; i += 1) {
				const _key = encodeQueryParam(params[i][0]);
				let v = params[i][1];
				if (v.constructor.name === 'Array' || v.constructor.name === 'Object') v = JSON.stringify(v);
				const _val = encodeQueryParam(v);
				kvs.push(_key + '=' + _val);
			}
		} else {
			for (let key in params) {
				const _key = encodeQueryParam(key);
				let v = params[key];
				if (v.constructor.name === 'Array' || v.constructor.name === 'Object') v = JSON.stringify(v);
				const _val = encodeQueryParam(v);
				kvs.push(_key + '=' + _val);
			}
		}
		return kvs.join('&');
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
