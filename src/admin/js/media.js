/**
 * Media Dialog
 *
 * @author Takuto Yanagida
 * @version 2022-12-16
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

	const upFile = document.getElementById('upload-file');
	upFile.addEventListener('change', () => {
		const f = upFile.files ? upFile.files[0] : null;
		if (f && maxFileSize < f.size) {
			msgMaxFileSize.classList.add('visible');
			return;
		}
		msgMaxFileSize.classList.remove('visible');
		if (upFile.value !== '') {
			window.parent.reopenDialogLater();
			setTimeout(() => { document.getElementById('form-upload').submit(); }, 100);
		}
	});

	const rs = document.querySelectorAll('.item-media input[type="radio"]');
	for (const r of rs) r.addEventListener('change', onSelected);
	window.parent.setMediaItemCount(rs.length);

	const selAlign = document.getElementById('image-align');
	const selSize  = document.getElementById('image-size');
	const chkLink  = document.getElementById('image-link');
	const mediaUrl = document.getElementById('media-url');
	selAlign.disabled = true;
	selSize.disabled  = true;
	chkLink.disabled  = true;


	// -------------------------------------------------------------------------


	function doAdd() {
		upFile.click();
	}

	function doClose() {
		window.parent.closeDialog();
	}

	function onSelected(e) {
		btnDelete.disabled = false;
		btnInsert.disabled = false;

		const url = e.target.parentElement.querySelector('.file-url').value;
		if (!url.includes('/?.')) {
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
			if (!sizeCls.includes(selSize.value)) {
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
		window.parent.reopenDialogLater();
		setTimeout(() => { document.getElementById('form-delete').submit(); }, 100);
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
