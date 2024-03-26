<?php
/**
 * Function for Session
 *
 * @author Takuto Yanagida
 * @version 2024-03-26
 */

namespace nt;

/**
 * Starts a new session or resumes the existing one.
 * If a session timeout is detected, it attempts to restart the session with a new session ID.
 *
 * @param int $timeout The session timeout in seconds. Default is 300 seconds.
 * @return bool Returns true if the session was successfully started, false otherwise.
 */
function session_start( int $timeout = 300 ): bool {
	$ret = \session_start();
	if ( isset( $_SESSION['_del'] ) ) {
		if ( $_SESSION['_del'] < time() - $timeout ) return false;
		if ( isset( $_SESSION['_new'] ) ) {
			session_write_close();
			ini_set( 'session.use_strict_mode', '0' );  // For assigning session IDs
			session_id( $_SESSION['_new'] );
			$ret = \session_start();
		}
	}
	return $ret;
}

/**
 * Regenerates the session ID.
 * It saves the current session, creates a new session ID, and then restores the session information.
 *
 * @return bool Returns true if the session ID was successfully regenerated, false otherwise.
 */
function session_regenerate_id(): bool {
	$s = $_SESSION;  // Saving current session

	$new = session_create_id();
	if ( ! is_string( $new ) ) {
		return false;
	}
	$_SESSION['_new'] = $new;
	$_SESSION['_del'] = time();
	session_write_close();
	ini_set( 'session.use_strict_mode', '0' );  // For assigning session IDs
	session_id( $new );
	$res = \session_start();

	if ( $res ) $_SESSION = $s;  // Restoring session information
	return $res;
}
