<?php
namespace nt;
/**
 *
 * Media Chooser
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @author Yusuke Manabe   @ Space-Time Inc.
 * @version 2018-10-18
 *
 */


require_once(__DIR__ . '/init-admin.php');


$media = new Media(NT_PATH_POST, NT_URL_POST, $nt_q['id']);

if ($nt_q['mode'] === 'delete') {
	$file = $nt_q['deleted_file'];
	if (!empty($file)) {
		$media->remove($file);
	}
} else if ($nt_q['mode'] === 'upload') {
	if (!isset($_FILES['uploadFile']['error']) || !is_int($_FILES['uploadFile']['error'])) {
		// error
	} else if (isset($_FILES['uploadFile'])) {
		$media->upload($_FILES['uploadFile']);
	}
}
$t_sid   = $nt_q['sid'];
$t_pid   = $nt_q['id'];
$t_items = $media->getItemList();


header('Content-Type: text/html;charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="css/style.min.css">
<script src="js/media.min.js"></script>
<script>document.addEventListener('DOMContentLoaded', function () {initMedia();});</script>
</head>
<body class="media">
<h1><?= _ht('Insert Media', 'admin') ?></h1>
<div class="header-row">
	<form action="media.php" method="post" enctype="multipart/form-data" id="uploadForm">
		<input type="hidden" name="sid" value="<?= _h($t_sid) ?>">
		<input type="hidden" name="id" value="<?= _h($t_pid) ?>">
		<input type="hidden" name="mode" value="upload">
		<div style="display: none">
			<input type="file" name="uploadFile" id="uploadFile" onchange="if (this.value !== '') document.getElementById('uploadForm').submit();">
		</div>
		<button type="button" onclick="document.getElementById('uploadFile').click();"><?= _ht('Add New', 'admin') ?></button>
	</form>
	<form action="media.php" method="post" id="deleteForm">
		<input type="hidden" name="sid" value="<?= _h($t_sid) ?>">
		<input type="hidden" name="id" value="<?= _h($t_pid) ?>">
		<input type="hidden" name="mode" value="delete">
		<input type="hidden" name="deleted_file" id="deleted_file">
		<button class="btn-delete" type="button" id="delete" onClick="deleteFile();"><?= _ht('Permanently Delete', 'admin') ?></button>
	</form>
</div>
<div class="media-list">
	<div class="filechooser">
<?php for ($i = 0; $i < count($t_items); $i += 1): $item = $t_items[$i]; ?>
		<div class="item">
			<label for="item<?=$i?>">
<?php
$is_img = !empty($item['img']);
if ($is_img):
?>
				<div class="icon" style="background-image: url(<?=_h($item['url'])?>)"></div>
<?php else: ?>
				<div class="icon"><?= _h($item['ext']) ?></div>
<?php endif ?>
			</label><br>
			<input type="radio" id="item<?=$i?>" value="<?= _h($item['caption']) ?>" onclick="setFile('<?= _h($item['file']) ?>', '<?= _h($item['url']) ?>', <?= _h($item['width']) ?>, <?= _h($item['height']) ?>, <?= $is_img ?>)">
			<label for="item<?=$i?>"><?= _h($item['caption']) ?></label>
		</div>
<?php endfor ?>
	</div>
</div>
<div class="image-option">
	<div style="display: none;">
		<h2><?= _ht('Image Alignment', 'admin') ?></h2>
		<input type="radio" name="pos" id="pos_c" value="c" checked><label for="pos_c"><?= _ht('Center', 'admin') ?></label>
		<input type="radio" name="pos" id="pos_l" value="l"><label for="pos_l"><?= _ht('Left', 'admin') ?></label>
		<input type="radio" name="pos" id="pos_r" value="r"><label for="pos_r"><?= _ht('Right', 'admin') ?></label>
	</div>
	<div>
		<h2><?= _ht('Image Size', 'admin') ?></h2>
		<input type="radio" name="size" id="size_o" value="o"><label for="size_o"><?= _ht('Original Size', 'admin') ?></label>
		<input type="radio" name="size" id="size_l" value="l"><label for="size_l"><?= _ht('Large (660px)', 'admin') ?></label>
		<input type="radio" name="size" id="size_m" value="m" checked><label for="size_m"><?= _ht('Medium (440px)', 'admin') ?></label>
		<input type="radio" name="size" id="size_s" value="s"><label for="size_s"><?= _ht('Small (220px)', 'admin') ?></label>
	</div>
</div>
<div>
	<button type="button" onClick="cancel();"><?= _ht('Close', 'admin') ?></button>
	<button type="button" id="insert" onClick="insert();"><?= _ht('Insert Into Post', 'admin') ?></button>
</div>
</body>
</html>
