<?php
namespace nt;
/**
 *
 * Media Dialog
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @version 2020-07-24
 *
 */


require_once( __DIR__ . '/index.php' );
require_once( __DIR__ . '/class-media.php' );
require_once( __DIR__ . '/../core/class-store.php' );

start_session( true );

$q = $_REQUEST;
$q_mode     = $q['mode']     ?? '';
$q_id       = $q['id']       ?? 0;
$q_del_file = $q['del_file'] ?? '';

$media = new Media( $q_id, Post::MEDIA_DIR_NAME, NT_URL );

if ( $q_mode === 'delete' ) {
	if ( ! empty( $q_del_file ) ) {
		$media->remove( $q_del_file );
	}
} else if ( $q_mode === 'upload' ) {
	if ( ! isset( $_FILES['upload_file']['error'] ) || ! is_int( $_FILES['upload_file']['error'] ) ) {
		// error
	} else if ( isset( $_FILES['upload_file'] ) ) {
		$media->upload( $_FILES['upload_file'] );
	}
}
$t_items = $media->getItemList();


function echo_item_file( $it, $i ) {
?>
	<div class="item-media">
		<input type="radio" name="file" id="item<?= $i ?>" value="<?= _h( $it['file_name'] ) ?>">
		<label for="item<?= $i ?>">
<?php if ( empty( $it['is_image'] ) ) : ?>
			<div class="thumbnail"><span><?= _h( $it['ext'] ) ?></span></div>
<?php else : ?>
			<div class="thumbnail"><img src="<?= _h( $it['url@min'] ) ?>"></div>
<?php endif ?>
			<div class="caption"><?= _h( $it['file_name'] ) ?></div>
		</label>
		<input type="hidden" class="file-name" value="<?= _h( $it['file_name'] ) ?>">
		<input type="hidden" class="file-url" value="<?= _h( $it['url'] ) ?>">
		<input type="hidden" class="is-image" value="<?= ( empty( $it['is_image'] ) ? 0 : 1 ) ?>">
<?php if ( isset( $it['sizes_json'] ) ) : ?>
		<input type="hidden" class="sizes" value="<?= _h( $it['sizes_json'] ) ?>">
<?php endif; ?>
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
<link rel="stylesheet" href="css/reset.min.css">
<link rel="stylesheet" href="css/style.min.css">
<script src="js/media.min.js"></script>
</head>
<body class="media dialog">

<header class="header">
	<div class="inner">
		<h1><?= _ht('Insert Media') ?></h1>
		<form action="media.php" method="post" enctype="multipart/form-data" id="form-upload">
			<input type="hidden" name="id" value="<?= _h( $q_id ) ?>">
			<input type="hidden" name="mode" value="upload">
			<div style="display:none"><input type="file" name="upload_file" id="upload-file"></div>
			<button id="btn-add" type="button"><?= _ht('Add New') ?></button>
		</form>
		<div class="spacer"></div>
		<button type="button" id="btn-close"><?= _ht( 'Close' ) ?></button>
	</div>
</header>

<div class="container">
	<div class="container-main frame">
		<div class="scroller">
			<ul class="list-item-media">
				<?php foreach ( $t_items as $i => $item ) echo_item_file( $item, $i ); ?>
			</ul>
		</div>
	</div>
	<div class="container-sub">
		<div class="frame frame-sub frame-compact">
			<div>
				<div class="heading"><?= _ht( 'Image Alignment' ) ?></div>
				<select id="image-align">
					<option value="alignleft"><?= _ht( 'Left' ) ?></option>
					<option value="aligncenter" selected><?= _ht( 'Center' ) ?></option>
					<option value="alignright"><?= _ht( 'Right' ) ?></option>
					<option value="alignnone"><?= _ht( 'None' ) ?></option>
				</select>
			</div>
			<div>
				<div class="heading"><?= _ht( 'Image Size' ) ?></div>
				<select id="image-size">
					<option value="small"><?= _ht( 'Small' ) ?></option>
					<option value="medium-small"><?= _ht( 'Medium Small' ) ?></option>
					<option value="medium" selected><?= _ht( 'Medium' ) ?></option>
					<option value="medium-large"><?= _ht( 'Medium Large' ) ?></option>
					<option value="large"><?= _ht( 'Large' ) ?></option>
					<option value="extra-large"><?= _ht( 'Extra Large' ) ?></option>
					<option value="huge"><?= _ht( 'Huge' ) ?></option>
					<option value="full"><?= _ht( 'Full Size' ) ?></option>
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
		<form action="media.php" method="post" id="form-delete">
			<input type="hidden" name="mode" value="delete">
			<input type="hidden" name="id" value="<?= _h( $q_id ) ?>">
			<input type="hidden" name="del_file" id="del-file">
			<button class="delete" type="button" id="btn-delete"><?= _ht( 'Permanently Delete' ) ?></button>
		</form>
		<div class="spacer"></div>
		<button type="button" class="accent" id="btn-insert"><?= _ht( 'Insert Into Post' ) ?></button>
	</div>
</footer>

<input type="hidden" id="msg-delete" value="<?= _ht( 'Do you want to delete the selected media file?' ) ?>">

</body>
</html>
