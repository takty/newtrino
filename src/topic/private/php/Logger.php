<?php
namespace nt;
/*
 * Logger
 * 2016-11-04
 *
 */

class Logger {

	const MAX_LOG_FILE_SIZE = 4000;
	const DEBUG_VIEW = false;

	static function output($msg) {
		$fileName = LOG_FILE;

		$fsize = @filesize($fileName);
		if ($fsize > self::MAX_LOG_FILE_SIZE) {
			$pi = pathinfo($fileName);
			$ext = '.' . $pi['extension'];
			$fn = $pi['filename'];
			$dir = $pi['dirname'] . '/';
			self::rotateFile($dir, $fn, $ext);
		}
		$fp = fopen($fileName, 'ab');
		if ($fp) {
			flock($fp, LOCK_EX);
			set_file_buffer($fp, 0);
			$line = join(' ', [date('Y-m-d H:i:s'), getenv('REMOTE_USER'), $msg]);
			if (self::DEBUG_VIEW) {
				print($line . "\n");
			}
			fputs($fp, $line . "\n");
			flock($fp, LOCK_UN);
			fclose($fp);
		}
	}

	static function rotateFile($dir, $fn, $ext) {
		@unlink($dir . $fn . '[5]' . $ext);
		for ($i = 4; $i > 0; $i -= 1) {
			@rename($dir . $fn . '[' . $i. ']' . $ext, $dir . $fn . '[' . ($i + 1) . ']' . $ext);
		}
		@rename($dir . $fn . $ext, $dir . $fn . '[1]' . $ext);
	}
}
