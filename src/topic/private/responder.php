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


$res = 'NG';
if ($nt_q['mode'] === 'set_state') {
	$p = $nt_store->getPost($nt_q['id']);
	$p->setState($nt_q['state']);
	$nt_store->writePost($p);
	$res = 'OK';
}
?>
<?= "<?xml version='1.0' encoding='utf-8' standalone='yes'?>" ?>
<result>
<?= $res ?>
</result>
