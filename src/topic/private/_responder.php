<?php
/*
 * Responder
 * 2017-02-22
 *
 */

require_once('admin_init.php');

$mode = $q['mode'];
$result = 'NG';

if ($mode === 'set_state') {
	$post = $store->getPost($q['id']);
	$post->setState($q['state']);
	$store->writePost($post);
	$result = 'OK';
}
?>
<?="<?xml version='1.0' encoding='utf-8' standalone='yes'?>"?>
<result>
<?=$result?>
</result>
