<?php
/**
 * Function for Nonce
 *
 * @author Takuto Yanagida
 * @version 2024-03-26
 */

namespace nt;

/**
 * Ensures the directory exists and has the correct permissions.
 *
 * @param string $path The path of the directory.
 * @param int    $mode The permissions mode.
 * @return bool Whether the directory exists and has the correct permissions.
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

/**
 * Deletes all files and directories in the specified directory.
 *
 * @param string   $dir      The directory to delete.
 * @param callable $on_error The function to call when an error occurs.
 */
function delete_all_in( string $dir, callable $on_error ): void {
	$dir = rtrim( $dir, '/' );
	if ( ! is_dir( $dir ) ) {
		$on_error( $dir );
		return;
	}
	$fns = scandir( $dir );
	if ( is_array( $fns ) ) {
		foreach ( $fns as $fn ) {
			if ( $fn === '.' || $fn === '..' ) continue;
			if ( is_dir( "$dir/$fn" ) ) {
				\nt\delete_all_in( "$dir/$fn", $on_error );
			} else {
				unlink( "$dir/$fn" );
			}
		}
	}
	rmdir( $dir );
}


// -----------------------------------------------------------------------------


/**
 * Gets a unique file name in the specified directory.
 *
 * @param string $dir      The directory.
 * @param string $fileName The original file name.
 * @param string $suffix   The suffix to append to the file name.
 * @return string The unique file name.
 */
function get_unique_file_name( string $dir, string $fileName, string $suffix = '' ): string {
	$pi   = pathinfo( $fileName );
	$ext  = isset( $pi['extension'] ) ? ( '.' . $pi['extension'] ) : '';
	$name = \nt\sanitize_file_name( $pi['filename'] ) . $suffix;

	$nfn = "$name$ext";
	if ( ! \nt\is_file_exist( $dir, $nfn ) ) return $nfn;

	for ( $num = 1; $num <= 256; $num += 1 ) {
		$nfn = $name . '[' . $num . ']' . $ext;
		if ( ! \nt\is_file_exist( $dir, $nfn ) ) return $nfn;
	}
	return '';
}

/**
 * Checks if a file exists in the specified directory.
 *
 * @param string $dir      The directory.
 * @param string $fileName The file name.
 * @return bool Whether the file exists.
 */
function is_file_exist( string $dir, string $fileName ): bool {
	return is_dir( $dir ) && is_file( $dir . $fileName );
}

/**
 * Sanitizes the file name.
 *
 * @param string $name The original file name.
 * @return string The sanitized file name.
 */
function sanitize_file_name( string $name ): string {
	$scs  = [ '/', '\\', ':', '*', '?', '"', '<', '>', '|', chr( 0 ) ];
	$name = str_replace( $scs, '_', $name );
	$name = preg_replace( '/[\r\n\t]+/u', '_', $name ) ?? $name;
	$name = preg_replace( '/^\./u', '_', $name ) ?? $name;
	return $name;
}
