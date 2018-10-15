<?php
define('SERVER_HOST_URL', (empty($_SERVER['HTTPS']) ? 'http://' : 'https://') . $_SERVER['HTTP_HOST']);

$ifs = get_included_files();
if (array_shift($ifs) === __FILE__) {
	$url = SERVER_HOST_URL . rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
	header("Location: $url/../");
	exit(1);
}
?>
<?php
define('SESSION_PATH', __DIR__ . '/var/session/');
define('POST_PATH',    __DIR__ . '/../post/');
define('DATA_PATH',    __DIR__ . '/data/');
define('LOG_FILE',     __DIR__ . '/var/log/log.txt');

$dir = dirname(dirname($_SERVER['PHP_SELF']));
$url = SERVER_HOST_URL . rtrim($dir, '/\\') . '/post/';
define('POST_URL', $url);

date_default_timezone_set('Asia/Tokyo');
mb_language('Japanese');
mb_internal_encoding('utf-8');
mb_http_output('utf-8');
mb_http_input('utf-8');
mb_regex_encoding('utf-8');

require_once(__DIR__ . '/php/util/TinySegmenter.php');
require_once(__DIR__ . '/php/util/simple_html_dom.php');
require_once(__DIR__ . '/php/util/url_resolver.php');
require_once(__DIR__ . '/php/Logger.php');
require_once(__DIR__ . '/php/Session.php');
require_once(__DIR__ . '/php/Store.php');
require_once(__DIR__ . '/php/Post.php');
require_once(__DIR__ . '/php/Media.php');
require_once(__DIR__ . '/php/Indexer.php');

if (!function_exists('_h')) {function _h($str) {return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');}}
if (!function_exists('_eh')) {function _eh($str) {echo htmlspecialchars($str, ENT_QUOTES, 'UTF-8');}}
if (!function_exists('_u')) {function _u($str) {return rawurlencode($str);}}
if (!function_exists('_eu')) {function _eu($str) {echo rawurlencode($str);}}

function t_wrap($flag, $before, $cont, $after) {
	if ($flag) echo $before . $cont . $after;
	else echo $cont;
}

$q = empty($_POST) ? $_GET : $_POST;
$q += ['mode' => '', 'id' => 0, 'page' => 1, 'posts_per_page' => 10, 'cat' => '', 'date' => '', 'date_bgn' => '', 'date_end' => ''];
$store = new Store(POST_PATH, POST_URL, DATA_PATH);

$url = SERVER_HOST_URL . rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
$session = new Session(DATA_PATH, SESSION_PATH, POST_PATH);
if (!$session->check($q)) {
	header("Location: $url/login.php");
	exit(1);
}
