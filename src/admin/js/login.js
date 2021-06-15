/**
 *
 * Login (JS)
 *
 * @author Takuto Yanagida
 * @version 2021-06-15
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

	function doLogin(e, showAcct = false) {
		e.preventDefault();
		e.stopPropagation();

		const user  = document.getElementById('user').value;
		const pwElm = document.getElementById('pw');
		const pw    = pwElm.value;

		const key    = document.getElementById('key').value;
		const nonce  = document.getElementById('nonce').value;
		const url    = document.getElementById('url').value;
		const cnonce = createNonce();

		const a1     = hash(user + ':' + key + ':' + pw);
		const a1p    = hash(a1 + ':' + url);
		const digest = hash(a1p + ':' + nonce + ':' + cnonce + ':' + hash(url));

		if (showAcct) {
			if (user && pw) {
				const elm = document.getElementById('account');
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

		const rands = new Uint32Array(64);
		window.crypto.getRandomValues(rands);

		let ret = '';
		for (let i = 0; i < rands.length; i += 1) {
			ret += str.charAt(rands[i] % len);
		}
		return ret;
	}

	function hash(str) {
		const sha256 = new jsSHA('SHA-256', 'TEXT');
		sha256.update(str);
		return sha256.getHash('HEX');
	}
});
