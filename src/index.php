<?php
define('NT_LANG', 'ja');
require_once(__DIR__ . '/topic/core/init.php');


header('Content-Type: text/html;charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Newtrino Sample Website</title>
</head>
<body>
	<header>
		<h1>Newtrino Sample Website</h1>
	</header>
	<main>
		<ul><?php \nt\the_recent(6); ?></ul>
		<nav>
			<a href="topic/">Show More...</a>
		</nav>
	</main>
</body>
</html>
