<?php
namespace nt;
/**
 *
 * Media Dialog
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @version 2020-07-16
 *
 */


require_once( __DIR__ . '/index.php' );


$media = new Media( NT_DIR_POST, NT_URL_POST, Post::MEDIA_DIR_NAME, $nt_q['id'] );

if ( $nt_q['mode'] === 'delete' ) {
	$file = $nt_q['deleted_file'];
	if ( ! empty( $file ) ) {
		$media->remove( $file );
	}
} else if ( $nt_q['mode'] === 'upload' ) {
	if ( ! isset( $_FILES['uploadFile']['error'] ) || ! is_int( $_FILES['uploadFile']['error'] ) ) {
		// error
	} else if ( isset( $_FILES['uploadFile'] ) ) {
		$media->upload( $_FILES['uploadFile'] );
	}
}
$t_pid   = $nt_q['id'];
$t_items = $media->getItemList();

function echo_item_file( $it, $i ) {
?>
	<div class="item-media">
		<input type="radio" name="file" id="item<?= $i ?>" value="<?= _h( $it['caption'] ) ?>">
		<label for="item<?= $i ?>">
<?php if ( empty( $it['img'] ) ) : ?>
			<div class="thumbnail"><span><?= _h( $it['ext'] ) ?></span></div>
<?php else : ?>
			<div class="thumbnail"><img src="<?= _h( $it['url'] ) ?>"></div>
<?php endif ?>
			<div class="caption"><?= _h( $it['caption'] ) ?></div>
		</label>
		<input type="hidden" class="file-name" value="<?= _h( $it['file'] ) ?>">
		<input type="hidden" class="file-url" value="<?= _h( $it['url'] ) ?>">
		<input type="hidden" class="is-img" value="<?= ( empty( $it['img'] ) ? 0 : 1 ) ?>">
		<input type="hidden" class="width" value="<?= _h( $it['width'] ) ?>">
		<input type="hidden" class="height" value="<?= _h( $it['height'] ) ?>">
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
<link rel="stylesheet" href="css/style.min.css">
<script src="js/media.min.js"></script>
</head>
<body class="media dialog">

<header class="header">
	<div class="inner">
		<h1><?= _ht('Insert Media') ?></h1>
		<div class="spacer"></div>
		<button type="button" id="btn-close"><?= _ht( 'Close' ) ?></button>
	</div>
</header>

<div class="container">
	<div class="container-sub">
		<div class="frame frame-sub">
			<form action="media.php" method="post" enctype="multipart/form-data" id="uploadForm">
				<input type="hidden" name="id" value="<?= _h($t_pid) ?>">
				<input type="hidden" name="mode" value="upload">
				<div style="display: none">
					<input type="file" name="uploadFile" id="uploadFile" onchange="if (this.value !== '') document.getElementById('uploadForm').submit();">
				</div>
				<button type="button" onclick="document.getElementById('uploadFile').click();"><?= _ht('Add New') ?></button>
			</form>
		</div>
		<div class="frame frame-sub frame-compact">
			<div>
				<div class="heading"><?= _ht( 'Image Alignment' ) ?></div>
				<select name="align">
					<option value="alignleft"><?= _ht( 'Left' ) ?></option>
					<option value="aligncenter" selected><?= _ht( 'Center' ) ?></option>
					<option value="alignright"><?= _ht( 'Right' ) ?></option>
					<option value="alignnone"><?= _ht( 'None' ) ?></option>
				</select>
			</div>
			<div>
				<div class="heading"><?= _ht( 'Image Size' ) ?></div>
				<select name="size">
					<option value="size-small"><?= _ht( 'Small' ) ?></option>
					<option value="size-medium" selected><?= _ht( 'Medium' ) ?></option>
					<option value="size-large"><?= _ht( 'Large' ) ?></option>
					<option value="size-full"><?= _ht( 'Full Size' ) ?></option>
				</select>
			</div>
		</div>
	</div>
	<div class="container-main frame">
		<div class="scroller">
			<ul class="list-item-media">
				<?php foreach ( $t_items as $i => $item ) echo_item_file( $item, $i ); ?>
			</ul>
		</div>
	</div>
</div>

<footer class="footer">
	<div class="inner">
		<form action="media.php" method="post" id="form-delete">
			<input type="hidden" name="mode" value="delete">
			<input type="hidden" name="id" value="<?= _h( $t_pid ) ?>">
			<input type="hidden" name="deleted_file" id="deleted-file">
			<button class="delete" type="button" id="btn-delete"><?= _ht( 'Permanently Delete' ) ?></button>
		</form>
		<div class="spacer"></div>
		<button type="button" class="accent" id="btn-insert"><?= _ht( 'Insert Into Post' ) ?></button>
	</div>
</footer>

<input type="hidden" id="msg-delete" value="<?= _ht( 'Do you want to delete the selected media file?' ) ?>">

</body>
</html>
