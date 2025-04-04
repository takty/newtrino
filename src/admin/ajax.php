<?php
/**
 * Ajax
 *
 * @author Takuto Yanagida
 * @version 2024-03-22
 */

namespace nt;

require_once( __DIR__ . '/index.php' );
require_once( __DIR__ . '/../core/class-store.php' );

$q      = $_REQUEST;
$q_mode = $q['mode'] ?? '';
$q_id   = $q['id']   ?? null;

global $nt_session, $nt_store;

$res = 'failure';
if ( $q_mode === 'status' ) {
	if ( start_ajax_session( true ) && $nt_session->checkNonce()) {
		$p = $nt_store->getPost( $q_id );
		if ( $p ) {
			$q_val = $q['val'] ?? '';
			$p->setStatus( $q_val );
			$nt_store->writePost( $p );
			$res = 'success';
		}
	}
} elseif ( $q_mode === 'ping' ) {
	if ( start_ajax_session( false ) ) {
		if ( $nt_session->receivePing( $q_id ) ) {
			$res = 'success';
		}
	}
}


// The following is just in case when PHP short tags are enabled ?>
<?= '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>' ?>
<result><?= $res ?></result>
