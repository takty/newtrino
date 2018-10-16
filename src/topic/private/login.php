<?php
namespace nt;
/**
 *
 * Login (PHP)
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @version 2018-10-16
 *
 */


define('SERVER_HOST_URL', (empty($_SERVER['HTTPS']) ? 'http://' : 'https://') . $_SERVER['HTTP_HOST']);

define('SESSION_PATH', __DIR__ . '/var/session/');
define('POST_PATH',    __DIR__ . '/../post/');
define('DATA_PATH',    __DIR__ . '/data/');

date_default_timezone_set('Asia/Tokyo');
mb_language('Japanese');
mb_internal_encoding('utf-8');
mb_http_output('utf-8');
mb_http_input('utf-8');
mb_regex_encoding('utf-8');

if (!function_exists('_h')) {function _h($str) {return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');}}

require_once(__DIR__ . '/php/Logger.php');
require_once(__DIR__ . '/php/Session.php');
require_once(__DIR__ . '/php/Store.php');

$q = $_POST;
$url = SERVER_HOST_URL . rtrim(dirname($_SERVER['PHP_SELF']), '/\\');

$t_url = "$url/";
$t_realm = 'newtrino';
$t_nonce = Session::getNonce();
$t_msg = '';
$error = '';

header('Content-Type: text/html;charset=utf-8');

if (!empty($q['digest'])) {
	$session = new Session(DATA_PATH, SESSION_PATH, POST_PATH);
	$sid = $session->login($q['user'], $q['digest'], $q['nonce'], $q['cnonce'], $error);
	if ($sid !== false) {
?>
<!DOCTYPE html>
<html>
<head>
	<title>Logining...</title>
</head>
<body onload="document.forms[0].submit();">
	<form method="post" action="index.php">
		<input type="hidden" name="sid" value="<?=_h($sid)?>">
	</form>
</body>
</html>
<?php
		exit(1);
	}
	$t_msg = 'User name or password is wrong.';
}




?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>User Authentication</title>
<link rel="stylesheet" href="css/sanitize.min.css">
<link rel="stylesheet" href="css/style.min.css">
<script src="js/jssha/sha256.js"></script>
<script src="js/login.js"></script>
<script>document.addEventListener('DOMContentLoaded', function () {initLogin('loginBtn');});console.log('<?=$error?>');</script>
</head>
<body class="login">
<div class="frame login-frame">
	<h1>Topics Management Page</h1>
	<h2>User Authentication</h2>
	<form action="login.php" method="post">
		<dl>
			<dt>User Name:</dt><dd><input type="text" name="user" id="user"></dd>
			<dt>Password:</dt><dd><input type="password" id="pw"></dd>
		</dl>
		<input type="hidden" name="realm" id="realm" value="<?=_h($t_realm)?>">
		<input type="hidden" name="nonce" id="nonce" value="<?=_h($t_nonce)?>">
		<input type="hidden" name="url" id="url" value="<?=_h($t_url)?>">
		<input type="hidden" name="cnonce" id="cnonce">
		<input type="hidden" name="digest" id="digest">
		<p><?=_h($t_msg)?></p>
		<div><a class="btn" href="../">Back To Index</a><button type="submit" id="loginBtn">Login</button></div>
	</form>
</div>
</body>
</html>
