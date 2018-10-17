<?php
namespace nt;
/**
 *
 * Edit
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @version 2018-10-16
 *
 */


require_once('admin_init.php');


$mode = $q['mode'];
$t_msg = '';
if ($mode === 'update') {
	$p = $store->getPost($q['id']);
	$p->assign($q);
	$t_p = $store->writePost($p);
	$t_msg = 'Update Complete';
} else if ($mode === 'new') {
	$t_p = $store->createNewPost();
	$session->addTempPostId($t_p->getId());
	$t_p->setPublishedDate('now');
} else {
	$t_p = $store->getPost($q['id']);
}
$t_sid      = $q['sid'];
$t_ppp      = $q['posts_per_page'];
$t_cat      = $q['cat'];
$t_date     = $q['date'];
$t_date_bgn = $q['date_bgn'];
$t_date_end = $q['date_end'];

$t_page = $q['page'];
$t_cats = $store->getCategoryData($t_p->getCategory());
header('Content-Type: text/html;charset=utf-8');




?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Post Edit</title>
<link rel="stylesheet" href="css/style.min.css">
<link rel="stylesheet" href="css/flatpickr/flatpickr.min.css">
<script src="js/flatpickr/flatpickr.min.js"></script>
<script src="js/flatpickr/ja.js"></script>
<script src="js/tinymce/tinymce.min.js"></script>
<script src="js/newtrino.js"></script>
<script>document.addEventListener('DOMContentLoaded', function () {initEdit();});</script>
</head>
<body class="edit">
<div id="dialog-placeholder"></div>
<div class="container container-edit">
	<div class="header-row">
		<h1>Topics Management</h1>
		<a class="btn" href="#" id="show-list" onClick="showList();">Post List</a>
		<a class="btn" href="#" id="show-post" onClick="showPost();">Show Post</a>
	</div>
	<h2>Post Edit  <span id="update-msg"><?=_h($t_msg)?></span></h2>
	<form name="form-post" id="form-post" action="edit.php" method="post">
		<div class="column">
			<div class="column-main">
				<div class="form-post">
				<!-- <form name="form-post" id="form-post" action="edit.php" method="post"> -->
					<input type="hidden" name="mode" id="mode" value="update">
					<input type="hidden" name="sid" id="sid" value="<?=_h($t_sid)?>">
					<input type="hidden" name="id" id="id" value="<?=_h($t_p->getId())?>">
					<input type="hidden" name="page" id="page" value="<?=_h($t_page)?>">
					<input type="hidden" name="posts_per_page" id="posts_per_page" value="<?=_h($t_ppp)?>">
					<input type="hidden" name="cat" id="cat" value="<?=_h($t_cat)?>">
					<input type="hidden" name="date" id="date" value="<?=_h($t_date)?>">
					<input type="hidden" name="date_bgn" id="date_bgn" value="<?=_h($t_date_bgn)?>">
					<input type="hidden" name="date_end" id="date_end" value="<?=_h($t_date_end)?>">

					<input placeholder="Enter Title Here" type="text" name="post_title" id="post_title" value="<?=_h($t_p->getTitle())?>">
					<div class="btn-row"><a class="btn" href="#" id="upload" onClick="showMediaChooser();">Add Media</a></div>
					<textarea name="post_content" id="post_content"><?=_h($t_p->getContent())?></textarea>
				<!-- </form> -->
				</div>
			</div>
			<div class="column-sub">
				<div class="frame">
					<h3>Publish</h3>
					<input form="form-post" type="text" name="post_published_date" id="post_published_date" value="<?=_h($t_p->getPublishedDateTime())?>">
					<div class="btn-row">
						<select form="form-post" name="post_state" id="post_state">
							<option id="post_state_published" value="published"<?php if ($t_p->isPublished()) {_eh(' selected');} ?>>Published</option>
							<option id="post_state_reserved" value="reserved"<?php if ($t_p->isReserved()) {_eh(' selected');} ?>>Reservation</option>
							<option id="post_state_draft" value="draft"<?php if ($t_p->isDraft()) {_eh(' selected');} ?>>Draft</option>
						</select>
					</div>
					<div>
						<a class="btn" href="#" onClick="showPreview();">Preview</a>
						<a class="btn btn-update" href="#" id="update" onClick="update();">Update</a>
					</div>
					<p id="message"></p>
				</div>
				<div class="frame">
					<h3>Category</h3>
					<select form="form-post" name="post_cat" id="post_cat">
<?php foreach($t_cats as $c): ?>
						<option value="<?=_h($c['slug'])?>"<?php if ($c['cur']) _eh(' selected');?>><?=_h($c['name'])?></option>
<?php endforeach; ?>
					</select>
				</div>
				<div class="frame" id="frame-event-duration">
					<h3>Event Duration</h3>
					<p class="flatpickr">
						<input form="form-post" type="text" name="event_date_bgn" id="event_date_bgn" value="<?=_h($t_p->getEventDateBgn())?>" data-input>
						<a class="input-button" data-clear></a>
					</p>
					<div class="to"> - </div>
					<p class="flatpickr">
						<input form="form-post" type="text" name="event_date_end" id="event_date_end" value="<?=_h($t_p->getEventDateEnd())?>" data-input>
						<a class="input-button" data-clear></a>
					</p>
				</div>
			</div>
		</div>
	</form>
</div>
</body>
</html>
