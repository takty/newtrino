
// for Index ------------------------------------------------------------------

function initIndex() {
	document.getElementById('ppp').value = document.getElementById('posts_per_page').value;
	var bgn = document.getElementById('date_bgn').value;
	var end = document.getElementById('date_end').value;
	document.getElementById('fp-date-bgn').value = bgn ? (bgn.substring(0, 4) + '-' + bgn.substring(4, 6) + '-' + bgn.substring(6, 8)) : null;
	document.getElementById('fp-date-end').value = end ? (end.substring(0, 4) + '-' + end.substring(4, 6) + '-' + end.substring(6, 8)) : null;
	flatpickr('.flatpickr', {wrap: true, dateFormat: 'YmdHiS', altInput: true, altFormat: 'Y-m-d'});
}

function changeDateRange() {
	document.getElementById('date_bgn').value = document.getElementById('fp-date-bgn').value;
	document.getElementById('date_end').value = document.getElementById('fp-date-end').value;
	document.forms[0].action = 'index.php';
	document.forms[0].submit();
}

function changeCategory(cat) {
	document.getElementById('cat').value = cat;
	document.forms[0].action = 'index.php';
	document.forms[0].submit();
}

function changePpp(ppp) {
	document.getElementById('posts_per_page').value = ppp;
	document.forms[0].action = 'index.php';
	document.forms[0].submit();
}

function submitPage(page) {
	document.getElementById('page').value = page;
	document.forms[0].action = 'index.php';
	document.forms[0].submit();
}

function editPost(id) {
	document.getElementById('id').value = id;
	document.forms[0].action = 'edit.php';
	document.forms[0].submit();
}

function newPost() {
	document.getElementById('mode').value = 'new';
	document.forms[0].action = 'edit.php';
	document.forms[0].submit();
}

function deletePost(id, date, title) {
	if (!confirm(date + 'Do you want to delete "' + title + '"?')) return false;
	document.getElementById('mode').value = 'delete';
	document.getElementById('id').value = id;
	document.forms[0].action = 'index.php';
	document.forms[0].submit();
}

function setPostState(id, state) {
	var sid = document.getElementById('sid').value;
	var req = new XMLHttpRequest();
	req.addEventListener('load', function (e) {});  // for debugging
	req.open('post', '_responder.php', true);
	req.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
	req.send('mode=set_state' + '&sid=' + sid + '&id=' + id + '&state=' + state + '&cache=' + Date.now());
}

// for Edit -------------------------------------------------------------------

function initEdit() {
	var fp = flatpickr('#post_published_date', {
		enableSeconds: true, time_24hr: true, enableTime: true,
		onChange: function(dateObj, dateStr, instance) {
			if (document.getElementById('post_state').value === 'draft') return;
			var s = dateStr.replace(/-|:|\s/g, '');
			var dn = parseInt(s, 10);
			var cn = parseInt(formatDate(new Date(), 'YYYYMMDDhhmmss'));
			if (dn <= cn) {
				document.getElementById('post_state_published').selected = true;
			} else {
				document.getElementById('post_state_reserved').selected = true;
			}
		}
	});
	document.getElementById('post_state').addEventListener('change', function () {
		if (this.value === 'draft') return;
		if (this.value === 'published') {
			var s = document.getElementById('post_published_date').value.replace(/-|:|\s/g, '');
			var dn = parseInt(s, 10);
			var cn = parseInt(formatDate(new Date(), 'YYYYMMDDhhmmss'));
			if (dn > cn) {
				document.getElementById('post_published_date').value = formatDate(new Date(), 'YYYY-MM-DD hh:mm:ss');
			}
		}
		if (this.value === 'reserved') {
			var s = document.getElementById('post_published_date').value.replace(/-|:|\s/g, '');
			var dn = parseInt(s, 10);
			var cn = parseInt(formatDate(new Date(), 'YYYYMMDDhhmmss'));
			if (dn <= cn) {
				setTimeout(function () {fp.open();}, 100);
			}
		}
	});
	tinymce.init({
		selector: '#post_content',
		plugins: [
			'advlist autolink lists link image charmap print preview anchor',
			'searchreplace visualblocks code fullscreen',
			'insertdatetime media table contextmenu paste code'
		],
		toolbar: 'insertfile undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image',
		content_css: 'css/editor_style.css',
		language: 'ja',
		setup: function (ed) {
			ed.on('change', function (ed) {
				changed = true;
			});
		}
	});
	document.getElementById('post_title').addEventListener('change', function () {
		if (this.value !== '') {
			var elm = document.getElementById('message');
			elm.innerText = '';
			elm.style.display = 'none';
		}
		onChanged();
	});
	document.getElementById('post_published_date').addEventListener('change', function () {
		onChanged();
	});
	document.getElementById('post_state').addEventListener('change', function () {
		onChanged();
	});
	document.getElementById('post_cat').addEventListener('change', function () {
		onChanged();
		var isEvent = document.getElementById('post_cat').value === 'event';
		document.getElementById('frame-event-duration').style.display = isEvent ? 'block' : 'none';
	});
	window.addEventListener('beforeunload', function (e) {
		if (changed) {
			var msg = 'Do you want to move from the page you are inputting?';
			e.returnValue = msg;
			return msg;
		}
	});
	flatpickr('#event_date_bgn', {});
	flatpickr('#event_date_end', {});

	var isEvent = document.getElementById('post_cat').value === 'event';
	document.getElementById('frame-event-duration').style.display = isEvent ? 'block' : 'none';
}

