<?php
namespace nt;
/**
 *
 * Logger
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @version 2020-07-27
 *
 */


class Logger {

	const MAX_SIZE   = 4000;
	const LOG_FILE   = __DIR__ . '/var/log/log.txt';

	static public $debug = false;

	static function output( string $type, string $msg ) {
		$path = self::LOG_FILE;
		self::ensureFile( $path );
		$fp = fopen( $path, 'ab+' );
		if ( $fp === false ) return;

		flock( $fp, LOCK_EX );
		set_file_buffer( $fp, 0 );
		$line = join( ' ', [ date( 'Y-m-d H:i:s' ), getenv( 'REMOTE_USER' ), ucfirst( strtolower( $type ) ), $msg ] );
		if ( self::$debug && $type !== 'info' ) print( "$line\n" );
		fputs( $fp, "$line\n" );
		flock( $fp, LOCK_UN );
		fclose( $fp );
	}

	static private function ensureFile( string $path ) {
		if ( is_file( $path ) ) {
			$fsize = filesize( $path );
			if ( self::MAX_SIZE < $fsize ) {
				$pi  = pathinfo( $path );
				$ext = '.' . $pi['extension'];
				$fn  = $pi['filename'];
				$dir = $pi['dirname'] . '/';
				self::rotateFile( $dir, $fn, $ext );
			}
		} else {
			$dir = dirname( $path );
			if ( ! is_dir( $dir ) ) {
				if ( mkdir( $dir, 0755, true ) ) chmod( $dir, 0755 );
			}
		}
	}

	static private function rotateFile( string $dir, string $fn, string $ext ) {
		if ( is_file( "$dir{$fn}[5]$ext" ) ) {
			unlink( "$dir{$fn}[5]$ext" );
		}
		for ( $i = 4; $i > 0; $i -= 1 ) {
			if ( ! is_file( "$dir{$fn}[$i]$ext" ) ) continue;
			$j = $i + 1;
			rename( "$dir{$fn}[$i]$ext", "$dir{$fn}[$j]$ext" );
		}
		if ( is_file( "$dir$fn$ext" ) ) {
			rename( "$dir$fn$ext", "$dir{$fn}[1]$ext" );
		}
	}

}
