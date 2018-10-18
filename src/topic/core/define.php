<?php
namespace nt;
/**
 *
 * Definitions of Constants
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @version 2018-10-18
 *
 */


define('NT_DIR_DATA', __DIR__ . '/../data/');
define('NT_DIR_POST', __DIR__ . '/../post/');
define('NT_URL_HOST', (empty($_SERVER['HTTPS']) ? 'http://' : 'https://') . $_SERVER['HTTP_HOST']);
define('NT_URL_POST', NT_URL_HOST . get_url_from_path(NT_DIR_POST) . '/');

if (defined('NT_PRIVATE')) {
	define('NT_DIR_PRIVATE', __DIR__ . '/../private/');
	define('NT_DIR_ACCOUNT', NT_DIR_DATA);
	define('NT_DIR_SESSION', NT_DIR_PRIVATE . 'var/session/');
	define('NT_URL_PRIVATE', NT_URL_HOST . get_url_from_path(NT_DIR_PRIVATE) . '/');
}

function get_right_intersection($str1, $str2) {
	$str1_len = mb_strlen($str1);
	$str2_len = mb_strlen($str2);
	$temp = '';

	for ($i = $str1_len; 0 < $i; $i--) {
		$temp = mb_substr($str1, $str1_len - $i, $i);
		if ($temp === mb_substr($str2, $str2_len - $i, $i)) break;
	}
	if ($i === 0) return '';
	return $temp;
}

function get_url_from_path($target) {
	$target = str_replace('/', DIRECTORY_SEPARATOR, $target);
	$target = realpath($target);
	$target = str_replace(DIRECTORY_SEPARATOR, '/', $target);

	$path = realpath($_SERVER['SCRIPT_FILENAME']);
	$path = str_replace(DIRECTORY_SEPARATOR, '/', $path);
	$url  = $_SERVER['SCRIPT_NAME'];

	$len = mb_strlen(get_right_intersection($url, $path));
	$url_root = mb_substr($url, 0, -$len);
	$doc_root = mb_substr($path, 0, -$len);
	return str_replace($doc_root, $url_root, $target);
}
