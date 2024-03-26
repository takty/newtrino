<?php
/**
 * Function for Nonce and Tokens
 *
 * @author Takuto Yanagida
 * @version 2024-03-26
 */

namespace nt;

require_once( __DIR__ . '/file.php' );

/**
 * Issues a token.
 *
 * @param string $path    The path of the file.
 * @param int    $timeout The timeout for the token.
 * @return string The issued token.
 */
function issue_token( $path, $timeout ): string {
	$token = \nt\create_nonce( 16 );
	$now   = time();
	$new   = [];
	$rs    = is_file( $path ) ? file( $path, FILE_IGNORE_NEW_LINES ) : [];
	if ( ! is_array( $rs ) ) {
		$rs = [];
	}

	foreach( $rs as $r ) {
		if ( empty( trim( $r ) ) ) continue;
		[ $l ] = explode( "\t", $r );
		if ( $now < intval( $l ) ) continue;
		$new[] = $r;
	}
	$limit = $now + $timeout;
	$new[] = "$limit\t$token";

	$dir = pathinfo( $path, PATHINFO_DIRNAME );
	if ( ! is_dir( $dir ) && ! \nt\ensure_dir( $dir, NT_MODE_DIR ) ) return '';
	$res = file_put_contents( $path, implode( "\n", $new ) );
	if ( false === $res ) return '';
	return $token;
}

/**
 * Checks the token.
 *
 * @param string $path  The path of the file.
 * @param string $token The token to check.
 * @return bool Whether the token is valid.
 */
function check_token( string $path, string $token ): bool {
	$valid = false;
	$now   = time();
	$new   = [];
	$rs    = is_file( $path ) ? file( $path, FILE_IGNORE_NEW_LINES ) : [];
	if ( ! is_array( $rs ) ) {
		$rs = [];
	}

	foreach( $rs as $r ) {
		if ( empty( trim( $r ) ) ) continue;
		[ $l, $t ] = explode( "\t", $r );
		if ( intval( $l ) < $now ) continue;
		if ( $token === $t ) {
			$valid = true;
			continue;
		}
		$new[] = $r;
	}
	if ( count( $rs ) !== count( $new ) ) {
		$dir = pathinfo( $path, PATHINFO_DIRNAME );
		if ( ! is_dir( $dir ) && ! \nt\ensure_dir( $dir, NT_MODE_DIR ) ) return false;
		$res = file_put_contents( $path, implode( "\n", $new ) );
		if ( $res === false ) return false;
	}
	return $valid;
}


// -----------------------------------------------------------------------------


/**
 * Gets a nonce.
 *
 * @param int $granularity The granularity of time.
 * @param int $step        The step for the nonce.
 * @return string The nonce.
 */
function get_nonce( int $granularity, int $step = 1 ): string {
	$time = intval( ceil( time() / $granularity ) );
	$time += $step;
	$seed = strval( getlastmod() );
	return hash( 'sha256', $seed . $time );
}

/**
 * Gets possible nonces.
 *
 * @param int $granularity The granularity of time.
 * @return array{string, string} The possible nonces.
 */
function get_possible_nonce( int $granularity ): array {
	return [ \nt\get_nonce( $granularity, 0 ), \nt\get_nonce( $granularity, 1 ) ];
}


// -----------------------------------------------------------------------------


/**
 * Creates a nonce.
 *
 * @param int $bytes The number of bytes for the nonce.
 * @return string The created nonce.
 */
function create_nonce( $bytes ): string {
	return bin2hex( (string) openssl_random_pseudo_bytes( $bytes ) );
}
