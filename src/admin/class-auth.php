<?php
namespace nt;
/**
 *
 * User Authentication
 *
 * @author Takuto Yanagida
 * @version 2021-06-16
 *
 */


require_once( __DIR__ . '/../core/class-logger.php' );


class Auth {

	const TIMEOUT_INVITATION = 604800;  // 7 days * 24 hours * 60 minutes * 60 seconds
	const AUTH_NONCE_GL      = 300;     // 5 minutes * 60 seconds (Expires in 10 minutes max.)

	const ACCT_FILE_NAME = 'account';
	const HASH_ALGO      = 'sha256';

	public static function getAuthKey(): string {
		if ( defined( 'NT_AUTH_KEY' ) ) return NT_AUTH_KEY;
		return 'newtrino';
	}

	public static function getAuthNonce( int $step = 1 ): string {
		$time = intval( ceil( time() / self::AUTH_NONCE_GL ) );
		$time += $step;
		$seed = strval( getlastmod() );
		return hash( self::HASH_ALGO, $seed . $time );
	}

	private static function _createCode(): string {
		return bin2hex( openssl_random_pseudo_bytes( 12 ) );
	}


	// ------------------------------------------------------------------------


	private $_url;
	private $_path;
	private $_errCode = '';

	public function __construct( string $urlAdmin, string $dirAcct ) {
		$this->_url  = $urlAdmin;
		$this->_path = $dirAcct . self::ACCT_FILE_NAME;
	}

	public function getErrorCode(): string {
		return $this->_errCode;
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
			$as = $this->_read();
			$as = $this->_cleanInvitation( $as );
			$this->_unlock( $h );
		}
		if ( $as === null ) return false;

		$a2 = hash( self::HASH_ALGO, $this->_url );
		$ns = [ self::getAuthNonce( 0 ), self::getAuthNonce( 1 ) ];

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


	// ------------------------------------------------------------------------


	public function issueInvitation( array $params ): ?string {
		if ( ! $this->signIn( $params ) ) return null;
		$code = self::_createCode();
		$limit = time() + self::TIMEOUT_INVITATION;

		$res = false;

		if ( $h = $this->_lock() ) {
			$as = $this->_read();
			$as[] = "*$limit\t$code";
			$res = $this->_write( $as );
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
			( isset( $params['user'] ) && ! preg_match( '/^(?=.*[a-z])[\-_a-z0-9]{4,32}$/i', $params['user'] ) )
		) {
			Logger::info( __METHOD__, 'Parameters are invalid' );
			$this->_errCode = 'invalid_param';
			return false;
		}
		[ 'user' => $user, 'code' => $code, 'hash' => $hash ] = $params;
		$ca = explode( '|', $code );
		$code = $ca[0];
		$lang = $ca[1] ?? null;

		$res = false;
		$now = time();

		if ( $h = $this->_lock() ) {
			$as = $this->_read();

			$users = $this->_getUsers( $as );
			if ( in_array( $user, $users, true ) ) {
				$this->_unlock( $h );
				$this->_errCode = 'invalid_param';
				return false;
			}

			$new = [];
			$mod = false;
			foreach ( $as as $a ) {
				$a = trim( $a );
				if ( empty( $a ) || $a[0] === '#' ) {
					$new[] = $a;
					continue;
				}
				$cs = explode( "\t", $a );
				$us = $cs[0];

				if ( $us[0] === '*' ) {
					if ( $cs[1] === $code ) {
						if ( $now <= intval( substr( $us, 1 ) ) ) {
							$cs[0] = $user;
							$cs[1] = $hash;
							if ( $lang ) $cs[2] = $lang;
							$mod = true;
						} else {
							Logger::info( __METHOD__, 'The invitation code has expired' );
							$this->_errCode = 'expired_code';
							break;
						}
					}
				}
				$new[] = implode( "\t", $cs );
			}
			if ( $mod ) {
				$res = $this->_write( $new );
			} else {
				Logger::info( __METHOD__, 'The invitation code is invalid' );
				$this->_errCode = 'invalid_code';
			}
			$this->_unlock( $h );
		}
		return $res;
	}

	private function _cleanInvitation( array $as ): array {
		$new = [];
		$mod = false;
		$now = time();
		foreach ( $as as $a ) {
			$a = trim( $a );
			if ( empty( $a ) || $a[0] === '#' ) {
				$new[] = $a;
				continue;
			}
			$cs = explode( "\t", $a );

			if ( $cs[0][0] === '*' && intval( substr( $cs[0], 1 ) ) < $now ) {
				$mod = true;
				continue;
			}
			$new[] = implode( "\t", $cs );
		}
		if ( $mod ) $this->_write( $new );
		return $new;
	}

	private function _getUsers( array $as ): array {
		$ret = [];
		foreach ( $as as $a ) {
			$a = trim( $a );
			if ( empty( $a ) || $a[0] === '#' ) continue;
			[ $user ] = explode( "\t", $a );
			if ( $user[0] === '*' ) continue;
			$ret[] = $user;
		}
		return $ret;
	}


	// ------------------------------------------------------------------------


	private function _lock() {
		if ( ! is_file( $this->_path ) ) {
			return null;
		}
		if ( $h = opendir( pathinfo( $this->_path, PATHINFO_DIRNAME ) ) ) {
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


	private function _read(): array {
		if ( is_file( $this->_path ) === false ) {
			Logger::error( __METHOD__, 'The account file does not exist' );
			$this->_errCode = 'internal_error';
			return [];
		}
		$as = file( $this->_path, FILE_IGNORE_NEW_LINES );
		if ( $as === false ) {
			Logger::error( __METHOD__, 'Cannot open the account file' );
			$this->_errCode = 'internal_error';
			return [];
		}
		return $as;
	}

	private function _write( array $ac ): bool {
		$res = file_put_contents( $this->_path, implode( "\n", $ac ) );
		if ( $res === false ) {
			Logger::error( __METHOD__, 'Cannot write the account file' );
			$this->_errCode = 'internal_error';
			return false;
		}
		return true;
	}

}