/**
 *
 * List (JS)
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @version 2020-07-25
 *
 */


document.addEventListener('DOMContentLoaded', () => {
	const delBtns = document.getElementsByClassName('delete-post');
	const delMsg = document.getElementById('message-deleting').value;

	for (let delBtn of delBtns) {
		delBtn.addEventListener('click', (e) => {
			const s = e.target.dataset.href;
			const tr = e.target.parentElement.parentElement;
			const title = tr.getElementsByClassName('title')[0].innerText;
			const date = tr.getElementsByClassName('date')[0].innerText;
			if (!confirm(delMsg + '\n"' + title + '"\n' + date)) return false;
			window.location.href = s;
		});
	}

	const statSels = document.getElementsByClassName('post-status');

	for (let statSel of statSels) {
		statSel.addEventListener('change', (e) => {
			const s = e.target.value;
			const id = e.target.parentElement.parentElement.dataset.id;
			setPostStatus(id, s);
		});
	}

	function setPostStatus(id, status) {
		const req = new XMLHttpRequest();
		req.addEventListener('load', (e) => {});  // For debug
		req.open('post', 'ajax.php', true);
		req.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
		req.send('mode=status' + '&id=' + id + '&val=' + status + '&cache=' + Date.now());
	}
});
