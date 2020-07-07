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

	function __construct( $urlAdmin, $dirAccount, $dirSession ) {
		$this->_urlAdmin   = $urlAdmin;
		$this->_dirAccount = $dirAccount;
		$this->_dirSession = $dirSession;

		ini_set( 'session.use_strict_mode', 1 );
		if ( isset( $_SERVER['HTTPS'] ) ) ini_set( 'session.cookie_secure', 1 );
		Logger::output( 'Info (Session)' );
	}


	// ------------------------------------------------------------------------


	public function login( $params, &$error ) {
		if ( empty( $params['user'] ) || empty( $params['digest'] ) || empty( $params['nonce'] ) || empty( $params['cnonce'] ) ) {
			$error = 'Parameters are wrong.';
			return false;
		}
		$this->_cleanUp();

		$url = $this->_urlAdmin;
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
			if ( $d[0] !== $params['user'] ) continue;
			$a1 = strtolower( $d[1] );
			$code = implode( ':', [ $a1, $params['nonce'], $params['cnonce'], $a2 ] );
			$dgst = hash( self::HASH_ALGORITHM, $code );
			if ( $dgst === $params['digest'] ) return $this->_startNew( $error );
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

	public function start() {
		session_start();
		$sid = session_id();
		if ( $sid === '' ) return false;
		if ( ! isset( $_SESSION['fingerprint'] ) ) return false;
		$fp = self::_getFingerprint();
		if ( $fp !== $_SESSION['fingerprint'] ) return false;

		$this->sessionId = $sid;
		return $this->_checkTime( $sid, true );
	}

	public function addTempDir( $dir ) {
		$data = $this->_loadSessionFile( $this->sessionId );
		if ( $data === null ) return false;
		if ( ! isset( $data['temp_dir'] ) || ! is_array( $data['temp_dir'] ) ) $data['temp_dir'] = [];
		$data['temp_dir'][] = $dir;
		$this->_saveSessionFile( $this->sessionId, $data );
		return true;
	}


	// ------------------------------------------------------------------------


	private function _cleanUp() {
		if ( ! is_dir( $this->_dirSession ) ) return;
		$fns = [];
		$sids = scandir( $this->_dirSession );
		if ( $sids === false ) return;
		foreach ( $sids as $sid ) {
			if ( is_dir( $this->_dirSession . $sid ) ) continue;
			$this->_checkTime( $sid, false );
		}
	}

	private function _startNew( &$error ) {
		session_start();
		$this->sessionId = session_id();
		$_SESSION['fingerprint'] = self::_getFingerprint();

		$res = $this->_saveSessionFile( $this->sessionId, [ 'timestamp' => time() ] );
		if ( $res === false ) {
			$error = 'Session file cannot be written.';
			return false;
		}
		return true;
	}

	private function _checkTime( string $sid, bool $doUpdate ): bool {
		$data = $this->_loadSessionFile( $sid );
		if ( $data === null ) return false;

		$curTime = time();
		$sessionTime = isset( $data['timestamp'] ) ? intval( $data['timestamp'] ) : 0;
		if ( self::SESSION_ALIVE_TIME < $curTime - $sessionTime ) {
			if ( isset( $data['temp_dir'] ) && is_array( $data['temp_dir'] ) ) {
				foreach ( $data['temp_dir'] as $dir ) {
					if ( is_dir( $dir ) ) Store::deleteAll( $dir );
				}
			}
			$this->_removeSessionFile( $sid );
			return false;
		}
		if ( $doUpdate ) {
			$data['timestamp'] = $curTime;
			$this->_saveSessionFile( $sid, $data );
		}
		return true;
	}


	// ------------------------------------------------------------------------


	private function _loadSessionFile( string $sid ): ?array {
		$path = $this->_dirSession . $sid;
		if ( ! is_file( $path ) ) return null;
		$json = file_get_contents( $path );
		if ( $json === false ) {
			Logger::output("Error (Session::_loadSessionFile file_get_contents) [$path]");
			return null;
		}
		return json_decode( $json, true );
	}

	private function _removeSessionFile( string $sid ) {
		if ( ! is_dir( $this->_dirSession ) ) return;
		$path = $this->_dirSession . $sid;
		if ( ! is_file( $path ) ) return;
		$res = unlink( $path );
		if ( $res === false ) {
			Logger::output("Error (Session::_removeSessionFile unlink) [$path]");
		}
	}

	private function _saveSessionFile( string $sid, array $data ): bool {
		if ( ! self::_ensureDir( $this->_dirSession ) ) return false;
		$path = $this->_dirSession . $sid;
		$json = json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		$res = file_put_contents( $path, $json, LOCK_EX );
		if ( $res === false ) {
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
		Logger::output("Error (Session::_ensureDir) [$path]");
		return false;
	}

}
