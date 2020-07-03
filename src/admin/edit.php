<?php
namespace nt;
/**
 *
 * Edit
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @version 2020-07-03
 *
 */


require_once(__DIR__ . '/init-private.php');


$t_msg = '';

switch ( $nt_q['mode'] ) {
	case 'new':
		$t_p = $nt_store->createNewPost();
		$nt_session->addTempPostId( $t_p->getId() );
		$t_p->setDate( 'now' );
		break;
	case 'update':
		$p = $nt_store->getPost( $nt_q['id'] );
		$p->assign( $nt_q, NT_URL_PRIVATE );
		$t_p = $nt_store->writePost( $p );
		$t_msg = _ht( 'Update Complete' );
		break;
	default:
		$t_p = $nt_store->getPost( $nt_q['id'] );
		break;
}


// -----------------------------------------------------------------------------


function echo_state_select( $post ) {
	$s = $post->getStatus();
?>
	<select form="form-post" name="post_status" id="post_status">
		<option id="post-status-published" value="published"<?php if ( $s === 'published' ) _eh( ' selected' ); ?>><?= _ht( 'Published' ) ?></option>
		<option id="post-status-reserved" value="reserved"<?php if ( $s === 'reserved' ) _eh( ' selected' ); ?>><?= _ht( 'Reserved' ) ?></option>
		<option id="post-status-draft" value="draft"<?php if ( $s === 'draft' ) _eh( ' selected' ); ?>><?= _ht( 'Draft' ) ?></option>
	</select>
<?php
}

function echo_taxonomy_metaboxes( $post ) {
	global $nt_store;
	$type = $post->getType();
	$taxes = $nt_store->type()->getTaxonomySlugAll( $type );
	foreach ( $taxes as $tax ) {
		echo_taxonomy_metabox( $tax, $post );
	}
}

function echo_taxonomy_metabox( $tax_slug, $post ) {
	global $nt_store;
	$tax = $nt_store->taxonomy()->getTaxonomy( $tax_slug );
	$is_exclusive = isset( $tax['is_exclusive'] ) && $tax['is_exclusive'] === true;
	$tss = $post->getTermSlugs( $tax_slug );
	$ts  = $nt_store->taxonomy()->getTermAll( $tax_slug, $tss );
?>
	<div class="frame">
		<h3><?= _h( $tax['label'] ) ?></h3>
<?php if ( $is_exclusive ) : ?>
		<select form="form-post" name="taxonomy:<?= $tax_slug ?>[]" id="taxonomy:<?= $tax_slug ?>">
<?php foreach( $ts as $t ): ?>
			<option value="<?= _h( $t['slug'] ) ?>"<?php if ( $t['is_selected'] ) _eh( ' selected' ); ?>><?= _h( $t['label'] ) ?></option>
<?php endforeach; ?>
		</select>
<?php else : ?>
<?php foreach( $ts as $t ): ?>
		<label>
			<input name="taxonomy:<?= $tax_slug ?>[]" type="checkbox" value="<?= _h( $t['slug'] ) ?>"<?php if ( $t['is_selected'] ) _eh( ' checked' ); ?>>
			<?= _h( $t['label'] ) ?>
		</label>
<?php endforeach; ?>
<?php endif; ?>
	</div>
<?php
}

function echo_meta_metaboxes( $post ) {
	global $nt_store;
	$type = $post->getType();
	$ms = $nt_store->type()->getMetaAll( $type );
	foreach ( $ms as $m ) {
		$key   = isset( $m['key'] ) ? $m['key'] : '';
		$type  = isset( $m['type'] ) ? $m['type'] : '';
		$label = isset( $m['label'] ) ? $m['label'] : '';
		if ( empty( $key ) || empty( $type ) || empty( $label ) ) continue;

		switch ( $type ) {
			case 'date-duration':
				echo_meta_duration_metabox( $post, $key, $label );
				break;
		}
	}
}

function echo_meta_duration_metabox( $post, $key, $label ) {
	$m = $post->getMeta();
	if ( ! isset( $m[ $key ] ) ) return;
	$bgn = Post::parseDate( $m[ $key ][0] );
	$end = Post::parseDate( $m[ $key ][1] );
?>
	<div class="frame" id="frame-duration">
		<h3><?= _ht( $label ) ?></h3>
		<p class="flatpickr" id="event_date_bgn_wrap">
			<input form="form-post" type="text" name="meta:<?= _h( $key ) ?>[]" id="event_date_bgn" value="<?= _h( $bgn ) ?>" data-input>
			<a class="input-button" data-clear></a>
		</p>
		<div class="to"> - </div>
		<p class="flatpickr" id="event_date_end_wrap">
			<input form="form-post" type="text" name="meta:<?= _h( $key ) ?>[]" id="event_date_end" value="<?= _h( $end ) ?>" data-input>
			<a class="input-button" data-clear></a>
		</p>
	</div>
<?php
}


// -----------------------------------------------------------------------------


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
					<input type="hidden" name="id" id="id" value="<?= _h( $t_p->getId() ) ?>">

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
						<?php echo_state_select( $t_p ); ?>
					</div>
					<div>
						<button class="btn" id="show-preview" type="button"><?= _ht('Preview') ?></button>
						<button class="btn btn-update" id="update" type="button"><?= _ht('Update') ?></button>
					</div>
					<p class="message" id="message_enter_title"><?= _ht('The title is blank.') ?></p>
				</div>
				<?php echo_taxonomy_metaboxes( $t_p ); ?>
				<?php echo_meta_metaboxes( $t_p ); ?>
			</div>
		</div>
	</form>
</div>
</body>
</html>
