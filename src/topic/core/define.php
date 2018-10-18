<?php
namespace nt;
/**
 *
 * Definitions of Directory Pathes
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @version 2018-10-18
 *
 */


define('NT_PATH_DATA',    __DIR__ . '/../data/');
define('NT_PATH_POST',    __DIR__ . '/../post/');

if (defined('NT_PRIVATE')) {
	define('NT_PATH_PRIVATE', __DIR__ . '/../private/');
	define('NT_PATH_ACCOUNT', NT_PATH_DATA);
	define('NT_PATH_SESSION', NT_PATH_PRIVATE . 'var/session/');

	$host = (empty($_SERVER['HTTPS']) ? 'http://' : 'https://') . $_SERVER['HTTP_HOST'];

	define('NT_URL_PRIVATE', $host . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . '/');
}
