<?php
namespace nt;
/**
 *
 * Preview
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @version 2018-10-17
 *
 */


require_once('admin-init.php');


$t_title = $q['post_title'];
$t_pdate = $q['post_published_date'];
$t_cat = $store->categorySlugToName($q['post_cat']);
$t_content = $q['post_content'];
header('Content-Type: text/html;charset=utf-8');




?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= _ht('Preview', 'admin') ?></title>
<link rel="stylesheet" href="css/sanitize.min.css">
<link rel="stylesheet" media="all" href="css/style.css" />
</head>
<body class="preview">
<div class="container">
	<h1><?= _ht('Newtrino Management Page', 'admin') ?> - <?= _ht('Preview', 'admin') ?></h1>
	<h2><?= _ht('Preview', 'admin') ?></h2>
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
