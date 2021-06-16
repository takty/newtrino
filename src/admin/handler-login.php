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
require_once( __DIR__ . '/class-auth.php' );
require_once( __DIR__ . '/../core/util/template.php' );


function handle_query( array $q, array $q_get ): array {
	global $nt_session;
	$auth = new Auth( NT_URL_ADMIN, NT_DIR_DATA );

	$mode      = $q['mode'] ?? '';
	$is_dialog = isset( $q['dialog'] ) || isset( $q_get['dialog'] );
	$msg_log   = '';
	$msg_reg   = '';

	$msgs = [
		'invalid_code'   => _ht( 'The invitation code is invalid.' ),
		'invalid_param'  => _ht( 'User name or password is not appropriate.' ),
		'expired_code'   => _ht( 'Your invitation code is expired.' ),
		'internal_error' => _ht( 'Internal error occurred.' ),
	];
	if ( $mode === 'login' ) {
		if ( empty( $q['digest'] ) ) {  // First view
			$nt_session->destroy();
		} else {
			$ul = $auth->signIn( $q );
			if ( $ul && $nt_session->create( $ul['user'], $ul['lang'] ) ) {
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
		$code = $auth->issueInvitation( $q );
		if ( $code ) {
			$msg_log = $code;
		} else {
			$msg_reg = $msgs[ $auth->getErrorCode() ] ?? '';
		}
	} else if ( $mode === 'register' ) {
		if ( $auth->signUp( $q ) ) {
			$msg_log = _ht( 'Registration succeeded.' );
		} else {
			$msg_reg = $msgs[ $auth->getErrorCode() ] ?? '';
		}
	}

	return [
		'key'       => Auth::getAuthKey(),
		'nonce'     => Auth::getAuthNonce(),
		'url'       => NT_URL_ADMIN,
		'msg_log'   => $msg_log,
		'msg_reg'   => $msg_reg,
		'is_dialog' => $is_dialog,
	];
}
