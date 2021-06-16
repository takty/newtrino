<?php
namespace nt;
/**
 *
 * Handler - Login
 *
 * @author Takuto Yanagida
 * @version 2021-06-16
 *
 */


require_once( __DIR__ . '/index.php' );
require_once( __DIR__ . '/../core/util/template.php' );


function handle_query( array $q, array $q_get ): array {
	global $nt_session;

	$mode      = $q['mode'] ?? '';
	$is_dialog = isset( $q['dialog'] ) || isset( $q_get['dialog'] );
	$msg_log   = '';
	$msg_reg   = '';

	$msgs = [
		'INVALID_CODE'   => _ht( 'The invitation code is invalid.' ),
		'INVALID_PARAM'  => _ht( 'User name or password is not appropriate.' ),
		'EXPIRED_CODE'   => _ht( 'Your invitation code is expired.' ),
		'INTERNAL_ERROR' => _ht( 'Internal error occurred.' ),
	];
	if ( $mode === 'login' ) {
		if ( empty( $q['digest'] ) ) {
			$nt_session->logout();
		} else {
			if ( $nt_session->login( $q ) ) {
				if ( $is_dialog ) {
					close_dialog_frame();
				} else {
					header( 'Location: ' . NT_URL_ADMIN . 'list.php' );
				}
				exit;
			}
			$msg_log = _ht( 'User name or password is wrong.' );
		}
	} else if ( $mode === 'issue' ) {
		$code = $nt_session->issueInvitationCode( $q );
		if ( $code ) {
			$msg_log = $code;
		} else {
			$msg_reg = $msgs[ $nt_session->getErrorCode() ] ?? '';
		}
	} else if ( $mode === 'register' ) {
		if ( $nt_session->register( $q ) ) {
			$msg_log = _ht( 'Registration succeeded.' );
		} else {
			$msg_reg = $msgs[ $nt_session->getErrorCode() ] ?? '';
		}
	}

	return [
		'key'       => Session::getAuthKey(),
		'nonce'     => Session::getAuthNonce(),
		'url'       => $nt_session->getUrl(),
		'msg_log'   => $msg_log,
		'msg_reg'   => $msg_reg,
		'is_dialog' => $is_dialog,
	];
}
