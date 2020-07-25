<?php
namespace nt;
/**
 *
 * Media Dialog
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @version 2020-07-25
 *
 */


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
<link rel="stylesheet" href="css/style.min.css">
<script src="js/media.min.js"></script>
</head>
<body class="media dialog">

<?php \nt\begin(); ?>
<header class="header">
	<div class="inner">
		<h1><?= _ht( 'Insert Media' ) ?></h1>
		<form action="media.php?id={{id}}" method="post" enctype="multipart/form-data" id="form-upload">
			<input type="hidden" name="mode" value="upload">
			<div hidden><input type="file" name="upload_file" id="upload-file"></div>
			<button id="btn-add" type="button"><?= _ht( 'Add New' ) ?></button>
		</form>
		<input type="hidden" id="max-file-size" value="{{max_file_size}}">
		<div class="spacer"></div>
		<button type="button" id="btn-close"><?= _ht( 'Close' ) ?></button>
	</div>
	<div class="message">{{message}}</div>
	<div class="message" id="message-max-file-size" hidden><?= _ht( 'The uploaded file exceeds the max file size.' ) ?></div>
</header>

<div class="container">
	<div class="container-main frame">
		<div class="scroller">
			<ul class="list-item-media">
{{#items}}
				<li class="item-media">
					<input type="hidden" class="file-name" value="{{file_name}}">
					<input type="hidden" class="file-url" value="{{url}}">
					<input type="radio" name="item" id="item{{index}}">
{{#is_image}}
					<label for="item{{index}}">
						<div class="thumbnail"><img src="{{url@min}}"></div>
						<div class="caption">{{file_name}}</div>
					</label>
					<input type="hidden" class="sizes" value="{{sizes_json}}">
{{/is_image}}
{{^is_image}}
					<label for="item{{index}}">
						<div class="thumbnail"><span>{{ext}}</span></div>
						<div class="caption">{{file_name}}</div>
					</label>
{{/is_image}}
				</li>
{{/items}}
			</ul>
		</div>
	</div>
	<div class="container-sub">
		<div class="frame frame-compact">
			<div>
				<div class="heading"><?= _ht( 'Image Alignment' ) ?></div>
				<select id="image-align">
					{{#aligns}}<option value="{{value}}"{{selected}}>{{label}}</option>{{/aligns}}
				</select>
			</div>
			<div>
				<div class="heading"><?= _ht( 'Image Size' ) ?></div>
				<select id="image-size">
					{{#sizes}}<option value="{{value}}"{{selected}}>{{label}}</option>{{/sizes}}
				</select>
			</div>
			<div>
				<label class="checkbox">
					<input id="image-link" type="checkbox">
					<?= _ht( 'Link To Full Size Image' ) ?>
				</label>
			</div>
			<div>
				<input id="media-url" type="text" readonly>
			</div>
		</div>
	</div>
</div>

<footer class="footer">
	<div class="inner">
		<form action="media.php?id={{id}}" method="post" id="form-delete">
			<input type="hidden" name="mode" value="delete">
			<input type="hidden" name="delete_file" id="delete-file">
			<button class="delete" type="button" id="btn-delete"><?= _ht( 'Permanently Delete' ) ?></button>
		</form>
		<div class="spacer"></div>
		<button type="button" class="accent" id="btn-insert"><?= _ht( 'Insert Into Post' ) ?></button>
	</div>
</footer>

<input type="hidden" id="msg-delete" value="<?= _ht( 'Do you want to delete the selected media file?' ) ?>">
<?php \nt\end( $view ); ?>

</body>
</html>
