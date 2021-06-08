<?php
namespace nt;
/**
 *
 * Post
 *
 * @author Takuto Yanagida
 * @version 2021-06-08
 *
 */


require_once( __DIR__ . '/handler-post.php' );
$view = handle_query( $_REQUEST );
$t_p = $view['t_p'];


header( 'Content-Type: text/html;charset=utf-8' );
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="css/reset.min.css">
<link rel="stylesheet" href="css/flatpickr/flatpickr.min.css">
<link rel="stylesheet" href="css/style.min.css">
<link rel="apple-touch-icon" type="image/png" href="css/logo-180x180.png">
<link rel="icon" type="image/png" href="css/logo.png">
<script src="js/moment/moment.min.js"></script>
<script src="js/flatpickr/flatpickr.min.js"></script>
<script src="js/flatpickr/ja.js"></script>
<script src="js/tinymce/tinymce.min.js"></script>
<script src="js/post.min.js"></script>
<title><?= _ht( 'Post Edit' ) ?> - Newtrino</title>
</head>
<body class="post">

<?php \nt\begin(); ?>
<header class="header">
	<div class="inner">
		<h1>Newtrino</h1>
		<span class="message" id="message-notification">{{message}}</span>
		<span class="spacer"></span>
		<a class="button" href="{{list_url}}" id="btn-list"><?= _ht( 'Post List' ) ?></a>
	</div>
	<div class="message" id="message-enter-title" hidden><?= _ht( 'The title is blank.' ) ?></div>
</header>

<form name="form-post" id="form-post" action="post.php" method="post" enctype="multipart/form-data">
	<div class="container container-post">
		<div class="container-sub">
			<div class="frame frame-sub">
				<div class="title"><?= _ht( 'Publish' ) ?></div>
				<div><input form="form-post" type="text" name="post_date" id="post-date" value="{{post_date}}"></div>
				<div>
					<label class="select">
						<select form="form-post" name="post_status" id="post-status">
{{#status@select}}
							<option id="post-status-{{slug}}" value="{{slug}}" {{#is_selected}}selected{{/is_selected}}>{{label}}</option>
{{/status@select}}
						</select>
					</label>
				</div>
				<hr class="horizontal">
				<div class="button-row">
					<button id="btn-dialog-preview" type="button" data-action="{{preview_url}}"><?= _ht( 'Preview' ) ?></button>
					<button class="accent right" id="btn-update" type="button" data-action="{{update_url}}"><?= _ht( 'Update' ) ?></button>
				</div>
			</div>
			<?php echo_taxonomy_metaboxes( $t_p ); ?>
			<?php echo_meta_metaboxes( $t_p ); ?>
		</div>

		<div class="container-main frame frame-post">
			<input placeholder="<?= _ht( 'Enter Title Here' ) ?>" type="text" name="post_title" id="post-title" value="{{post_title}}">
			<div class="button-row"><button id="btn-dialog-media" type="button" data-src="{{media_url}}"><?= _ht( 'Insert Media' ) ?></button></div>
			<textarea name="post_content" id="post-content">{{post_content}}</textarea>
		</div>
	</div>
</form>

<input id="lang" type="hidden" value="{{lang}}">
<input id="editor-css" type="hidden" value="{{editor_css}}">
<input id="editor-option" type="hidden" value="{{editor_option}}">
<input id="assets-url" type="hidden" value="{{assets_url}}">
<script src="{{editor_js}}"></script>

<div id="dialog-placeholder">
	<iframe id="dialog-media"></iframe>
	<iframe id="dialog-login"></iframe>
	<div id="dialog-preview" class="frame dialog">
		<header class="header">
			<div class="inner">
				<h1><?= _ht( 'Preview' ) ?></h1>
				<span class="spacer"></span>
				<button class="accent" id="btn-close"><?= _ht( 'Close' ) ?></button>
			</div>
		</header>
		<iframe name="iframe-preview" class="frame preview"></iframe>
	</div>
</div>
<?php \nt\end( $view ); ?>

</body>
</html>
