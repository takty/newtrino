<?php
/**
 * Login
 *
 * @author Takuto Yanagida
 * @version 2021-06-28
 */

namespace nt;

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
<link rel="apple-touch-icon" type="image/png" href="css/logo-180x180.png">
<link rel="icon" type="image/png" href="css/logo.png">
<script src="js/jssha/sha256.js"></script>
<script src="js/login.min.js"></script>
<title><?= _ht( 'Log In' ) ?> - Newtrino</title>
</head>
<?php \nt\begin(); ?>
<body class="login{{#is_dialog}} dialog{{/is_dialog}}">

<div class="frame frame-login">
	<h1>Newtrino</h1>
	<form action="login.php" method="post" class="log{{#msg_reg}} hidden{{/msg_reg}}">
		<dl>
			<dt><?= _ht( 'User Name:' ) ?></dt><dd><input type="text" name="user" id="user"></dd>
			<dt><?= _ht( 'Password:' ) ?></dt><dd><input type="password" id="pw"></dd>
		</dl>
		<input type="hidden" name="cnonce" id="cnonce">
		<input type="hidden" name="digest" id="digest">
		<input type="hidden" name="mode" id="mode" value="login">
		<input type="hidden" name="token" value="{{token}}">
{{#is_dialog}}
		<input type="hidden" name="dialog" value="">
{{/is_dialog}}
{{#msg_log}}
		<p id="msg-log">{{.}}</p>
{{/msg_log}}
		<nav>
			<button type="submit" id="btn-log" class="accent"><?= _ht( 'Log In' ) ?></button>
		</nav>
		<details class="cookie"><summary><?= _ht( 'Use of Cookies...' ) ?></summary><div><?= _ht( 'Newtrino admin screen uses a cookie to prevent unauthorized access and ensure security. If cookies are blocked, you cannot log in. Note that cookies do not contain personal information.' ) ?></div></details>
	</form>
{{^is_dialog}}
	<form action="login.php" method="post" class="reg{{^msg_reg}} hidden{{/msg_reg}}">
		<dl>
			<dt><?= _ht( 'Invitation Code:' ) ?></dt><dd><input type="text" id="code" name="code" invalid required></dd>
			<dt><?= _ht( 'New User Name:' ) ?></dt><dd><input type="text" id="new-user" name="user" invalid required></dd>
			<dt><?= _ht( 'New Password:' ) ?></dt><dd><input type="password" id="new-pw" autocomplete="new-password" invalid required></dd>
		</dl>
		<input type="hidden" name="hash" id="hash">
		<input type="hidden" name="mode" value="register">
		<input type="hidden" name="token" value="{{token}}">
{{#msg_reg}}
		<p id="msg-reg">{{.}}</p>
{{/msg_reg}}
		<nav>
			<button type="submit" id="btn-reg" class="accent" disabled><?= _ht( 'Register' ) ?></button>
		</nav>
	</form>
{{/is_dialog}}
</div>

<input type="hidden" id="key" value="{{key}}">
<input type="hidden" id="nonce" value="{{nonce}}">
<input type="hidden" id="url" value="{{url}}">
<input type="hidden" id="msg-issue" value="<?= _ht( "Do you want to issue an invitation code?\n(You must enter an existing user name and password.)" ) ?>">

</body>
<?php \nt\end( $view ); ?>
</html>
