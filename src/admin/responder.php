<?php
namespace nt;
/**
 *
 * Responder
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @version 2020-07-12
 *
 */


require_once( __DIR__ . '/index.php' );


$res = 'NG';
if ( $nt_q['mode'] === 'set_status' ) {
	$p = $nt_store->getPost( $nt_q['id'] );
	$p->setStatus( $nt_q['status'] );
	$nt_store->writePost( $p );
	$res = 'OK';
}
?>
<?= "<?xml version='1.0' encoding='utf-8' standalone='yes'?>" ?>
<result>
<?= $res ?>
</result>
