<?php
/**
 * Function for Timestamp Query Parameter
 *
 * @author Takuto Yanagida
 * @version 2022-12-20
 */

namespace nt;

function tqs( string $base, string $path ): void {
	$fts  = gmdate( 'Ymdhis', filemtime( $base . '/' . $path ) );
	$hash = hash( 'crc32b', $path . $fts );
	echo "$path?v$hash";
}
