<?php
namespace nt;
/**
 *
 * Ajax
 *
 * @author Takuto Yanagida
 * @version 2021-06-07
 *
 */


require_once( __DIR__ . '/index.php' );
require_once( __DIR__ . '/../core/class-store.php' );

$q = $_REQUEST;
$q_mode = $q['mode'] ?? '';
$q_id   = $q['id']   ?? null;

$res = 'failure';
if ( $q_mode === 'status' ) {
	start_session( true );

	$p = $nt_store->getPost( $q_id );
	if ( $p ) {
		$q_val = $q['val'] ?? '';
		$p->setStatus( $q_val );
		$nt_store->writePost( $p );
		$res = 'success';
	}
} else if ( $q_mode === 'ping' ) {
	start_session( false );

	global $nt_session;
	if ( $nt_session->receivePing( $q_id ) ) {
		$res = 'success';
	}
}


?>
<?xml version='1.0' encoding='utf-8' standalone='yes'?>
<result><?= $res ?></result>
