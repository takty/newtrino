<?php
namespace nt;
/**
 *
 * Logger
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @version 2020-08-01
 *
 */


class Logger {

	const MAX_SIZE = 4000;
	const LOG_FILE = __DIR__ . '/var/log/log.txt';

	static public $debug = false;

	static function output( string $type, string $msg ): void {
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

	static private function ensureFile( string $path ): void {
		if ( is_file( $path ) ) {
			chmod( $path, NT_MODE_FILE );
			$fsize = filesize( $path );
			if ( self::MAX_SIZE < $fsize ) {
				$pi  = pathinfo( $path );
				$ext = '.' . $pi['extension'];
				$fn  = $pi['filename'];
				$dir = $pi['dirname'] . '/';
				self::rotateFile( $dir, $fn, $ext );
			}
		} else {
			$dirLog = dirname( $path );
			if ( ! is_dir( $dirLog ) ) mkdir( $dirLog, NT_MODE_DIR, true );
			if ( is_dir( $dirLog ) ) chmod( $dirLog, NT_MODE_DIR );
		}
	}

	static private function rotateFile( string $dir, string $fn, string $ext ): void {
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
