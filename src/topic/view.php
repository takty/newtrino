<?php
require_once(__DIR__ . '/system/init.php');
global $nt_post;
if ($nt_post === false) {
	header("Location: {$_SERVER['PHP_SELF']}/../");
	exit(1);
}


header('Content-Type: text/html;charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?=\nt\_h($nt_post->getTitle())?> - Newtrino Sample Website</title>
</head>
<body>
	<header>
		<a href="../"><h1>Newtrino Sample Website</h1></a>
	</header>
	<main>
		<header>
			<div><?=\nt\_h($nt_post->getCategoryName())?></div>
			<div>
				<h2><?=\nt\_h($nt_post->getTitle())?></h2>
			</div>
<?php if ($nt_post->getCategory() === 'event'): ?>
			<div class="event-term <?=\nt\_h($nt_post->getEventState())?>">
				Event Date<?=\nt\_h($nt_post->getEventDateBgn())?> - <?=\nt\_h($nt_post->getEventDateEnd())?>
			</div>
<?php endif ?>
		</header>
		<section><?=$nt_post->getContent()?></section>
		<footer>
			<div class="date">Updated: <?=\nt\_h($nt_post->getPublishedDate())?></div>
		</footer>
<?php \nt\the_postlink(); ?>
<?php \nt\the_filter(); ?>
	</main>
</body>
</html>
