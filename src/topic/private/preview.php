<?php
namespace nt;
/**
 *
 * Preview
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @version 2018-10-18
 *
 */


require_once(__DIR__ . '/init-private.php');


$t_title = $nt_q['post_title'];
$t_pdate = $nt_q['post_published_date'];
$t_cat = $nt_store->categorySlugToName($nt_q['post_cat']);
$t_content = $nt_q['post_content'];


header('Content-Type: text/html;charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= _ht('Preview', 'private') ?></title>
<link rel="stylesheet" media="all" href="css/style.min.css" />
</head>
<body class="preview">
<div class="container">
	<h1><?= _ht('Newtrino Management Page', 'private') ?> - <?= _ht('Preview', 'private') ?></h1>
	<h2><?= _ht('Preview', 'private') ?></h2>
	<main class="topic-post">
		<header>
			<h3><?= _h($t_title) ?></h3>
			<span class="date"><?= _h($t_pdate) ?></span>
			<span class="cat"> [<?= _ht($t_cat, 'category') ?>]</span>
		</header>
		<div class="post-content"><?= $t_content ?></div>
	</main>
</div>
</body>
</html>
