<?php
namespace nt;
/**
 *
 * Responder
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @version 2018-10-18
 *
 */


require_once(__DIR__ . 'init-admin.php');


$result = 'NG';
if ($nt_q['mode'] === 'set_state') {
	$post = $nt_store->getPost($nt_q['id']);
	$post->setState($nt_q['state']);
	$nt_store->writePost($post);
	$result = 'OK';
}
?>
<?="<?xml version='1.0' encoding='utf-8' standalone='yes'?>"?>
<result>
<?= $result ?>
</result>
