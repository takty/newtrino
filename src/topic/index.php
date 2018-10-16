<?php
const NT_POSTS_PER_PAGE = 10;
require_once(__DIR__ . '/system/init.php');
global $nt_posts;


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
		<a href="../"><h1>Newtrino Sample Website</h1></a>
	</header>
	<main>
		<ul>
<?php foreach($nt_posts as $p): ?>
			<li <?php if ($p->isNewItem()) \nt\_eh(' class="new"')?>>
				<span class="date"><?=\nt\_h($p->getPublishedDate())?></span>
				<a href="<?=\nt\_h($p->getPermalink('view.php'))?>"><?=\nt\_h($p->getTitle())?></a>
				<span class="cat<?php if ($p->getCategory() === 'event') \nt\_eh(' ' . $p->getEventState()); ?>"><?=\nt\_h($p->getCategoryName())?></span>
			</li>
<?php endforeach ?>
		</ul>
<?php \nt\the_pagination(); ?>
<?php \nt\the_filter(); ?>
	</main>
</body>
</html>
