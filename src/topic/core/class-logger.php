<?php
namespace nt;
/**
 *
 * Logger
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @version 2023-06-22
 *
 */


class Logger {

	const MAX_SIZE   = 4000;
	const DEBUG_VIEW = false;
	const LOG_FILE   = __DIR__ . '/var/log/log.txt';

	static function output( $msg ) {
		$path = self::LOG_FILE;
		self::ensureFile( $path );
		$fp = fopen( $path, 'ab+' );
		if ( $fp === false ) return;

		flock( $fp, LOCK_EX );
		set_file_buffer( $fp, 0 );
		$line = join( ' ', [ date('Y-m-d H:i:s'), getenv('REMOTE_USER'), $msg ] );
		if ( self::DEBUG_VIEW ) print( "$line\n" );
		fputs( $fp, "$line\n" );
		flock( $fp, LOCK_UN );
		fclose( $fp );
	}

	static private function ensureFile( $path ) {
		if ( file_exists( $path ) ) {
			$fsize = filesize( $path );
			if ( self::MAX_SIZE < $fsize ) {
				$pi  = pathinfo( $path );
				$ext = isset( $pi['extension'] ) ? ( '.' . $pi['extension'] ) : '';
				$fn  = $pi['filename'];
				$dir = $pi['dirname'] . '/';
				self::rotateFile( $dir, $fn, $ext );
			}
		} else {
			$dir = dirname( $path );
			if ( ! file_exists( $dir ) ) {
				if ( mkdir( $dir, 0755, true ) ) chmod( $dir, 0755 );
			}
		}
	}

	static private function rotateFile( $dir, $fn, $ext ) {
		if ( file_exists( "$dir{$fn}[5]$ext" ) ) {
			unlink( "$dir{$fn}[5]$ext" );
		}
		for ( $i = 4; $i > 0; $i -= 1 ) {
			if ( ! file_exists( "$dir{$fn}[$i]$ext" ) ) continue;
			$j = $i + 1;
			rename( "$dir{$fn}[$i]$ext", "$dir{$fn}[$j]$ext" );
		}
		if ( file_exists( "$dir$fn$ext" ) ) {
			rename( "$dir$fn$ext", "$dir{$fn}[1]$ext" );
		}
	}

}
