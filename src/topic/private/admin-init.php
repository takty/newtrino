<?php
namespace nt;
/**
 *
 * Init Admin
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @version 2018-10-18
 *
 */


if (!defined('NT_LANG')) define('NT_LANG', 'ja');

require_once(__DIR__ . '/../core/function.php');
require_once(__DIR__ . '/../core/class-store.php');
require_once(__DIR__ . '/class-session.php');

reject_direct_access(__FILE__, 2);

define('POST_PATH', __DIR__ . '/../post/');
define('SERVER_HOST_URL', (empty($_SERVER['HTTPS']) ? 'http://' : 'https://') . $_SERVER['HTTP_HOST']);
define('POST_URL', SERVER_HOST_URL . rtrim(dirname(dirname($_SERVER['PHP_SELF'])), '/\\') . '/post/');

setLocaleSetting();
loadResource();
prepareDefaultQuery(['mode' => '', 'id' => 0, 'page' => 1, 'posts_per_page' => 10, 'cat' => '', 'date' => '', 'date_bgn' => '', 'date_end' => '']);

$nt_store   = new Store(POST_PATH, POST_URL);
$nt_session = new Session(POST_PATH);

if (!$nt_session->check($q)) {
	$url = SERVER_HOST_URL . rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
	header("Location: $url/login.php");
	exit(1);
}
