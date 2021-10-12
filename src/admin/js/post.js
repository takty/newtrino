/**
 *
 * Post (JS)
 *
 * @author Takuto Yanagida
 * @version 2021-10-12
 *
 */


// @include _common.js

window.NT = window['NT'] || {};
window.NT.tiny_mce_before_init = window.NT['tiny_mce_before_init'] || [];

document.addEventListener('DOMContentLoaded', () => {
	const lang = document.getElementById('lang').value;

	window.addEventListener('beforeunload', (e) => {
		if (isModified) {
			e.preventDefault();
			e.returnValue = '';
		}
	});

	setButtonEvents();
	initPublishingMetabox();
	initDateMetabox();
	initDateRangeMetabox();
	initMediaMetabox();
	initMediaImageMetabox();
	initEditorPane();
	adjustEditorHeight();
	startPing();


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
					document.getElementById('post-status-publish').selected = true;
				} else {
					document.getElementById('post-status-future').selected = true;
				}
			}
		});
		postStatus.addEventListener('change', () => {
			const s = postStatus.value;
			if (s === 'draft') return;
			const dn = parseInt(moment(postDate.value).format('YYYYMMDDhhmmss'));
			const cn = parseInt(moment().format('YYYYMMDDhhmmss'));
			if (s === 'publish' && dn > cn) {
				postDate.value = moment().format('YYYY-MM-DD hh:mm:ss');
			}
			if (s === 'future' && dn <= cn) {
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
				const f = document.getElementsByName('meta:' + e.dataset.key)[0];
				const ds = e._flatpickr.selectedDates;
				if (ds.length) {
					f.value = moment(ds[0]).format('YYYY-MM-DD');
				} else {
					f.value = '';
				}
			});
			const f = document.getElementsByName('meta:' + e.dataset.key)[0];
			if (!f.value) continue;
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
				const fs = document.getElementsByName('meta:' + e.dataset.key)[0];
				const ds = e._flatpickr.selectedDates;
				if (ds.length) {
					fs.value = JSON.stringify({
						from: moment(ds[0]).format('YYYY-MM-DD'),
						to  : moment(ds[1]).format('YYYY-MM-DD'),
					});
				} else {
					fs.value = '';
				}
			});
			const fs = document.getElementsByName('meta:' + e.dataset.key)[0];
			if (!fs.value) continue;
			const m = JSON.parse(fs.value);
			if (m === null) continue;
			const from = moment(m.from).toDate();
			const to   = moment(m.to  ).toDate();
			e._flatpickr.setDate([from, to]);
		}
	}

	function initMediaMetabox() {
		const ms = document.querySelectorAll('.metabox-media');
		addBtnEvent('.metabox-media .open-media-dialog', openMediaDialog);
		for (let m of ms) {
			const btnDel = m.querySelector('.button.delete');
			if (!btnDel) continue;
			btnDel.addEventListener('click', (e) => {
				m.querySelector('.media-name').value = '';
				m.querySelector('.media-json').value = '';
				btnDel.setAttribute('disabled', true);
			});
		}
	}

	function initMediaImageMetabox() {
		const ms = document.querySelectorAll('.metabox-media-image');
		addBtnEvent('.metabox-media-image .open-media-dialog', openMediaDialog);
		for (const m of ms) {
			const btnDel = m.querySelector('.button.delete');
			if (!btnDel) continue;
			btnDel.addEventListener('click', (e) => {
				m.querySelector('.media-name').value = '';
				m.querySelector('.media-json').value = '';
				m.querySelector('.image > div').style.backgroundImage = null;
				btnDel.setAttribute('disabled', true);
			});
		}
	}


	// -------------------------------------------------------------------------


	function initEditorPane() {
		const plugins = [
			'advlist anchor autolink charmap code directionality hr image insertdatetime',
			'link lists media nonbreaking noneditable paste print searchreplace table textpattern visualblocks visualchars',
		];
		const toolbars = [
			'undo redo | bold italic underline strikethrough | superscript subscript | link unlink | forecolor backcolor | removeformat |',
			'formatselect | bullist numlist | blockquote | alignleft aligncenter alignright | styleselect |',
		];
		const formats = [
			'Paragraph=p',
			'Heading 3=h3',
			'Heading 4=h4',
			'Heading 5=h5',
			'Blockquote=blockquote',
			'Preformatted=pre',
		].join(';');

		const css       = document.getElementById('editor-css').value;
		const json      = document.getElementById('editor-option').value;
		const opt       = json ? JSON.parse(json) : {};
		const assetsUrl = document.getElementById('assets-url').value;

		let args = Object.assign({
			// Integration and setup options
			plugins : plugins,
			selector: '#post-content',
			setup   : (e) => { e.on('change', () => { isModified = true; }); },

			// User interface options
			block_formats    : formats,
			removed_menuitems: 'newdocument fontformats fontsizes lineheight',
			toolbar1         : toolbars[0],
			toolbar2         : toolbars[1],

			// Content appearance options
			content_css       : css,
			visual_table_class: ' ',

			// Content formatting options
			element_format: 'html',

			// Localization options
			language: lang,

			// Advanced editing behaviors
			object_resizing: 'img',


			// Code Plugin
			code_dialog_width: 800,

			// Link plugin
			link_context_toolbar: true,

			// Nonbreaking Space plugin
			nonbreaking_force_tab: true,

			// Table plugin
			table_default_attributes: {},
			table_default_styles    : {},
			table_class_list        : [],
			table_advtab            : false,
			table_resize_bars       : false,
		}, opt)
		for (let f of window.NT.tiny_mce_before_init) args = f(args, lang, assetsUrl);
		tinymce.init(args);

		let st = null
		document.getElementById('post-title').addEventListener('input', (e) => {
			if (st) clearTimeout(st);
			st = setTimeout(() => {
				if (e.target.value !== '') {
					const es = document.getElementsByClassName('message');
					for (let e of es) e.style.display = '';
				}
				onModified();
			}, 100);
		});
	}

	function adjustEditorHeight() {
		const onResize = () => {
			const clm = document.querySelector('.container-main');
			const ed  = document.querySelector('.container-main .tox-tinymce');
			if (ed) {
				const clmStyle  = window.getComputedStyle(clm);
				const innerH    = clm.clientHeight - parseInt(clmStyle.paddingTop) - parseInt(clmStyle.paddingBottom);

				// Calculate offsetTop using getBoundingClientRect instead of using ed.offsetTop for Safari
				const offsetTop = ed.getBoundingClientRect().top - clm.getBoundingClientRect().top;
				const innerOffY = offsetTop - parseInt(clmStyle.paddingTop);

				ed.style.height = (innerH - innerOffY) + 'px';
			} else {
				setTimeout(onResize, 100);
			}
		}
		setTimeout(onResize, 200);

		window.addEventListener('resize', throttle(onResize), { passive: true });
		function throttle(fn) {
			let isRunning;
			function run() {
				isRunning = false;
				fn();
			}
			return () => {
				if (isRunning) return;
				isRunning = true;
				requestAnimationFrame(run);
			};
		}
	}

	function startPing() {
		const sp  = new URLSearchParams(document.location.search.substring(1));
		const id  = sp.get('id') ?? '';
		const dlg = document.getElementById('dialog-login');

		function ping() {
			const req = new XMLHttpRequest();
			req.addEventListener('load', pong);
			req.open('post', 'ajax.php', true);
			req.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
			req.send('mode=ping&id=' + id + '&cache=' + Date.now());
		}
		const MAX_COUNT = 3;
		const INTERVAL  = 10000;
		let fc = 0;

		function pong(e) {
			const msg = e.currentTarget.responseText.match(/<result>([\s\S]*?)<\/result>/i);
			if (msg !== null && msg[1] === 'success') {
				fc = 0;
			} else {
				fc += 1;
			}
			if (MAX_COUNT <= fc) {
				fc = 0;
				if (curDlg) {
					closeDialog(true);
				} else {
					openLoginDialog();
				}
			}
		}
		function iterate() {
			if (!dlg.classList.contains('active')) ping();
			setTimeout(iterate, INTERVAL);
		}
		setTimeout(iterate, INTERVAL);
	}


	// -------------------------------------------------------------------------


	function setButtonEvents() {
		const btnPreviewClose = document.querySelector('#btn-close');
		btnPreviewClose.addEventListener('click', () => { closeDialog(); });

		addBtnEvent('#btn-list');
		addBtnEvent('#btn-update', update);
		addBtnEvent('#btn-dialog-media', openMediaDialog);
		addBtnEvent('#btn-dialog-preview', openPreviewDialog);
	}

	function addBtnEvent(sel, fn = null) {
		const btns = document.querySelectorAll(sel);
		for (let btn of btns) {
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
			elm.hidden = false;
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
		openDialog(dlg);
	}

	function openMediaDialog(e) {
		const dlg = document.getElementById('dialog-media');
		dlg.src = e.target.dataset.src;
		openDialog(dlg);
	}
});

