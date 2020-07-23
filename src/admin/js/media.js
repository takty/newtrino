/**
 *
 * Media (JS)
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @version 2020-07-24
 *
 */


document.addEventListener('DOMContentLoaded', () => {
	const btnAdd    = document.getElementById('btn-add');
	const btnClose  = document.getElementById('btn-close');
	const btnDelete = document.getElementById('btn-delete');
	const btnInsert = document.getElementById('btn-insert');

	btnAdd.addEventListener('click', addMedia);
	btnClose.addEventListener('click', closeDialog);
	btnInsert.addEventListener('click', insertMedia);
	btnDelete.addEventListener('click', deleteMedia);
	btnDelete.disabled = true;
	btnInsert.disabled = true;

	const uploadFile = document.getElementById('upload-file');
	uploadFile.addEventListener('change', () => {
		if (uploadFile.value !== '') document.getElementById('form-upload').submit();
	});

	const rs = document.querySelectorAll('.item-media input[type="radio"]');
	for (let r of rs) r.addEventListener('change', onItemSelected);

	const selAlign = document.getElementById('image-align');
	const selSize  = document.getElementById('image-size');
	const chkLink  = document.getElementById('image-link');
	const mediaUrl = document.getElementById('media-url');
	selAlign.disabled = true;
	selSize.disabled  = true;
	chkLink.disabled  = true;

	function onItemSelected(e) {
		btnDelete.disabled = false;
		btnInsert.disabled = false;

		const url = e.target.parentElement.querySelector('.file-url').value;
		mediaUrl.value = url;

		const s = e.target.parentElement.querySelector('.sizes');
		if (s) {
			selAlign.disabled = false;
			selSize.disabled  = false;
			chkLink.disabled  = false;

			const sizes = JSON.parse(s.value);
			selSize.className = Object.keys(sizes).join(' ');
		} else {
			selAlign.disabled = true;
			selSize.disabled  = true;
			chkLink.disabled  = true;
		}
	}

	function addMedia() {
		document.getElementById('upload-file').click();
	}

	function closeDialog() {
		window.parent.closeDialog();
	}

	function insertMedia() {
		const p = document.querySelector('.item-media input[type="radio"]:checked').parentElement;
		const isImg = p.querySelector('.is-image').value;
		const fn    = p.querySelector('.file-name').value;

		if (isImg) {
			const align = selAlign.value;
			const size  = selSize.value;
			const link  = chkLink.checked;

			const sizesJson = p.querySelector('.sizes').value;
			const sizes = JSON.parse(sizesJson);
			const s = sizes[size];

			const w   = s.width;
			const h   = s.height;
			const url = s.url;

			const url2x   = w ? get2xUrl(sizes, w) : null;
			const srcset  = (url2x) ? `${url}, ${url2x} 2x` : null;
			const linkUrl = (link) ? sizes['full'].url : null;
			window.parent.insertImage(fn, url, w, h, align, size, srcset, linkUrl);
		} else {
			const url = p.querySelector('.file-url').value;
			window.parent.insertFile(fn, url);
		}
	}

	function get2xUrl(sizes, width) {
		let ret = null;
		for (const vals of Object.values(sizes)) {
			if (width * 2 === vals.width) {
				ret = vals.url;
				break;
			}
		}
		if (ret === null && sizes.full.width !== width) {
			ret = sizes.full.url;
		}
		return ret;
	}

	function deleteMedia() {
		const msg = document.getElementById('msg-delete').value;
		if (!confirm(msg)) return;

		const p = document.querySelector('.item-media input[type="radio"]:checked').parentElement;
		const file_name = p.querySelector('.file-name').value;

		document.getElementById('del-file').value = file_name;
		document.getElementById('form-delete').submit();
	}
});
