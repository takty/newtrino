/**
 * Post
 *
 * @author Takuto Yanagida
 * @version 2023-01-11
 */

window.NT = window['NT'] || {};
window.NT.tiny_mce_before_init = window.NT['tiny_mce_before_init'] || [];

document.addEventListener('DOMContentLoaded', () => {
	const ntcEnterTitle = document.getElementById('ntc-enter-title').value;
	const lang          = document.getElementById('lang').value;
	const editorCss     = document.getElementById('editor-css').value;
	const editorOpts    = document.getElementById('editor-option').value;
	const assetsUrl     = document.getElementById('assets-url').value;

	window.addEventListener('beforeunload', e => {
		if (isModified) {
			e.preventDefault();
			e.returnValue = '';
		}
	});

	addBtnEvent('#btn-update', update);
	addBtnEvent('#btn-dialog-media', openMediaDialog);
	addBtnEvent('#btn-dialog-preview', openPreviewDialog);
	addBtnEvent('#btn-close', () => closeDialog());  // Must call closeDialog with no argument.

	initPublishingMetabox();
	initTaxonomyMetabox();
	initCheckboxMetabox();
	initDateMetabox();
	initDateRangeMetabox();
	initMediaMetabox();
	initMediaImageMetabox();
	initEditorPane();
	adjustEditorHeight();
	startPing();


	// -------------------------------------------------------------------------


	function dateToFormat(d, format) {
		return (d ? luxon.DateTime.fromJSDate(d) : luxon.DateTime.now()).toFormat(format);
	}

	function sqlToInt(sql = null) {
		return parseInt((sql ? luxon.DateTime.fromSQL(sql) : luxon.DateTime.now()).toFormat('yyyyMMddhhmmss'), 10);
	}

	function sqlToDate(sql) {
		return luxon.DateTime.fromSQL(sql).toJSDate();
	}


	// -------------------------------------------------------------------------


	function initPublishingMetabox() {
		const postDate   = document.getElementById('post-date');
		const postStatus = document.getElementById('post-status');

		const fp = flatpickr('#post-date', {
			enableSeconds: true,
			time_24hr    : true,
			enableTime   : true,
			locale       : lang,
			onChange     : function (dateObj, dateStr, instance) {
				if (postStatus.value === 'draft') return;
				const dn = sqlToInt(dateStr);
				const cn = sqlToInt();
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
			const dn = sqlToInt(postDate.value);
			const cn = sqlToInt();
			if (s === 'publish' && dn > cn) {
				postDate.value = dateToFormat(null, 'yyyy-MM-dd hh:mm:ss');  // SQL format
			}
			if (s === 'future' && dn <= cn) {
				setTimeout(() => { fp.open(); }, 100);
			}
		});
		postDate.addEventListener('change', onModified);
		postStatus.addEventListener('change', onModified);
	}

	function initTaxonomyMetabox() {
		const ms = document.querySelectorAll('.metabox-taxonomy');
		for (const m of ms) {
			const is = m.querySelectorAll('input');
			for (const i of is) {
				i.addEventListener('change', onModified);
			}
			const s = m.querySelector('select');
			if (s) {
				s.addEventListener('change', onModified);
			}
		}
	}

	function initCheckboxMetabox() {
		const ms = document.querySelectorAll('.metabox-checkbox');
		for (const m of ms) {
			const is = m.querySelectorAll('input');
			for (const i of is) {
				i.addEventListener('change', onModified);
			}
		}
	}

	function initDateMetabox() {
		const es = document.querySelectorAll('.flatpickr.date');
		if (es.length === 0) return;
		flatpickr('.flatpickr.date', { wrap: true, locale: lang });
		for (const e of es) {
			e.addEventListener('change', () => {
				const f = document.getElementsByName('meta:' + e.dataset.key)[0];
				const ds = e._flatpickr.selectedDates;
				if (ds.length) {
					f.value = dateToFormat(ds[0], 'yyyy-MM-dd');  // SQL format
				} else {
					f.value = '';
				}
				onModified();
			});
			const f = document.getElementsByName('meta:' + e.dataset.key)[0];
			if (!f.value) continue;
			const d = sqlToDate(f.value);
			e._flatpickr.setDate(d);
		}
	}

	function initDateRangeMetabox() {
		const es = document.querySelectorAll('.flatpickr.date-range');
		if (es.length === 0) return;
		flatpickr('.flatpickr.date-range', { wrap: true, mode: 'range', locale: lang });
		for (const e of es) {
			e.addEventListener('change', () => {
				const fs = document.getElementsByName('meta:' + e.dataset.key)[0];
				const ds = e._flatpickr.selectedDates;
				if (ds.length) {
					fs.value = JSON.stringify({
						from: dateToFormat(ds[0], 'yyyy-MM-dd'),  // SQL format
						to  : dateToFormat(ds[1], 'yyyy-MM-dd'),  // SQL format
					});
				} else {
					fs.value = '';
				}
				onModified();
			});
			const fs = document.getElementsByName('meta:' + e.dataset.key)[0];
			if (!fs.value) continue;
			const m = JSON.parse(fs.value);
			if (m === null) continue;
			const from = sqlToDate(m.from);
			const to   = sqlToDate(m.to);
			e._flatpickr.setDate([from, to]);
		}
	}

	function initMediaMetabox() {
		const ms = document.querySelectorAll('.metabox-media');
		addBtnEvent('.metabox-media .open-media-dialog', openMediaDialog);
		for (const m of ms) {
			const btnDel = m.querySelector('button.delete');
			if (!btnDel) continue;
			btnDel.addEventListener('click', () => {
				m.querySelector('.media-name').value = '';
				m.querySelector('.media-json').value = '';
				btnDel.setAttribute('disabled', true);
				onModified();
			});
		}
	}

	function initMediaImageMetabox() {
		const ms = document.querySelectorAll('.metabox-media-image');
		addBtnEvent('.metabox-media-image .open-media-dialog', openMediaDialog);
		for (const m of ms) {
			const btnDel = m.querySelector('button.delete');
			if (!btnDel) continue;
			btnDel.addEventListener('click', () => {
				m.querySelector('.media-name').value = '';
				m.querySelector('.media-json').value = '';
				m.querySelector('.image > div').style.backgroundImage = null;
				btnDel.setAttribute('disabled', true);
				onModified();
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

		let args = Object.assign({
			// Integration and setup options
			plugins : plugins,
			selector: '#post-content',
			setup   : e => { e.on('change', () => { isModified = true; }); },

			// User interface options
			block_formats    : formats,
			removed_menuitems: 'newdocument fontformats fontsizes lineheight',
			toolbar1         : toolbars[0],
			toolbar2         : toolbars[1],

			// Content appearance options
			content_css       : editorCss,
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
		}, editorOpts ? JSON.parse(editorOpts) : {})
		for (const f of window.NT.tiny_mce_before_init) {
			args = f(args, lang, assetsUrl);
		}
		tinymce.init(args);

		let st = null
		document.getElementById('post-title').addEventListener('input', e => {
			if (st) clearTimeout(st);
			st = setTimeout(() => {
				if (e.target.value !== '') {
					const ntc = document.querySelector('.site-header .notice');
					ntc.innerHTML = '';
				}
				onModified();
			}, 100);
		});
	}

	function adjustEditorHeight() {
		const onResize = () => {
			const clm = document.querySelector('.column.post > .column-main');
			const ed  = clm.querySelector('.tox-tinymce');
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


	function addBtnEvent(sel, fn) {
		for (const b of document.querySelectorAll(sel)) {
			b.addEventListener('click', fn);
		}
	}

	function update(e) {
		if (document.getElementById('post-title').value === '') {
			const ntc = document.querySelector('.site-header .notice');
			ntc.innerHTML = ntcEnterTitle;
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
	const ntc = document.querySelector('.site-header .notice');
	ntc.innerHTML = '';
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
		if (data.linkUrl) {
			str = `<a href="${data.linkUrl}" class="${data.align}">${str}</a>`;
		}
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
	const nameElm = f.querySelector('.media-name');
	const imgElm  = f.querySelector('.image > div');
	const delBtn  = f.querySelector('.button.delete');

	if (jsonElm) {
		jsonElm.value = JSON.stringify(data);
	}
	if (nameElm) {
		nameElm.value = data.name;
	}
	if (imgElm && data && data.minUrl) {
		imgElm.style.backgroundImage = `url('${data.minUrl}')`;
	}
	if (delBtn) {
		delBtn.removeAttribute('disabled');
	}
	onModified();
}
