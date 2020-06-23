/**
 *
 * View (JS)
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @version 2020-06-22
 *
 */


window.NT = window['NT'] || {};


(function (NS) {

	function query(url, callback, filter = { date: 'month', taxonomy: ['category'] }, baseUrl = false) {
		url += (url.endsWith('/') ? '' : '/') + 'core/ajax.php';
		if (!baseUrl) baseUrl = window.location.origin + window.location.pathname;
		const msg = { query: parseQueryString('id') };
		msg.filter = filter;
		if (msg.query['id']) _createViewSingle(url, msg, callback, baseUrl);
		else _createViewArchive(url, msg, callback, baseUrl);
	}

	function queryRecentPosts(url, callback, count = 10, baseUrl = false) {
		url += (url.endsWith('/') ? '' : '/') + 'core/ajax.php';
		if (!baseUrl) baseUrl = window.location.origin + window.location.pathname;
		const msg = { query: { posts_per_page: count } };
		_createViewArchive(url, msg, callback, baseUrl);
	}


	// -------------------------------------------------------------------------


	function _createViewArchive(url, msg, callback, baseUrl) {
		sendRequest(url, msg, (res) => {
			if (!res || res.status !== 'success') res.posts = [];

			const view = {};
			view.posts = _processPostsForView(res.posts, baseUrl);
			view.navigation = {};
			view.navigation.pagination = _createPaginationView(msg, res.page_count, baseUrl);
			view.filter = _createFilterView(msg, res, baseUrl);

			callback(view);
		});
	}

	function _createViewSingle(url, msg, callback, baseUrl) {
		sendRequest(url, msg, (res) => {
			if (!res || res.status !== 'success') res.post = null;

			const view = {};
			[view.post] = _processPostsForView([res.post], baseUrl);
			view.navigation = {};
			view.navigation.post_navigation = _createPostNavigationView(msg, res.adjacent_post, baseUrl);
			view.filter = _createFilterView(msg, res, baseUrl);

			callback(view);
		});
	}


	// -------------------------------------------------------------------------


	function _processPostsForView(items, baseUrl) {
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
			if (i === cur) p['current'] = true;
			pages.push(p);
		}
		return {
			previous: ((1 < cur) ? pages[cur - 2].url : ''),
			next    : ((cur < pageCount) ? pages[cur].url : ''),
			pages   : pages
		};
	}

	function _createPostNavigationView(msg, adjacentPosts, baseUrl) {
		const ps = _processPostsForView([adjacentPosts.previous, adjacentPosts.next], baseUrl);
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
		const as = [];
		for (let i = 0; i < dates.length; i += 1) {
			const cq = _createCanonicalQuery({ date: dates[i].slug });
			const url = baseUrl + (cq.length ? ('?' + cq) : '');
			const p = { label: dates[i].label, url: url };
			if (dates[i].slug == cur /* == */) p['current'] = true;
			as.push(p);
		}
		return {
			[type]: as
		};
	}

	function _createTaxonomyFilterView(msg, taxonomy, terms, baseUrl) {
		const cur = (msg.query && msg.query[taxonomy]) ? msg.query[taxonomy] : '';
		const as = [];
		for (let i = 0; i < terms.length; i += 1) {
			const cq = _createCanonicalQuery({ [taxonomy]: terms[i].slug });
			const url = baseUrl + (cq.length ? ('?' + cq) : '');
			const p = { label: terms[i].label, url: url };
			if (terms[i].slug === cur) p['current'] = true;
			as.push(p);
		}
		return {
			[taxonomy]: as
		};
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
