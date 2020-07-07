<?php
namespace nt;
/**
 *
 * Session
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @version 2020-07-07
 *
 */


require_once( __DIR__ . '/../core/class-logger.php' );


class Session {

	public static function getNonce() {
		return bin2hex( openssl_random_pseudo_bytes( 16 ) );
	}

	const MODE_DIR           = 0777;
	const SESSION_ALIVE_TIME = 7200;  // 7200 = 120 minutes * 60 seconds
	const ACCOUNT_FILE_NAME  = 'account';
	const HASH_ALGORITHM     = 'sha256';

	function __construct( $urlPrivate, $dirPost, $dirAccount, $dirSession ) {
		$this->_urlPrivate = $urlPrivate;
		$this->_dirPost    = $dirPost;
		$this->_dirAccount = $dirAccount;
		$this->_dirSession = $dirSession;

		ini_set( 'session.use_strict_mode', 1 );
		if ( isset( $_SERVER['HTTPS'] ) ) ini_set( 'session.cookie_secure', 1 );
		Logger::output( 'Info (Session)' );
	}


	// ------------------------------------------------------------------------


	public function login( $user, $digest, $nonce, $cnonce, &$error ) {
		if ( empty( $user ) || empty( $digest ) || empty( $nonce ) || empty( $cnonce ) ) {
			$error = 'Parameters are wrong.';
			return false;
		}
		$this->_cleanUp();

		$url = $this->_urlPrivate;
		$a2 = hash( self::HASH_ALGORITHM, 'post:' . $url );

		$accountPath = $this->_dirAccount . self::ACCOUNT_FILE_NAME;
		if ( is_file( $accountPath ) === false ) {
			Logger::output( "Error (Session::login file_exists) [$accountPath]" );
			$error = 'Account file does not exist.';
			return false;
		}
		$as = file( $accountPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
		if ( $as === false ) {
			Logger::output( "Error (Session::login file) [$accountPath]" );
			$error = 'Account file cannot be opened.';
			return false;
		}
		foreach ( $as as $a ) {
			$d = explode( "\t", trim( $a ) );
			if ( $d[0] !== $user ) continue;
			$a1 = strtolower( $d[1] );
			$dgst = hash( self::HASH_ALGORITHM, "$a1:$nonce:$cnonce:$a2" );
			if ( $dgst === $digest ) return $this->_startNew( $error );
		}
		return false;
	}

	public function logout() {
		session_start();
		$_SESSION = [];
		if ( isset( $_COOKIE[ session_name() ] ) ) {
			setcookie( session_name(), '', time() - 42000, '/' );
		}
		session_destroy();
	}

	public function check() {
		session_start();
		$sid = session_id();
		if ( $sid === '' ) return false;
		if ( ! isset( $_SESSION['fingerprint'] ) ) return false;
		$fp = self::_getFingerprint();
		if ( $fp !== $_SESSION['fingerprint'] ) return false;

		$this->sessionId = $sid;
		return $this->_checkTime( $sid, true );
	}

	public function addTempPostId( $pid ) {
		$lines = $this->_loadSessionFile( $this->sessionId );
		if ( $lines === false ) return false;
		$lines[] = $pid;
		$this->_saveSessionFile( $this->sessionId, $lines );
		return true;
	}


	// ------------------------------------------------------------------------


	private function _cleanUp() {
		if ( ! file_exists( $this->_dirSession ) ) return;
		$fns = [];
		$handle = self::_ensureDir( $this->_dirSession ) ? opendir( $this->_dirSession ) : false;
		if ( $handle ) {
			while ( ( $fn = readdir( $handle ) ) !== false ) {
				if ( is_file( $this->_dirSession . $fn ) ) $fns[] = $fn;
			}
			closedir( $handle );
		}
		foreach ( $fns as $fn ) {
			$this->_checkTime( $fn, false );
		}
	}

	private function _startNew( &$error ) {
		session_start();
		$this->sessionId = session_id();
		$_SESSION['fingerprint'] = self::_getFingerprint();

		$res = $this->_saveSessionFile( $this->sessionId, [ time() ] );
		if ( $res === false ) {
			$error = 'Session file cannot be written.';
			return false;
		}
		return true;
	}

	private function _checkTime( $sid, $doUpdate ) {
		$lines = $this->_loadSessionFile( $sid );
		if ( $lines === false ) return false;

		$curTime = time();
		$sessionTime = array_shift( $lines );
		if ( self::SESSION_ALIVE_TIME < $curTime - $sessionTime ) {
			if ( $this->_dirPost !== false && 0 < count( $lines ) ) {
				foreach ( $lines as $id ) {
					$temp_post_path = $this->_dirPost . $id;
					if ( file_exists( $temp_post_path ) ) Store::deleteAll( $temp_post_path );
				}
			}
			$this->_removeSessionFile( $sid );
			return false;
		}
		if ( $doUpdate ) {
			array_unshift( $lines, $curTime );
			$this->_saveSessionFile( $sid, $lines );
		}
		return true;
	}


	// ------------------------------------------------------------------------


	private function _loadSessionFile( $sid ) {
		$path = $this->_dirSession . $sid;
		if ( ! is_file( $path ) ) return false;
		$lines = file( $path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
		if ( $lines === false ) {
			Logger::output("Error (Session::_loadSessionFile file) [$path]");
			return false;
		}
		return $lines;
	}

	private function _removeSessionFile( $sid ) {
		if ( ! is_dir( $this->_dirSession ) ) return;
		$path = $this->_dirSession . $sid;
		$res = unlink( $path );
		if ( $res === false ) {
			Logger::output("Error (Session::_removeSessionFile unlink) [$path]");
		}
	}

	private function _saveSessionFile( $sid, $lines ) {
		if ( self::_ensureDir( $this->_dirSession ) === false ) {
			Logger::output("Error (Session::_saveSessionFile ensureDir) [$this->_dirSession]");
			return false;
		}
		$path = $this->_dirSession . $sid;
		$cont = implode("\n", $lines);
		if ( file_put_contents( $path, $cont, LOCK_EX ) === false ) {
			Logger::output("Error (Session::_saveSessionFile file_put_contents) [$path]");
			return false;
		}
		return true;
	}


	// ------------------------------------------------------------------------


	static private function _getFingerprint() {
		$fp = 'newtrino';

		if ( ! empty( $_SERVER['HTTP_USER_AGENT'] ) ) {
			$fp .= $_SERVER['HTTP_USER_AGENT'];
		}
		if ( ! empty( $_SERVER['HTTP_ACCEPT_CHARSET'] ) ) {
			$fp .= $_SERVER['HTTP_ACCEPT_CHARSET'];
		}
		$fp .= session_id();
		return md5( $fp );
	}

	static private function _ensureDir( $path ) {
		if ( is_dir( $path ) ) {
			if ( self::MODE_DIR !== ( fileperms( $path ) & 0777 ) ) {
				chmod( $path, self::MODE_DIR );
			}
			return true;
		}
		if ( mkdir($path, self::MODE_DIR, true ) ) {
			chmod( $path, self::MODE_DIR );
			return true;
		}
		return false;
	}

}
