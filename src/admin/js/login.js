/**
 * Login
 *
 * @author Takuto Yanagida
 * @version 2022-12-22
 */

document.addEventListener('DOMContentLoaded', () => {
	const formL = document.querySelector('form.log');
	const formR = document.querySelector('form.reg');

	const isDlg = document.querySelector('.site.dialog');

	initLoginDialog(isDlg);
	if (!isDlg) {
		initRegistrationDialog();

		const header = document.querySelector('.site-title');
		header.addEventListener('click', () => {
			formL.classList.toggle('hidden');
			formR.classList.toggle('hidden');
		});
	}
	setTimeout(() => location.reload(), 5 * 60 * 1000);  // For refreshing nonce


	// -------------------------------------------------------------------------


	function initLoginDialog(isDialog) {
		const btn = formL.querySelector('[type=\'submit\']');
		addLongPressListener(btn, doLogin, () => doLogin(!isDialog));

		const iptUsr = formL.elements['user'];
		const iptPwd = formL.querySelector('[type=\'password\']');

		const ntc = formL.querySelector('.notice');
		if (ntc) {
			iptUsr.addEventListener('change', clearMessage);
			iptPwd.addEventListener('change', clearMessage);
		}
		let st = null;
		function clearMessage() {
			clearTimeout(st);
			st = setTimeout(() => (ntc.innerHTML = ''), 2000);
		}

		function doLogin(showCode = false) {
			const key    = document.getElementById('key').value;
			const nonce  = document.getElementById('nonce').value;
			const url    = document.getElementById('url').value;
			const cnonce = createNonce();

			const usr = iptUsr.value;
			const pwd = iptPwd.value;
			iptPwd.value = '';

			const uph    = hash(`${usr}:${key}:${pwd}`);
			const digest = hash(`${uph}:${nonce}:${cnonce}:${hash(url)}`);

			if (showCode) {
				if (usr && pwd) {
					if (!confirm(document.getElementById('msg-issue').value)) return;
					formL.elements['mode'].value = 'issue';
				}
			}
			formL.elements['cnonce'].value = cnonce;
			formL.elements['digest'].value = digest;
			formL.submit();
		}
	}


	// -------------------------------------------------------------------------


	function initRegistrationDialog() {
		const btn = formR.querySelector('[type=\'submit\']');
		btn.addEventListener('click', doRegister);

		const iptCode = formR.elements['code'];
		const iptUsr  = formR.elements['user'];
		const iptPwd  = formR.querySelector('[type=\'password\']');

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
			const notice = formR.querySelector('.notice');
			if (notice) notice.innerHTML = '';
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
			iptPwd.value = '';

			const key = document.getElementById('key').value;
			const uph = hash(`${usr}:${key}:${pwd}`);

			formR.elements['hash'].value = uph;
			formR.submit();
		}
	}


	// -------------------------------------------------------------------------


	function createNonce() {
		const str = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
		const len = str.length;

		const rands = new Uint32Array(64);
		window.crypto.getRandomValues(rands);

		let ret = '';
		for (const r of rands) {
			ret += str.charAt(r % len);
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
			e.preventDefault();
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
		elm.addEventListener('click', (e) => {
			if (touch) {
				touch = false;
			} else if (!longClk) {
				clearTimeout(st);
				fnNorm();
			}
			e.preventDefault();  // for Safari to enable to cancel confirm dialog
		});
	}
});