function openLoginDialog() {
	const dlg = document.getElementById('dialog-login');
	dlg.src = 'login.php?dialog';
	openDialog(dlg, 'login');
}

let curDlg = null;

function openDialog(dlg) {
	if (curDlg) return;
	curDlg = dlg;

	const ph = document.getElementById('dialog-placeholder');
	ph.classList.add('active');

	dlg.classList.add('active');
	const f = curDlg.tagName === 'IFRAME' ? curDlg : curDlg.querySelector('iframe');
	if (f) f.onload = () => {
		setTimeout(() => {
			ph.classList.remove('loading');
			f.classList.add('visible');
		}, 100);
	}
	setTimeout(() => { ph.classList.add('visible'); }, 100);
	setTimeout(() => { dlg.classList.add('visible'); }, 200);
}

function reopenDialogLater() {
	const f = curDlg.tagName === 'IFRAME' ? curDlg : curDlg.querySelector('iframe');
	if (f) f.classList.remove('visible');
	const ph = document.getElementById('dialog-placeholder');
	ph.classList.add('loading');
}

function closeDialog(doReLogin = false) {
	if (!curDlg) return;

	const ph = document.getElementById('dialog-placeholder');
	ph.classList.remove('visible');

	curDlg.classList.remove('visible');
	setTimeout(() => {
		ph.classList.remove('active');

		curDlg.classList.remove('active');
		const f = curDlg.tagName === 'IFRAME' ? curDlg : curDlg.querySelector('iframe');
		if (f && f.src) f.removeAttribute('src');
		if (f) f.onload = null;
		curDlg = null;

		if (doReLogin) openLoginDialog();
	}, 100);
}


