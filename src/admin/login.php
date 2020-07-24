<?php
namespace nt;
/**
 *
 * Login
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @version 2020-07-25
 *
 */


require_once( __DIR__ . '/handler-login.php' );
$view = handle_query( $_POST );


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
<title><?= _ht( 'User Authentication' ) ?> - Newtrino</title>
</head>
<body class="login">

<?php \nt\begin(); ?>
<div class="frame frame-login">
	<h1>Newtrino</h1>
	<form action="login.php" method="post">
		<dl>
			<dt><?= _ht( 'Username:' ) ?></dt><dd><input type="text" name="user" id="user"></dd>
			<dt><?= _ht( 'Password:' ) ?></dt><dd><input type="password" id="pw"></dd>
		</dl>
		<input type="hidden" name="realm" id="realm" value="{{realm}}">
		<input type="hidden" name="nonce" id="nonce" value="{{nonce}}">
		<input type="hidden" name="url" id="url" value="{{url}}">
		<input type="hidden" name="cnonce" id="cnonce">
		<input type="hidden" name="digest" id="digest">
{{#is_login_failed}}
		<p><?= _ht( 'Username or password is wrong.' ) ?></p>
{{/is_login_failed}}
		<nav>
			<button type="submit" id="btn-login" class="accent"><?= _ht( 'Log In' ) ?></button>
		</nav>
	</form>
	<div id="key"></div>
</div>
<script>console.log('{{error_message}}');</script>
<?php \nt\end( $view ); ?>

</body>
</html>
