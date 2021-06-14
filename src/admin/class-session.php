<?php
namespace nt;
/**
 *
 * Session
 *
 * @author Takuto Yanagida
 * @version 2021-06-14
 *
 */


require_once( __DIR__ . '/../core/class-logger.php' );


class Session {

	const TIMEOUT_SESSION = 1800;  //  1800 = 30 minutes * 60 seconds
	const TIMEOUT_LOCK    = 60;    //    60 =  1 minutes * 60 seconds
	const ACCT_FILE_NAME  = 'account';
	const HASH_ALGO       = 'sha256';

	public static function getRealm(): string {
		return 'newtrino';
	}

	public static function getNonce(): string {
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
		ini_set( 'session.use_cookies', 1 );
		ini_set( 'session.use_only_cookies', 1 );
		if ( isset( $_SERVER['HTTPS'] ) ) ini_set( 'session.cookie_secure', 1 );

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
			Logger::output( 'info', '(Session::login) Parameters are invalid' );
			$this->_errMsg = 'Parameters are invalid';
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
				$user = $params['user'];
				$lang = ( 2 < count( $cs ) && ! empty( $cs[2] ) ) ? $cs[2] : null;
				$ret = $this->_create( $user, $lang );
				if ( $ret ) Logger::output( 'info', "(Session:login) Login succeeded [$user]" );
				return $ret;
			}
		}
		Logger::output( 'info', '(Session:login) Login failed' );
		return false;
	}

	private function _getAccountFile(): ?array {
		$path = $this->_dirAcct . self::ACCT_FILE_NAME;
		if ( is_file( $path ) === false ) {
			Logger::output( 'error', "(Session::_getAccountFile) The account file does not exist" );
			$this->_errMsg = 'The account file does not exist';
			return null;
		}
		$as = file( $path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
		if ( $as === false ) {
			Logger::output( 'error', "(Session::_getAccountFile) Cannot open the account file" );
			$this->_errMsg = 'Cannot open the account file';
			return null;
		}
		return $as;
	}

	public function logout(): void {
		$this->_stop();
		Logger::output( 'info', '(Session::logout) Logout' );
	}


	// ------------------------------------------------------------------------


	private function _create( string $user, ?string $lang ): bool {
		if ( ! self::_session_start() ) {
			$this->_doDestroySession();
			return false;
		}
		$res = false;
		if ( $h = $this->_lock() ) {
			$existingSession = $this->_cleanSessions( $user );

			$nonce = $existingSession ?? self::getNonce();
			$_SESSION['user']        = $user;
			$_SESSION['session_id']  = $nonce;
			$_SESSION['fingerprint'] = self::_getFingerprint( $nonce, $user );

			$this->_sessionId = $nonce;
			if ( $lang ) $_SESSION['lang'] = $lang;

			$res = $this->_saveSessionFile( $this->_sessionId, [ 'timestamp' => time(), 'user' => $user ] );
			$this->_unlock( $h );
		}
		if ( $res === false ) {
			Logger::output( 'error', '(Session::_create) Cannot write the session file' );
			$this->_errMsg = 'Cannot write the session file';
		}
		return $res;
	}

	public static function canStart(): bool {
		if ( ! self::_session_start() ) return false;

		if ( empty( $_SESSION['user'] ) )        return false;
		if ( empty( $_SESSION['session_id'] ) )  return false;
		if ( empty( $_SESSION['fingerprint'] ) ) return false;

		$fp = self::_getFingerprint( $_SESSION['session_id'], $_SESSION['user'] );
		if ( $fp !== $_SESSION['fingerprint'] ) return false;
		return true;
	}

	public function start(): bool {
		$ret = self::canStart();
		if ( ! $ret ) {
			$this->_doDestroySession();
			return false;
		}
		$this->_sessionId = $_SESSION['session_id'];
		if ( ! empty( $_SESSION['lang'] ) ) $this->_lang = $_SESSION['lang'];
		return $this->_checkTimestamp( $this->_sessionId );
	}

	private function _stop(): void {
		self::_session_start();

		$this->_doDestroySession();
		if ( isset( $_COOKIE[ session_name() ] ) ) {
			setcookie( session_name(), '', [ 'expires' => time() - 42000, 'samesite' => 'Strict' ] );
		}
		$_SESSION = [];
		session_destroy();
	}

	private function _doDestroySession() {
		if ( isset( $_SESSION['session_id'] ) && $h = $this->_lock() ) {
			Logger::output( 'info', '(Session::_doDestroySession) Destroy the session file [' . $_SESSION['session_id'] . ']' );
			$this->_removeSessionFile( $_SESSION['session_id'] );
			$this->_unlock( $h );
		}
	}

	private static function _session_start(): bool {
		if ( ! session_start() ) return false;
		if ( ! session_regenerate_id( true ) ) return false;
		return true;
	}

	private static function _getFingerprint( $sid, $user ): string {
		$fp  = self::getRealm();
		$fp .= $sid . $user;
		$fp .= $_SERVER['HTTP_USER_AGENT'] ?? '';
		$fp .= $_SERVER['HTTP_ACCEPT_ENCODING'] ?? '';
		$fp .= $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
		$fp .= $_SERVER['REMOTE_ADDR'] ?? '';
		return hash( self::HASH_ALGO, $fp );
	}

	private function _cleanSessions( string $user ): ?string {
		$now = time();
		$sfs = [];

		foreach ( $this->_loadSessionFileAll() as $sid => $sf ) {
			$time = intval( $sf['timestamp'] ?? 0 );
			if ( self::TIMEOUT_SESSION < $now - $time ) {
				$this->_removeSessionFile( $sid, $sf );
			} else {
				$sfs[ $sid ] = $sf;
			}
		}
		foreach ( $sfs as $sid => $sf ) {
			if ( $sf['user'] === $user ) return $sid;
		}
		return null;
	}

	private function _checkTimestamp( string $sid ): bool {
		$sf = $this->_loadSessionFile( $sid );
		if ( $sf === null ) return false;

		$now  = time();
		$time = intval( $sf['timestamp'] ?? 0 );

		if ( self::TIMEOUT_SESSION < $now - $time ) {
			$this->_removeSessionFile( $sid, $sf );
			return false;
		}
		$sf['timestamp'] = $now;
		$sf = $this->_cleanLock( $sf );
		return $this->_saveSessionFile( $sid, $sf );
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
		Logger::output( 'info', '(Session::lock) Post locking ' . ( $ret ? 'succeeded' : 'failed' ) . " [$pid]" );
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

	private function _getLockingSession( string $pid ) {
		foreach ( $this->_loadSessionFileAll() as $sid => $sf ) {
			if ( $this->_isLockValid( $sf, $pid ) ) {
				return $sid;
			}
		}
		return null;
	}

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


	// ------------------------------------------------------------------------


	private function _lock() {
		if ( ! is_dir( $this->_dirSession ) ) {
			if ( ! self::_ensureDir( $this->_dirSession ) ) return null;
		}
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

	private function _loadSessionFileAll(): array {
		$sids = scandir( $this->_dirSession );
		$sids = ( $sids === false ) ? [] : array_diff( $sids, [ '.', '..' ] );

		$ret = [];
		foreach ( $sids as $sid ) {
			$sf = $this->_loadSessionFile( $sid );
			if ( $sf !== null ) {
				$ret[ $sid ] = $sf;
			}
		}
		return $ret;
	}

	private function _loadSessionFile( string $sid, bool $silent = false ): ?array {
		$path = $this->_dirSession . $sid;
		if ( ! is_file( $path ) || ! is_readable( $path ) ) {
			Logger::output( $silent ? 'info' : 'error', "(Session::_loadSessionFile) The session file does not exist or is not readable [$sid]" );
			return null;
		}
		$json = file_get_contents( $path );
		if ( $json === false ) {
			Logger::output( 'error', "(Session::_loadSessionFile) Cannot read the session file [$sid]" );
			return null;
		}
		$data = json_decode( $json, true );
		if ( $data === null ) {
			Logger::output( 'error', "(Session::_loadSessionFile) The session file is invalid [$sid]" );
		}
		return $data;
	}

	private function _removeSessionFile( string $sid, array $sf = null ): void {
		if ( ! $sf ) $sf = $this->_loadSessionFile( $sid, true );
		if ( $sf ) {
			if ( isset( $sf['temp_dir'] ) && is_array( $sf['temp_dir'] ) ) {
				foreach ( $sf['temp_dir'] as $dir ) {
					self::_deleteAllIn( $dir );
				}
			}
		}
		$path = $this->_dirSession . $sid;
		if ( ! is_file( $path ) ) {
			Logger::output( 'info', "(Session::_removeSessionFile) Session file does not exist [$sid]" );
			return;
		}
		$res = unlink( $path );
		if ( $res === false ) {
			Logger::output( 'error', "(Session::_removeSessionFile) Cannot remove the session file [$sid]" );
		}
	}

	private function _saveSessionFile( string $sid, array $sf ): bool {
		if ( ! self::_ensureDir( $this->_dirSession ) ) return false;
		$path = $this->_dirSession . $sid;
		$json = json_encode( $sf, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		$res = file_put_contents( $path, $json, LOCK_EX );
		if ( $res === false ) {
			Logger::output( 'error', "(Session::_saveSessionFile) Cannot write the session file [$sid]" );
			return false;
		}
		@chmod( $path, NT_MODE_FILE );
		return true;
	}

	private static function _deleteAllIn( string $dir ): void {
		$dir = rtrim( $dir, '/' );
		if ( ! is_dir( $dir ) ) {
			Logger::output( 'error', "(Session::_deleteAllIn) The directory does not exist [$dir]" );
			return;
		}
		foreach ( scandir( $dir ) as $fn ) {
			if ( $fn === '.' || $fn === '..' ) continue;
			if ( is_dir( "$dir/$fn" ) ) {
				self::_deleteAllIn( "$dir/$fn" );
			} else {
				unlink( "$dir/$fn" );
			}
		}
		rmdir( $dir );
	}

	private static function _ensureDir( string $path ): bool {
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
		Logger::output( 'error', "(Session::_ensureDir) The session directory is not usable [$path]" );
		return false;
	}

}
