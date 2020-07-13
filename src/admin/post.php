<?php
namespace nt;
/**
 *
 * Post
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @version 2020-07-12
 *
 */


require_once( __DIR__ . '/index.php' );
require_once( __DIR__ . '/metabox.php' );


$lang = $nt_config['lang_admin'];

$t_msg = '';

switch ( $nt_q['mode'] ) {
	case 'new':
		$t_p = $nt_store->createNewPost();
		$nt_session->addTempDir( $nt_store->getPostDir( $t_p->getId() ) );
		$t_p->setDate();
		break;
	case 'update':
		$p = $nt_store->getPost( $nt_q['id'] );
		$p->assign( $nt_q, NT_URL_ADMIN );
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
	<select form="form-post" name="post_status" id="post-status">
		<option id="post-status-published" value="published"<?php if ( $s === 'published' ) _eh( ' selected' ); ?>><?= _ht( 'Published' ) ?></option>
		<option id="post-status-reserved" value="reserved"<?php if ( $s === 'reserved' ) _eh( ' selected' ); ?>><?= _ht( 'Reserved' ) ?></option>
		<option id="post-status-draft" value="draft"<?php if ( $s === 'draft' ) _eh( ' selected' ); ?>><?= _ht( 'Draft' ) ?></option>
	</select>
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
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.27.0/moment.min.js"></script>
<script src="js/flatpickr/flatpickr.min.js"></script>
<script src="js/flatpickr/ja.js"></script>
<script src="js/tinymce/tinymce.min.js"></script>
<script src="js/post.min.js"></script>
</head>
<body class="edit">
<div id="dialog-placeholder"></div>

<header class="header">
	<div class="inner">
		<h1>Newtrino</h1>
		<span id="update-msg"><?= _h($t_msg) ?></span>
		<span class="spacer"></span>
		<a class="button" href="#" id="show-list"><?= _ht( 'Post List' ) ?></a>
		<a class="button" href="#" id="show-post"><?= _ht( 'Show Post' ) ?></a>
	</div>
</header>

<form name="form-post" id="form-post" action="post.php" method="post">
	<input type="hidden" name="mode" id="mode" value="update">
	<input type="hidden" name="id" id="id" value="<?= _h( $t_p->getId() ) ?>">

	<div class="container">
		<div class="container-sub">
			<div class="frame frame-sub">
				<h3 class="frame-title"><?= _ht( 'Publish' ) ?></h3>
				<input form="form-post" type="text" name="post_date" id="post-date" value="<?= _h( $t_p->getDate() ) ?>">
				<div class="btn-row">
					<?php echo_state_select( $t_p ); ?>
				</div>
				<div class="button-row">
					<button id="show-preview"><?= _ht( 'Preview' ) ?></button>
					<button class="accent right" id="update"><?= _ht( 'Update' ) ?></button>
				</div>
				<p class="message" id="message_enter_title"><?= _ht( 'The title is blank.' ) ?></p>
			</div>
			<?php echo_taxonomy_metaboxes( $t_p ); ?>
			<?php echo_meta_metaboxes( $t_p ); ?>
		</div>

		<div class="container-main frame frame-post">
			<input placeholder="<?= _ht( 'Enter Title Here' ) ?>" type="text" name="post_title" id="post-title" value="<?= _h( $t_p->getTitle() ) ?>">
			<div class="button-row"><button id="show-media-chooser"><?= _ht( 'Insert Media' ) ?></button></div>
			<textarea name="post_content" id="post-content"><?= _h( $t_p->getContent() ) ?></textarea>
		</div>
	</div>
</form>

<input id="confirmation-message" type="hidden" value="<?= _ht( 'Do you want to move from the page you are inputting?' ) ?>">
<input id="lang" type="hidden" value="<?= $lang ?>">

</body>
</html>
