<?php
namespace nt;
/**
 *
 * Login
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @version 2020-07-07
 *
 */


define( 'NT_ADMIN', true );

require_once( __DIR__ . '/../core/index.php' );
require_once( __DIR__ . '/class-session.php' );

set_locale_setting();

$nt_config  = load_config( NT_DIR_DATA );
$nt_res     = load_resource( NT_DIR_ADMIN, $nt_config['lang'] );
$nt_session = new Session( NT_URL_ADMIN, NT_DIR_DATA, NT_DIR_SESSION );
$success    = true;
$error      = '';

if ( ! empty( $_POST['digest'] ) ) {
	$success = $nt_session->login( $_POST, $error );
	if ( $success ) {
		header( 'Location: ' . NT_URL_ADMIN . 'index.php' );
		exit();
	}
} else {
	$nt_session->logout();
}


header( 'Content-Type: text/html;charset=utf-8' );
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= _ht( 'User Authentication' ) ?> - Newtrino</title>
<link rel="stylesheet" href="css/style.min.css">
<script src="js/jssha/sha256.js"></script>
<script src="js/login.min.js"></script>
<script>console.log('<?= $error ?>');</script>
</head>
<body class="login">
<div class="login-frame">
	<h1>Newtrino</h1>
	<form action="login.php" method="post">
		<dl>
			<dt><?= _ht( 'User Name:' ) ?></dt><dd><input type="text" name="user" id="user"></dd>
			<dt><?= _ht( 'Password:' ) ?></dt><dd><input type="password" id="pw"></dd>
		</dl>
		<input type="hidden" name="realm" id="realm" value="<?= _h( 'newtrino' ) ?>">
		<input type="hidden" name="nonce" id="nonce" value="<?= _h( Session::getNonce() ) ?>">
		<input type="hidden" name="url" id="url" value="<?= _h( NT_URL_ADMIN ) ?>">
		<input type="hidden" name="cnonce" id="cnonce">
		<input type="hidden" name="digest" id="digest">
<?php if ( ! $success ) : ?>
		<p><?= _ht( 'User name or password is wrong.' ) ?></p>
<?php endif; ?>
		<nav>
			<button type="submit" id="loginBtn"><?= _ht( 'Login' ) ?></button>
		</nav>
	</form>
</div>
</body>
</html>
