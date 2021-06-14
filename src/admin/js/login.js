/**
 *
 * Login (JS)
 *
 * @author Takuto Yanagida
 * @version 2021-06-14
 *
 */


// @include _common.js

document.addEventListener('DOMContentLoaded', () => {
	const btn = document.getElementById('btn-login');
	btn.addEventListener('click', doLogin);
	btn.addEventListener('contextmenu', (e) => {
		if (document.body.classList.contains('dialog')) {
			e.preventDefault();
			return;
		}
		doLogin(e, true);
	});

	function doLogin(event, showKey = false) {
		event.preventDefault();
		const user  = document.getElementById('user').value;
		const pwElm = document.getElementById('pw');
		const pw    = pwElm.value;

		const realm  = document.getElementById('realm').value;
		const cnonce = createNonce();
		const method = 'post';
		const url    = document.getElementById('url').value;
		const nonce  = document.getElementById('nonce').value;

		const a1     = hash(user + ':' + realm + ':' + pw);
		const a2     = hash(method + ':' + url);
		const digest = hash(a1 + ':' + nonce + ':' + cnonce + ':' + a2);

		if (showKey) {
			if (user && pw) {
				const elm = document.getElementById('key');
				elm.innerHTML = user + '\t' + a1;
			}
			return;
		}
		pwElm.value = '';
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
});
