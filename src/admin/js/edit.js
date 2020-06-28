/**
 *
 * Edit (JS)
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @author Yusuke Manabe @ Space-Time Inc.
 * @version 2020-06-28
 *
 */


function initEdit() {
	setButtonEvents();

	const fp = flatpickr('#post_date', {
		enableSeconds: true, time_24hr: true, enableTime: true,
		onChange: function(dateObj, dateStr, instance) {
			if (document.getElementById('post_status').value === 'draft') return;
			const s = dateStr.replace(/-|:|\s/g, '');
			const dn = parseInt(s, 10);
			const cn = parseInt(formatDate(new Date(), 'YYYYMMDDhhmmss'));
			if (dn <= cn) {
				document.getElementById('post_status_published').selected = true;
			} else {
				document.getElementById('post_status_reserved').selected = true;
			}
		}
	});
	document.getElementById('post_status').addEventListener('change', function () {
		if (this.value === 'draft') return;
		if (this.value === 'published') {
			const s = document.getElementById('post_date').value.replace(/-|:|\s/g, '');
			const dn = parseInt(s, 10);
			const cn = parseInt(formatDate(new Date(), 'YYYYMMDDhhmmss'));
			if (dn > cn) {
				document.getElementById('post_date').value = formatDate(new Date(), 'YYYY-MM-DD hh:mm:ss');
			}
		}
		if (this.value === 'reserved') {
			const s = document.getElementById('post_date').value.replace(/-|:|\s/g, '');
			const dn = parseInt(s, 10);
			const cn = parseInt(formatDate(new Date(), 'YYYYMMDDhhmmss'));
			if (dn <= cn) {
				setTimeout(function () {fp.open();}, 100);
			}
		}
	});
	tinymce.init({
		selector: '#post_content',
		plugins: [
			'advlist anchor autolink charmap code contextmenu fullscreen image insertdatetime',
			'link lists media paste preview print searchreplace table visualblocks',
		],
		toolbar: 'insertfile undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image',
		content_css: '../data/editor-style.css',
		language: 'ja',
		setup: function (ed) { ed.on('change', function (ed) { changed = true; }); },
		code_dialog_width: 800,
	});
	document.getElementById('post_title').addEventListener('change', function () {
		if (this.value !== '') {
			const elms = document.getElementsByClassName('message');
			for (let i = 0; i < elms.length; i += 1) elms[i].style.display = '';
		}
		onChanged();
	});
	document.getElementById('post_date').addEventListener('change', onChanged);
	document.getElementById('post_status').addEventListener('change', onChanged);

	document.getElementById('taxonomy:category').addEventListener('change', function () {
		onChanged();
		const isEvent = document.getElementById('taxonomy:category').value === 'event';
		document.getElementById('frame-event-duration').style.display = (isEvent ? 'block' : 'none');
	});

	window.addEventListener('beforeunload', function (e) {
		if (changed) {
			const msg = 'Do you want to move from the page you are inputting?';
			e.returnValue = msg;
			return msg;
		}
	});
	flatpickr('#event_date_bgn_wrap', { wrap: true });
	flatpickr('#event_date_end_wrap', { wrap: true });

	const isEvent = document.getElementById('taxonomy:category').value === 'event';
	document.getElementById('frame-event-duration').style.display = (isEvent ? 'block' : 'none');

	function onResize() {
		const clm = document.querySelector('.column-main');
		const div = document.querySelector('.column-main .btn-row + div');
		if (div) {
			const r = div.getBoundingClientRect();
			const h = clm.clientHeight - (0 | r.top);
			tinymce.activeEditor.theme.resizeTo(null, h);
		} else {
			setTimeout(onResize, 100);
		}
	}
	window.addEventListener('resize', onResize);
	setTimeout(onResize, 200);
}

function setButtonEvents() {
	addBtnEvent('show-list', showList, 'index.php');
	addBtnEvent('show-post', showPost, '../view.php?id=' + document.getElementById('id').value);
	addBtnEvent('show-media-chooser', showMediaChooser);
	addBtnEvent('show-preview', showPreview);
	addBtnEvent('update', update);

	function addBtnEvent(id, fn, url = false) {
		const btn = document.getElementById(id);
		btn.addEventListener('mouseup', function (e) {
			if (e.button === 0) {
				e.preventDefault();
				fn();
			} else if (e.button === 1) {
				e.preventDefault();
			}
		});
		btn.addEventListener('mousedown', function (e) {
			if (e.button === 1) e.preventDefault();
		});
		if (url) btn.href = url;
	}
}

