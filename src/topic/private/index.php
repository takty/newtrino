<?php
namespace nt;
/**
 *
 * Index
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @version 2023-06-22
 *
 */


require_once(__DIR__ . '/init-private.php');


if ($nt_q['mode'] === 'delete') {
	$nt_store->delete($nt_q['id']);
}
$ppp = $nt_q['posts_per_page'];
$ret = $nt_store->getPostsByPage($nt_q['page'] - 1, $ppp, ['cat' => $nt_q['cat'], 'date' => $nt_q['date'], 'date_bgn' => $nt_q['date_bgn'], 'date_end' => $nt_q['date_end'], 'published_only' => false]);
$t_posts = $ret['posts'];
$page = $ret['page'] + 1;

$t_pgs = []; $t_pg_prev = false; $t_pg_next = false;
if ($ppp < $ret['size']) {
	$maxPage = ceil($ret['size'] / $ppp);
	for ($i = 1; $i <= $maxPage; $i += 1) {
		$t_pgs[] = ['page' => $i, 'index' => ($i === $page) ? false : $i];
	}
	if ($page > 1) $t_pg_prev = $page - 1;
	if ($page < $maxPage) $t_pg_next = $page + 1;
}
$t_ppp      = $nt_q['posts_per_page'];
$t_cat      = $nt_q['cat'];
$t_date     = $nt_q['date'];
$t_date_bgn = $nt_q['date_bgn'];
$t_date_end = $nt_q['date_end'];

$t_cats = $nt_store->getCategoryData($nt_q['cat']);
$t_page = $page;


header('Content-Type: text/html;charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= _ht('Post List') ?> - Newtrino</title>
<link rel="stylesheet" href="css/style.min.css">
<link rel="stylesheet" href="css/flatpickr/flatpickr.min.css">
<script src="js/flatpickr/flatpickr.min.js"></script>
<script src="js/flatpickr/ja.js"></script>
<script src="js/index.min.js"></script>
<script>document.addEventListener('DOMContentLoaded', function () {initIndex();});</script>
</head>
<body class='list'>
<div class="container">
	<div class="header-row">
		<h1>Newtrino</h1>
		<a href="login.php" class="btn"><?= _ht('Log Out') ?></a>
	</div>
	<h2><?= _ht('Post List') ?></h2>
	<div class="list-ops nav">
		<div class="filter-column">
			<div class="filter-item">
				<h3><?= _ht('Display Period') ?></h3>
				<div class="period-filter">
					<p class="flatpickr"><input type="text" id="fp-date-bgn" size="12" value="" data-input><a class="input-button" data-clear></a></p>
					<span>-</span>
					<p class="flatpickr"><input type="text" id="fp-date-end" size="12" value="" data-input><a class="input-button" data-clear></a></p>
					<button type="button" onclick="changeDateRange();"><?= _ht('Filter') ?></button>
				</div>
			</div>
		</div>
		<div class="filter-column">
			<div class="filter-item">
				<h3><?= _ht('Category') ?></h3>
				<select onchange="changeCategory(this.value);">
					<option value=""><?= _ht('All') ?></option>
<?php foreach($t_cats as $c): ?>
						<option value="<?= _h($c['slug']) ?>"<?php if ($c['cur']) _eh(' selected') ?>><?= _ht($c['name'], 'category') ?></option>
<?php endforeach; ?>
				</select>
			</div>
			<div class="filter-item">
				<h3><?= _ht('View Count') ?></h3>
				<select id="ppp" onchange="changePpp(this.value);">
					<option value="10">10</option>
					<option value="20">20</option>
					<option value="50">50</option>
					<option value="100">100</option>
				</select>
			</div>
			<div class="filter-item">
				<a class="btn btn-new" href="#" onclick="newPost();"><?= _ht("New Post") ?></a>
			</div>
		</div>
	</div>
	<table class="list">
		<tr><th><?= _ht('State') ?></th><th><?= _ht('Date') ?></th><th><?= _ht('Title') ?></th><th><?= _ht('Category') ?></th><th><?= _ht('Updated') ?></th><th></th></tr>
<?php foreach($t_posts as $p): ?>
		<tr>
			<td>
				<select onchange="setPostState(<?= _h($p->getId()) ?>, this.value);">
<?php if ($p->canPublished()): ?>
					<option value="published"<?php if ($p->isPublished()) _eh(' selected'); ?>><?= _ht('Published') ?></option>
<?php else: ?>
					<option value="reserved"<?php if ($p->isReserved()) _eh(' selected'); ?>><?= _ht('Reserved') ?></option>
<?php endif ?>
					<option value="draft"<?php if ($p->isDraft()) _eh(' selected'); ?>><?= _ht('Draft') ?></option>
				</select>
			</td>
			<td><a href="#" onclick="editPost(<?= _h($p->getId()) ?>);"><?= _h($p->getPublishedDate()) ?></a></td>
			<td><a href="#" onclick="editPost(<?= _h($p->getId()) ?>);"><?= _h($p->getTitle()) ?></a></td>
<?php if ($p->getCategory() === 'event'): ?>
			<td class="category"><div><?= _ht($p->getCategoryName(), 'category') ?></div> <span><?= _h($p->getEventDateBgn()) ?></span> <span>- <?= _h($p->getEventDateEnd()) ?></span></td>
<?php else: ?>
			<td class="category"><div><?= _ht($p->getCategoryName(), 'category') ?></div></td>
<?php endif ?>
			<td class="mod-date-time"><?= implode('', array_map(function ($e) {return '<span>'._h($e).'</span> ';}, explode(' ', $p->getModifiedDateTime()))) ?></td>
			<td><a class="btn btn-delete" href="#" onClick="deletePost(<?= _h($p->getId()) ?>, '<?= _h($p->getPublishedDate()) ?>','<?= _h($p->getTitle()) ?>');"><?= _ht('Delete') ?></a></td>
		</tr>
<?php endforeach ?>
	</table>
	<nav>
		<ul class="pagination-nav">
<?php if ($t_pg_prev): ?>
				<li><a href="#" onClick="submitPage(<?= _h($t_pg_prev) ?>);"><?= _ht('Next') ?></a></li>
<?php endif ?>
<?php foreach($t_pgs as $pg): ?>
				<li><?php wrap($pg['index'], '<a href="#" onclick="submitPage(' . $pg['page'] . ')">', $pg['page'], '</a>') ?></li>
<?php endforeach ?>
<?php if ($t_pg_next): ?>
				<li><a href="#" onClick="submitPage(<?= _h($t_pg_next) ?>);"><?= _ht('Previous') ?></a></li>
<?php endif ?>
		</ul>
	</nav>
	<form name="form" id="form" action="" method="post">
		<input type="hidden" name="mode" id="mode" value="">
		<input type="hidden" name="id" id="id" value="">
		<input type="hidden" name="page" id="page" value="<?= _h($t_page) ?>">
		<input type="hidden" name="posts_per_page" id="posts_per_page" value="<?= _h($t_ppp) ?>">
		<input type="hidden" name="cat" id="cat" value="<?= _h($t_cat) ?>">
		<input type="hidden" name="date" id="date" value="<?= _h($t_date) ?>">
		<input type="hidden" name="date_bgn" id="date_bgn" value="<?= _h($t_date_bgn) ?>">
		<input type="hidden" name="date_end" id="date_end" value="<?= _h($t_date_end) ?>">
	</form>
</div>
</body>
</html>
