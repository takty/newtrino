/**
 *
 * Media (JS)
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @version 2020-07-16
 *
 */


document.addEventListener('DOMContentLoaded', () => {
	const btnClose  = document.getElementById('btn-close');
	const btnDelete = document.getElementById('btn-delete');
	const btnInsert = document.getElementById('btn-insert');

	btnClose.addEventListener('click', closeDialog);
	btnInsert.addEventListener('click', insertMedia);
	btnDelete.addEventListener('click', deleteMedia);
	btnDelete.disabled = true;
	btnInsert.disabled = true;

	const rs = document.querySelectorAll('.item-media input[type="radio"]');
	for (let r of rs) {
		r.addEventListener('change', enableButtons);
	}

	function enableButtons() {
		btnDelete.disabled = false;
		btnInsert.disabled = false;
	}

	function closeDialog() {
		window.parent.closeDialog();
	}

	function insertMedia() {
		const p = document.querySelector('.item-media input[type="radio"]:checked').parentElement;
		const file_name = p.querySelector('.file-name').value;
		const file_url  = p.querySelector('.file-url').value;
		const width     = p.querySelector('.width').value;
		const height    = p.querySelector('.height').value;
		const is_img    = p.querySelector('.is-img').value;

		const align = document.getElementsByName('align')[0].value;
		const size  = document.getElementsByName('size')[0].value;

		console.log(file_name, file_url, width, height, align, size, is_img);
		window.parent.insertMedia(file_name, file_url, width, height, align, size, is_img);
	}

	function deleteMedia() {
		const msg = document.getElementById('msg-delete').value;
		if (!confirm(msg)) return;

		const p = document.querySelector('.item-media input[type="radio"]:checked').parentElement;
		const file_name = p.querySelector('.file-name').value;

		document.getElementById('deleted-file').value = file_name;
		document.getElementById('form-delete').submit();
	}
});