let changed = false;

const onChanged = function () {
	if (changed === false) {
		changed = true;
		const elms = document.getElementsByClassName('message');
		for (let i = 0; i < elms.length; i += 1) elms[i].style.display = '';
		const um = document.getElementById('update-msg');
		um.innerText = '';
	}
}

const formatDate = function (date, format) {
	if (!format) format = 'YYYY-MM-DD hh:mm:ss';
	format = format.replace(/YYYY/g, date.getFullYear());
	format = format.replace(/MM/g, ('0' + (date.getMonth() + 1)).slice(-2));
	format = format.replace(/DD/g, ('0' + date.getDate()).slice(-2));
	format = format.replace(/hh/g, ('0' + date.getHours()).slice(-2));
	format = format.replace(/mm/g, ('0' + date.getMinutes()).slice(-2));
	format = format.replace(/ss/g, ('0' + date.getSeconds()).slice(-2));
	return format;
};

function showPreview() {
	window.open('about:blank', 'preview');
	const fp = document.getElementById('form-post');
	fp.target = 'preview';
	fp.action = 'preview.php';
	fp.submit();
}

function update() {
	if (document.getElementById('post_title').value === '') {
		const elm = document.getElementById('message_enter_title');
		elm.style.display = 'block';
		return false;
	}
	document.getElementById('mode').value = 'update';
	const fp = document.getElementById('form-post');
	fp.target = '';
	fp.action = 'edit.php';
	changed = false;
	fp.submit();
}

function showList() {
	const fp = document.getElementById('form-post');
	fp.target = '';
	fp.action = 'index.php';
	fp.submit();
}

function showPost() {
	const id = document.getElementById('id').value;
	window.open('../view.php?id=' + id, 'post', '');
}

function showMediaChooser() {
	const pid = document.getElementById('id').value;
	const dialogPlaceholder = document.getElementById('dialog-placeholder');

	const win = document.createElement('iframe');
	win.id = 'mediaChooser';
	win.src = 'media.php?id=' + pid;
	win.style.width     = '960px';
	win.style.height    = '720px';
	win.style.maxWidth  = '90vw';
	win.style.maxHeight = '90vh';

	dialogPlaceholder.appendChild(win);
	dialogPlaceholder.style.display = 'flex';
}

function closeMediaChooser() {
	const dialogPlaceholder = document.getElementById('dialog-placeholder');
	const win = document.getElementById('mediaChooser');
	dialogPlaceholder.removeChild(win);
	dialogPlaceholder.style.display = 'none';
}

const wh_min = 220;

function insertMedia(name, src, w, h, align, size, isImage) {
	closeMediaChooser();
	const cs = [];
	if (align === 'l') cs.push('alignleft');
	if (align === 'c') cs.push('aligncenter');
	if (align === 'r') cs.push('alignright');
	if (align === 'n') cs.push('alignnone');

	if (src.match(/[zip|pdf]\.jpg$/ig)) size = "";
	if (isImage) {
		if (size === 's') cs.push('size-small');
		if (size === 'm') cs.push('size-medium');
		if (size === 'l') cs.push('size-large');
		if (size === 'f') cs.push('size-full');

		let imgstr = '<a href="' + src + '" class="' + cs.join(' ') + '"><img src="' + src + '"';
		if (size !== 'f') {  // Not Full Size
			let r = wh_min
			if (size === "l") r *= 3;
			if (size === "m") r *= 2;
			const vw = Math.round(getSizeW(r, w, h));
			const vh = Math.round(getSizeH(r, w, h));
			const is = ' width="' + vw + '" height="' + vh + '" ';
			imgstr = imgstr + is;
		}
		imgstr += "></a>";
		tinymce.activeEditor.execCommand('mceInsertContent', false, imgstr);
	} else {
		const linkStr = '<a href="' + src + '" class="' + cs.join(' ') + '">' + name + '</a>';
		tinymce.activeEditor.execCommand('mceInsertContent', false, linkStr);
	}
}

function getSizeW(wh, w, h) {
	if (w > h) {
		const p = parseFloat(w) / parseFloat(h);
		return parseFloat(wh) * parseFloat(p);
	} else {
		return wh;
	}
}

function getSizeH(wh, w, h) {
	if (w > h) {
		return wh;
	} else {
		const p = parseFloat(h) / parseFloat(w);
		return parseFloat(wh) * parseFloat(p);
	}
}