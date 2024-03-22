<?php
/**
 * User Authentication
 *
 * @author Takuto Yanagida
 * @version 2024-03-22
 */

namespace nt;

require_once( __DIR__ . '/../core/class-logger.php' );

class Auth {

	const TIMEOUT_INVITATION = 604800;  // 7 days * 24 hours * 60 minutes * 60 seconds
	const TIMEOUT_TOKEN      = 300;     // 5 minutes * 60 seconds
	const AUTH_NONCE_GL      = 300;     // 5 minutes * 60 seconds (Expires in 10 minutes max.)

	const FILE_NAME_ACCT  = 'account';
	const FILE_NAME_INV   = 'invitation';
	const FILE_NAME_TOKEN = 'token';
	const HASH_ALGO       = 'sha256';  // Hashes written in the account file depend on this

	private $_url;
	private $_pathAcct;
	private $_pathInv;
	private $_pathToken;
	private $_errCode = '';

	public function __construct( string $urlAdmin, string $dirAcct, string $dirAuth ) {
		$this->_url       = $urlAdmin;
		$this->_pathAcct  = $dirAcct . self::FILE_NAME_ACCT;
		$this->_pathInv   = $dirAuth . self::FILE_NAME_INV;
		$this->_pathToken = $dirAuth . self::FILE_NAME_TOKEN;
	}

	public function getErrorCode(): string {
		return $this->_errCode;
	}


	// ------------------------------------------------------------------------


	public static function getAuthKey(): string {
		if ( defined( 'NT_AUTH_KEY' ) ) return NT_AUTH_KEY;
		return 'newtrino';
	}

	public static function getAuthNonce( int $step = 1 ): string {
		return \nt\get_nonce( self::AUTH_NONCE_GL );
	}

	public function issueToken(): string {
		return \nt\issue_token( $this->_pathToken, self::TIMEOUT_TOKEN );
	}

	public function checkToken( $token ): bool {
		return \nt\check_token( $this->_pathToken, $token );
	}


	// ------------------------------------------------------------------------


	public function signIn( array $params ): ?array {
		if ( empty( $params['user'] ) || empty( $params['digest'] ) || empty( $params['cnonce'] ) ) {
			Logger::info( __METHOD__, 'Parameters are invalid' );
			$this->_errCode = 'INVALID PARAM';
			return null;
		}
		[ 'user' => $user, 'digest' => $digest, 'cnonce' => $cnonce ] = $params;

		if ( $this->_verify( $user, $digest, $cnonce, $out_lang ) ) {
			Logger::info( __METHOD__, 'Sign-in succeeded', $user );
			return [ 'user' => $user, 'lang' => $out_lang ];
		}
		Logger::info( __METHOD__, 'Sign-in failed', $user );
		return null;
	}

	private function _verify( string $user, string $digest, string $cnonce, ?string &$out_lang ): bool {
		$as = null;
		if ( $h = $this->_lock() ) {
			$as = $this->_read( $this->_pathAcct );
			$this->_cleanInvitation();
			$this->_unlock( $h );
		}
		if ( $as === null ) return false;

		$a2 = hash( self::HASH_ALGO, $this->_url );
		$ns = \nt\get_possible_nonce( self::AUTH_NONCE_GL );

		foreach ( $as as $a ) {
			$a = trim( $a );
			if ( empty( $a ) || $a[0] === '#' ) continue;
			$cs = explode( "\t", $a );
			if ( $cs[0] !== $user ) continue;
			$a1 = strtolower( $cs[1] );

			foreach ( $ns as $nonce ) {
				$d = hash( self::HASH_ALGO, "$a1:$nonce:$cnonce:$a2" );
				if ( $d === $digest ) {
					$out_lang = ( 2 < count( $cs ) && ! empty( $cs[2] ) ) ? $cs[2] : null;
					return true;
				}
			}
		}
		return false;
	}

	private function _cleanInvitation(): void {
		$is  = $this->_read( $this->_pathInv, true );
		$new = [];
		$now = time();

		foreach ( $is as $i ) {
			$i = trim( $i );
			if ( empty( $i ) ) continue;
			[ $limit, $code ] = explode( "\t", $i );
			if ( intval( $limit ) < $now ) continue;
			$new[] = $i;
		}
		if ( count( $is ) !== count( $new ) ) $this->_write( $this->_pathInv, $new );
	}


	// ------------------------------------------------------------------------


