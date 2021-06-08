<?php
namespace nt;
/**
 *
 * Session
 *
 * @author Takuto Yanagida
 * @version 2021-06-08
 *
 */


require_once( __DIR__ . '/../core/class-logger.php' );


class Session {

	const TIMEOUT_SESSION = 7200;  // 7200 = 120 minutes * 60 seconds
	const TIMEOUT_LOCK    = 60;    //   60 =   1 minutes * 60 seconds
	const ACCT_FILE_NAME  = 'account';
	const HASH_ALGO       = 'sha256';

	static public function getRealm(): string {
		return 'newtrino';
	}

	static public function getNonce(): string {
		return bin2hex( openssl_random_pseudo_bytes( 16 ) );
	}


	// ------------------------------------------------------------------------


	private $_url;
	private $_dirAcct;
	private $_dirSession;

	private $_lang      = '';
	private $_errMsg    = '';
	private $_sessionId = '';

	public function __construct( string $urlAdmin, string $dirAcct, string $dirSession ) {
		$this->_url        = $urlAdmin;
		$this->_dirAcct    = $dirAcct;
		$this->_dirSession = $dirSession;

		ini_set( 'session.name', 'newtrino' );
		ini_set( 'session.use_strict_mode', 1 );
		if ( isset( $_SERVER['HTTPS'] ) ) ini_set( 'session.cookie_secure', 1 );
		Logger::output( 'info', '(Session)' );

		session_set_cookie_params([
			'samesite' => 'Strict',
		]);
	}

	public function getUrl(): string {
		return $this->_url;
	}

	public function getLanguage(): string {
		return $this->_lang;
	}

	public function getErrorMessage(): string {
		return $this->_errMsg;
	}


	// ------------------------------------------------------------------------


	public function addTemporaryDirectory( string $dir ): bool {
		$sf = $this->_loadSessionFile( $this->_sessionId );
		if ( $sf === null ) return false;
		if ( ! isset( $sf['temp_dir'] ) || ! is_array( $sf['temp_dir'] ) ) $sf['temp_dir'] = [];
		$sf['temp_dir'][] = $dir;
		$this->_saveSessionFile( $this->_sessionId, $sf );
		return true;
	}


	// ------------------------------------------------------------------------


	public function login( array $params ): bool {
		if ( empty( $params['user'] ) || empty( $params['digest'] ) || empty( $params['nonce'] ) || empty( $params['cnonce'] ) ) {
			$this->_errMsg = 'Parameters are wrong.';
			return false;
		}
		$as = $this->_getAccountFile();
		if ( $as === null ) return false;

		$a2 = hash( self::HASH_ALGO, 'post:' . $this->_url );

		foreach ( $as as $a ) {
			$a = trim( $a );
			if ( empty( $a ) || $a[0] === '#' ) continue;
			$cs = explode( "\t", trim( $a ) );
			if ( $cs[0] !== $params['user'] ) continue;
			$a1 = strtolower( $cs[1] );
			$code = implode( ':', [ $a1, $params['nonce'], $params['cnonce'], $a2 ] );

			$digest = hash( self::HASH_ALGO, $code );
			if ( $digest === $params['digest'] ) {
				$lang = ( 2 < count( $cs ) && ! empty( $cs[2] ) ) ? $cs[2] : null;
				return $this->_create( $lang );
			}
		}
		return false;
	}