var changed = false;

var onChanged = function () {
	if (changed === false) {
		changed = true;
		var elm = document.getElementById('message');
		elm.innerText = '';
		elm.style.display = 'none';
		var um = document.getElementById('update-msg');
		um.innerText = '';
	}
}

var formatDate = function(date, format) {
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
	window.open('about:blank', 'preview', 'scrollbars=yes', 'windowStyle');
	var fp = document.getElementById('form-post');
	fp.target = 'preview';
	fp.action = '_preview.php';
	fp.submit();
}

function update() {
	if (document.getElementById('post_title').value === '') {
		var elm = document.getElementById('message');
		elm.innerText = 'The title is blank.';
		elm.style.display = 'block';
		return false;
	}
	document.getElementById('mode').value = 'update';
	var fp = document.getElementById('form-post');
	fp.target = '';
	fp.action = 'edit.php';
	changed = false;
	fp.submit();
}

function showList() {
	var fp = document.getElementById('form-post');
	fp.target = '';
	fp.action = 'index.php';
	fp.submit();
}

function showPost() {
	var id = document.getElementById('id').value;
	window.open('../view.php?id=' + id, 'post', '');
}

function showMediaChooser() {
	var sid = document.getElementById('sid').value;
	var pid = document.getElementById('id').value;
	var dialogPlaceholder = document.getElementById('dialog-placeholder');

	var win = document.createElement('iframe');
	win.id = 'mediaChooser';
	win.src = '_media.php?sid=' + sid + '&id=' + pid;
	win.style.width = '800px';
	win.style.height = '700px';
	win.style.position = 'absolute';

	dialogPlaceholder.appendChild(win);
	dialogPlaceholder.style.display = 'flex';
}

function closeMediaChooser() {
	var dialogPlaceholder = document.getElementById('dialog-placeholder');
	var win = document.getElementById('mediaChooser');
	dialogPlaceholder.removeChild(win);
	dialogPlaceholder.style.display = 'none';
}

var wh_min = 220;

function insertMedia(src, w, h, pos, size) {
	closeMediaChooser();
	var vc = "";
	if (pos == "l") vc = "pos-left";
	if (pos == "c") vc = "pos-center";
	if (pos == "r") vc = "pos-right";
	if (src.match(/[zip|pdf]\.jpg$/ig)) size = "";
	var imgstr = '<a href="' + src + '" class="' + vc + '"><img src="' + src + '"';
	if (size !== '') {
		var r = wh_min
		if (size === "l") r *= 3;
		if (size === "m") r *= 2;
		var vw = Math.round(getSizeW(r, w, h));
		var vh = Math.round(getSizeH(r, w, h));
		var is = ' width="' + vw + '" height="' + vh + '" ';
		imgstr = imgstr + is;
	}
	imgstr += "></a>";
	tinymce.activeEditor.execCommand('mceInsertContent', false, imgstr);
}

function getSizeW(wh, w, h) {
	if (w > h) {
		var p = parseFloat(w) / parseFloat(h);
		return parseFloat(wh) * parseFloat(p);
	} else {
		return wh;
	}
}

function getSizeH(wh, w, h) {
	if (w > h) {
		return wh;
	} else {
		var p = parseFloat(h) / parseFloat(w);
		return parseFloat(wh) * parseFloat(p);
	}
}

// for Media ------------------------------------------------------------------

var file_name = '';
var file_url  = '';
var image_cx  = '';
var image_cy  = '';

function initMedia() {
	document.getElementById('delete').disabled = true;
	document.getElementById('insert').disabled = true;
}

function setFile(fileName, url, width, height) {
	file_name = fileName;
	file_url = url;
	image_cx = width;
	image_cy = height;
	document.getElementById('delete').disabled = false;
	document.getElementById('insert').disabled = false;
}

function deleteFile() {
	if (!confirm('Do you want to delete it?')) return;
	document.getElementById('deleted_file').value = file_name;
	document.getElementById('deleteForm').submit();
}

function insert() {
	var pos = checkRadio('pos');
	var size = checkRadio('size');
	window.parent.insertMedia(file_url, image_cx, image_cy, pos, size);
}

function checkRadio(tag) {
	var radioList = document.getElementsByName(tag);
	for (var i = 0; i < radioList.length; i += 1) {
		if (radioList[i].checked) return radioList[i].value;
	}
	return '';
}

function cancel() {
	window.parent.closeMediaChooser();
}
