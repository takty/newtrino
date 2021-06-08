<?php
namespace nt;
/**
 *
 * Handler - Login
 *
 * @author Takuto Yanagida
 * @version 2021-06-08
 *
 */


require_once( __DIR__ . '/index.php' );
require_once( __DIR__ . '/../core/util/template.php' );


function handle_query( array $q, array $q_get ): array {
	global $nt_session;

	$q_mode = $q['mode'] ?? $q_get['mode'] ?? '';

	$res = true;
	if ( empty( $q['digest'] ) ) {
		$nt_session->logout();
	} else {
		$res = $nt_session->login( $q );
		if ( $res ) {
			if ( $q_mode === 'dialog' ) {
				close_dialog_frame();
			} else {
				header( 'Location: ' . NT_URL_ADMIN . 'list.php' );
			}
			exit;
		}
	}

	return [
		'realm'           => Session::getRealm(),
		'nonce'           => Session::getNonce(),
		'url'             => $nt_session->getUrl(),
		'error_message'   => $nt_session->getErrorMessage(),
		'is_login_failed' => ! $res,
		'mode'            => $q_mode,
	];
}
