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


if (!defined('NT_PRIVATE')) define('NT_PRIVATE', true);
if (!defined('NT_LANG')) define('NT_LANG', 'ja');

require_once(__DIR__ . '/../core/define.php');
require_once(__DIR__ . '/../core/function.php');
require_once(__DIR__ . '/class-session.php');

setLocaleSetting();
loadResource();

$nt_q = prepareDefaultQuery();

$success = true;
$error   = '';

header('Content-Type: text/html;charset=utf-8');

if (!empty($nt_q['digest'])) {
	$nt_session = new Session();
	$sid = $nt_session->login($nt_q['user'], $nt_q['digest'], $nt_q['nonce'], $nt_q['cnonce'], $error);
	if ($sid !== false) {
?>
<!DOCTYPE html>
<html>
<head><title><?= _ht('Logining...', 'admin') ?></title></head>
<body onload="document.forms[0].submit();">
<form method="post" action="index.php"><input type="hidden" name="sid" value="<?= _h($sid) ?>"></form>
</body>
</html>
<?php
		exit(1);
	}
	$success = false;
}


?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= _h('User Authentication') ?></title>
<link rel="stylesheet" href="css/style.min.css">
<script src="js/jssha/sha256.js"></script>
<script src="js/login.min.js"></script>
<script>document.addEventListener('DOMContentLoaded', function () {initLogin();});console.log('<?= $error ?>');</script>
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
		<input type="hidden" name="realm" id="realm" value="<?= _h('newtrino') ?>">
		<input type="hidden" name="nonce" id="nonce" value="<?= _h(Session::getNonce()) ?>">
		<input type="hidden" name="url" id="url" value="<?= _h(NT_URL_PRIVATE) ?>">
		<input type="hidden" name="cnonce" id="cnonce">
		<input type="hidden" name="digest" id="digest">
<?php if (!$success): ?>
		<p><?= _ht('User name or password is wrong.', 'admin') ?></p>
<?php endif; ?>
		<div><a class="btn" href="../"><?= _ht('Back To Top Page', 'admin') ?></a><button type="submit" id="loginBtn"><?= _ht('Login', 'admin') ?></button></div>
	</form>
</div>
</body>
</html>
