<?php
namespace nt;
/**
 *
 * Index
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @version 2018-10-16
 *
 */


require_once('_init.php');


if ($q['mode'] === 'delete') {
	$store->delete($q['id']);
}
$ppp = $q['posts_per_page'];
$ret = $store->getPostsByPage($q['page'] - 1, $ppp, ['cat' => $q['cat'], 'date' => $q['date'], 'date_bgn' => $q['date_bgn'], 'date_end' => $q['date_end'], 'published_only' => false]);
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
$t_sid      = $q['sid'];
$t_ppp      = $q['posts_per_page'];
$t_cat      = $q['cat'];
$t_date     = $q['date'];
$t_date_bgn = $q['date_bgn'];
$t_date_end = $q['date_end'];

$t_cats = $store->getCategoryData($q['cat']);
$t_page = $page;
header('Content-Type: text/html;charset=utf-8');




?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Post List</title>
<link rel="stylesheet" href="css/sanitize.min.css">
<link rel="stylesheet" href="css/style.min.css">
<link rel="stylesheet" href="css/flatpickr/flatpickr.min.css">
<script src="js/flatpickr/flatpickr.min.js"></script>
<script src="js/flatpickr/flatpickr.l10n.ja.js"></script>
<script src="js/newtrino.js"></script>
<script>document.addEventListener('DOMContentLoaded', function () {initIndex();});</script>
</head>
<body class='list'>
<div class="container">
	<div class="header-row">
		<h1>Topics Management</h1>
		<a href="login.php" class="btn">Log out</a>
	</div>
	<h2>Post list</h2>
	<div class="list-ops">
		<nav>
			<h3>Display Period</h3>
			<p class="flatpickr">
				<input type="text" id="fp-date-bgn" size="12" value="" data-input><a class="input-button" data-clear></a>
			</p>
			-
			<p class="flatpickr">
				<input type="text" id="fp-date-end" size="12" value="" data-input><a class="input-button" data-clear></a>
			</p>
			<button type="button" onclick="changeDateRange();">Filter</button>
		</nav>
		<nav>
			<h3>Category</h3>
			<select onchange="changeCategory(this.value);">
				<option value="">Select Category</option>
<?php foreach($t_cats as $c): ?>
					<option value="<?=_h($c['slug'])?>"<?php if ($c['cur']) _eh(' selected')?>><?=_h($c['name'])?></option>
<?php endforeach; ?>
			</select>
		</nav>
		<nav>
			<h3>View Count</h3>
			<select id="ppp" onchange="changePpp(this.value);">
				<option value="10">10</option>
				<option value="20">20</option>
				<option value="50">50</option>
				<option value="100">100</option>
			</select>
		</nav>
		<div><a class="btn btn-new" href="#" onclick="newPost();">New Post</a></div>
	</div>
	<table class="list">
		<tr><th>State</th><th>Date</th><th>Title</th><th>Category</th><th>Updated</th><th></th></tr>
<?php foreach($t_posts as $p): ?>
		<tr>
			<td>
				<select onchange="setPostState(<?=_h($p->getId())?>, this.value);">
<?php if ($p->canPublished()): ?>
					<option value="published"<?php if ($p->isPublished()) {_eh(' selected');} ?>>Published</option>
<?php else: ?>
					<option value="reserved"<?php if ($p->isReserved()) {_eh(' selected');} ?>>Reservation</option>
<?php endif ?>
					<option value="draft"<?php if ($p->isDraft()) {_eh(' selected');} ?>>Draft</option>
				</select>
			</td>
			<td><a href="#" onclick="editPost(<?=_h($p->getId())?>);"><?=_h($p->getPublishedDate())?></a></td>
			<td><a href="#" onclick="editPost(<?=_h($p->getId())?>);"><?=_h($p->getTitle())?></a></td>
<?php if ($p->getCategory() === 'event'): ?>
			<td><?=_h($p->getCategoryName())?><br /><?=_h($p->getEventDateBgn())?> - <?=_h($p->getEventDateEnd())?></td>
<?php else: ?>
			<td><?=_h($p->getCategoryName())?></td>
<?php endif ?>
			<td><?=_h($p->getModifiedDateTime())?></td>
			<td><a class="btn btn-delete" href="#" onClick="deletePost(<?=_h($p->getId())?>, '<?=_h($p->getPublishedDate())?>','<?=_h($p->getTitle(true))?>');">Delete</a></td>
		</tr>
<?php endforeach ?>
	</table>
	<nav>
		<ul class="pagination-nav">
<?php if ($t_pg_prev): ?>
				<li><a href="#" onClick="submitPage(<?=_h($t_pg_prev)?>);">Next</a></li>
<?php endif ?>
<?php foreach($t_pgs as $pg): ?>
				<li><?php t_wrap($pg['index'], '<a href="#" onclick="submitPage(' . $pg['page'] . ')">', $pg['page'], '</a>') ?></li>
<?php endforeach ?>
<?php if ($t_pg_next): ?>
				<li><a href="#" onClick="submitPage(<?=_h($t_pg_next)?>);">Previous</a></li>
<?php endif ?>
		</ul>
	</nav>
	<form name="form" id="form" action="" method="post">
		<input type="hidden" name="mode" id="mode" value="">
		<input type="hidden" name="sid" id="sid" value="<?=_h($t_sid)?>">
		<input type="hidden" name="id" id="id" value="">
		<input type="hidden" name="page" id="page" value="<?=_h($t_page)?>">
		<input type="hidden" name="posts_per_page" id="posts_per_page" value="<?=_h($t_ppp)?>">
		<input type="hidden" name="cat" id="cat" value="<?=_h($t_cat)?>">
		<input type="hidden" name="date" id="date" value="<?=_h($t_date)?>">
		<input type="hidden" name="date_bgn" id="date_bgn" value="<?=_h($t_date_bgn)?>">
		<input type="hidden" name="date_end" id="date_end" value="<?=_h($t_date_end)?>">
	</form>
</div>
</body>
</html>
