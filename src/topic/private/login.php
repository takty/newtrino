<?php
namespace nt;
/**
 *
 * Login
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @version 2018-10-18
 *
 */


if (!defined('NT_LANG')) define('NT_LANG', 'ja');

require_once(__DIR__ . '/../core/function.php');
require_once(__DIR__ . '/class-session.php');


define('SERVER_HOST_URL', (empty($_SERVER['HTTPS']) ? 'http://' : 'https://') . $_SERVER['HTTP_HOST']);

setLocaleSetting();
loadResource();

$nt_q = prepareDefaultQuery();

$t_url   = SERVER_HOST_URL . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . '/';
$t_realm = 'newtrino';
$t_nonce = Session::getNonce();
$t_msg   = '';
$error   = '';

header('Content-Type: text/html;charset=utf-8');

if (!empty($nt_q['digest'])) {
	$nt_session = new Session();
	$sid = $nt_session->login($nt_q['user'], $nt_q['digest'], $nt_q['nonce'], $nt_q['cnonce'], $error);
	if ($sid !== false) {
?>
<!DOCTYPE html>
<html>
<head>
	<title><?= _ht('Logining...', 'admin') ?></title>
</head>
<body onload="document.forms[0].submit();">
	<form method="post" action="index.php">
		<input type="hidden" name="sid" value="<?= _h($sid) ?>">
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
<title><?= _h('User Authentication') ?></title>
<link rel="stylesheet" href="css/style.min.css">
<script src="js/jssha/sha256.js"></script>
<script src="js/login.min.js"></script>
<script>document.addEventListener('DOMContentLoaded', function () {initLogin();});console.log('<?=$error?>');</script>
</head>
<body class="login">
<div class="frame login-frame">
	<h1><?= _ht('Newtrino Management Page', 'admin') ?></h1>
	<h2><?= _ht('User Authentication', 'admin') ?></h2>
	<form action="login.php" method="post">
		<dl>
			<dt><?= _ht('User Name: ', 'admin') ?></dt><dd><input type="text" name="user" id="user"></dd>
			<dt><?= _ht('Password: ', 'admin') ?></dt><dd><input type="password" id="pw"></dd>
		</dl>
		<input type="hidden" name="realm" id="realm" value="<?= _h($t_realm) ?>">
		<input type="hidden" name="nonce" id="nonce" value="<?= _h($t_nonce) ?>">
		<input type="hidden" name="url" id="url" value="<?= _h($t_url) ?>">
		<input type="hidden" name="cnonce" id="cnonce">
		<input type="hidden" name="digest" id="digest">
		<p><?= _ht($t_msg, 'admin') ?></p>
		<div><a class="btn" href="../"><?= _ht('Back To Top Page', 'admin') ?></a><button type="submit" id="loginBtn"><?= _ht('Login', 'admin') ?></button></div>
	</form>
</div>
</body>
</html>
