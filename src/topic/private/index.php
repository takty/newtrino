<?php
namespace nt;
/**
 *
 * Index
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @version 2018-10-18
 *
 */


require_once(__DIR__ . '/init-admin.php');


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
$t_sid      = $nt_q['sid'];
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
<title><?= _ht('Post List', 'admin') ?></title>
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
		<h1><?= _ht('Newtrino Management Page', 'admin') ?></h1>
		<a href="login.php" class="btn"><?= _ht('Log Out', 'admin') ?></a>
	</div>
	<h2><?= _ht('Post List', 'admin') ?></h2>
	<div class="list-ops">
		<nav>
			<h3><?= _ht('Display Period', 'admin') ?></h3>
			<p class="flatpickr">
				<input type="text" id="fp-date-bgn" size="12" value="" data-input><a class="input-button" data-clear></a>
			</p>
			-
			<p class="flatpickr">
				<input type="text" id="fp-date-end" size="12" value="" data-input><a class="input-button" data-clear></a>
			</p>
			<button type="button" onclick="changeDateRange();"><?= _ht('Filter', 'admin') ?></button>
		</nav>
		<nav>
			<h3><?= _ht('Category', 'admin') ?></h3>
			<select onchange="changeCategory(this.value);">
				<option value=""><?= _ht('Select Category', 'admin') ?></option>
<?php foreach($t_cats as $c): ?>
					<option value="<?= _h($c['slug']) ?>"<?php if ($c['cur']) _eh(' selected') ?>><?= _ht($c['name'], 'category') ?></option>
<?php endforeach; ?>
			</select>
		</nav>
		<nav>
			<h3><?= _ht('View Count', 'admin') ?></h3>
			<select id="ppp" onchange="changePpp(this.value);">
				<option value="10">10</option>
				<option value="20">20</option>
				<option value="50">50</option>
				<option value="100">100</option>
			</select>
		</nav>
		<div><a class="btn btn-new" href="#" onclick="newPost();"><?= _ht("New Post", 'admin') ?></a></div>
	</div>
	<table class="list">
		<tr><th><?= _ht('State', 'admin') ?></th><th><?= _ht('Date', 'admin') ?></th><th><?= _ht('Title', 'admin') ?></th><th><?= _ht('Category', 'admin') ?></th><th><?= _ht('Updated', 'admin') ?></th><th></th></tr>
<?php foreach($t_posts as $p): ?>
		<tr>
			<td>
				<select onchange="setPostState(<?= _h($p->getId()) ?>, this.value);">
<?php if ($p->canPublished()): ?>
					<option value="published"<?php if ($p->isPublished()) _eh(' selected'); ?>><?= _ht('Published', 'admin') ?></option>
<?php else: ?>
					<option value="reserved"<?php if ($p->isReserved()) _eh(' selected'); ?>><?= _ht('Reserved', 'admin') ?></option>
<?php endif ?>
					<option value="draft"<?php if ($p->isDraft()) _eh(' selected'); ?>><?= _ht('Draft', 'admin') ?></option>
				</select>
			</td>
			<td><a href="#" onclick="editPost(<?= _h($p->getId()) ?>);"><?= _h($p->getPublishedDate()) ?></a></td>
			<td><a href="#" onclick="editPost(<?= _h($p->getId()) ?>);"><?= _h($p->getTitle()) ?></a></td>
<?php if ($p->getCategory() === 'event'): ?>
			<td><?= _ht($p->getCategoryName(), 'category') ?><br /><?= _h($p->getEventDateBgn()) ?> - <?= _h($p->getEventDateEnd()) ?></td>
<?php else: ?>
			<td><?= _ht($p->getCategoryName(), 'category') ?></td>
<?php endif ?>
			<td><?= _h($p->getModifiedDateTime()) ?></td>
			<td><a class="btn btn-delete" href="#" onClick="deletePost(<?= _h($p->getId()) ?>, '<?= _h($p->getPublishedDate()) ?>','<?= _h($p->getTitle(true)) ?>');"><?= _ht('Delete', 'admin') ?></a></td>
		</tr>
<?php endforeach ?>
	</table>
	<nav>
		<ul class="pagination-nav">
<?php if ($t_pg_prev): ?>
				<li><a href="#" onClick="submitPage(<?= _h($t_pg_prev) ?>);"><?= _ht('Next', 'admin') ?></a></li>
<?php endif ?>
<?php foreach($t_pgs as $pg): ?>
				<li><?php wrap($pg['index'], '<a href="#" onclick="submitPage(' . $pg['page'] . ')">', $pg['page'], '</a>') ?></li>
<?php endforeach ?>
<?php if ($t_pg_next): ?>
				<li><a href="#" onClick="submitPage(<?= _h($t_pg_next) ?>);"><?= _ht('Previous', 'admin') ?></a></li>
<?php endif ?>
		</ul>
	</nav>
	<form name="form" id="form" action="" method="post">
		<input type="hidden" name="mode" id="mode" value="">
		<input type="hidden" name="sid" id="sid" value="<?= _h($t_sid) ?>">
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
