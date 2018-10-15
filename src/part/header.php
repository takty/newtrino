<?php
ini_set( 'display_errors', 1 );
define('INSTALL_DIR', '/newtrino/');
date_default_timezone_set('Asia/Tokyo');
if (!function_exists('_h')) {function _h($str) {return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');}}
if (!function_exists('_eh')) {function _eh($str) {echo htmlspecialchars($str, ENT_QUOTES, 'UTF-8');}}
if (!function_exists('_u')) {function _u($str) {return rawurlencode($str);}}
if (!function_exists('_eu')) {function _eu($str) {echo rawurlencode($str);}}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta http-equiv="x-ua-compatible" content="ie=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="<?=INSTALL_DIR?>css/style.css">
<title><?php if (isset($PAGE_TITLE)) {_eh($PAGE_TITLE.' - ');} ?>Newtrino Sample Website</title>
</head>
<body>
<div class="site">
	<header class="site-header">
		<div class="branding">
			<a href="<?=INSTALL_DIR?>">
				<h1>Newtrino Sample Website</h1>
			</a>
		</div>
	</header>
	<main class="site-main<?php if (isset($PAGE_CLASS)) {_eh(' '.$PAGE_CLASS);} ?>">