	private function _getAccountFile(): ?array {
		$path = $this->_dirAcct . self::ACCT_FILE_NAME;
		if ( is_file( $path ) === false ) {
			Logger::output( 'error', "(Session::login is_file) [$path]" );
			$this->_errMsg = 'Account file does not exist.';
			return null;
		}
		$as = file( $path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
		if ( $as === false ) {
			Logger::output( 'error', "(Session::login file) [$path]" );
			$this->_errMsg = 'Account file cannot be opened.';
			return null;
		}
		return $as;
	}

	public function logout(): void {
		$this->_stop();
	}


	// ------------------------------------------------------------------------


	private function _create( ?string $lang ): bool {
		session_start();
		session_regenerate_id( true );
		$_SESSION['session_id']  = self::getNonce();
		$_SESSION['fingerprint'] = self::_getFingerprint( $_SESSION['session_id'] );

		$this->_sessionId = $_SESSION['session_id'];
		if ( $lang ) $_SESSION['lang'] = $lang;

		if ( $h = $this->_lock() ) {
			foreach ( $this->_getSessionIds() as $sid ) {
				$this->_checkTimestamp( $sid, false );
			}
			$res = $this->_saveSessionFile( $this->_sessionId, [ 'timestamp' => time() ] );
			$this->_unlock( $h );
		}
		if ( $res === false ) {
			$this->_errMsg = 'Session file cannot be written.';
		}
		return $res;
	}

	static public function canStart(): bool {
		session_start();
		session_regenerate_id( true );
		if ( empty( $_SESSION['session_id'] ) )  return false;
		if ( empty( $_SESSION['fingerprint'] ) ) return false;

		if ( self::_getFingerprint( $_SESSION['session_id'] ) !== $_SESSION['fingerprint'] ) return false;
		return true;
	}

	public function start(): bool {
		$ret = self::canStart();
		if ( ! $ret ) return false;

		$this->_sessionId = $_SESSION['session_id'];
		if ( ! empty( $_SESSION['lang'] ) ) $this->_lang = $_SESSION['lang'];
		return $this->_checkTimestamp( $this->_sessionId, true );
	}

	private function _stop(): void {
		session_start();
		session_regenerate_id( true );
		if ( empty( $_SESSION['session_id'] ) )  return;
		if ( empty( $_SESSION['fingerprint'] ) ) return;

		if ( isset( $_COOKIE[ session_name() ] ) ) {
			setcookie( session_name(), '', [ 'expires' => time() - 42000, 'samesite' => 'Strict' ] );
		}
		if ( $h = $this->_lock() ) {
			$this->_removeSessionFile( $_SESSION['session_id'] );
			$this->_unlock( $h );
		}
		$_SESSION = [];
		session_destroy();
	}

	static private function _getFingerprint( $sid ): string {
		$fp = self::getRealm();
		$fp .= $_SERVER['HTTP_USER_AGENT'] ?? '';
		$fp .= $_SERVER['HTTP_ACCEPT_ENCODING'] ?? '';
		$fp .= $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
		$fp .= $sid;
		return md5( $fp );
	}


	// ------------------------------------------------------------------------


	private function _checkTimestamp( string $sid, bool $doUpdate ): bool {
		$sf = $this->_loadSessionFile( $sid );
		if ( $sf === null ) return false;

		$now  = time();
		$time = intval( $sf['timestamp'] ?? 0 );

		if ( self::TIMEOUT_SESSION < $now - $time ) {
			$this->_removeAllTemporaryDirectory( $sf );
			$this->_removeSessionFile( $sid );
			return false;
		}
		if ( $doUpdate ) {
			$sf['timestamp'] = $now;
			$sf = $this->_cleanLock( $sf );
			$this->_saveSessionFile( $sid, $sf );
		}
		return true;
	}


	// ------------------------------------------------------------------------


	public function lock( string $pid ): bool {
		$ret = false;
		if ( $h = $this->_lock() ) {
			foreach ( $this->_loadSessionFileAll() as $sid => $sf ) {
				$sf = $this->_cleanLock( $sf );
				$this->_saveSessionFile( $sid, $sf );
			}
			$lockingSid = $this->_getLockingSession( $pid );
			if ( $lockingSid === null || $lockingSid === $this->_sessionId ) {
				$sf = $this->_loadSessionFile( $this->_sessionId );
				if ( $sf !== null ) {
					$sf = $this->_updateLock( $sf, $pid );
					$ret = $this->_saveSessionFile( $this->_sessionId, $sf );
				}
			}
			$this->_unlock( $h );
		}
		return $ret;
	}

	public function receivePing( ?string $pid ): bool {
		$ret = false;
		if ( $h = $this->_lock() ) {
			$sf = $this->_loadSessionFile( $this->_sessionId );
			if ( $sf !== null ) {
				if ( $this->_isLockValid( $sf, $pid ) ||  ! $this->_getLockingSession( $pid ) ) {
					$sf = $this->_updateLock( $sf, $pid );
					$ret  = $this->_saveSessionFile( $this->_sessionId, $sf );
				}
			}
			$this->_unlock( $h );
		}
		return $ret;
	}


	// ------------------------------------------------------------------------


	private function _getLockingSession( string $pid ) {
		foreach ( $this->_loadSessionFileAll() as $sid => $sf ) {
			if ( $this->_isLockValid( $sf, $pid ) ) {
				return $sid;
			}
		}
		return null;
	}


	// -------------------------------------------------------------------------


	private function _isLockValid( array $sf, string $pid ): bool {
		if ( ! isset( $sf['lock'][ $pid ] ) ) return false;
		$time = $sf['lock'][ $pid ];
		return time() - $time <= self::TIMEOUT_LOCK;
	}

	private function _updateLock( array $sf, string $pid ): array {
		if ( ! isset( $sf['lock'] ) ) $sf['lock'] = [];
		$sf['lock'][ $pid ] = time();
		return $sf;
	}

	private function _cleanLock( array $sf ): array {
		if ( ! isset( $sf['lock'] ) ) return $sf;
		$lock = $sf['lock'];

		$now = time();
		foreach ( $lock as $pid => $time ) {
			if ( self::TIMEOUT_LOCK < $now - $time ) {
				unset( $lock[ $pid ] );
			}
		}
		if ( empty( $lock ) ) {
			unset( $sf['lock'] );
		} else {
			$sf['lock'] = $lock;
		}
		return $sf;
	}

	private function _removeAllTemporaryDirectory( array $sf ): void {
		if ( isset( $sf['temp_dir'] ) && is_array( $sf['temp_dir'] ) ) {
			foreach ( $sf['temp_dir'] as $dir ) {
				self::deleteAllIn( $dir );
			}
		}
	}

	static public function deleteAllIn( string $dir ): void {
		$dir = rtrim( $dir, '/' );
		if ( ! is_dir( $dir ) ) {
			Logger::output( 'error', "(Session::deleteAllIn is_dir) [$dir]" );
			return;
		}
		foreach ( scandir( $dir ) as $fn ) {
			if ( $fn === '.' || $fn === '..' ) continue;
			if ( is_dir( "$dir/$fn" ) ) {
				self::deleteAllIn( "$dir/$fn" );
			} else {
				unlink( "$dir/$fn" );
			}
		}
		rmdir( $dir );
	}


	// ------------------------------------------------------------------------


	private function _lock() {
		if ( ! is_dir( $this->_dirSession ) ) return null;
		if ( $h = opendir( $this->_dirSession ) ) {
			flock( $h, LOCK_EX );
			return $h;
		}
		return null;
	}

	private function _unlock( $h ) {
		flock( $h, LOCK_UN );
		closedir( $h );
	}


	// -------------------------------------------------------------------------


	private function _getSessionIds(): array {
		$sids = scandir( $this->_dirSession );
		return ( $sids === false ) ? [] : $sids;
	}

	private function _loadSessionFileAll(): array {
		$ret = [];
		foreach ( $this->_getSessionIds() as $sid ) {
			$sf = $this->_loadSessionFile( $sid );
			if ( $sf !== null ) {
				$ret[ $sid ] = $sf;
			}
		}
		return $ret;
	}


	// -------------------------------------------------------------------------


	private function _loadSessionFile( string $sid ): ?array {
		$path = $this->_dirSession . $sid;
		if ( ! is_file( $path ) || ! is_readable( $path ) ) return null;
		$json = file_get_contents( $path );
		if ( $json === false ) {
			Logger::output( 'error', "(Session::_loadSessionFile file_get_contents) [$path]" );
			return null;
		}
		return json_decode( $json, true );
	}

	private function _removeSessionFile( string $sid ): void {
		$path = $this->_dirSession . $sid;
		if ( ! is_file( $path ) ) return;
		$res = unlink( $path );
		if ( $res === false ) {
			Logger::output( 'error', "(Session::_removeSessionFile unlink) [$path]" );
		}
	}

	private function _saveSessionFile( string $sid, array $sf ): bool {
		if ( ! self::_ensureDir( $this->_dirSession ) ) return false;
		$path = $this->_dirSession . $sid;
		$json = json_encode( $sf, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		$res = file_put_contents( $path, $json, LOCK_EX );
		if ( $res === false ) {
			Logger::output( 'error', "(Session::_saveSessionFile file_put_contents) [$path]" );
			return false;
		}
		@chmod( $path, NT_MODE_FILE );
		return true;
	}

	static private function _ensureDir( string $path ): bool {
		if ( is_dir( $path ) ) {
			if ( NT_MODE_DIR !== ( fileperms( $path ) & 0777 ) ) {
				@chmod( $path, NT_MODE_DIR );
			}
			return true;
		}
		if ( mkdir($path, NT_MODE_DIR, true ) ) {
			@chmod( $path, NT_MODE_DIR );
			return true;
		}
		Logger::output( 'error', "(Session::_ensureDir) [$path]" );
		return false;
	}

}
