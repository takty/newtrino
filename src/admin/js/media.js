/**
 *
 * Media Dialog (JS)
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @version 2020-08-14
 *
 */


document.addEventListener('DOMContentLoaded', () => {
	const btnAdd    = document.getElementById('btn-add');
	const btnClose  = document.getElementById('btn-close');
	const btnDelete = document.getElementById('btn-delete');
	const btnInsert = document.getElementById('btn-insert');

	const maxFileSize    = parseInt(document.getElementById('max-file-size').value);
	const msgMaxFileSize = document.getElementById('message-max-file-size');

	const metaTarget    = document.getElementById('meta-target').value;
	const metaSizeWidth = document.getElementById('meta-size-width').value;

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
		const it   = document.querySelector('.item-media input[type="radio"]:checked').parentElement;
		const ss   = it.querySelector('.sizes');
		const url  = it.querySelector('.file-url').value;
		const name = it.querySelector('.file-name').value;
		const data = { url, name };


		if (metaTarget) {
			if (ss) {
				const sizes           = JSON.parse(ss.value);
				const [minSize, size] = getMinAndCeilSize(sizes, metaSizeWidth);
				const m               = sizes[size];
				const url2x           = m.width ? get2xUrl(sizes, m.width) : null;

				data['url']    = m.url;
				data['width']  = m.width;
				data['height'] = m.height;
				data['size']   = size;
				data['srcset'] = (url2x) ? `${m.url}, ${url2x} 2x` : null;
				data['minUrl'] = sizes[minSize].url;
			}
			window.parent.insertMediaToMeta(metaTarget, data);
		} else {
			if (ss) {
				const sizes = JSON.parse(ss.value);
				const size  = selSize.value;
				const m     = sizes[size];
				const url2x = m.width ? get2xUrl(sizes, m.width) : null;

				data['url']    = m.url;
				data['url2x']  = url2x;
				data['width']  = m.width;
				data['height'] = m.height;
				data['size']   = size;
				data['srcset'] = (url2x) ? `${m.url}, ${url2x} 2x` : null;

				data['align']   = selAlign.value;
				data['linkUrl'] = (chkLink.checked) ? sizes['full'].url : null;
			}
			window.parent.insertMediaToContent(data);
		}
	}

	function get2xUrl(sizes, width) {
		for (const val of Object.values(sizes)) {
			if (width * 2 === val.width) return val.url;
		}
		if (sizes.full.width !== width) return sizes.full.url;
		return null;
	}

	function getMinAndCeilSize(sizes, width) {
		const kvs = [];
		for (const key of Object.keys(sizes)) kvs.push([key, sizes[key]]);
		kvs.sort((a, b) => { return a[1].width < b[1].width; });
		for (const kv of kvs) {
			if (width <= kv[1].width) return [kvs[0][0], kv[0]];
		}
		return [kvs[0][0], 'full'];
	}

});
