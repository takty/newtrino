<?php
namespace nt;
/**
 *
 * Login
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @version 2020-07-24
 *
 */


require_once( __DIR__ . '/index.php' );


$res = true;
if ( empty( $_POST['digest'] ) ) {
	$nt_session->logout();
} else {
	$res = $nt_session->login( $_POST );
	if ( $res ) {
		header( 'Location: ' . NT_URL_ADMIN . 'list.php' );
		exit;
	}
}


header( 'Content-Type: text/html;charset=utf-8' );
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="css/reset.min.css">
<link rel="stylesheet" href="css/style.min.css">
<script src="js/jssha/sha256.js"></script>
<script src="js/login.min.js"></script>
<script>console.log('<?= $nt_session->getErrorMessage() ?>');</script>
<title><?= _ht( 'User Authentication' ) ?> - Newtrino</title>
</head>
<body class="login">
<div class="frame frame-login">
	<h1>Newtrino</h1>
	<form action="login.php" method="post">
		<dl>
			<dt><?= _ht( 'Username:' ) ?></dt><dd><input type="text" name="user" id="user"></dd>
			<dt><?= _ht( 'Password:' ) ?></dt><dd><input type="password" id="pw"></dd>
		</dl>
		<input type="hidden" name="realm" id="realm" value="<?= _h( $nt_session->getRealm() ) ?>">
		<input type="hidden" name="nonce" id="nonce" value="<?= _h( $nt_session->getNonce() ) ?>">
		<input type="hidden" name="url" id="url" value="<?= _h( $nt_session->getUrl() ) ?>">
		<input type="hidden" name="cnonce" id="cnonce">
		<input type="hidden" name="digest" id="digest">
<?php if ( ! $res ) : ?>
		<p><?= _ht( 'User name or password is wrong.' ) ?></p>
<?php endif; ?>
		<nav>
			<button type="submit" id="btn-login" class="accent"><?= _ht( 'Log In' ) ?></button>
		</nav>
	</form>
	<div id="key"></div>
</div>
</body>
</html>
