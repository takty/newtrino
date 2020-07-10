<?php
namespace nt;
/**
 *
 * Functions for URLs
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @version 2020-07-10
 *
 */


function resolve_url( string $target, string $base ): string {
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

function get_url_from_path( string $target ): string {
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
