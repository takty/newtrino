<?php
/*
 * Media
 * 2017-02-22
 *
 */

require_once('_init.php');
$media = new Media(POST_PATH, POST_URL, $q['id']);

if ($q['mode'] === 'delete') {
	$file = $q['deleted_file'];
	if (!empty($file)) {
		$media->remove($file);
	}
} else if ($q['mode'] === 'upload') {
   	if (!isset($_FILES['uploadFile']['error']) || !is_int($_FILES['uploadFile']['error'])) {
		// error
	} else if (isset($_FILES['uploadFile'])) {
		$media->upload($_FILES['uploadFile']);
	}
}
$t_sid   = $q['sid'];
$t_pid   = $q['id'];
$t_items = $media->getItemList();
header('Content-Type: text/html;charset=utf-8');




?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="css/sanitize.min.css">
<link rel="stylesheet" href="css/style.css">
<script src="js/newtrino.js"></script>
<script>document.addEventListener('DOMContentLoaded', function () {initMedia();});</script>
</head>
<body class="media">
<h1>Insert Media</h1>
<div class="header-row">
	<form action="_media.php" method="post" enctype="multipart/form-data" id="uploadForm">
		<input type="hidden" name="sid" value="<?=_h($t_sid)?>">
		<input type="hidden" name="id" value="<?=_h($t_pid)?>">
		<input type="hidden" name="mode" value="upload">
		<div style="display: none">
			<input type="file" name="uploadFile" id="uploadFile" onchange="if (this.value !== '') document.getElementById('uploadForm').submit();">
		</div>
		<button type="button" onclick="document.getElementById('uploadFile').click();">Add New</button>
	</form>
	<form action="_media.php" method="post" id="deleteForm">
		<input type="hidden" name="sid" value="<?=_h($t_sid)?>">
		<input type="hidden" name="id" value="<?=_h($t_pid)?>">
		<input type="hidden" name="mode" value="delete">
		<input type="hidden" name="deleted_file" id="deleted_file">
		<button class="btn-delete" type="button" id="delete" onClick="deleteFile();">Permanently Delete</button>
	</form>
</div>
<div class="media-list">
	<div class="filechooser">
<?php for ($i = 0; $i < count($t_items); $i += 1): $item = $t_items[$i]; ?>
		<div class="item">
			<label for="item<?=$i?>">
<?php if (!empty($item['img'])): ?>
				<div class="icon" style="background-image: url(<?=_u($item['url'])?>)"></div>
<?php else: ?>
				<div class="icon"><?=_h($item['ext'])?></div>
<?php endif ?>
			</label><br>
			<input type="radio" id="item<?=$i?>" value="<?=_h($item['caption'])?>" onclick="setFile('<?=_h($item['file'])?>', '<?=_h($item['url'])?>', <?=_h($item['width'])?>, <?=_h($item['height'])?>)">
			<label for="item<?=$i?>"><?=_h($item['caption'])?></label>
		</div>
<?php endfor ?>
	</div>
</div>
<div class="image-option">
	<div style="display: none;">
		<h2>Image Alignment</h2>
		<input type="radio" name="pos" id="pos_c" value="c" checked><label for="pos_c">Center</label>
		<input type="radio" name="pos" id="pos_l" value="l"><label for="pos_l">Left</label>
		<input type="radio" name="pos" id="pos_r" value="r"><label for="pos_r">Right</label>
	</div>
	<div>
		<h2>Image Size</h2>
		<input type="radio" name="size" id="size_o" value="o"><label for="size_o">Original Size</label>
		<input type="radio" name="size" id="size_l" value="l"><label for="size_l">Large (660px)</label>
		<input type="radio" name="size" id="size_m" value="m" checked><label for="size_m">Medium (440px)</label>
		<input type="radio" name="size" id="size_s" value="s"><label for="size_s">Small (220px)</label>
	</div>
</div>
<div>
	<button type="button" onClick="cancel();">Close</button>
	<button type="button" id="insert" onClick="insert();">Insert Into Post</button>
</div>
</body>
</html>
