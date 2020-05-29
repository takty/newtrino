/**
 *
 * Ajax (JS)
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @version 2020-05-28
 *
 */


window.NT = window['NT'] || {};


(function (NS) {

	function renderRecentPosts(url, args, tempSel, parentSel) {
		args = Object.assign({ action: 'recent', count: 10 }, args);
		sendRequest(url, args, (res) => {
			if (!res || res.status !== 'success') res.posts = [];
			NT.processForTemplate(res.posts);
			NT.render(res.posts, tempSel, parentSel);
		});
	}


	// -------------------------------------------------------------------------


	function render(items, tempSel, parentSel) {
		const tmpl = document.querySelector(tempSel);
		const parent = document.querySelector(parentSel);
		parent.innerHTML = Mustache.render(tmpl.innerHTML, { items });
	}

	function processForTemplate(items) {
		for (let i = 0; i < items.length; i += 1) {
			const p = items[i];
			if (p['taxonomy']) {
				const tax = Object.entries(p['taxonomy']);
				for (let i = 0; i < tax.length; i += 1) {
					const [tax_slug, terms] = tax[i];
					const a = {};
					for (let j = 0; j < terms.length; j += 1) {
						a[terms[j].slug] = true;
					}
					p['taxonomy']['$' + tax_slug] = a;
				}
			}
		}
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

	function createQueryString(params) {
		const kvs = [];
		for (key in params) {
			const _key = encodeURIComponent(key);
			const _val = encodeURIComponent(params[key]);
			kvs.push(_key + '=' + _val);
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

	NS.renderRecentPosts  = renderRecentPosts;
	NS.render             = render;
	NS.processForTemplate = processForTemplate;
	NS.sendRequest        = sendRequest;
	NS.createQueryString  = createQueryString;
	NS.escapeHtml         = escapeHtml;
	NS.unescapeHtml       = unescapeHtml;

})(window.NT);
