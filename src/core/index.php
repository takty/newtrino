<?php
namespace nt;
/**
 *
 * Definitions of Constants and Functions
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @version 2020-07-12
 *
 */


require_once( __DIR__ . '/../index.php' );
require_once( __DIR__ . '/util/url.php' );

define( 'NT_DIR_DATA', NT_DIR . '/data/' );
define( 'NT_DIR_POST', NT_DIR . '/post/' );

define( 'NT_URL_HOST', ( empty( $_SERVER['HTTPS'] ) ? 'http://' : 'https://' ) . $_SERVER['HTTP_HOST'] );
define( 'NT_URL', NT_URL_HOST . get_url_from_path( NT_DIR ) . '/' );
define( 'NT_URL_POST', NT_URL_HOST . get_url_from_path( NT_DIR_POST ) . '/' );

if ( defined( 'NT_ADMIN' ) ) {
	define( 'NT_DIR_ADMIN', NT_DIR . '/admin/' );
	define( 'NT_DIR_ADMIN_RES', NT_DIR . '/admin/res/' );
	define( 'NT_DIR_SESSION', NT_DIR_ADMIN . 'var/session/' );

	define( 'NT_URL_ADMIN', NT_URL_HOST . get_url_from_path( NT_DIR_ADMIN ) . '/' );
}


// Functions Used in Initial Process -------------------------------------------


function set_locale_setting() {
	date_default_timezone_set( 'Asia/Tokyo' );
	mb_language( 'Japanese' );
	mb_internal_encoding( 'utf-8' );
	mb_http_output( 'utf-8' );
	mb_http_input( 'utf-8' );
	mb_regex_encoding( 'utf-8' );
}

function load_config( string $dirData ): array {
	$conf = [];
	$path = $dirData . 'config.json';
	if ( file_exists( $path ) ) {
		$json = file_get_contents( $path );
		$conf = json_decode( $json, true );
	}
	// Default Config
	$conf += [
		'lang'               => 'en',
		'lang_admin'         => 'en',
		'per_page'           => 10,
		'new_arrival_period' => 7,
		'archive_by_year'    => false,
		'archive_by_type'    => false,
	];
	return $conf;
}

function load_resource( string $dirData, string $lang ): array {
	$path = $dirData . $lang . '.json';
	if ( file_exists( $path ) ) {
		$json = file_get_contents( $path );
		return json_decode( $json, true );
	}
	return [];
}


// Output Functions ------------------------------------------------------------


function _h( string $str ): string {
	return htmlspecialchars( $str, ENT_QUOTES, 'UTF-8' );
}

function _eh( string $str ) {
	echo htmlspecialchars( $str, ENT_QUOTES, 'UTF-8' );
}

function _u( string $str ): string {
	return rawurlencode( $str );
}

function _ht( string $str, string $context = 'default' ): string {
	return htmlspecialchars( translate( $str, $context ), ENT_QUOTES, 'UTF-8' );
}

function translate( string $str, string $context = 'default' ): string {
	if ( defined( 'NT_ADMIN' ) && $context === 'default' ) {
		$context = 'admin';
	}
	global $nt_res;
	if ( isset( $nt_res[ $context ][ $str ] ) ) {
		return $nt_res[ $context ][ $str ];
	}
	if ( $context !== 'default' ) {
		if ( isset( $nt_res['default'][ $str ] ) ) {
			return $nt_res['default'][ $str ];
		}
	}
	return $str;
}
