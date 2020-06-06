/**
 *
 * Login (JS)
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @version 2019-07-16
 *
 */


function initLogin() {
	document.getElementById('loginBtn').addEventListener('click', doLogin);
	document.getElementById('loginBtn').addEventListener('contextmenu', (e) => { doLogin(e, true); });
}

function doLogin(event, showKey) {
	const user = document.getElementById('user').value;
	const pwElm = document.getElementById('pw');
	const pw = pwElm.value;
	pwElm.value = '';

	const realm = document.getElementById('realm').value;
	const cnonce = createNonce();
	const method = 'post';
	const url = document.getElementById('url').value;
	const nonce = document.getElementById('nonce').value;

	const a1 = hash(user + ':' + realm + ':' + pw);
	const a2 = hash(method + ':' + url);
	const digest = hash(a1 + ':' + nonce + ':' + cnonce + ':' + a2);

	if (showKey && event.altKey && event.ctrlKey) {
		console.log(url + '\n' + user + '\t' + a1);
		event.preventDefault();
		return;
	}
	document.getElementById('cnonce').value = cnonce;
	document.getElementById('digest').value = digest;
	document.forms[0].submit();
}

function createNonce() {
	const str = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
	const len = str.length;
	let ret = '';
	for (let i = 0; i < 64; i += 1) {
		ret += str.charAt(Math.random() * 23456789 % len);
	}
	return ret;
}

function hash(str) {
	const sha256 = new jsSHA('SHA-256', 'TEXT');
	sha256.update(str);
	return sha256.getHash('HEX');
}
