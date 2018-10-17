<?php
namespace nt;
/**
 *
 * Responder
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @version 2018-10-17
 *
 */


require_once('admin-init.php');


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
<?= $result ?>
</result>
