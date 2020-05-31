/**
 *
 * Ajax (JS)
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @version 2020-05-30
 *
 */


window.NT = window['NT'] || {};


(function (NS) {

	function render(url, tmplSel, bodyClass, filter = { date: 'month', taxonomy: ['category'] }) {
		const msg = { query: NT.parseQueryString('id') };
		msg.filter = filter;
		if (msg.query['id']) NT.renderSingle(url, msg, tmplSel, bodyClass);
		else NT.renderArchive(url, msg, tmplSel, bodyClass);
	}


	// -------------------------------------------------------------------------

/*
	msg {
		action: 'recent' or 'archive' or 'single'
		filter: {
			date: 'day' or 'month' or 'year'
			taxonomy: ['category']
		}
		query: {
			id: xxxx
			date: xxxx, xxxx-xx, xxxx-xx-xx
			<taxonomy>: term
			search: xxxx
			page: x
		}
	}

	view {
		posts: []
		post: {
			id:
			url:
		}
		filter: {
			date: {
				month: [
					{
						label:
						url:
					}
				]
			}
			search: {
				keyword: str
			}
			taxonomy: {
				category: [
					label:
					url:
				]
			}
		}
		navigation {
			pagination: {
				previous: p
				next: p
				pages: [
					{
						label:
						url:
					}
				]
			}
		}
	}
 */

	function renderRecentPosts(url, msg, tmplSel, bodyClass) {
		msg.action = 'recent';
		msg.query = { count: 10 }
		sendRequest(url, msg, (res) => {
			if (!res || res.status !== 'success') res.posts = [];

			const view = {};
			view.posts = processForTemplate(res.posts, msg.base_url);
			renderTemplate(tmplSel, view);

			if (bodyClass) document.body.classList.add(bodyClass);
		});
	}

	function renderArchive(url, msg, tmplSel, bodyClass) {
		msg.action = 'archive';
		sendRequest(url, msg, (res) => {
			if (!res || res.status !== 'success') res.posts = [];

			const view = {};
			view.posts = processForTemplate(res.posts, msg.base_url);
			view.navigation = {}
			view.navigation.pagination = createPaginationView(msg, res.page_count, msg.base_url);

			view.filter = createFilterView(msg, res, msg.base_url);
			renderTemplate(tmplSel, view);

			if (bodyClass) document.body.classList.add(bodyClass);
		});
	}

	function renderSingle(url, msg, tmplSel, bodyClass) {
		msg.action = 'single';
		sendRequest(url, msg, (res) => {
			if (!res || res.status !== 'success') res.posts = [];

			const view = {};
			[view.post] = processForTemplate([res.post], msg.base_url);
			view.navigation = {}
			view.navigation.post_navigation = createPostNavigationView(msg, res.adjacent_post, msg.base_url);

			view.filter = createFilterView(msg, res, msg.base_url);
			renderTemplate(tmplSel, view);

			if (bodyClass) document.body.classList.add(bodyClass);
		});
	}


	// -------------------------------------------------------------------------


	function processForTemplate(items, baseUrl = false) {
		if (!baseUrl) baseUrl = window.location.origin + window.location.pathname;
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
			p.url = baseUrl + '?' + encodeURIComponent(p.id);
		}
		return items;
	}

	function createPaginationView(msg, pageCount, baseUrl = false) {
		if (!baseUrl) baseUrl = window.location.origin + window.location.pathname;
		const cur = (msg.query && msg.query.page) ? Math.max(1, Math.min(msg.query.page, pageCount)) : 1;
		const pages = [];
		for (let i = 1; i <= pageCount; i += 1) {
			const cq = createCanonicalQuery(msg.query, { page: i });
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

	function createPostNavigationView(msg, adjacentPosts, baseUrl = false) {
		const ps = processForTemplate([adjacentPosts.previous, adjacentPosts.next], baseUrl);
		return {
			previous: ps[0],
			next    : ps[1],
		}
	}


	// -------------------------------------------------------------------------


	function createFilterView(msg, res, baseUrl = false) {
		const v = {};
		if (res.date) {
			const des = Object.entries(res.date);
			v.date = createDateFilterView(msg, des[0][0], des[0][1], baseUrl);
		}
		v.taxonomy = {};
		if (res.taxonomy) {
			const tes = Object.entries(res.taxonomy);
			for (let i = 0; i < tes.length; i += 1) {
				Object.assign(v.taxonomy, createTaxonomyFilterView(msg, tes[i][0], tes[i][1], baseUrl));
			}
		}
		v.search = {
			keyword: (msg.query && msg.query.search) ? msg.query.search : ''
		}
		return v;
	}

	function createDateFilterView(msg, type, dates, baseUrl = false) {
		if (!baseUrl) baseUrl = window.location.origin + window.location.pathname;
		const cur = (msg.query && msg.query.date) ? msg.query.date : '';
		const as = [];
		for (let i = 0; i < dates.length; i += 1) {
			const cq = createCanonicalQuery({ date: dates[i].slug });
			const url = baseUrl + (cq.length ? ('?' + cq) : '');
			const p = { label: dates[i].label, url: url };
			if (dates[i].slug == cur /* == */) p['current'] = true;
			as.push(p);
		}
		return {
			[type]: as
		};
	}

	function createTaxonomyFilterView(msg, taxonomy, terms, baseUrl = false) {
		if (!baseUrl) baseUrl = window.location.origin + window.location.pathname;
		const cur = (msg.query && msg.query[taxonomy]) ? msg.query[taxonomy] : '';
		const as = [];
		for (let i = 0; i < terms.length; i += 1) {
			const cq = createCanonicalQuery({ [taxonomy]: terms[i].slug});
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


	function createCanonicalQuery(ps, overwrite) {
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
			const tarSel = tmpl.dataset['target'];
			if (!tarSel) continue;
			const sec = tmpl.dataset['section'];
			if (sec && 0 < sec.length) {
				const k = sec.substring(1);
				if (sec[0] === '#') {
					if (view[k] === undefined ||  isEmptyArray(view[k]) || !view[k]) continue;
				}
				if (sec[0] === '^') {
					if (view[k] !== undefined && !isEmptyArray(view[k]) &&  view[k]) continue;
				}
			}
			const tar = document.querySelector(tarSel);
			tar.innerHTML = Mustache.render(tmpl.innerHTML, view);
		}
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
		const decode = (s) => { return decodeURIComponent(s.replace(/\+/g, ' ')); };
		const regex = /([^&=]+)=?([^&]*)/g;
		const str = window.location.search.substring(1);

		let m;
		const ps = {};
		while (m = regex.exec(str)) ps[decode(m[1])] = decode(m[2]);

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
				const _key = encodeURIComponent(params[i][0]);
				let v = params[i][1];
				if (v.constructor.name === 'Array' || v.constructor.name === 'Object') v = JSON.stringify(v);
				const _val = encodeURIComponent(v);
				kvs.push(_key + '=' + _val);
			}
		} else {
			for (let key in params) {
				const _key = encodeURIComponent(key);
				let v = params[key];
				if (v.constructor.name === 'Array' || v.constructor.name === 'Object') v = JSON.stringify(v);
				const _val = encodeURIComponent(v);
				kvs.push(_key + '=' + _val);
			}
		}
		return kvs.join('&');
	}

	function escapeHtml(str) {
		return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
	}

	function unescapeHtml(str) {
		str = str.replace(/<\w+(\s+("[^"]*"|'[^']*'|[^>])+)?(\/)?>|<\/\w+>/gi, '');
		return str.replace(/&lt;/g, '<').replace(/&gt;/g, '>').replace(/&amp;/g, '&');
	}


	// -------------------------------------------------------------------------


	NS.render             = render;
	NS.renderRecentPosts  = renderRecentPosts;
	NS.renderArchive      = renderArchive;
	NS.renderSingle       = renderSingle;

	NS.renderTemplate     = renderTemplate;
	NS.sendRequest        = sendRequest;
	NS.parseQueryString   = parseQueryString;
	NS.createQueryString  = createQueryString;
	NS.escapeHtml         = escapeHtml;
	NS.unescapeHtml       = unescapeHtml;

})(window.NT);
