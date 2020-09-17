/**
 *
 * Post (JS)
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @version 2020-09-17
 *
 */


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
			});
		}
	}


	// -------------------------------------------------------------------------


	function initEditorPane() {
		const css  = document.getElementById('editor-css').value;
		const json = document.getElementById('editor-option').value;
		const opt  = json ? JSON.parse(json) : {};
		const assetsUrl = document.getElementById('assets-url').value;

		let args = Object.assign({
			selector: '#post-content',
			plugins: [
				'advlist anchor autolink charmap code colorpicker contextmenu directionality fullscreen hr image insertdatetime',
				'link lists media nonbreaking noneditable paste print searchreplace table textcolor textpattern visualblocks visualchars',
			],
			removed_menuitems: 'newdocument',
			toolbar1: 'undo redo | bold italic underline strikethrough | superscript subscript | link unlink | forecolor backcolor | removeformat',
			toolbar2: 'formatselect | bullist numlist | blockquote | alignleft aligncenter alignright | styleselect',
			block_formats: 'Paragraph=p;Heading 3=h3;Heading 4=h4;Heading 5=h5;Blockquote=blockquote;Preformatted=pre',
			content_css: css,
			language: lang,
			element_format: 'html',
			code_dialog_width: 800,
			nonbreaking_force_tab: true,
			object_resizing: 'img',
			link_context_toolbar: true,
			table_default_attributes: {},
			table_default_styles: {},
			table_resize_bars: false,
			table_advtab: false,
			table_class_list: [],
			visual_table_class: ' ',
			setup: (e) => { e.on('change', () => { isModified = true; }); },
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
			const div = document.querySelector('.container-main .button-row + div');
			if (div) {
				const r = div.getBoundingClientRect();
				const h = clm.clientHeight - (0 | r.top);
				tinymce.activeEditor.theme.resizeTo(null, h - 64);
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


function insertMediaToContent(data) {
	closeDialog();
	if (data['size']) {
		const ss  = data.srcset ? ` srcset="${data.srcset}"` : '';
		const cls = `size-${data.size}` + (data.linkUrl ? '' : ` ${data.align}`);
		let str = `<img class="${cls}" src="${data.url}"${ss} alt="${data.name}" width="${data.width}" height="${data.height}" loading="lazy">`;
		if (data.linkUrl) str = `<a href="${data.linkUrl}" class="${data.align}">${str}</a>`;
		tinymce.activeEditor.execCommand('mceInsertContent', false, str);
	} else {
		const s = tinymce.activeEditor.selection.getContent();
		if (s !== '') {
			tinymce.activeEditor.execCommand('mceInsertLink', false, data.url);
		} else {
			const str = `<a href="${data.url}">${data.name}</a>`;
			tinymce.activeEditor.execCommand('mceInsertContent', false, str);
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
}