// -----------------------------------------------------------------------------


let isModified = false;

function onModified() {
	if (isModified) return;
	isModified = true;
	const es = document.getElementsByClassName('message');
	for (let e of es) e.style.display = '';
	const um = document.getElementById('message-notification');
	um.innerText = '';
}

function setMediaItemCount(count) {
	const sp = new URLSearchParams(document.location.search.substring(1));
	if (sp.get('mode') === 'new' && 0 < count) {
		onModified();
	}
}

function insertMediaToContent(data) {
	closeDialog();
	if (data['size']) {
		const ss  = data.srcset ? ` srcset="${data.srcset}"` : '';
		const cls = `size-${data.size}` + (data.linkUrl ? '' : ` ${data.align}`);
		let str = `<img class="${cls}" src="${data.url}"${ss} alt="${data.name}" width="${data.width}" height="${data.height}" loading="lazy">`;
		if (data.linkUrl) str = `<a href="${data.linkUrl}" class="${data.align}">${str}</a>`;
		tinymce.execCommand('mceInsertContent', false, str);
	} else {
		const s = tinymce.activeEditor.selection.getContent();
		if (s !== '') {
			tinymce.execCommand('mceInsertLink', false, data.url);
		} else {
			const str = `<a href="${data.url}">${data.name}</a>`;
			tinymce.execCommand('mceInsertContent', false, str);
		}
	}
}

function insertMediaToMeta(target, data) {
	closeDialog();
	const f = document.getElementById(target);
	if (!f) return;
	const jsonElm = f.querySelector('.media-json');
	if (jsonElm) jsonElm.value = JSON.stringify(data);

	const nameElm = f.querySelector('.media-name');
	if (nameElm) nameElm.value = data.name;
	const imgElm = f.querySelector('.image > div');
	if (imgElm && data && data.minUrl) imgElm.style.backgroundImage = 'url("' + data.minUrl + '")';
	const delBtn = f.querySelector('.button.delete');
	if (delBtn) delBtn.removeAttribute('disabled');
}
