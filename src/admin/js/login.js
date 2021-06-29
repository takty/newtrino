/**
 *
 * Login (JS)
 *
 * @author Takuto Yanagida
 * @version 2021-06-29
 *
 */


// @include _common.js

history.pushState(null, null, location.href);
window.addEventListener('popstate', () => { history.go(-1); });

document.addEventListener('DOMContentLoaded', () => {

	const header = document.getElementsByTagName('h1')[0];
	header.addEventListener('click', () => {
		const formL = document.querySelector('form.log');
		const formR = document.querySelector('form.reg');
		formL.classList.toggle('hidden');
		formR.classList.toggle('hidden');
	});
	setTimeout(() => { location.reload(); }, 5 * 60 * 1000);  // For refreshing nonce

	initLoginDialog();
	if (!document.body.classList.contains('dialog')) initRegistrationDialog();


	// -------------------------------------------------------------------------


	function initLoginDialog() {
		const btn = document.getElementById('btn-log');
		addLongPressListener(btn, doLogin, () => { doLogin(true); });

		const iptUsr = document.getElementById('user');
		const iptPwd = document.getElementById('pw');

		function doLogin(showCode = false) {
			if (showCode && document.body.classList.contains('dialog')) return;

			const usr = iptUsr.value;
			const pwd = iptPwd.value;

			const key    = document.getElementById('key').value;
			const nonce  = document.getElementById('nonce').value;
			const url    = document.getElementById('url').value;
			const cnonce = createNonce();

			const uph    = hash(usr + ':' + key + ':' + pwd);
			const digest = hash(uph + ':' + nonce + ':' + cnonce + ':' + hash(url));

			if (showCode) {
				if (usr && pwd) {
					if (!confirm(document.getElementById('msg-issue').value)) return;
					document.getElementById('mode').value = 'issue';
				}
			}
			iptPwd.value = '';
			document.getElementById('cnonce').value = cnonce;
			document.getElementById('digest').value = digest;
			document.forms[0].submit();
		}
	}


	// -------------------------------------------------------------------------


	function initRegistrationDialog() {
		const btn = document.getElementById('btn-reg');
		btn.addEventListener('click', doRegister);

		const iptCode = document.getElementById('code');
		const iptUsr  = document.getElementById('new-user');
		const iptPwd  = document.getElementById('new-pw');

		iptCode.value = '';
		iptUsr.value  = '';
		iptPwd.value  = '';

		iptCode.addEventListener('keyup', e => checkLen(e, 0));
		iptCode.addEventListener('change', e => checkLen(e, 0));

		iptUsr.addEventListener('keyup', checkUser);
		iptUsr.addEventListener('change', checkUser);

		iptPwd.addEventListener('keyup', e => checkLen(e, 3));
		iptPwd.addEventListener('change', e => checkLen(e, 3));

		function checkLen(e, len) {
			const str = e.target.value.trim();
			if (len < str.length) {
				e.target.removeAttribute('invalid');
			} else {
				e.target.setAttribute('invalid', '');
			}
			regChanged();
		}

		function checkUser(e) {
			const un = new RegExp('^(?=.*[a-z])[\-_a-z0-9]{4,32}$', 'i');
			if (un.test(e.target.value)) {
				e.target.removeAttribute('invalid');
			} else {
				e.target.setAttribute('invalid', '');
			}
			regChanged();
		}

		function regChanged() {
			const msg = document.getElementById('msg-reg');
			if (msg) msg.innerHTML = '';
			if (iptCode.hasAttribute('invalid') || iptUsr.hasAttribute('invalid') || iptPwd.hasAttribute('invalid')) {
				btn.setAttribute('disabled', '');
			} else {
				btn.removeAttribute('disabled');
			}
		}

		function doRegister(e) {
			e.preventDefault();
			e.stopPropagation();

			const usr = iptUsr.value;
			const pwd = iptPwd.value;

			const key = document.getElementById('key').value;
			const uph = hash(usr + ':' + key + ':' + pwd);

			iptPwd.value = '';
			document.getElementById('hash').value = uph;
			document.querySelector('form.reg').submit();
		}
	}


	// -------------------------------------------------------------------------


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


	// -------------------------------------------------------------------------


	function addLongPressListener(elm, fnNorm, fnLong) {
		let longClk = false;
		let longTap = false;
		let touch   = false;
		let st;

		elm.addEventListener('touchstart', () => {
			touch = true;
			longTap = false;
			st = setTimeout(() => {
				longTap = true;
				fnLong();
			}, 500);
		});
		elm.addEventListener('touchend', () => {
			if (!longTap) {
				clearTimeout(st);
				fnNorm();
			} else {
				touch = false;
			}
		});
		elm.addEventListener('mousedown', (e) => {
			if (e.button !== 0) return;
			if (touch) return;
			longClk = false;
			st = setTimeout(() => {
				longClk = true;
				setTimeout(fnLong, 0);
			}, 500);
		});
		elm.addEventListener('click', () => {
			if (touch) {
				touch = false;
			} else if (!longClk) {
				clearTimeout(st);
				fnNorm();
			}
		});
	}
});
