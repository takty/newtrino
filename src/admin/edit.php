<?php
namespace nt;
/**
 *
 * Edit
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @version 2020-06-28
 *
 */


require_once(__DIR__ . '/init-private.php');


$mode = $nt_q['mode'];
$t_msg = '';
if ($mode === 'update') {
	$p = $nt_store->getPost($nt_q['id']);
	$p->assign($nt_q);
	$t_p = $nt_store->writePost($p);
	$t_msg = _ht('Update Complete');
} else if ($mode === 'new') {
	$t_p = $nt_store->createNewPost();
	$nt_session->addTempPostId($t_p->getId());
	$t_p->setDate( 'now' );
} else {
	$t_p = $nt_store->getPost($nt_q['id']);
}
$t_ppp      = $nt_q['posts_per_page'];
$t_cat      = $nt_q['cat'];
$t_date     = $nt_q['date'];
$t_date_bgn = $nt_q['date_bgn'];
$t_date_end = $nt_q['date_end'];

$t_page = $nt_q['page'];


function echo_taxonomy_metabox( $tax_slug, $post ) {
	global $nt_store;
	$tax = $nt_store->taxonomy()->getTaxonomy( $tax_slug );
	$tss = $post->getTermSlugs( $tax_slug );
	$ts  = $nt_store->taxonomy()->getTermAll( $tax_slug, $tss );
?>
	<div class="frame">
		<h3><?= _h( $tax['label'] ) ?></h3>
		<select form="form-post" name="taxonomy:<?= $tax_slug ?>[]" id="taxonomy:<?= $tax_slug ?>">
<?php foreach( $ts as $t ): ?>
			<option value="<?= _h( $t['slug'] ) ?>"<?php if ( $t['is_current'] ) _eh( ' selected' ); ?>><?= _h( $t['label'] ) ?></option>
<?php endforeach; ?>
		</select>
	</div>
<?php
}


header('Content-Type: text/html;charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= _ht('Post Edit') ?> - Newtrino</title>
<link rel="stylesheet" href="css/style.min.css">
<link rel="stylesheet" href="css/flatpickr/flatpickr.min.css">
<script src="js/flatpickr/flatpickr.min.js"></script>
<script src="js/flatpickr/ja.js"></script>
<script src="js/tinymce/tinymce.min.js"></script>
<script src="js/edit.min.js"></script>
<script>document.addEventListener('DOMContentLoaded', function () {initEdit();});</script>
</head>
<body class="edit">
<div id="dialog-placeholder"></div>
<div class="container container-edit">
	<div class="header-row">
		<h1>Newtrino</h1>
		<a class="btn" href="#" id="show-list"><?= _ht('Post List') ?></a>
		<a class="btn" href="#" id="show-post"><?= _ht('Show Post') ?></a>
	</div>
	<h2><?= _ht('Post Edit') ?>  <span id="update-msg"><?= _h($t_msg) ?></span></h2>
	<form name="form-post" id="form-post" action="edit.php" method="post">
		<div class="column">
			<div class="column-main">
				<div class="form-post">
					<input type="hidden" name="mode" id="mode" value="update">
					<input type="hidden" name="id" id="id" value="<?= _h($t_p->getId()) ?>">
					<input type="hidden" name="page" id="page" value="<?= _h($t_page) ?>">
					<input type="hidden" name="posts_per_page" id="posts_per_page" value="<?= _h($t_ppp) ?>">
					<input type="hidden" name="cat" id="cat" value="<?= _h($t_cat) ?>">
					<input type="hidden" name="date" id="date" value="<?= _h($t_date) ?>">
					<input type="hidden" name="date_bgn" id="date_bgn" value="<?= _h($t_date_bgn) ?>">
					<input type="hidden" name="date_end" id="date_end" value="<?= _h($t_date_end) ?>">

					<input placeholder="<?= _ht('Enter Title Here') ?>" type="text" name="post_title" id="post_title" value="<?= _h($t_p->getTitle()) ?>">
					<div class="btn-row"><button class="btn" id="show-media-chooser" type="button"><?= _ht('Insert Media') ?></button></div>
					<textarea name="post_content" id="post_content"><?= _h($t_p->getContent()) ?></textarea>
				</div>
			</div>
			<div class="column-sub">
				<div class="frame">
					<h3><?= _ht('Publish') ?></h3>
					<input form="form-post" type="text" name="post_date" id="post_date" value="<?= _h($t_p->getDate()) ?>">
					<div class="btn-row">
						<select form="form-post" name="post_status" id="post_status">
							<option id="post_status_published" value="published"<?php if ($t_p->isPublished()) {_eh(' selected');} ?>><?= _ht('Published') ?></option>
							<option id="post_status_reserved" value="reserved"<?php if ($t_p->isReserved()) {_eh(' selected');} ?>><?= _ht('Reserved') ?></option>
							<option id="post_status_draft" value="draft"<?php if ($t_p->isDraft()) {_eh(' selected');} ?>><?= _ht('Draft') ?></option>
						</select>
					</div>
					<div>
						<button class="btn" id="show-preview" type="button"><?= _ht('Preview') ?></button>
						<button class="btn btn-update" id="update" type="button"><?= _ht('Update') ?></button>
					</div>
					<p class="message" id="message_enter_title"><?= _ht('The title is blank.') ?></p>
				</div>
				<?php echo_taxonomy_metabox( 'category', $t_p ); ?>
				<div class="frame" id="frame-event-duration">
					<h3><?= _ht('Event Duration') ?></h3>
					<p class="flatpickr" id="event_date_bgn_wrap">
						<input form="form-post" type="text" name="date_bgn" id="event_date_bgn" value="<?= _h($t_p->getEventDateBgn()) ?>" data-input>
						<a class="input-button" data-clear></a>
					</p>
					<div class="to"> - </div>
					<p class="flatpickr" id="event_date_end_wrap">
						<input form="form-post" type="text" name="date_end" id="event_date_end" value="<?= _h($t_p->getEventDateEnd()) ?>" data-input>
						<a class="input-button" data-clear></a>
					</p>
				</div>
			</div>
		</div>
	</form>
</div>
</body>
</html>
