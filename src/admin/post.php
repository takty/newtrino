<?php
/**
 * Post
 *
 * @author Takuto Yanagida
 * @version 2023-06-22
 */

namespace nt;

require_once( __DIR__ . '/handler-post.php' );
$view = handle_query_post( $_REQUEST );
$t_p  = $view['t_p'];

header( 'Content-Type: text/html;charset=utf-8' );
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="icon" type="image/png" href="css/logo.png">
<link rel="apple-touch-icon" type="image/png" href="css/logo-180x180.png">
<link rel="stylesheet" href="css/reset.min.css">
<link rel="stylesheet" href="css/flatpickr/flatpickr.min.css">
<link rel="stylesheet" href="<?php tqs( __DIR__, 'css/style.min.css' ); ?>">
<script src="js/luxon/luxon.min.js"></script>
<script src="js/flatpickr/flatpickr.min.js"></script>
<script src="js/flatpickr/ja.js"></script>
<script src="js/tinymce/tinymce.min.js"></script>
<script src="<?php tqs( __DIR__, 'js/post.min.js' ); ?>"></script>
<title><?= _ht( 'Post Edit' ); ?> - Newtrino</title>
</head>
<body class="post">

<div class="site">
	<header class="site-header">
<?php \nt\begin( $view ); ?>
		<div class="row">
			<h1 class="site-title">Newtrino</h1>
			<a class="button" href="{{list_url}}" id="btn-list"><?= _ht( 'Post List' ); ?></a>
		</div>

		<p class="notice">{{ntc}}</p>
<?php \nt\end(); ?>
	</header>

	<main class="site-content">
		<form name="form-post" id="form-post" action="post.php" method="post" enctype="multipart/form-data">
			<div class="column post">
				<div class="column-sub">
<?php \nt\begin( $view ); ?>
					<div class="frame frame-box">
						<div class="title"><?= _ht( 'Publish' ); ?></div>
						<div class="row-col">
							<input type="text" form="form-post" name="post_date" id="post-date" value="{{post_date}}">
							<label class="select">
								<select form="form-post" name="post_status" id="post-status">
{{#status@select}}
									<option id="post-status-{{slug}}" value="{{slug}}"{{#is_selected}} selected{{/is_selected}}>{{label}}</option>
{{/status@select}}
								</select>
							</label>
						</div>

						<hr>

						<div class="button-row">
							<button type="button" id="btn-dialog-preview" data-action="{{preview_url}}"><?= _ht( 'Preview' ); ?></button>
							<button type="button" class="accent right" id="btn-update" data-action="{{update_url}}"><?= _ht( 'Update' ); ?></button>
						</div>
					</div>
<?php \nt\end(); ?>

					<?php echo_taxonomy_metaboxes( $t_p ); ?>
					<?php echo_meta_metaboxes( $t_p ); ?>
				</div>

				<div class="column-main frame frame-post">
<?php \nt\begin( $view ); ?>
					<input type="text" placeholder="<?= _ht( 'Enter Title Here' ); ?>" name="post_title" id="post-title" value="{{post_title}}">
					<div class="button-row">
						<button type="button" id="btn-dialog-media" data-src="{{media_url}}"><?= _ht( 'Insert Media' ); ?></button>
					</div>
					<textarea name="post_content" id="post-content">{{post_content}}</textarea>
<?php \nt\end(); ?>
				</div>
			</div>
		</form>
	</main>
</div>

<?php \nt\begin( $view ); ?>
<input type="hidden" id="ntc-enter-title" value="<?= _ht( 'The title is blank.' ); ?>">
<input type="hidden" id="lang" value="{{lang}}">
<input type="hidden" id="editor-css" value="{{editor_css}}">
<input type="hidden" id="editor-option" value="{{editor_option}}">
<input type="hidden" id="assets-url" value="{{assets_url}}">
<script src="{{editor_js}}"></script>
<?php \nt\end(); ?>

<div id="dialog-placeholder">
	<iframe id="dialog-media"></iframe>
	<iframe id="dialog-login"></iframe>

	<div id="dialog-preview" class="frame dialog">
		<header class="dialog-header">
			<div class="row">
				<h1 class="dialog-title"><?= _ht( 'Preview' ); ?></h1>
				<button class="accent" id="btn-close"><?= _ht( 'Close' ); ?></button>
			</div>
		</header>

		<iframe name="iframe-preview" class="frame preview"></iframe>
	</div>
</div>

</body>
</html>
