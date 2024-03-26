<?php
/**
 * Functions for URLs
 *
 * @author Takuto Yanagida
 * @version 2024-03-26
 */

namespace nt;

/**
 * Resolves a URL.
 *
 * @param string $target The target string.
 * @param string $base   The base string.
 * @return string The resolved URL.
 */
function resolve_url( string $target, string $base ): string {
	$target = trim( $target );
	if ( strpos( $target, '#' ) === 0 ) return $target;

	$comp = parse_url( $base );
	if ( ! isset( $comp['scheme'] ) || ! isset( $comp['host'] ) ) return $target;

	if ( ! isset( $comp['path'] ) ) $comp['path'] = '/';
	$dir = preg_replace( '!/[^/]*$!', '/', $comp['path'] );

	switch ( true ) {
		case preg_match( '/^http/', $target ):
			return $target;
		case preg_match( '/^\/\/.+/', $target ):
			return $comp['scheme'] . ':' . $target;
		case preg_match( '/^\/[^\/].+/', $target ):
			return $comp['scheme'] . '://' . $comp['host'] . $target;
		case preg_match( '/^\.\/(.+)/', $target, $ms ):
			return $comp['scheme'] . '://' . $comp['host'] . $dir . $ms[1];
		case preg_match( '/^([^\.\/]+)(.*)/', $target, $ms ):
			return $comp['scheme'] . '://' . $comp['host'] . $dir . $ms[1] . $ms[2];
		case preg_match( '/^\.\.\/.+/', $target ):
			preg_match_all( '!\.\./!', $target, $ms );
			$nest = count( $ms[0] );

			$dir       = preg_replace( '!/[^/]*$!', '/', $comp['path'] ) . '\n';
			$dir_array = explode( '/', $dir );
			array_shift( $dir_array );
			array_pop( $dir_array );
			$dir_count = count( $dir_array );
			$count     = $dir_count - $nest;
			$path_to   = '';
			$i         = 0;
			while ( $i < $count ) {
				$path_to .= '/' . $dir_array[ $i ];
				$i++;
			}
			$file = str_replace( '../', '', $target );
			return $comp['scheme'] . '://' . $comp['host'] . $path_to . '/' . $file;
	}
	return $target;
}

/**
 * Gets a URL from a path.
 *
 * @param string $target The target string.
 * @return string The URL.
 */
function get_url_from_path( string $target ): string {
	$target = str_replace( '/', DIRECTORY_SEPARATOR, $target );
	$target = realpath( $target );
	if ( ! is_string( $target ) ) {
		return '';
	}
	$target = str_replace( DIRECTORY_SEPARATOR, '/', $target );

	$path = realpath( $_SERVER['SCRIPT_FILENAME'] );
	if ( ! is_string( $path ) ) {
		return '';
	}
	$path = str_replace( DIRECTORY_SEPARATOR, '/', $path );
	$url  = $_SERVER['SCRIPT_NAME'];

	$len      = mb_strlen( get_right_intersection( $url, $path ) );
	$url_root = mb_substr( $url, 0, -$len );
	$doc_root = mb_substr( $path, 0, -$len );
	return str_replace( $doc_root, $url_root, $target );
}

/**
 * Gets the right intersection of two strings.
 *
 * @param string $str1 The first string.
 * @param string $str2 The second string.
 * @return string The right intersection of the two strings.
 */
function get_right_intersection( string $str1, string $str2 ): string {
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
