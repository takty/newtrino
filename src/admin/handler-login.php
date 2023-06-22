<?php
/**
 * Handler - Login
 *
 * @author Takuto Yanagida
 * @version 2023-06-22
 */

namespace nt;

require_once( __DIR__ . '/index.php' );
require_once( __DIR__ . '/class-auth.php' );
require_once( __DIR__ . '/../core/util/template.php' );

function handle_query_login( array $q, array $q_get ): array {
	global $nt_session;
	$auth = new Auth( NT_URL_ADMIN, NT_DIR_DATA, NT_DIR_AUTH );

	$mode      = $q['mode']  ?? '';
	$token     = $q['token'] ?? '';
	$is_dialog = isset( $q['dialog'] ) || isset( $q_get['dialog'] );
	$ntc_log   = '';
	$ntc_reg   = '';

	$c_ntc = [
		'invalid_code'   => _ht( 'The invitation code is invalid.' ),
		'invalid_param'  => _ht( 'User name or password is not appropriate.' ),
		'expired_code'   => _ht( 'Your invitation code is expired.' ),
		'internal_error' => _ht( 'Internal error occurred.' ),
	];
	if ( $auth->checkToken( $token ) ) {
		if ( $mode === 'login' ) {
			$ul = $auth->signIn( $q );
			if ( $ul && $nt_session->create( $ul['user'], $ul['lang'] ) ) {
				if ( $is_dialog ) {
					close_dialog_frame();
				} else {
					nocache_headers();
					header( 'Location: ' . NT_URL_ADMIN . 'list.php', true, 302 );
				}
				exit;
			}
			$ntc_log = _ht( 'User name or password is wrong.' );
		} elseif ( $mode === 'issue' ) {
			$code = $auth->issueInvitation( $q );
			if ( $code ) {
				$ntc_log = $code;
			} else {
				$ntc_reg = $c_ntc[ $auth->getErrorCode() ] ?? '';
			}
		} elseif ( $mode === 'register' ) {
			if ( $auth->signUp( $q ) ) {
				$ntc_log = _ht( 'Registration succeeded.' );
			} else {
				$ntc_reg = $c_ntc[ $auth->getErrorCode() ] ?? '';
			}
		}
	} elseif ( ! empty( $token ) ) {
		$ntc_log = _ht( 'Please log in again.' );
	}
	if ( $mode === 'logout' ) {
		$nt_session->destroy();
	}

	return [
		'key'       => Auth::getAuthKey(),
		'nonce'     => Auth::getAuthNonce(),
		'token'     => $auth->issueToken(),
		'url'       => NT_URL_ADMIN,
		'ntc_log'   => $ntc_log,
		'ntc_reg'   => $ntc_reg,
		'is_dialog' => $is_dialog,
	];
}
