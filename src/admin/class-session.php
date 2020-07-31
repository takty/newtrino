<?php
namespace nt;
/**
 *
 * Session
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @version 2020-08-01
 *
 */


require_once( __DIR__ . '/../core/class-logger.php' );
require_once( __DIR__ . '/../core/class-store.php' );


class Session {

	const TIMEOUT        = 7200;  // 7200 = 120 minutes * 60 seconds
	const ACCT_FILE_NAME = 'account';
	const HASH_ALGO      = 'sha256';

	static public function getRealm(): string {
		return 'newtrino';
	}

	static public function getNonce(): string {
		return bin2hex( openssl_random_pseudo_bytes( 16 ) );
	}

	private $_urlAdmin;
	private $_dirAcct;
	private $_dirSession;

	private $_adminLang = '';
	private $_errMsg    = '';
	private $_sessionId = '';

	function __construct( string $urlAdmin, string $dirAcct, string $dirSession ) {
		$this->_urlAdmin   = $urlAdmin;
		$this->_dirAcct    = $dirAcct;
		$this->_dirSession = $dirSession;

		ini_set( 'session.use_strict_mode', 1 );
		if ( isset( $_SERVER['HTTPS'] ) ) ini_set( 'session.cookie_secure', 1 );
		Logger::output( 'info', '(Session)' );
	}

	public function getLangAdmin(): string {
		return $this->_adminLang;
	}


	// ------------------------------------------------------------------------


	public function getUrl(): string {
		return $this->_urlAdmin;
	}

	public function getErrorMessage(): string {
		return $this->_errMsg;
	}


	// ------------------------------------------------------------------------


	public function login( array $params ): bool {
		if ( empty( $params['user'] ) || empty( $params['digest'] ) || empty( $params['nonce'] ) || empty( $params['cnonce'] ) ) {
			$this->_errMsg = 'Parameters are wrong.';
			return false;
		}
		$this->_cleanUp();

		$url = $this->_urlAdmin;
		$a2 = hash( self::HASH_ALGO, 'post:' . $url );

		$accountPath = $this->_dirAcct . self::ACCT_FILE_NAME;
		if ( is_file( $accountPath ) === false ) {
			Logger::output( 'error', "(Session::login is_file) [$accountPath]" );
			$this->_errMsg = 'Account file does not exist.';
			return false;
		}
		$as = file( $accountPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
		if ( $as === false ) {
			Logger::output( 'error', "(Session::login file) [$accountPath]" );
			$this->_errMsg = 'Account file cannot be opened.';
			return false;
		}
		foreach ( $as as $a ) {
			$a = trim( $a );
			if ( empty( $a ) || $a[0] === '#' ) continue;
			$cs = explode( "\t", trim( $a ) );
			if ( $cs[0] !== $params['user'] ) continue;
			$a1 = strtolower( $cs[1] );
			$code = implode( ':', [ $a1, $params['nonce'], $params['cnonce'], $a2 ] );
			$dgst = hash( self::HASH_ALGO, $code );
			$lang = ( 2 < count( $cs ) && ! empty( $cs[2] ) ) ? $cs[2] : null;
			if ( $dgst === $params['digest'] ) return $this->_startNew( $lang );
		}
		return false;
	}

	public function logout(): void {
		session_start();
		$_SESSION = [];
		if ( isset( $_COOKIE[ session_name() ] ) ) {
			setcookie( session_name(), '', time() - 42000, '/' );
		}
		session_destroy();
	}

	public function start(): bool {
		session_start();
		$sid = session_id();
		if ( $sid === '' ) return false;
		if ( ! isset( $_SESSION['fingerprint'] ) ) return false;
		$fp = self::_getFingerprint();
		if ( $fp !== $_SESSION['fingerprint'] ) return false;
		if ( isset( $_SESSION['lang_admin'] ) ) {
			$this->_adminLang = $_SESSION['lang_admin'];
		}
		$this->_sessionId = $sid;
		return $this->_checkTime( $sid, true );
	}

	public function addTempDir( string $dir ): bool {
		$data = $this->_loadSessionFile( $this->_sessionId );
		if ( $data === null ) return false;
		if ( ! isset( $data['temp_dir'] ) || ! is_array( $data['temp_dir'] ) ) $data['temp_dir'] = [];
		$data['temp_dir'][] = $dir;
		$this->_saveSessionFile( $this->_sessionId, $data );
		return true;
	}


	// ------------------------------------------------------------------------


	private function _cleanUp(): void {
		if ( ! is_dir( $this->_dirSession ) ) return;
		$fns = [];
		$sids = scandir( $this->_dirSession );
		if ( $sids === false ) return;
		foreach ( $sids as $sid ) {
			if ( is_dir( $this->_dirSession . $sid ) ) continue;
			$this->_checkTime( $sid, false );
		}
	}

	private function _startNew( ?string $lang ): bool {
		session_start();
		$this->_sessionId = session_id();
		$_SESSION['fingerprint'] = self::_getFingerprint();
		if ( $lang ) $_SESSION['lang_admin'] = $lang;

		$res = $this->_saveSessionFile( $this->_sessionId, [ 'timestamp' => time() ] );
		if ( $res === false ) {
			$this->_errMsg = 'Session file cannot be written.';
			return false;
		}
		return true;
	}

	private function _checkTime( string $sid, bool $doUpdate ): bool {
		$data = $this->_loadSessionFile( $sid );
		if ( $data === null ) return false;

		$curTime = time();
		$sessionTime = isset( $data['timestamp'] ) ? intval( $data['timestamp'] ) : 0;
		if ( self::TIMEOUT < $curTime - $sessionTime ) {
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
			Logger::output( 'error', "(Session::_loadSessionFile file_get_contents) [$path]" );
			return null;
		}
		return json_decode( $json, true );
	}

	private function _removeSessionFile( string $sid ): void {
		if ( ! is_dir( $this->_dirSession ) ) return;
		$path = $this->_dirSession . $sid;
		if ( ! is_file( $path ) ) return;
		$res = unlink( $path );
		if ( $res === false ) {
			Logger::output( 'error', "(Session::_removeSessionFile unlink) [$path]" );
		}
	}

	private function _saveSessionFile( string $sid, array $data ): bool {
		if ( ! self::_ensureDir( $this->_dirSession ) ) return false;
		$path = $this->_dirSession . $sid;
		$json = json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		$res = file_put_contents( $path, $json, LOCK_EX );
		if ( $res === false ) {
			Logger::output( 'error', "(Session::_saveSessionFile file_put_contents) [$path]" );
			return false;
		}
		return true;
	}


	// ------------------------------------------------------------------------


	static private function _getFingerprint(): string {
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

	static private function _ensureDir( string $path ): bool {
		if ( is_dir( $path ) ) {
			if ( NT_MODE_DIR !== ( fileperms( $path ) & 0777 ) ) {
				chmod( $path, NT_MODE_DIR );
			}
			return true;
		}
		if ( mkdir($path, NT_MODE_DIR, true ) ) {
			chmod( $path, NT_MODE_DIR );
			return true;
		}
		Logger::output( 'error', "(Session::_ensureDir) [$path]" );
		return false;
	}

}
