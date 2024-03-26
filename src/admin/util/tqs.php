<?php
/**
 * Function for Timestamp Query Parameter
 *
 * @author Takuto Yanagida
 * @version 2024-03-26
 */

namespace nt;

/**
 * Appends a version query string to a file path.
 *
 * @param string $base The base directory of the file.
 * @param string $path The path of the file relative to the base directory.
 */
function tqs( string $base, string $path ): void {
	$ft = filemtime( $base . '/' . $path );
	if ( is_int( $ft ) ) {
		$fts = gmdate( 'Ymdhis', $ft );
	} else {
		$fts = '';
	}
	$hash = hash( 'crc32b', $path . $fts );
	echo "$path?v$hash";
}
