<?php
/**
 * Session
 *
 * @author Takuto Yanagida
 * @version 2021-06-25
 */

namespace nt;

require_once( __DIR__ . '/../core/class-logger.php' );
require_once( __DIR__ . '/util/session.php' );
require_once( __DIR__ . '/util/nonce.php' );
require_once( __DIR__ . '/util/file.php' );

class Session {

	const TIMEOUT_SESSION = 1800;  // 1800 = 30 minutes * 60 seconds
	const TIMEOUT_RESTORE = 300;   //  300 =  5 minutes * 60 seconds
	const TIMEOUT_LOCK    = 60;    //   60 =  1 minutes * 60 seconds

	const HASH_ALGO = 'sha256';

	public static function getAuthKey(): string {
		if ( defined( 'NT_AUTH_KEY' ) ) return NT_AUTH_KEY;
		return 'newtrino';
	}

	private static function _setCookieParams(): void {
		ini_set( 'session.name', 'newtrino' );
		ini_set( 'session.use_cookies', 1 );  // PHP default
		ini_set( 'session.use_only_cookies', 1 );  // PHP default
		ini_set( 'session.use_strict_mode', 1 );
		ini_set( 'session.cookie_httponly', 1 );
		if ( isset( $_SERVER['HTTPS'] ) ) ini_set( 'session.cookie_secure', 1 );

		session_set_cookie_params([
			'samesite' => 'Strict',
			'path'     => \nt\get_url_from_path( NT_DIR ),
		]);
	}


	// ------------------------------------------------------------------------


	private $_url;
	private $_dirSession;

