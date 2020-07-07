<?php
namespace nt;
/**
 *
 * Definitions of Constants and Functions
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @version 2020-07-07
 *
 */


require_once( __DIR__ . '/../index.php' );

define( 'NT_DIR_DATA', NT_DIR . '/data/' );
define( 'NT_DIR_POST', NT_DIR . '/post/' );

define( 'NT_URL_HOST', ( empty( $_SERVER['HTTPS'] ) ? 'http://' : 'https://' ) . $_SERVER['HTTP_HOST'] );
define( 'NT_URL', NT_URL_HOST . get_url_from_path( NT_DIR ) . '/' );
define( 'NT_URL_POST', NT_URL_HOST . get_url_from_path( NT_DIR_POST ) . '/' );

if ( defined( 'NT_PRIVATE' ) ) {
	define( 'NT_DIR_ADMIN', NT_DIR . '/admin/' );
	define( 'NT_DIR_SESSION', NT_DIR_ADMIN . 'var/session/' );

	define( 'NT_URL_ADMIN', NT_URL_HOST . get_url_from_path( NT_DIR_ADMIN ) . '/' );
}


// Functions Used in Initial Process -------------------------------------------


function reject_direct_access( $urlHost, $path, $depth = 1 ) {
	$ifs = get_included_files();
	if ( array_shift( $ifs ) === $path ) {
		$to = $_SERVER['SCRIPT_NAME'];
		for ( $i = 0; $i < $depth; $i += 1 ) {
			$to = dirname( $to );
		}
		$url = $urlHost . rtrim( $to, '/\\' );
		header( "Location: $url/" );
		exit( 1 );
	}
}

function set_locale_setting() {
	date_default_timezone_set( 'Asia/Tokyo' );
	mb_language( 'Japanese' );
	mb_internal_encoding( 'utf-8' );
	mb_http_output( 'utf-8' );
	mb_http_input( 'utf-8' );
	mb_regex_encoding( 'utf-8' );
}

function load_config( $dirData ) {
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
		'posts_per_page'     => 10,
		'new_arrival_period' => 7,
		'archive_by_year'    => false,
		'archive_by_type'    => false,
	];
	return $conf;
}

function load_resource( $dirData, $lang ) {
	$path = $dirData . 'text.' . $lang . '.json';
	if ( file_exists( $path ) ) {
		$json = file_get_contents( $path );
		return json_decode( $json, true );
	}
	return [];
}


// Utility Functions -----------------------------------------------------------


function resolve_url( $target, $base ) {
	$comp = parse_url( $base );
	$dir = preg_replace( '!/[^/]*$!', '/', $comp['path'] );

	switch ( true ) {
		case preg_match( '/^http/', $target ):
			return $target;
		case preg_match( '/^\/\/.+/', $target ):
			return $comp['scheme'] . ':' . $target;
		case preg_match( '/^\/[^\/].+/', $target ):
			return $comp['scheme'] . '://' . $comp['host'] . $target;
		case preg_match( '/^\.\/(.+)/', $target, $maches ):
			return $comp['scheme'] . '://' . $comp['host'] . $dir . $maches[1];
		case preg_match( '/^([^\.\/]+)(.*)/', $target, $maches ):
			return $comp['scheme'] . '://' . $comp['host'] . $dir . $maches[1] . $maches[2];
		case preg_match( '/^\.\.\/.+/', $target ):
			preg_match_all( '!\.\./!', $target, $matches );
			$nest = count( $matches[0] );

			$dir = preg_replace( '!/[^/]*$!', '/', $comp['path'] ) . '\n';
			$dir_array = explode( '/', $dir );
			array_shift( $dir_array );
			array_pop( $dir_array );
			$dir_count = count( $dir_array );
			$count = $dir_count - $nest;
			$pathto = '';
			$i = 0;
			while ( $i < $count ) {
				$pathto .= '/' . $dir_array[ $i ];
				$i++;
			}
			$file = str_replace( '../', '', $target );
			return $comp['scheme'] . '://' . $comp['host'] . $pathto . '/' . $file;
	}
	return $uri;
}

function get_url_from_path( $target ) {
	$target = str_replace( '/', DIRECTORY_SEPARATOR, $target );
	$target = realpath( $target );
	$target = str_replace( DIRECTORY_SEPARATOR, '/', $target );

	$path = realpath( $_SERVER['SCRIPT_FILENAME'] );
	$path = str_replace( DIRECTORY_SEPARATOR, '/', $path );
	$url  = $_SERVER['SCRIPT_NAME'];

	$len = mb_strlen( get_right_intersection( $url, $path ) );
	$url_root = mb_substr( $url, 0, -$len );
	$doc_root = mb_substr( $path, 0, -$len );
	return str_replace( $doc_root, $url_root, $target );
}

function get_right_intersection( $str1, $str2 ) {
	$str1_len = mb_strlen( $str1 );
	$str2_len = mb_strlen( $str2 );
	$temp = '';

	for ( $i = $str1_len; 0 < $i; $i-- ) {
		$temp = mb_substr( $str1, $str1_len - $i, $i );
		if ( $temp === mb_substr( $str2, $str2_len - $i, $i ) ) break;
	}
	if ( $i === 0 ) return '';
	return $temp;
}


// Output Functions ------------------------------------------------------------


function _h( $str ) {
	return htmlspecialchars( $str, ENT_QUOTES, 'UTF-8' );
}

function _eh( $str ) {
	echo htmlspecialchars( $str, ENT_QUOTES, 'UTF-8' );
}

function _u( $str ) {
	return rawurlencode( $str );
}

function _eu( $str ) {
	echo rawurlencode( $str );
}

function _ht( $str, $context = 'default' ) {
	return htmlspecialchars( translate( $str, $context ), ENT_QUOTES, 'UTF-8' );
}

function _eht( $str, $context = 'default' ) {
	echo htmlspecialchars( translate( $str, $context ), ENT_QUOTES, 'UTF-8' );
}

function translate( $str, $context = 'default' ) {
	if ( defined( 'NT_PRIVATE' ) && $context === 'default' ) {
		$context = 'private';
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
