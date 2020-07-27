/**
 *
 * Media Dialog (JS)
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @version 2020-07-27
 *
 */


document.addEventListener('DOMContentLoaded', () => {
	const btnAdd    = document.getElementById('btn-add');
	const btnClose  = document.getElementById('btn-close');
	const btnDelete = document.getElementById('btn-delete');
	const btnInsert = document.getElementById('btn-insert');

	const maxFileSize = parseInt(document.getElementById('max-file-size').value);
	const msgMaxFileSize = document.getElementById('message-max-file-size');

	btnAdd.addEventListener('click', doAdd);
	btnClose.addEventListener('click', doClose);
	btnInsert.addEventListener('click', doInsert);
	btnDelete.addEventListener('click', doDelete);
	btnDelete.disabled = true;
	btnInsert.disabled = true;

	const upfile = document.getElementById('upload-file');
	upfile.addEventListener('change', () => {
		const f = upfile.files ? upfile.files[0] : null;
		if (f && maxFileSize < f.size) {
			msgMaxFileSize.hidden = false;
			return;
		}
		msgMaxFileSize.hidden = true;
		if (upfile.value !== '') document.getElementById('form-upload').submit();
	});

	const rs = document.querySelectorAll('.item-media input[type="radio"]');
	for (let r of rs) r.addEventListener('change', onSelected);

	const selAlign = document.getElementById('image-align');
	const selSize  = document.getElementById('image-size');
	const chkLink  = document.getElementById('image-link');
	const mediaUrl = document.getElementById('media-url');
	selAlign.disabled = true;
	selSize.disabled  = true;
	chkLink.disabled  = true;


	// -------------------------------------------------------------------------


	function doAdd() {
		upfile.click();
	}

	function doClose() {
		window.parent.closeDialog();
	}

	function onSelected(e) {
		btnDelete.disabled = false;
		btnInsert.disabled = false;

		const url = e.target.parentElement.querySelector('.file-url').value;
		if (url.indexOf('/?.') === -1) {
			mediaUrl.value = url;
		} else {
			mediaUrl.value = '';
		}

		const ss = e.target.parentElement.querySelector('.sizes');
		if (ss) {
			selAlign.disabled = false;
			selSize.disabled  = false;
			chkLink.disabled  = false;

			const sizes = JSON.parse(ss.value);
			const sizeCls = Object.keys(sizes);
			selSize.className = sizeCls.join(' ');
			if (sizeCls.indexOf(selSize.value) === -1) {
				if (sizeCls.length === 1) selSize.value = sizeCls[sizeCls.length - 1];
				else selSize.value = sizeCls[sizeCls.length - 2];
			}
		} else {
			selAlign.disabled = true;
			selSize.disabled  = true;
			chkLink.disabled  = true;
		}
	}

	function doDelete() {
		const msg = document.getElementById('msg-delete').value;
		if (!confirm(msg)) return;

		const p = document.querySelector('.item-media input[type="radio"]:checked').parentElement;
		const file_name = p.querySelector('.file-name').value;

		document.getElementById('delete-file').value = file_name;
		document.getElementById('form-delete').submit();
	}

	function doInsert() {
		const p = document.querySelector('.item-media input[type="radio"]:checked').parentElement;
		const fn = p.querySelector('.file-name').value;
		const ss = p.querySelector('.sizes');

		if (ss) {
			const metas = JSON.parse(ss.value);
			const align = selAlign.value;
			const size  = selSize.value;
			const link  = chkLink.checked;

			const m   = metas[size];
			const w   = m.width;
			const h   = m.height;
			const url = m.url;

			const url2x   = w ? get2xUrl(metas, w) : null;
			const srcset  = (url2x) ? `${url}, ${url2x} 2x` : null;
			const linkUrl = (link) ? metas['full'].url : null;
			window.parent.insertImage(fn, url, w, h, align, size, srcset, linkUrl);
		} else {
			const url = p.querySelector('.file-url').value;
			window.parent.insertFile(fn, url);
		}
	}

	function get2xUrl(sizes, width) {
		for (const vals of Object.values(sizes)) {
			if (width * 2 === vals.width) return vals.url;
		}
		if (sizes.full.width !== width) {
			return sizes.full.url;
		}
		return null;
	}
});
