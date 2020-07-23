<?php
namespace nt;
/**
 *
 * Init for Admin
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @version 2020-07-23
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
	if ( is_file( $path ) ) {
		$json = file_get_contents( $path );
		return json_decode( $json, true );
	}
	return [];
}

function start_session( bool $create_store ) {
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
	} else {
		header( 'Location: ' . NT_URL_ADMIN . 'login.php' );
		exit();
	}
}
