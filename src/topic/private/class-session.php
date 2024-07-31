<?php
/**
 *
 * Session Manager
 *
 * @author Space-Time Inc.
 * @version 2023-06-22
 *
 */

namespace nt;

require_once(__DIR__ . '/../core/class-logger.php');


class Session {

	public static function getNonce() {
		return bin2hex(openssl_random_pseudo_bytes(16));
	}

	private static function getSessionId() {
		return bin2hex(openssl_random_pseudo_bytes(16));
	}

	const MODE_DIR           = 0777;
	const SESSION_ALIVE_TIME = 7200;  // 7200 = 120 minutes * 60 seconds
	const ACCOUNT_FILE_NAME  = 'account';
	const HASH_ALGORITHM     = 'sha256';

	private $_urlPrivate;
	private $_dirPost;
	private $_dirAccount;
	private $_dirSession;
	private $_sessionId;

	function __construct($urlPrivate, $dirPost, $dirAccount, $dirSession) {
		$this->_urlPrivate = $urlPrivate;
		$this->_dirPost    = $dirPost;
		$this->_dirAccount = $dirAccount;
		$this->_dirSession = $dirSession;

		ini_set('session.use_strict_mode', '1');
		if (isset($_SERVER['HTTPS'])) ini_set('session.cookie_secure', '1');
		Logger::output("Info (Session)");
	}

	// ------------------------------------------------------------------------

	public function login($user, $digest, $nonce, $cnonce, &$error) {
		if ($user === '' || $digest === '' || $nonce === '' || $cnonce === '') {
			$error = 'Parameters are wrong.';
			return false;
		}
		$this->cleanUp();

		$url = $this->_urlPrivate;
		$a2 = hash(self::HASH_ALGORITHM, 'post:' . $url);

		$accountPath = $this->_dirAccount . self::ACCOUNT_FILE_NAME;
		if (file_exists($accountPath) === false) {
			Logger::output("Error (Session::login file_exists) [$accountPath]");
			$error = 'Account file does not exist.';
			return false;
		}
		$as = file($accountPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		if ($as === false) {
			Logger::output("Error (Session::login file) [$accountPath]");
			$error = 'Account file cannot be opened.';
			return false;
		}
		foreach ($as as $a) {
			$d = explode("\t", trim($a));
			if ($d[0] !== $user) continue;
			$a1 = strtolower($d[1]);
			$dgst = hash(self::HASH_ALGORITHM, "$a1:$nonce:$cnonce:$a2");
			if ($dgst === $digest) return $this->startNew($error);
		}
		return false;
	}

	public function logout() {
		session_start();
		$_SESSION = [];
		if (isset($_COOKIE[session_name()])) {
			setcookie(session_name(), '', time() - 42000, '/');
		}
		session_destroy();
	}

	public function check() {
		session_start();
		$sid = session_id();
		if ($sid === '') return false;
		if (! isset($_SESSION['fingerprint'])) return false;
		$fp = $this->getFingerprint();
		if ($fp !== $_SESSION['fingerprint']) return false;

		$this->_sessionId = $sid;
		return $this->checkTime($sid, true);
	}

	public function addTempPostId($pid) {
		$lines = $this->loadSessionFile($this->_sessionId);
		if ($lines === false) return false;
		$lines[] = $pid;
		$this->saveSessionFile($this->_sessionId, $lines);
		return true;
	}

	// ------------------------------------------------------------------------

	private function getFingerprint() {
		$fp = 'newtrino';

		if (!empty($_SERVER['HTTP_USER_AGENT'])) {
			$fp .= $_SERVER['HTTP_USER_AGENT'];
		}
		if (!empty($_SERVER['HTTP_ACCEPT_CHARSET'])) {
			$fp .= $_SERVER['HTTP_ACCEPT_CHARSET'];
		}
		$fp .= session_id();
		return md5($fp);
	}

	private function cleanUp() {
		if (!file_exists($this->_dirSession)) return;
		$fns = [];
		$handle = $this->ensureDir($this->_dirSession) ? opendir($this->_dirSession) : false;
		if ($handle) {
			while (($fn = readdir($handle)) !== false) {
				if (is_file($this->_dirSession . $fn)) $fns[] = $fn;
			}
			closedir($handle);
		}
		foreach ($fns as $fn) {
			$this->checkTime($fn, false);
		}
	}

	private function startNew(&$error) {
		session_start();
		$this->_sessionId = session_id();
		$_SESSION['fingerprint'] = $this->getFingerprint();

		$res = $this->saveSessionFile($this->_sessionId, [time()]);
		if ($res === false) {
			$error = 'Session file cannot be written.';
			return false;
		}
		return true;
	}

	private function checkTime($sid, $doUpdate) {
		$lines = $this->loadSessionFile($sid);
		if ($lines === false) return false;

		$curTime = time();
		$sessionTime = array_shift($lines);
		if (self::SESSION_ALIVE_TIME < $curTime - (int) $sessionTime) {
			if ($this->_dirPost !== false && 0 < count($lines)) {
				foreach ($lines as $id) {
					$temp_post_path = $this->_dirPost . $id;
					if (file_exists($temp_post_path)) Store::deleteAll($temp_post_path);
				}
			}
			$this->removeSessionFile($sid);
			return false;
		}
		if ($doUpdate) {
			array_unshift($lines, $curTime);
			$this->saveSessionFile($sid, $lines);
		}
		return true;
	}

	private function loadSessionFile($sid) {
		$path = $this->_dirSession . $sid;
		if (file_exists($path) === false) return false;
		$lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		if ($lines === false) {
			Logger::output("Error (Session::loadSessionFile file) [$path]");
			return false;
		}
		return $lines;
	}

	private function removeSessionFile($sid) {
		if (!file_exists($this->_dirSession)) return;
		$path = $this->_dirSession . $sid;
		$res = unlink($path);
		if ($res === false) {
			Logger::output("Error (Session::removeSessionFile unlink) [$path]");
		}
	}

	private function saveSessionFile($sid, $lines) {
		if ($this->ensureDir($this->_dirSession) === false) {
			Logger::output("Error (Session::saveSessionFile ensureDir) [$this->_dirSession]");
			return false;
		}
		$path = $this->_dirSession . $sid;
		$cont = implode("\n", $lines);
		if (file_put_contents($path, $cont, LOCK_EX) === false) {
			Logger::output("Error (Session::saveSessionFile file_put_contents) [$path]");
			return false;
		}
		return true;
	}

	// ------------------------------------------------------------------------

	private function ensureDir($path) {
		if (file_exists($path)) {
			if (self::MODE_DIR !== (fileperms($path) & 0777)) {
				chmod($path, self::MODE_DIR);
			}
			return true;
		}
		if (mkdir($path, self::MODE_DIR, true)) {
			chmod($path, self::MODE_DIR);
			return true;
		}
		return false;
	}

}
