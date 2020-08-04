<?php
namespace nt;
/**
 *
 * Init for Admin
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @version 2020-08-04
 *
 */


define( 'NT_ADMIN', true );

require_once( __DIR__ . '/../core/index.php' );
require_once( __DIR__ . '/class-session.php' );
require_once( __DIR__ . '/util/tag.php' );

$nt_config  = load_config( NT_DIR_DATA );
$nt_res     = load_resource( NT_DIR_ADMIN_RES, $nt_config['lang_admin'] );
$nt_session = new Session( NT_URL_ADMIN, NT_DIR_DATA, NT_DIR_SESSION );


// Functions for Initial Process -----------------------------------------------


function load_resource( string $dirData, string $lang ): array {
	$path = $dirData . $lang . '.json';
	if ( is_file( $path ) && is_readable( $path ) ) {
		$json = file_get_contents( $path );
		$res = json_decode( $json, true );
		if ( $res !== null ) return $res;
	}
	return [];
}

function start_session( bool $create_store, bool $close_dialog = false ) {
	global $nt_session, $nt_config, $nt_res;

	if ( $nt_session->start() ) {
		$la = $nt_session->getLangAdmin();
		if ( $la ) {
			$nt_config['lang_admin'] = $la;
			$nt_res = load_resource( NT_DIR_ADMIN_RES, $nt_config['lang_admin'] );
		}
		if ( $create_store ) {
			global $nt_store;
			$nt_store = new Store( NT_URL, NT_DIR, NT_DIR_DATA, $nt_config );
		}
	} else if ( $close_dialog ) {
		header( 'Content-Type: text/html;charset=utf-8' );
		echo '<!DOCTYPE html><html><head><script>window.parent.closeDialog();</script></head><body></body></html>';
	} else {
		header( 'Location: ' . NT_URL_ADMIN . 'login.php' );
		exit();
	}
}


// Utilities -------------------------------------------------------------------


function get_asset_url( $fs ) {
	foreach ( $fs as $f ) {
		if ( is_file( NT_DIR_DATA . $f ) && is_readable( NT_DIR_DATA . $f ) ) {
			return NT_URL . 'data/' . $f;
		}
	}
	return null;
}
