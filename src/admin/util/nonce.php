<?php
namespace nt;
/**
 *
 * Function for Nonce and Tokens
 *
 * @author Takuto Yanagida
 * @version 2021-06-28
 *
 */


require_once( __DIR__ . '/file.php' );


function issue_token( $path, $timeout ): string {
	$token = \nt\create_nonce( 16 );
	$now   = time();
	$new   = [];
	$rs    = is_file( $path ) ? file( $path, FILE_IGNORE_NEW_LINES ) : [];

	foreach( $rs as $r ) {
		[ $l, $t ] = explode( "\t", $r );
		if ( $now < $l ) continue;
		$new[] = $r;
	}
	$limit = $now + $timeout;
	$new[] = "$limit\t$token";

	$dir = pathinfo( $path, PATHINFO_DIRNAME );
	if ( ! is_dir( $dir ) && ! \nt\ensure_dir( $dir, NT_MODE_DIR ) ) return '';
	$res = file_put_contents( $path, implode( "\n", $new ) );
	if ( $res === false ) return '';
	return $token;
}

function check_token( $path, $token ): bool {
	$valid = false;
	$now   = time();
	$new   = [];
	$rs    = is_file( $path ) ? file( $path, FILE_IGNORE_NEW_LINES ) : [];

	foreach( $rs as $r ) {
		if ( empty( trim( $r ) ) ) continue;
		[ $l, $t ] = explode( "\t", $r );
		if ( $l < $now ) continue;
		if ( $token === $t ) {
			$valid = true;
			continue;
		}
		$new[] = $t;
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


function get_nonce( int $granularity, int $step = 1 ): string {
	$time = intval( ceil( time() / $granularity ) );
	$time += $step;
	$seed = strval( getlastmod() );
	return hash( 'sha256', $seed . $time );
}

function get_possible_nonce( int $granularity ): array {
	return [ \nt\get_nonce( $granularity, 0 ), \nt\get_nonce( $granularity, 1 ) ];
}


// -----------------------------------------------------------------------------


function create_nonce( $bytes ): string {
	return bin2hex( openssl_random_pseudo_bytes( $bytes ) );
}
