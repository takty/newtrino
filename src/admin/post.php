<?php
namespace nt;
/**
 *
 * Post
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @version 2020-07-22
 *
 */


require_once( __DIR__ . '/index.php' );
require_once( __DIR__ . '/metabox.php' );
require_once( __DIR__ . '/view-admin.php' );
$q = $_REQUEST;
$q_mode = $q['mode'] ?? '';
$q_id   = $q['id']   ?? 0;


$view = query();
$lang = $nt_config['lang_admin'];
$t_msg = '';

switch ( $q_mode ) {
	case 'new':
		$t_p = $nt_store->createNewPost();
		$nt_session->addTempDir( $nt_store->getPostDir( $t_p->getId() ) );
		$t_p->setDate();
		break;
	case 'update':
		$p = $nt_store->getPost( $q_id );
		$p->assign( $q, NT_URL_ADMIN );
		$t_p = $nt_store->writePost( $p );
		$t_msg = _ht( 'Update Complete' );
		break;
	default:
		$t_p = $nt_store->getPost( $q_id );
		break;
}


// -----------------------------------------------------------------------------


function echo_state_select( $post ) {
	$s = $post->getStatus();
?>
	<select form="form-post" name="post_status" id="post-status">
		<option id="post-status-publish" value="publish"<?php if ( $s === 'publish' ) _eh( ' selected' ); ?>><?= _ht( 'Published' ) ?></option>
		<option id="post-status-future" value="future"<?php if ( $s === 'future' ) _eh( ' selected' ); ?>><?= _ht( 'Scheduled' ) ?></option>
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
<link rel="stylesheet" href="css/reset.min.css">
<link rel="stylesheet" href="css/flatpickr/flatpickr.min.css">
<link rel="stylesheet" href="css/style.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.27.0/moment.min.js"></script>
<script src="js/flatpickr/flatpickr.min.js"></script>
<script src="js/flatpickr/ja.js"></script>
<script src="js/tinymce/tinymce.min.js"></script>
<script src="js/post.min.js"></script>
<title><?= _ht('Post Edit') ?> - Newtrino</title>
</head>
<body class="edit">

<header class="header">
	<div class="inner">
		<h1>Newtrino</h1>
		<span id="message-updated"><?= _h( $t_msg ) ?></span>
		<span class="spacer"></span>
		<a class="button" href="<?= _h( $view['list'] ) ?>" id="btn-list"><?= _ht( 'Post List' ) ?></a>
	</div>
</header>

<form name="form-post" id="form-post" action="post.php" method="post">
	<div class="container">
		<div class="container-sub">
			<div class="frame frame-sub">
				<div class="title"><?= _ht( 'Publish' ) ?></div>
				<input form="form-post" type="text" name="post_date" id="post-date" value="<?= _h( $t_p->getDate() ) ?>">
				<div class="btn-row">
					<?php echo_state_select( $t_p ); ?>
				</div>
				<div class="button-row">
					<button id="btn-dialog-preview" type="button" data-action="<?= _h( $view['preview'] ) ?>"><?= _ht( 'Preview' ) ?></button>
					<button class="accent right" id="btn-update" type="button" data-action="<?= _h( $view['update'] ) ?>"><?= _ht( 'Update' ) ?></button>
				</div>
				<p class="message" id="message-enter-title"><?= _ht( 'The title is blank.' ) ?></p>
			</div>
			<?php echo_taxonomy_metaboxes( $t_p ); ?>
			<?php echo_meta_metaboxes( $t_p ); ?>
		</div>

		<div class="container-main frame frame-post">
			<input placeholder="<?= _ht( 'Enter Title Here' ) ?>" type="text" name="post_title" id="post-title" value="<?= _h( $t_p->getTitle() ) ?>">
			<div class="button-row"><button id="btn-dialog-media" type="button" data-src="<?= _h( $view['media'] ) ?>"><?= _ht( 'Insert Media' ) ?></button></div>
			<textarea name="post_content" id="post-content"><?= _h( $t_p->getContent() ) ?></textarea>
		</div>
	</div>
</form>

<input id="message-confirmation" type="hidden" value="<?= _ht( 'Do you want to move from the page you are inputting?' ) ?>">
<input id="lang" type="hidden" value="<?= $lang ?>">

<div id="dialog-placeholder">
	<iframe id="dialog-media"></iframe>
	<div id="dialog-preview" class="frame dialog">
		<header class="header">
			<div class="inner">
				<h1><?= _ht( 'Preview' ) ?></h1>
				<span class="spacer"></span>
				<button class="accent" id="btn-close"><?= _ht( 'Close' ) ?></button>
			</div>
		</header>
		<iframe name="iframe-preview"></iframe>
	</div>
</div>

</body>
</html>
