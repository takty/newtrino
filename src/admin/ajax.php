<?php
namespace nt;
/**
 *
 * Ajax
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @version 2020-07-25
 *
 */


require_once( __DIR__ . '/index.php' );
require_once( __DIR__ . '/../core/class-store.php' );

start_session( true );

$q = $_REQUEST;
$q_mode = $q['mode'] ?? '';
$q_id   = $q['id']   ?? 0;
$q_val  = $q['val']  ?? '';

$res = 'failure';
if ( $q_mode === 'status' ) {
	$p = $nt_store->getPost( $q_id );
	if ( $p ) {
		$p->setStatus( $q_val );
		$nt_store->writePost( $p );
		$res = 'success';
	}
}


?>
<?= "<?xml version='1.0' encoding='utf-8' standalone='yes'?>" ?>
<result><?= $res ?></result>
