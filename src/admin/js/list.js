/**
 *
 * Index (JS)
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @version 2018-10-23
 *
 */


document.addEventListener('DOMContentLoaded', () => {
	const fm  = document.getElementById('filter-month');
	const fmr = document.getElementById('filter-month-reset');
	fm.addEventListener('change', () => {
		if (!fm.value) {
			window.location.href = 'list.php';
		} else {
			window.location.href = 'list.php?date=' + fm.value.replace('-', '');
		}
	});
	fmr.addEventListener('click', () => {
		fm.value = '';
		window.location.href = 'list.php';
	});

	document.getElementById('ppp').value = document.getElementById('posts_per_page').value;
	// const bgn = document.getElementById('date_bgn').value;
	// const end = document.getElementById('date_end').value;
	// document.getElementById('fp-date-bgn').value = bgn ? (bgn.substring(0, 4) + '-' + bgn.substring(4, 6) + '-' + bgn.substring(6, 8)) : null;
	// document.getElementById('fp-date-end').value = end ? (end.substring(0, 4) + '-' + end.substring(4, 6) + '-' + end.substring(6, 8)) : null;
	// flatpickr('.flatpickr', {wrap: true, dateFormat: 'YmdHiS', altInput: true, altFormat: 'Y-m-d'});
});

// function changeDateRange() {
// 	const m = document.getElementById('filter-month').value;
// 	if (!m) {
// 		window.location.href = 'list.php';
// 	} else {
// 		window.location.href = 'list.php?date=' + m.replace('-', '');
// 	}
// 	// return;
// 	// document.getElementById('date_bgn').value = document.getElementById('fp-date-bgn').value;
// 	// document.getElementById('date_end').value = document.getElementById('fp-date-end').value;
// 	// document.forms[0].action = 'list.php';
// 	// document.forms[0].submit();
// }

function changeCategory(cat) {
	document.getElementById('cat').value = cat;
	document.forms[0].action = 'list.php';
	document.forms[0].submit();
}

function changePpp(ppp) {
	document.getElementById('posts_per_page').value = ppp;
	document.forms[0].action = 'list.php';
	document.forms[0].submit();
}

function submitPage(page) {
	document.getElementById('page').value = page;
	document.forms[0].action = 'list.php';
	document.forms[0].submit();
}

function editPost(id) {
	document.getElementById('id').value = id;
	document.forms[0].action = 'post.php';
	document.forms[0].submit();
}

function newPost() {
	document.getElementById('mode').value = 'new';
	document.forms[0].action = 'post.php';
	document.forms[0].submit();
}

function deletePost(id, date, title) {
	if (!confirm(date + 'Do you want to delete "' + title + '"?')) return false;
	document.getElementById('mode').value = 'delete';
	document.getElementById('id').value = id;
	document.forms[0].action = 'list.php';
	document.forms[0].submit();
}

function setPostStatus(id, state) {
	const req = new XMLHttpRequest();
	req.addEventListener('load', function (e) {});  // for debugging
	req.open('post', 'responder.php', true);
	req.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
	req.send('mode=set_state' + '&id=' + id + '&state=' + state + '&cache=' + Date.now());
}
