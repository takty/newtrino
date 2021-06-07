<?php
namespace nt;
/**
 *
 * Handler - Login
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @version 2021-06-07
 *
 */


require_once( __DIR__ . '/index.php' );
require_once( __DIR__ . '/../core/util/template.php' );


function handle_query( array $q ): array {
	global $nt_session;

	$q_mode = $q['mode'] ?? '';

	$res = true;
	if ( $q_mode === 'logout' || empty( $q['digest'] ) ) {
		$nt_session->logout();
	} else {
		$res = $nt_session->login( $q );
		if ( $res ) {
			header( 'Location: ' . NT_URL_ADMIN . 'list.php' );
			exit;
		}
	}

	return [
		'realm'           => Session::getRealm(),
		'nonce'           => Session::getNonce(),
		'url'             => $nt_session->getUrl(),
		'error_message'   => $nt_session->getErrorMessage(),
		'is_login_failed' => ! $res,
	];
}
