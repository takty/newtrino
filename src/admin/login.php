<?php
/**
 * Login
 *
 * @author Takuto Yanagida
 * @version 2023-06-22
 */

namespace nt;

require_once( __DIR__ . '/handler-login.php' );
$view = handle_query_login( $_POST, $_GET );

nocache_headers();
header( 'Content-Type: text/html;charset=utf-8' );
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="icon" type="image/png" href="css/logo.png">
<link rel="apple-touch-icon" type="image/png" href="css/logo-180x180.png">
<link rel="stylesheet" href="css/reset.min.css">
<link rel="stylesheet" href="<?php tqs( __DIR__, 'css/style.min.css' ); ?>">
<script src="js/jssha/sha256.js"></script>
<script src="<?php tqs( __DIR__, 'js/login.min.js' ); ?>"></script>
<title><?= _ht( 'Log In' ); ?> - Newtrino</title>
</head>
<body class="login">

<?php \nt\begin( $view ); ?>
<div class="site{{#is_dialog}} dialog{{/is_dialog}}">
	<div class="frame login">
		<h1 class="site-title">Newtrino</h1>

		<form action="login.php" method="post" class="log{{#ntc_reg}} hidden{{/ntc_reg}}">
			<dl>
				<dt><?= _ht( 'User Name:' ); ?></dt><dd><input type="text" name="user"></dd>
				<dt><?= _ht( 'Password:' ); ?></dt><dd><input type="password"></dd>
			</dl>

			<input type="hidden" name="mode" value="login">
			<input type="hidden" name="cnonce">
			<input type="hidden" name="digest">
			<input type="hidden" name="token" value="{{token}}">
{{#is_dialog}}
			<input type="hidden" name="dialog" value="">
{{/is_dialog}}
{{#ntc_log}}
			<p class="notice">{{.}}</p>
{{/ntc_log}}

			<nav>
				<button type="submit" class="accent"><?= _ht( 'Log In' ); ?></button>
			</nav>

			<details class="cookie">
				<summary><?= _ht( 'Use of Cookies...' ); ?></summary>
				<div><?= _ht( 'Newtrino admin screen uses a cookie to prevent unauthorized access and ensure security. If cookies are blocked, you cannot log in. Note that cookies do not contain personal information.' ); ?></div>
			</details>
		</form>

{{^is_dialog}}
		<form action="login.php" method="post" class="reg{{^ntc_reg}} hidden{{/ntc_reg}}">
			<dl>
				<dt><?= _ht( 'Invitation Code:' ); ?></dt><dd><input type="text" name="code" invalid required></dd>
				<dt><?= _ht( 'New User Name:' ); ?></dt><dd><input type="text" name="user" invalid required></dd>
				<dt><?= _ht( 'New Password:' ); ?></dt><dd><input type="password" autocomplete="new-password" invalid required></dd>
			</dl>

			<input type="hidden" name="mode" value="register">
			<input type="hidden" name="hash">
			<input type="hidden" name="token" value="{{token}}">
{{#ntc_reg}}
			<p class="notice">{{.}}</p>
{{/ntc_reg}}

			<nav>
				<button type="submit" class="accent" disabled><?= _ht( 'Register' ); ?></button>
			</nav>
		</form>
{{/is_dialog}}
	</div>
</div>
<?php \nt\end(); ?>

<?php \nt\begin( $view ); ?>
<input type="hidden" id="key" value="{{key}}">
<input type="hidden" id="nonce" value="{{nonce}}">
<input type="hidden" id="url" value="{{url}}">
<input type="hidden" id="ntc-issue" value="<?= _ht( "Do you want to issue an invitation code?\n(You must enter an existing user name and password.)" ); ?>">
<?php \nt\end(); ?>

</body>
</html>
