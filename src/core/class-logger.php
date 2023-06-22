<?php
/**
 * Logger
 *
 * @author Takuto Yanagida
 * @version 2023-06-22
 */

namespace nt;

class Logger {

	const MAX_SIZE = 4000;
	const LOG_FILE = __DIR__ . '/var/log/log.txt';
	const FILE_NUM = 5;

	static public $debug = false;

	static public function info( string $where, $msg, $added = '' ): void {
		$msg = is_string( $msg ) ? $msg : var_export( $msg, true );
		if ( ! empty( $added ) ) {
			$added = is_string( $added ) ? $added : var_export( $added, true );
			$added = " [$added]";
		}
		self::output( 'info', "($where) $msg$added" );
	}

	static public function error( string $where, $msg, $added = '' ): void {
		$msg = is_string( $msg ) ? $msg : var_export( $msg, true );
		if ( ! empty( $added ) ) {
			$added = is_string( $added ) ? $added : var_export( $added, true );
			$added = " [$added]";
		}
		self::output( 'error', "($where) $msg$added" );
	}

	static public function output( string $type, $msg ): void {
		if ( ! is_string( $msg ) ) {
			$msg = var_export( $msg, true );
		}
		$path = self::LOG_FILE;
		if ( ! self::_ensureFile( $path ) ) {
			if ( self::$debug ) {
				self::_echoError();
			}
			return;
		}
		$fp = fopen( $path, 'ab+' );
		if ( false === $fp ) {
			return;
		}
		flock( $fp, LOCK_EX );
		set_file_buffer( $fp, 0 );
		$line = join( ' ', [ date( '[Y-m-d H:i:s]' ), self::_createTimeHash(), $_SERVER['REMOTE_ADDR'], ucfirst( strtolower( $type ) ), $msg ] );
		if ( self::$debug && 'info' !== $type ) {
			print( str_replace( "\n", "<br>\n", htmlspecialchars( $line, ENT_HTML5 ) ) . "<br>\n" );
		}
		fputs( $fp, "$line\n" );
		flock( $fp, LOCK_UN );
		fclose( $fp );
	}

	static private function _echoError() {
		register_shutdown_function(
			function () {
				echo '<script>console.error("Logger cannot make any logs in the directory. Check file permissions.");</script>';
			}
		);
	}

	static private function _createTimeHash() {
		$hash = hash( 'crc32b', $_SERVER['REQUEST_TIME_FLOAT'], true );
		return rtrim( base64_encode( $hash ), '=' );
	}

	static private function _ensureFile( string $path ): bool {
		if ( is_file( $path ) ) {
			@chmod( $path, NT_MODE_FILE );
			if ( null !== error_get_last() ) {
				return false;
			}
			$fs = filesize( $path );
			if ( self::MAX_SIZE < $fs ) {
				$pi  = pathinfo( $path );
				$ext = isset( $pi['extension'] ) ? ( '.' . $pi['extension'] ) : '';
				$fn  = $pi['filename'];
				$dir = $pi['dirname'] . '/';
				self::_rotateFile( $dir, $fn, $ext );
			}
		} else {
			$dirLog = dirname( $path );
			if ( ! is_dir( $dirLog ) ) {
				@mkdir( $dirLog, NT_MODE_DIR, true );
				if ( null !== error_get_last() ) {
					return false;
				}
			}
			if ( is_dir( $dirLog ) ) {
				@chmod( dirname( $dirLog ), NT_MODE_DIR );
				@chmod( $dirLog, NT_MODE_DIR );
			}
		}
		return true;
	}

	static private function _rotateFile( string $dir, string $fn, string $ext ): void {
		$n = self::FILE_NUM;
		$path = "$dir{$fn}[$n]$ext";
		if ( is_file( $path ) ) {
			unlink( $path );
		}
		for ( $i = $n - 1; $i > 0; $i -= 1 ) {
			if ( ! is_file( "$dir{$fn}[$i]$ext" ) ) {
				continue;
			}
			$j = $i + 1;
			rename( "$dir{$fn}[$i]$ext", "$dir{$fn}[$j]$ext" );
		}
		if ( is_file( "$dir$fn$ext" ) ) {
			rename( "$dir$fn$ext", "$dir{$fn}[1]$ext" );
		}
	}

}