	public function issueInvitation( array $params ): ?string {
		if ( ! $this->signIn( $params ) ) return null;
		$code = \nt\create_nonce( 12 );
		$limit = time() + self::TIMEOUT_INVITATION;

		$res = false;

		if ( $h = $this->_lock() ) {
			$as   = $this->_read( $this->_pathInv, true );
			$as[] = "$limit\t$code";
			$res  = $this->_write( $this->_pathInv, $as );
			$this->_unlock( $h );
		}
		return $res ? $code : null;
	}

	public function signUp( array $params ): bool {
		if ( empty( $params['code'] ) ) {
			Logger::info( __METHOD__, 'The invitation code is empty' );
			$this->_errCode = 'invalid_code';
			return false;
		}
		if (
			empty( $params['user'] ) || empty( $params['hash'] ) ||
			! preg_match( '/^(?=.*[a-z])[\-_a-z0-9]{4,32}$/i', $params['user'] )
		) {
			Logger::info( __METHOD__, 'Parameters are invalid' );
			$this->_errCode = 'invalid_param';
			return false;
		}
		[ 'user' => $user, 'code' => $code, 'hash' => $hash ] = $params;
		$cl   = explode( '|', $code );
		$code = $cl[0];
		$lang = $cl[1] ?? null;

		$rec = "$user\t$hash";
		if ( $lang ) $rec .= "\t$lang";

		$res = false;
		if ( $h = $this->_lock() ) {
			$as = $this->_read( $this->_pathAcct );
			if ( in_array( $user, $this->_getUsers( $as ), true ) ) {
				$this->_errCode = 'invalid_param';
			} elseif ( $this->_checkInvitation( $code ) ) {
				$as[] = $rec;
				$res = $this->_write( $this->_pathAcct, $as );
			}
			$this->_unlock( $h );
		}
		return $res;
	}

	private function _checkInvitation( $code ): bool {
		$is    = $this->_read( $this->_pathInv, true );
		$new   = [];
		$now   = time();
		$found = false;
		$valid = false;

		foreach ( $is as $i ) {
			$i = trim( $i );
			if ( empty( $i ) ) continue;
			[ $l, $c ] = explode( "\t", $i );

			if ( $c === $code ) {
				$found = true;
				if ( $now <= intval( $l ) ) $valid = true;
				continue;
			}
			$new[] = $i;
		}
		if ( ! $found && ! $valid ) {
			Logger::info( __METHOD__, 'The invitation code is invalid' );
			$this->_errCode = 'invalid_code';
			return false;
		} elseif ( $found && ! $valid ) {
			Logger::info( __METHOD__, 'The invitation code has expired' );
			$this->_errCode = 'expired_code';
			return false;
		}
		$res = $this->_write( $this->_pathInv, $new );
		return $found && $valid && $res;
	}

	private function _getUsers( array $as ): array {
		$ret = [];
		foreach ( $as as $a ) {
			$a = trim( $a );
			if ( empty( $a ) || $a[0] === '#' ) continue;
			[ $user ] = explode( "\t", $a );
			$ret[] = $user;
		}
		return $ret;
	}


	// ------------------------------------------------------------------------


	private function _lock() {
		if ( ! is_file( $this->_pathAcct ) ) {
			return null;
		}
		if ( $h = opendir( pathinfo( $this->_pathAcct, PATHINFO_DIRNAME ) ) ) {
			flock( $h, LOCK_EX );
			return $h;
		}
		return null;
	}

	private function _unlock( $h ): void {
		flock( $h, LOCK_UN );
		closedir( $h );
	}


	// ------------------------------------------------------------------------


	private function _read( string $path, bool $silent = false ): array {
		if ( is_file( $path ) === false ) {
			if ( ! $silent ) {
				Logger::error( __METHOD__, 'The file does not exist', $path );
				$this->_errCode = 'internal_error';
			}
			return [];
		}
		$as = file( $path, FILE_IGNORE_NEW_LINES );
		if ( $as === false ) {
			Logger::error( __METHOD__, 'Cannot open the file', $path );
			$this->_errCode = 'internal_error';
			return [];
		}
		return $as;
	}

	private function _write( string $path, array $ac ): bool {
		$dir = pathinfo( $path, PATHINFO_DIRNAME );
		if ( ! is_dir( $dir ) ) {
			if ( ! \nt\ensure_dir( $dir, NT_MODE_DIR ) ) {
				Logger::error( __METHOD__, 'The directory is not usable', $dir );
				return false;
			}
		}
		$res = file_put_contents( $path, implode( "\n", $ac ) );
		if ( $res === false ) {
			Logger::error( __METHOD__, 'Cannot write the file', $path );
			$this->_errCode = 'internal_error';
			return false;
		}
		return true;
	}

}
