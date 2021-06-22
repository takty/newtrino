<?php
namespace nt;
/**
 *
 * Function for Session
 *
 * @author Takuto Yanagida
 * @version 2021-06-23
 *
 */


function session_start( int $timeout = 300 ): bool {
	$ret = \session_start();
	if ( isset( $_SESSION['_del'] ) ) {
		if ( $_SESSION['_del'] < time() - $timeout ) return false;
		if ( isset( $_SESSION['_new'] ) ) {
			session_write_close();
			ini_set( 'session.use_strict_mode', 0 );  // For assigning session IDs
			session_id( $_SESSION['_new'] );
			$ret = \session_start();
		}
	}
	return $ret;
}

function session_regenerate_id(): bool {
	$s = $_SESSION;  // Saving current session

	$new = session_create_id();
	$_SESSION['_new'] = $new;
	$_SESSION['_del'] = time();
	session_write_close();
	ini_set( 'session.use_strict_mode', 0 );  // For assigning session IDs
	session_id( $new );
	$res = \session_start();

	if ( $res ) $_SESSION = $s;  // Restoring session information
	return $res;
}
