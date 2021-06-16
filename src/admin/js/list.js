/**
 *
 * List (JS)
 *
 * @author Takuto Yanagida
 * @version 2021-06-16
 *
 */


// @include _common.js

document.addEventListener('DOMContentLoaded', () => {
	const btnsRemove  = document.getElementsByClassName('remove-post');
	const btnsRestore = document.getElementsByClassName('restore-post');
	const btnEmpty    = document.getElementById('btn-empty-trash');

	const msgTrash  = document.getElementById('message-trash').value;
	const msgDelPer = document.getElementById('message-delete-permanently').value;
	const msgEmpty  = document.getElementById('message-empty-trash').value;

	for (let btn of btnsRemove) {
		btn.addEventListener('click', onRemoveClicked);
	}
	for (let btn of btnsRestore) {
		btn.addEventListener('click', onRestoreClicked);
	}
	btnEmpty.addEventListener('click', onEmptyTrashClicked);

	function onRemoveClicked(e) {
		const href = e.target.dataset.href;
		const tr = e.target.parentElement.parentElement;

		const title = tr.getElementsByClassName('title')[0].innerText;
		const date  = tr.getElementsByClassName('date')[0].innerHTML.replace('</span><span>', ' ').replace(/(<([^>]+)>)/ig, '')

		if (e.target.classList.contains('delper')) {
			if (!confirm(`${msgDelPer}\n"${title}"\n${date}`)) return false;
		} else {
			if (!confirm(`${msgTrash}\n"${title}"\n${date}`)) return false;
		}
		window.location.href = href;
	}

	function onRestoreClicked(e) {
		const href = e.target.dataset.href;
		window.location.href = href;
	}

	function onEmptyTrashClicked(e) {
		const href = e.target.dataset.href;
		if (!confirm(msgEmpty)) return false;
		window.location.href = href;
	}

	function clearErrorMessage() {
		const mn = document.getElementById('message-error');
		if (mn) mn.style.display = 'none';
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


	const statSels = document.getElementsByClassName('post-status');

	for (const statSel of statSels) {
		statSel.addEventListener('focus', (e) => {
			statSel.dataset.prev = e.target.selectedIndex;
		});
		statSel.addEventListener('change', (e) => {
			const s = e.target.value;
			const id = e.target.parentElement.parentElement.parentElement.dataset.id;
			setPostStatus(id, s, statSel);
			clearErrorMessage();
		});
	}

	function setPostStatus(id, status, statSel) {
		const n = document.getElementById('nonce');
		const nonce = n ? n.value : '';
		const req = new XMLHttpRequest();
		req.addEventListener('load', (e) => {
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
		req.send('mode=status' + '&id=' + id + '&val=' + status + '&cache=' + Date.now() + '&nonce=' + nonce);
	}
});


// -----------------------------------------------------------------------------


window.addEventListener('load', () => {  // Must when 'load' event
	const selNewPost = document.getElementById('sel-new-post');
	const optFirst = selNewPost.querySelector('option:first-child');
	selNewPost.value = optFirst.value;
});
