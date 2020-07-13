/**
 *
 * Edit (JS)
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @author Yusuke Manabe @ Space-Time Inc.
 * @version 2020-07-13
 *
 */


document.addEventListener('DOMContentLoaded', () => {
	setButtonEvents();

	const lang = document.getElementById('lang').value;

	const conMsg = document.getElementById('confirmation-message').value;
	window.addEventListener('beforeunload', (e) => { if (isModified) return (e.returnValue = conMsg); });

	initPublishingMetabox();
	initDateRangeMetabox();
	initEditorPane();
	adjustEditorHeight();

	let isModified = false;

	function onModified() {
		if (isModified === false) {
			isModified = true;
			const es = document.getElementsByClassName('message');
			for (let e of es) e.style.display = '';
			const um = document.getElementById('update-msg');
			um.innerText = '';
		}
	}


	// -------------------------------------------------------------------------


	function initPublishingMetabox() {
		const postDate = document.getElementById('post-date');
		const postStatus = document.getElementById('post-status');

		const fp = flatpickr('#post-date', {
			enableSeconds: true,
			time_24hr    : true,
			enableTime   : true,
			locale       : lang,
			onChange     : function (dateObj, dateStr, instance) {
				if (postStatus.value === 'draft') return;
				const dn = parseInt(moment(dateStr).format('YYYYMMDDhhmmss'));
				const cn = parseInt(moment().format('YYYYMMDDhhmmss'));
				if (dn <= cn) {
					document.getElementById('post-status-published').selected = true;
				} else {
					document.getElementById('post-status-reserved').selected = true;
				}
			}
		});
		postStatus.addEventListener('change', () => {
			const s = postStatus.value;
			if (s === 'draft') return;
			const dn = parseInt(moment(postDate.value).format('YYYYMMDDhhmmss'));
			const cn = parseInt(moment().format('YYYYMMDDhhmmss'));
			if (s === 'published' && dn > cn) {
				postDate.value = moment().format('YYYY-MM-DD hh:mm:ss');
			}
			if (s === 'reserved' && dn <= cn) {
				setTimeout(() => { fp.open(); }, 100);
			}
		});
		postDate.addEventListener('change', onModified);
		postStatus.addEventListener('change', onModified);
	}

	function initDateRangeMetabox() {
		const es = document.querySelectorAll('.flatpickr.date-range');
		if (es.length === 0) return;
		flatpickr('.flatpickr.date-range', { wrap: true, mode: 'range', locale: lang });
		for (let e of es) {
			e.addEventListener('change', () => {
				const ds = e._flatpickr.selectedDates;
				const fs = document.getElementsByName('meta:' + e.dataset.key + '[]');
				fs[0].value = moment(ds[0]).format('YYYY-MM-DD');
				fs[1].value = moment(ds[1]).format('YYYY-MM-DD');
			});
			const fs = document.getElementsByName('meta:' + e.dataset.key + '[]');
			const bgn = moment(fs[0].value).toDate();
			const end = moment(fs[1].value).toDate();
			e._flatpickr.setDate([bgn, end]);
		}
	}

	function initEditorPane() {
		tinymce.init({
			selector: '#post-content',
			plugins: [
				'advlist anchor autolink charmap code contextmenu image insertdatetime',
				'link lists media paste preview print searchreplace table visualblocks',
			],
			toolbar: 'insertfile undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image',
			content_css: '../data/editor-style.css',
			language: lang,
			setup: (ed) => { ed.on('change', (ed) => { isModified = true; }); },
			code_dialog_width: 800,
		});
		document.getElementById('post-title').addEventListener('change', () => {
			if (this.value !== '') {
				const es = document.getElementsByClassName('message');
				for (let e of es) e.style.display = '';
			}
			onModified();
		});
	}

	function adjustEditorHeight() {
		const onResize = () => {
			const clm = document.querySelector('.container-main');
			const div = document.querySelector('.container-main .button-row + div');
			if (div) {
				const r = div.getBoundingClientRect();
				const h = clm.clientHeight - (0 | r.top);
				tinymce.activeEditor.theme.resizeTo(null, h - 32);
			} else {
				setTimeout(onResize, 100);
			}
		}
		setTimeout(onResize, 200);
	}


	// -------------------------------------------------------------------------











	function setButtonEvents() {
		addBtnEvent('show-list', showList, 'list.php');
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
		fp.action = 'post.php';
		isModified = false;
		fp.submit();
	}

	function showList() {
		const fp = document.getElementById('form-post');
		fp.target = '';
		fp.action = 'list.php';
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
});

