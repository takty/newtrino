/**
 *
 * Login (JS)
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @version 2017-07-24
 *
 */


function initLogin(loginBtn) {
	document.getElementById('loginBtn').addEventListener('click', doLogin);
	document.getElementById('loginBtn').addEventListener('contextmenu', function (e) {doLogin(e, true);});
}

function doLogin(event, showKey) {
	var user = document.getElementById('user').value;
	var pwElm = document.getElementById('pw');
	var pw = pwElm.value;
	pwElm.value = '';

	var realm = document.getElementById('realm').value;
	var cnonce = createNonce();
	var method = 'post';
	var url = document.getElementById('url').value;
	var nonce = document.getElementById('nonce').value;

	var a1 = forge_sha256(user + ':' + realm + ':' + pw);
	var a2 = forge_sha256(method + ':' + url);
	var digest = forge_sha256(a1 + ':' + nonce + ':' + cnonce + ':' + a2);

	if (showKey && event.altKey && event.ctrlKey && event.shiftKey) {
		console.log(url + '\n' + user + '\t' + a1);
		event.preventDefault();
		return;
	}
	document.getElementById('cnonce').value = cnonce;
	document.getElementById('digest').value = digest;
	document.forms[0].submit();
}

function createNonce() {
	var str = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
	var len = str.length;
	var ret = '';
	for (var i = 0; i < 64; i += 1) {
		ret += str.charAt(Math.random() * 23456789 % len);
	}
	return ret;
}
