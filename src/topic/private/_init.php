<?php
namespace nt;


require_once(__DIR__ . '/../system/function.php');
require_once(__DIR__ . '/php/Session.php');
require_once(__DIR__ . '/php/Store.php');


reject_direct_access(__FILE__, 2);
define('SERVER_HOST_URL', (empty($_SERVER['HTTPS']) ? 'http://' : 'https://') . $_SERVER['HTTP_HOST']);

define('POST_PATH',    __DIR__ . '/../post/');
define('DATA_PATH',    __DIR__ . '/data/');

$dir = dirname(dirname($_SERVER['PHP_SELF']));
$url = SERVER_HOST_URL . rtrim($dir, '/\\') . '/post/';
define('POST_URL', $url);

setLocaleSetting();

$q = empty($_POST) ? $_GET : $_POST;
$q += ['mode' => '', 'id' => 0, 'page' => 1, 'posts_per_page' => 10, 'cat' => '', 'date' => '', 'date_bgn' => '', 'date_end' => ''];
$store = new Store(POST_PATH, POST_URL, DATA_PATH);

$url = SERVER_HOST_URL . rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
$session = new Session(DATA_PATH, POST_PATH);
if (!$session->check($q)) {
	header("Location: $url/login.php");
	exit(1);
}
