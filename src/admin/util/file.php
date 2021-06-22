<?php
namespace nt;
/**
 *
 * Function for Nonce
 *
 * @author Takuto Yanagida
 * @version 2021-06-23
 *
 */


function ensure_dir( string $path, int $mode ): bool {
	if ( is_dir( $path ) ) {
		if ( $mode !== ( fileperms( $path ) & 0777 ) ) {
			@chmod( $path, $mode );
		}
		return true;
	}
	if ( mkdir($path, $mode, true ) ) {
		@chmod( $path, $mode );
		return true;
	}
	return false;
}

function delete_all_in( string $dir, $on_error ): void {
	$dir = rtrim( $dir, '/' );
	if ( ! is_dir( $dir ) ) {
		$on_error( $dir );
		return;
	}
	foreach ( scandir( $dir ) as $fn ) {
		if ( $fn === '.' || $fn === '..' ) continue;
		if ( is_dir( "$dir/$fn" ) ) {
			\nt\delete_all_in( "$dir/$fn", $on_error );
		} else {
			unlink( "$dir/$fn" );
		}
	}
	rmdir( $dir );
}


// -----------------------------------------------------------------------------


function get_unique_file_name( string $dir, string $fileName, string $postFix = '' ): string {
	$pi   = pathinfo( $fileName );
	$ext  = '.' . $pi['extension'];
	$name = \nt\sanitize_file_name( $pi['filename'] ) . $postFix;

	$nfn = "$name$ext";
	if ( ! \nt\is_file_exist( $dir, $nfn ) ) return $nfn;

	for ( $num = 1; $num <= 256; $num += 1 ) {
		$nfn = $name . '[' . $num . ']' . $ext;
		if ( ! \nt\is_file_exist( $dir, $nfn ) ) return $nfn;
	}
	return '';
}

function is_file_exist( string $dir, string $fileName ): bool {
	return is_dir( $dir ) && is_file( $dir . $fileName );
}

function sanitize_file_name( string $name ): string {
	$scs  = [ '/', '\\', ':', '*', '?', '"', '<', '>', '|', chr( 0 ) ];
	$name = str_replace( $scs, '_', $name );
	$name = preg_replace( '/[\r\n\t]+/u', '_', $name );
	$name = preg_replace( '/^\./u', '_', $name );
	return $name;
}
