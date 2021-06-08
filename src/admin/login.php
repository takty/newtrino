<?php
namespace nt;
/**
 *
 * Login
 *
 * @author Takuto Yanagida
 * @version 2021-06-08
 *
 */


require_once( __DIR__ . '/handler-login.php' );
$view = handle_query( $_POST, $_GET );


header( 'Content-Type: text/html;charset=utf-8' );
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="css/reset.min.css">
<link rel="stylesheet" href="css/style.min.css">
<link rel="icon" type="image/png" href="css/logo.png">
<script src="js/jssha/sha256.js"></script>
<script src="js/login.min.js"></script>
<title><?= _ht( 'User Authentication' ) ?> - Newtrino</title>
</head>
<?php \nt\begin(); ?>
<body class="login{{#mode}} {{mode}}{{/mode}}">

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
{{#mode}}
		<input type="hidden" name="mode" value="{{mode}}">
{{/mode}}
{{#is_login_failed}}
		<p><?= _ht( 'Username or password is wrong.' ) ?></p>
{{/is_login_failed}}
		<nav>
			<button type="submit" id="btn-login" class="accent"><?= _ht( 'Log In' ) ?></button>
		</nav>
		<details class="cookie"><summary><?= _ht( 'Use Of Cookies...' ) ?></summary><div><?= _ht( 'Newtrino admin screen uses a cookie to prevent unauthorized access and ensure security. If cookies are blocked, you cannot log in. Note that cookies do not contain personal information.' ) ?></div></details>
	</form>
</div>
<div id="key"></div>
{{#error_message}}
<script>console.log('{{error_message}}');</script>
{{/error_message}}

</body>
<?php \nt\end( $view ); ?>
</html>
