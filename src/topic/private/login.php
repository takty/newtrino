<?php
namespace nt;
/**
 *
 * Login
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @version 2018-10-19
 *
 */


define('NT_PRIVATE', true);

require_once(__DIR__ . '/../core/define.php');
require_once(__DIR__ . '/../core/function.php');
require_once(__DIR__ . '/class-session.php');

set_locale_setting();

$nt_config = load_config(NT_DIR_DATA);
$nt_res    = load_resource(NT_DIR_DATA, $nt_config['language']);
$nt_q      = prepare_query();
$success   = true;
$error     = '';

header('Content-Type: text/html;charset=utf-8');
if (!empty($nt_q['digest'])) {
	$nt_session = new Session(NT_URL_PRIVATE, false, NT_DIR_ACCOUNT, NT_DIR_SESSION);
	$sid = $nt_session->login($nt_q['user'], $nt_q['digest'], $nt_q['nonce'], $nt_q['cnonce'], $error);
	if ($sid !== false) {
?>
<!DOCTYPE html>
<html>
<head><title><?= _ht('Logining...') ?></title></head>
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
<title><?= _ht('User Authentication') ?></title>
<link rel="stylesheet" href="css/style.min.css">
<script src="js/jssha/sha256.js"></script>
<script src="js/login.min.js"></script>
<script>document.addEventListener('DOMContentLoaded', function () {initLogin();});console.log('<?= $error ?>');</script>
</head>
<body class="login">
<div class="login-frame">
	<h1><?= _ht('Newtrino Management Page') ?></h1>
	<h2><?= _ht('User Authentication') ?></h2>
	<form action="login.php" method="post">
		<dl>
			<dt><?= _ht('User Name: ') ?></dt><dd><input type="text" name="user" id="user"></dd>
			<dt><?= _ht('Password: ') ?></dt><dd><input type="password" id="pw"></dd>
		</dl>
		<input type="hidden" name="realm" id="realm" value="<?= _h('newtrino') ?>">
		<input type="hidden" name="nonce" id="nonce" value="<?= _h(Session::getNonce()) ?>">
		<input type="hidden" name="url" id="url" value="<?= _h(NT_URL_PRIVATE) ?>">
		<input type="hidden" name="cnonce" id="cnonce">
		<input type="hidden" name="digest" id="digest">
<?php if (!$success): ?>
		<p><?= _ht('User name or password is wrong.') ?></p>
<?php endif; ?>
		<nav>
			<a href="../"><?= _ht('Back To Top Page') ?></a><button type="submit" id="loginBtn"><?= _ht('Login') ?></button>
		</nav>
	</form>
</div>
</body>
</html>
