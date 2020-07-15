/**
 *
 * Edit (JS)
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @author Yusuke Manabe @ Space-Time Inc.
 * @version 2020-07-14
 *
 */


document.addEventListener('DOMContentLoaded', () => {
	setButtonEvents();

	const lang = document.getElementById('lang').value;

	const msgCon = document.getElementById('message-confirmation').value;
	window.addEventListener('beforeunload', (e) => { if (isModified) return (e.returnValue = msgCon); });

	initPublishingMetabox();
	initDateMetabox();
	initDateRangeMetabox();
	initEditorPane();
	adjustEditorHeight();

	let isModified = false;

	function onModified() {
		if (isModified) return;
		isModified = true;
		const es = document.getElementsByClassName('message');
		for (let e of es) e.style.display = '';
		const um = document.getElementById('message-updated');
		um.innerText = '';
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

	function initDateMetabox() {
		const es = document.querySelectorAll('.flatpickr.date');
		if (es.length === 0) return;
		flatpickr('.flatpickr.date', { wrap: true, locale: lang });
		for (let e of es) {
			e.addEventListener('change', () => {
				const d = e._flatpickr.selectedDate;
				const f = document.getElementsByName('meta:' + e.dataset.key)[0];
				f.value = moment(d).format('YYYY-MM-DD');
			});
			const f = document.getElementsByName('meta:' + e.dataset.key)[0];
			const d = moment(f.value).toDate();
			e._flatpickr.setDate(d);
		}
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
			content_css: '../data/editor.css',
			language: lang,
			setup: (ed) => { ed.on('change', (ed) => { isModified = true; }); },
			code_dialog_width: 800,
		});
		document.getElementById('post-title').addEventListener('change', (e) => {
			if (e.target.value !== '') {
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
		const btnPreviewClose = document.querySelector('#btn-close');
		btnPreviewClose.addEventListener('click', closeDialog);

		addBtnEvent('btn-list');
		addBtnEvent('btn-update', update);
		addBtnEvent('btn-dialog-media', openMediaDialog);
		addBtnEvent('btn-dialog-preview', openPreviewDialog);

		function addBtnEvent(id, fn = null) {
			const btn = document.getElementById(id);
			btn.addEventListener('mouseup', (e) => {
				if (e.button === 0) {
					e.preventDefault();
					if (fn) fn(e);
					else window.location.href = btn.href;
				} else if (e.button === 1) {
					e.preventDefault();
				}
			});
			btn.addEventListener('mousedown', (e) => {
				if (e.button === 1) e.preventDefault();
			});
		}
	}

	function update(e) {
		if (document.getElementById('post-title').value === '') {
			const elm = document.getElementById('message-enter-title');
			elm.style.display = 'block';
			return false;
		}
		isModified = false;
		const fp = document.getElementById('form-post');
		fp.target = '';
		fp.action = e.target.dataset.action;
		fp.submit();
	}

	function openPreviewDialog(e) {
		const fp = document.getElementById('form-post');
		fp.target = 'iframe-preview';
		fp.action = e.target.dataset.action;
		fp.submit();

		const dlg = document.getElementById('dialog-preview');
		dlg.classList.add('visible');

		const ph = document.getElementById('dialog-placeholder');
		ph.classList.add('visible');
	}

	function openMediaDialog(e) {
		const dlg = document.getElementById('dialog-media');
		dlg.src = e.target.dataset.src;
		dlg.classList.add('visible');

		const ph = document.getElementById('dialog-placeholder');
		ph.classList.add('visible');
	}
});

function closeDialog() {
	const ph = document.getElementById('dialog-placeholder');
	ph.classList.remove('visible');
	for (let c of ph.children) c.classList.remove('visible');
}


// -----------------------------------------------------------------------------


const wh_min = 220;

function insertMedia(name, src, w, h, align, size, isImage) {
	closeDialog();
	const cs = [];
	if (align === 'l') cs.push('alignleft');
	if (align === 'c') cs.push('aligncenter');
	if (align === 'r') cs.push('alignright');
	if (align === 'n') cs.push('alignnone');

	if (src.match(/[zip|pdf]\.jpg$/ig)) size = '';
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
			const [vw, vh] = getSize(w, h, r);
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

function getSize(w, h, max) {
	let nw = max, nh = max;
	if (w > h) {
		const p = parseFloat(w) / parseFloat(h);
		nw = parseFloat(max) * parseFloat(p);
	}
	if (w < h) {
		const p = parseFloat(h) / parseFloat(w);
		nh = parseFloat(max) * parseFloat(p);
	}
	return [Math.round(nw), Math.round(nh)];
}
