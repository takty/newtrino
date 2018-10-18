<?php
namespace nt;
/**
 *
 * Session Manager
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @version 2018-10-18
 *
 */


require_once(__DIR__ . '/../core/class-logger.php');


class Session {

	static function getNonce() {
		return bin2hex(openssl_random_pseudo_bytes(16));
	}

	private static function getSessionId() {
		return bin2hex(openssl_random_pseudo_bytes(16));
	}

	const SESSION_ALIVE_TIME = 7200;  // 7200 = 120 minutes * 60 seconds
	const ACCOUNT_FILE_NAME  = 'account';
	const HASH_ALGORITHM     = 'sha256';

	function __construct($urlPrivate, $dirPost, $dirAccount, $dirSession) {
		$this->_urlPrivate = $urlPrivate;
		$this->_dirPost    = $dirPost;
		$this->_dirAccount = $dirAccount;
		$this->_dirSession = $dirSession;
	}

	// ------------------------------------------------------------------------

	function login($user, $digest, $nonce, $cnonce, &$error) {
		if ($user == '' || $digest == '' || $nonce == '' || $cnonce == '') {
			$error = 'Parameters are wrong.';
			return false;
		}
		$this->cleanUp();

		$url = $this->_urlPrivate;
		$a2 = hash(self::HASH_ALGORITHM, 'post:' . $url);

		$accountPath = $this->_dirAccount . self::ACCOUNT_FILE_NAME;
		if (file_exists($accountPath) === false) {
			Logger::output('Error (Session::login file_exists) [' . $accountPath . ']');
			$error = 'Account file does not exist.';
			return false;
		}
		$as = file($accountPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		if ($as === false) {
			Logger::output('Error (Session::login file) [' . $accountPath . ']');
			$error = 'Account file cannot be opened.';
			return false;
		}
		foreach ($as as $a) {
			$d = explode("\t", trim($a));
			if ($d[0] !== $user) continue;
			$a1 = strtolower($d[1]);
			$dgst = hash(self::HASH_ALGORITHM, $a1 . ':' . $nonce . ':' . $cnonce . ':' . $a2);
			if ($dgst === $digest) return $this->startNew($error);
		}
		return false;
	}

	function check($query) {
		$sid = empty($query['sid']) ? '' : $query['sid'];
		if ($sid === '') return false;
		$this->sessionId = $sid;
		return $this->checkTime($sid, true);
	}

	function addTempPostId($pid) {
		$lines = $this->loadSessionFile($this->sessionId);
		if ($lines === false) return false;
		$lines[] = $pid;
		$this->saveSessionFile($this->sessionId, $lines);
		return true;
	}

	// ------------------------------------------------------------------------

	private function cleanUp() {
		$fns = [];
		if ($handle = $this->openDir($this->_dirSession)) {
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
		$sid = self::getSessionId();
		$res = $this->saveSessionFile($sid, [time()]);
		if ($res === false) {
			$error = 'Session file cannot be written.';
			return false;
		}
		$this->sessionId = $sid;
		return $sid;
	}

	private function checkTime($sid, $doUpdate) {
		$lines = $this->loadSessionFile($sid);
		if ($lines === false) return false;

		$curTime = time();
		$sessionTime = array_shift($lines);
		if (self::SESSION_ALIVE_TIME < $curTime - $sessionTime) {
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
			Logger::output('Error (Session::loadSessionFile file) [' . $path . ']');
			return false;
		}
		return $lines;
	}

	private function removeSessionFile($sid) {
		$path = $this->_dirSession . $sid;
		$res = unlink($path);
		if ($res === false) {
			Logger::output('Error (Session::removeSessionFile unlink) [' . $path . ']');
		}
	}

	private function saveSessionFile($sid, $lines) {
		$path = $this->_dirSession . $sid;
		$cont = implode("\n", $lines);
		if (file_put_contents($path, $cont, LOCK_EX) === false) {
			Logger::output('Error (Session::saveSessionFile file_put_contents) [' . $path . ']');
			return false;
		}
		return true;
	}

	// ------------------------------------------------------------------------

	private function openDir($path) {
		if (file_exists($path)) {
			return opendir($path);
		}
		if (mkdir($path, 0755, true)) {
			chmod($path, 0755);
			return opendir($path);
		}
		return false;
	}

}
