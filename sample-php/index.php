<?php
require_once(__DIR__ . '/nt/core/init.php');


header('Content-Type: text/html;charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Newtrino Sample</title>
</head>
<body>
	<header>
		<h1>Newtrino Sample</h1>
	</header>
	<main>
		<ul><?php \nt\the_recent(6, '', false, 'topic/'); ?></ul>
		<nav>
			<a href="topic/">Show More...</a>
		</nav>
	</main>
</body>
</html>
