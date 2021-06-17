<?php
namespace nt;
/**
 *
 * Session
 *
 * @author Takuto Yanagida
 * @version 2021-06-17
 *
 */


require_once( __DIR__ . '/../core/class-logger.php' );


class Session {

	const TIMEOUT_SESSION = 1800;    // 1800 = 30 minutes * 60 seconds
	const TIMEOUT_LOCK    = 60;      //   60 =  1 minutes * 60 seconds

	const HASH_ALGO = 'sha256';

	public static function getAuthKey(): string {
		if ( defined( 'NT_AUTH_KEY' ) ) return NT_AUTH_KEY;
		return 'newtrino';
	}

	private static function _createNonce(): string {
		return bin2hex( openssl_random_pseudo_bytes( 16 ) );
	}

	private static function _setCookieParams(): void {
		ini_set( 'session.name', 'newtrino' );
		ini_set( 'session.use_strict_mode', 1 );
		ini_set( 'session.use_cookies', 1 );
		ini_set( 'session.use_only_cookies', 1 );
		if ( isset( $_SERVER['HTTPS'] ) ) ini_set( 'session.cookie_secure', 1 );

		session_set_cookie_params([
			'samesite' => 'Strict',
			'path'     => get_url_from_path( NT_DIR ),
		]);
	}


	// ------------------------------------------------------------------------


	private $_url;
	private $_dirSession;

	private $_lang      = '';
	private $_sessionId = '';

	public function __construct( string $urlAdmin, string $dirSession ) {
		$this->_url        = $urlAdmin;
		$this->_dirSession = $dirSession;

		self::_setCookieParams();
	}

	public function getLanguage(): string {
		return $this->_lang;
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


	public function create( string $user, ?string $lang ): bool {
		if ( ! self::_sessionStart() ) {
			$this->_doDestroy();
			return false;
		}
		$res = false;
		if ( $h = $this->_lock() ) {
			$existingSession = $this->_cleanSessions( $user );

			$sid = $existingSession ?? self::_createNonce();
			$_SESSION['sid']   = $sid;
			$_SESSION['user']  = $user;
			$_SESSION['nonce'] = self::_createNonce();

			$this->_sessionId = $sid;
			if ( $lang ) $_SESSION['lang'] = $lang;

			$res = $this->_saveSessionFile( $this->_sessionId, [ 'timestamp' => time(), 'user' => $user ] );
			$this->_unlock( $h );
		}
		if ( $res === false ) {
			Logger::error( __METHOD__, 'Cannot write the session file' );
		}
		return $res;
	}

	public static function canStart( $deleteOld = true ): bool {
		global $nt_session;
		if ( ! isset( $nt_session ) ) self::_setCookieParams();
		if ( ! self::_sessionStart( $deleteOld ) ) return false;

		if ( empty( $_SESSION['sid'] ) )   return false;
		if ( empty( $_SESSION['user'] ) )  return false;
		if ( empty( $_SESSION['nonce'] ) ) return false;
		return true;
	}

	public function start( bool $silent = false ): bool {
		$ret = self::canStart();
		if ( ! $ret ) {
			$this->_doDestroy();
			return false;
		}
		$this->_sessionId = $_SESSION['sid'];
		if ( ! empty( $_SESSION['lang'] ) ) $this->_lang = $_SESSION['lang'];
		return $this->_checkTimestamp( $this->_sessionId, $silent );
	}

	public function destroy(): void {
		self::_sessionStart();

		$this->_doDestroy();
		if ( isset( $_COOKIE[ session_name() ] ) ) {
			setcookie( session_name(), '', [ 'expires' => time() - 42000, 'samesite' => 'Strict' ] );
		}
		$_SESSION = [];
		session_destroy();
		Logger::info( __METHOD__, 'Session destroyed' );
	}


	// ------------------------------------------------------------------------


	public static function getNonce(): string {
		return $_SESSION['nonce'];
	}

	public static function checkNonce(): bool {
		if ( empty( $_REQUEST['nonce'] ) || empty( $_SESSION['nonce'] ) ) return false;
		return $_REQUEST['nonce'] === $_SESSION['nonce'];
	}


	// ------------------------------------------------------------------------


	private static function _sessionStart( $deleteOld = true ): bool {
		if ( ! session_start() ) return false;
		if ( ! session_regenerate_id( $deleteOld ) ) return false;
		return true;
	}

	private function _doDestroy() {
		if ( isset( $_SESSION['sid'] ) && $h = $this->_lock() ) {
			Logger::info( __METHOD__, 'Destroy the session file', $_SESSION['sid'] );
			$this->_removeSessionFile( $_SESSION['sid'] );
			$this->_unlock( $h );
		}
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

	private function _checkTimestamp( string $sid, bool $silent = false ): bool {
		$sf = $this->_loadSessionFile( $sid, $silent );
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
		Logger::info( __METHOD__, 'Post locking ' . ( $ret ? 'succeeded' : 'failed' ), $pid );
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
				Logger::info( __METHOD__, 'Post unlocking succeeded', $pid );
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
			if ( $silent ) {
				Logger::info( __METHOD__, 'The session file does not exist or is not readable', $sid );
			} else {
				Logger::error( __METHOD__, 'The session file does not exist or is not readable', $sid );
			}
			return null;
		}
		$json = file_get_contents( $path );
		if ( $json === false ) {
			Logger::error( __METHOD__, 'Cannot read the session file', $sid );
			return null;
		}
		$data = json_decode( $json, true );
		if ( $data === null ) {
			Logger::error( __METHOD__, 'The session file is invalid', $sid );
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
			Logger::info( __METHOD__, 'Session file does not exist', $sid );
			return;
		}
		$res = unlink( $path );
		if ( $res === false ) {
			Logger::error( __METHOD__, 'Cannot remove the session file', $sid );
		}
	}

	private function _saveSessionFile( string $sid, array $sf ): bool {
		if ( ! self::_ensureDir( $this->_dirSession ) ) return false;
		$path = $this->_dirSession . $sid;
		$json = json_encode( $sf, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		$res = file_put_contents( $path, $json, LOCK_EX );
		if ( $res === false ) {
			Logger::error( __METHOD__, 'Cannot write the session file', $sid );
			return false;
		}
		@chmod( $path, NT_MODE_FILE );
		return true;
	}

	private static function _deleteAllIn( string $dir ): void {
		$dir = rtrim( $dir, '/' );
		if ( ! is_dir( $dir ) ) {
			Logger::info( __METHOD__, 'The directory does not exist', $dir );
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
		Logger::error( __METHOD__, 'The session directory is not usable', $path );
		return false;
	}

}
