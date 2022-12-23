<?php
/**
 * Media Dialog
 *
 * @author Takuto Yanagida
 * @version 2022-12-23
 */

namespace nt;

require_once( __DIR__ . '/handler-media.php' );
$view = handle_query( $_REQUEST );

header( 'Content-Type: text/html;charset=utf-8' );
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="css/reset.min.css">
<link rel="stylesheet" href="<?= tqs( __DIR__, 'css/style.min.css' ); ?>">
<script src="<?= tqs( __DIR__, 'js/media.min.js' ); ?>"></script>
</head>
<body class="media">

<div class="frame frame-media dialog">
	<header class="dialog-header">
<?php \nt\begin( $view ); ?>
		<div class="row">
			<h1 class="dialog-title">
				<span><?= _ht( 'Insert Media' ); ?></span>
				<span>{{filter_type}}</span>
			</h1>

			<div>
				<form action="media.php?id={{id}}" method="post" enctype="multipart/form-data" id="form-upload">
					<input type="hidden" name="mode" value="upload">
					<input type="hidden" name="target" value="{{meta_target}}">
					<div hidden><input type="file" name="upload_file" id="upload-file"></div>
					<button type="button" id="btn-add"><?= _ht( 'Add New' ); ?></button>
				</form>

				<button type="button" id="btn-close"><?= _ht( 'Close' ); ?></button>
			</div>
		</div>

		<div class="notice">{{ntc}}</div>
<?php \nt\end(); ?>
	</header>

	<div class="dialog-content">
		<div class="column media">
<?php \nt\begin( $view ); ?>
			<div class="column-main frame">
				<div class="scroller">
					<ul class="list-item-media">
{{#items}}
						<li class="item-media">
							<input type="hidden" class="file-name" value="{{file_name}}">
							<input type="hidden" class="file-url" value="{{url}}">
{{#is_image}}
							<input type="hidden" class="sizes" value="{{sizes_json}}">
{{/is_image}}
							<button type="button" class="card">
								<div class="thumbnail">{{#is_image}}<img src="{{url@min}}">{{/is_image}}{{^is_image}}<span>{{ext}}</span>{{/is_image}}</div>
								<div class="caption">{{file_name}}</div>
							</button>
						</li>
{{/items}}
					</ul>
				</div>
			</div>
<?php \nt\end(); ?>

<?php \nt\begin( $view ); ?>
			<div class="column-sub"{{#meta_target}} disabled{{/meta_target}}>
				<div class="frame frame-compact">
					<div>
						<div class="heading"><?= _ht( 'Image Alignment' ); ?></div>
						<label class="select">
							<select id="image-align">
{{#aligns}}
								<option value="{{value}}"{{selected}}>{{label}}</option>
{{/aligns}}
							</select>
						</label>
					</div>

					<div>
						<div class="heading"><?= _ht( 'Image Size' ); ?></div>
						<label class="select">
							<select id="image-size">
{{#sizes}}
								<option value="{{value}}"{{selected}}>{{label}}</option>
{{/sizes}}
							</select>
						</label>
					</div>

					<div>
						<label class="checkbox">
							<input type="checkbox" id="image-link">
							<span><?= _ht( 'Link To Full Size Image' ); ?></span>
						</label>
					</div>

					<div>
						<input type="text" id="media-url" readonly>
					</div>
				</div>
			</div>
<?php \nt\end(); ?>
		</div>
	</div>

	<footer class="dialog-footer">
<?php \nt\begin( $view ); ?>
		<div class="row">
			<form action="media.php?id={{id}}" method="post" id="form-delete">
				<input type="hidden" name="mode" value="delete">
				<input type="hidden" name="delete_file" id="delete-file">
				<button type="button" class="delete" id="btn-delete"><?= _ht( 'Permanently Delete' ); ?></button>
			</form>

			<button type="button" class="accent" id="btn-insert">{{button_label}}</button>
		</div>
<?php \nt\end(); ?>
	</footer>
</div>

<?php \nt\begin( $view ); ?>
<input type="hidden" id="meta-target" value="{{meta_target}}">
<input type="hidden" id="meta-size-width" value="{{meta_size_width}}">
<input type="hidden" id="max-file-size" value="{{max_file_size}}">
<input type="hidden" id="ntc-delete" value="<?= _ht( 'Do you want to delete the selected media file?' ); ?>">
<input type="hidden" id="ntc-file-size" value="<?= _ht( 'The uploaded file exceeds the max file size.' ); ?>">
<?php \nt\end(); ?>

</body>
</html>
