/**
 * List
 *
 * @author Takuto Yanagida
 * @version 2022-12-22
 */

document.addEventListener('DOMContentLoaded', () => {
	const nonce     = document.getElementById('nonce').value;
	const msgTrash  = document.getElementById('msg-trash').value;
	const msgDelPer = document.getElementById('msg-del-per').value;
	const msgEmpty  = document.getElementById('msg-empty-trash').value;

	const elmsNav     = document.querySelectorAll('.do-navigate');
	const btnsRemove  = document.getElementsByClassName('do-remove-post');
	const btnsRestore = document.getElementsByClassName('do-restore-post');
	const btnEmpty    = document.getElementById('do-empty-trash');

	for (const elm of elmsNav)     elm.addEventListener('SELECT' === elm.tagName ? 'change' : 'click', doNavigate);
	for (const btn of btnsRemove)  btn.addEventListener('click', doRemovePost);
	for (const btn of btnsRestore) btn.addEventListener('click', doRestorePost);
	btnEmpty.addEventListener('click', doEmptyTrash);

	function doNavigate(e) {
		const et = e.target;
		const nc = et.classList.contains('nc');
		setLocation('SELECT' === et.tagName ? et.value : et.dataset.href, nc);
	}

	function doRemovePost(e) {
		if (!confirmRemove(e.target)) return false;
		setLocation(e.target.dataset.href, true);
	}

	function doRestorePost(e) {
		setLocation(e.target.dataset.href, true);
	}

	function doEmptyTrash(e) {
		if (!confirm(msgEmpty)) return false;
		setLocation(e.target.dataset.href, true);
	}

	function confirmRemove(btn) {
		const tr = btn.parentElement.parentElement;

		const title = tr.getElementsByClassName('title')[0].innerText;
		const date  = tr.getElementsByClassName('date')[0].innerHTML.replace('</span><span>', ' ').replace(/(<([^>]+)>)/ig, '')

		if (btn.classList.contains('delete')) {
			if (!confirm(`${msgDelPer}\n"${title}"\n${date}`)) return false;
		} else {
			if (!confirm(`${msgTrash}\n"${title}"\n${date}`)) return false;
		}
		return true;
	}

	function setLocation(href, nc) {
		if ('' === href) {
			const qs = nc ? `?nonce=${nonce}` : '';
			document.location.href = location.pathname + qs;
		} else {
			const qs = nc ? `&nonce=${nonce}` : '';
			document.location.href = href + qs;
		}
	}


	// -------------------------------------------------------------------------


	const regex = /([^&=]+)=?([^&]*)/g;
	const str = window.location.search.substring(1);

	let m;
	const ps = [];
	while (m = regex.exec(str)) {
		if (m[1] === 'remove_id') continue;
		if (m[1] === 'restore_id') continue;
		if (m[1] === 'empty_trash') continue;
		ps.push(m[1] + '=' + m[2]);
	}
	if (str.length !== 0) {
		const q = (ps.length === 0) ? '' : ('?' + ps.join('&'));
		const newHref = window.location.origin + window.location.pathname + q;
		window.history.replaceState('', '', newHref);
	}


	// -------------------------------------------------------------------------


	const statSel_s = document.getElementsByClassName('post-status');

	for (const statSel of statSel_s) {
		statSel.addEventListener('focus', e => {
			statSel.dataset.prev = e.target.selectedIndex;
		});
		statSel.addEventListener('change', e => {
			const s  = e.target.value;
			const id = e.target.dataset.id;
			setPostStatus(id, s, statSel);
			clearNotice();
		});
	}

	function setPostStatus(id, stat, statSel) {
		const req = new XMLHttpRequest();
		req.addEventListener('load', e => {
			const msg = e.currentTarget.responseText.match(/<result>([\s\S]*?)<\/result>/i);
			if (msg !== null && msg[1] === 'success') {
				statSel.dataset.prev = statSel.selectedIndex;
			} else {
				statSel.selectedIndex = statSel.dataset.prev;
			}
		});
		req.addEventListener('error', () => {
			statSel.selectedIndex = statSel.dataset.prev;
		});
		req.open('post', 'ajax.php', true);
		req.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
		req.send(`mode=status&id=${id}&val=${stat}&cache=${Date.now()}&nonce=${nonce}`);
	}

	function clearNotice() {
		const ntc = document.querySelector('.site-header .notice');
		if (ntc) ntc.style.display = 'none';
	}
});


// -----------------------------------------------------------------------------


window.addEventListener('load', () => {  // Must when 'load' event
	const selNewPost = document.getElementById('sel-new-post');
	const optFirst   = selNewPost.querySelector('option:first-child');
	selNewPost.value = optFirst.value;
});