	private $_lang = '';
	private $_sid  = '';

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
		$sf = $this->_loadSessionFile( $this->_sid );
		if ( $sf === null ) return false;
		if ( ! isset( $sf['temp_dir'] ) || ! is_array( $sf['temp_dir'] ) ) $sf['temp_dir'] = [];
		$sf['temp_dir'][] = $dir;
		$this->_saveSessionFile( $this->_sid, $sf );
		return true;
	}

	public function listTemporaryDirectories(): array {
		$ret = [];
		if ( $h = $this->_lockSession() ) {
			foreach ( $this->_loadSessionFileAll() as $sid => $sf ) {
				if ( isset( $sf['temp_dir'] ) && is_array( $sf['temp_dir'] ) ) {
					$ret = array_merge( $ret, $sf['temp_dir'] );
				}
			}
			$this->_unlockSession( $h );
		}
		return $ret;
	}


	// ------------------------------------------------------------------------


	public function create( string $user, ?string $lang ): bool {
		if ( ! self::_startSession() ) return false;

		$res = false;
		if ( $h = $this->_lockSession() ) {
			[ $oldSid, $oldSf ] = $this->_cleanSessions( $user, $_SERVER['REMOTE_ADDR'] );
			$this->_sid = $oldSid ?? \nt\create_nonce( 16 );

			$_SESSION['sid']   = $this->_sid;
			$_SESSION['nonce'] = \nt\create_nonce( 16 );
			if ( ! empty( $lang ) ) $_SESSION['lang'] = $lang;

			$sf = array_merge( $oldSf ?? [], [ 'timestamp' => time(), 'user' => $user, 'ip' => $_SERVER['REMOTE_ADDR'] ] );
			$res = $this->_saveSessionFile( $this->_sid, $sf );
			if ( ! $res ) {
				Logger::error( __METHOD__, 'Cannot write the session file' );
			}
			$this->_unlockSession( $h );
		}
		Logger::info( __METHOD__, 'Creating session ' . ( $res ? 'succeeded' : 'failed' ) );
		return $res;
	}

	public static function canStart( $regenerate = true ): bool {
		global $nt_session;
		if ( ! isset( $nt_session ) ) self::_setCookieParams();
		if ( ! self::_startSession( $regenerate ) ) return false;

		if ( empty( $_SESSION['sid'] ) )   return false;
		if ( empty( $_SESSION['nonce'] ) ) return false;
		return true;
	}

	public function start( bool $silent = false ): bool {
		if ( ! self::canStart() ) {
			Logger::info( __METHOD__, 'Starting session failed (canStart)' );
			return false;
		}
		$this->_sid = $_SESSION['sid'];
		if ( ! empty( $_SESSION['lang'] ) ) $this->_lang = $_SESSION['lang'];

		$res = false;
		if ( $h = $this->_lockSession() ) {
			$res = $this->_checkTimestamp( $this->_sid, $silent );
			$this->_unlockSession( $h );
		}
		Logger::info( __METHOD__, 'Starting session ' . ( $res ? 'succeeded' : 'failed' ) );
		return $res;
	}

	public function destroy(): void {
		self::_startSession();

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
		if ( empty( $_REQUEST['nonce'] ) || empty( $_SESSION['nonce'] ) ) {
			Logger::info( __METHOD__, 'Nonce does not exist' );
			return false;
		}
		$res = ( $_REQUEST['nonce'] === $_SESSION['nonce'] );
		if ( ! $res ) Logger::info( __METHOD__, 'Nonce does not match' );
		return $res;
	}


	// ------------------------------------------------------------------------


	private static function _startSession( $regenerate = true ): bool {
		if ( ! \nt\session_start( self::TIMEOUT_RESTORE ) ) return false;
		if ( $regenerate && ! \nt\session_regenerate_id() ) return false;
		return true;
	}

	private function _doDestroy() {
		if ( isset( $_SESSION['sid'] ) && $h = $this->_lockSession() ) {
			Logger::info( __METHOD__, 'Destroy the session file', $_SESSION['sid'] );
			$this->_removeSessionFile( $_SESSION['sid'] );
			$this->_unlockSession( $h );
		}
	}

	private function _cleanSessions( string $user, string $ip ): array {
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
			if ( $sf['user'] === $user && $sf['ip'] === $ip ) return [ $sid, $sf ];
		}
		return [ null, null ];
	}

	private function _checkTimestamp( string $sid, bool $silent = false ): bool {
		$sf = $this->_loadSessionFile( $sid, $silent );
		if ( $sf === null ) return false;

		$now  = time();
		$time = intval( $sf['timestamp'] ?? 0 );

		if ( self::TIMEOUT_SESSION < $now - $time ) {
			$this->_removeSessionFile( $sid, $sf );
			Logger::info( __METHOD__, 'Session timeout' );
			return false;
		}
		$sf['timestamp'] = $now;
		$sf = $this->_cleanLock( $sf );
		return $this->_saveSessionFile( $sid, $sf );
	}


	// ------------------------------------------------------------------------


	public function lock( string $pid ): bool {
		$res = false;
		if ( $h = $this->_lockSession() ) {
			foreach ( $this->_loadSessionFileAll() as $sid => $sf ) {
				$sf = $this->_cleanLock( $sf );
				$this->_saveSessionFile( $sid, $sf );
			}
			$lockingSid = $this->_getLockingSession( $pid );
			if ( $lockingSid === null || $lockingSid === $this->_sid ) {
				$sf = $this->_loadSessionFile( $this->_sid );
				if ( $sf !== null ) {
					$sf = $this->_updateLock( $sf, $pid );
					$res = $this->_saveSessionFile( $this->_sid, $sf );
				}
			}
			$this->_unlockSession( $h );
		}
		Logger::info( __METHOD__, 'Locking post ' . ( $res ? 'succeeded' : 'failed' ), $pid );
		return $res;
	}

	public function receivePing( ?string $pid ): bool {
		$res = false;
		if ( $h = $this->_lockSession() ) {
			$sf = $this->_loadSessionFile( $this->_sid );
			if ( $sf !== null ) {
				if ( $pid === '' ) {
					$res = true;
				} else if ( $this->_isLockValid( $sf, $pid ) || ! $this->_getLockingSession( $pid ) ) {
					$sf = $this->_updateLock( $sf, $pid );
					$res  = $this->_saveSessionFile( $this->_sid, $sf );
				}
			}
			$this->_unlockSession( $h );
		}
		return $res;
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
		$lock = [];

		$now = time();
		foreach ( $sf['lock'] as $pid => $time ) {
			if ( self::TIMEOUT_LOCK < $now - $time ) {
				Logger::info( __METHOD__, 'Unlocking post succeeded', $pid );
			} else {
				$lock[ $pid ] = $time;
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


	private function _lockSession() {
		if ( ! is_dir( $this->_dirSession ) ) {
			if ( ! \nt\ensure_dir( $this->_dirSession, NT_MODE_DIR ) ) {
				Logger::error( __METHOD__, 'The session directory is not usable', $path );
				return null;
			}
		}
		if ( $h = opendir( $this->_dirSession ) ) {
			flock( $h, LOCK_EX );
			return $h;
		}
		return null;
	}

	private function _unlockSession( $h ) {
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
					\nt\delete_all_in(
						$dir,
						function ( $dir ) { Logger::info( __METHOD__, 'The directory does not exist', $dir ); }
					);
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
		if ( ! \nt\ensure_dir( $this->_dirSession, NT_MODE_DIR ) ) {
			Logger::error( __METHOD__, 'The session directory is not usable', $path );
			return false;
		}
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

}
