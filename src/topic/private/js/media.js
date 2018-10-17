/**
 *
 * Media (JS)
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @author Yusuke Manabe   @ Space-Time Inc.
 * @version 2018-10-17
 *
 */


var file_name   = '';
var file_url    = '';
var file_is_img = false;
var image_cx    = '';
var image_cy    = '';

function initMedia() {
	document.getElementById('delete').disabled = true;
	document.getElementById('insert').disabled = true;
}

function setFile(fileName, url, width, height, isImage) {
	file_name   = fileName;
	file_url    = url;
	file_is_img = isImage;
	image_cx    = width;
	image_cy    = height;
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
	window.parent.insertMedia(file_name, file_url, image_cx, image_cy, pos, size, file_is_img);
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
