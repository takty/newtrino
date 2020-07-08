<?php
namespace nt;
/**
 *
 * Init for Private
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @version 2020-07-08
 *
 */


 define( 'NT_ADMIN', true );

require_once( __DIR__ . '/../core/index.php' );
require_once( __DIR__ . '/../core/class-store.php' );
require_once( __DIR__ . '/class-session.php' );
require_once( __DIR__ . '/class-media.php' );

reject_direct_access( NT_URL_HOST, __FILE__, 2 );
set_locale_setting();

$nt_config  = load_config( NT_DIR_DATA );
$nt_res     = load_resource( NT_DIR_ADMIN, $nt_config['lang_admin'] );
$nt_q       = empty( $_POST ) ? $_GET : $_POST;
$nt_q      += [
	'mode'           => '',
	'id'             => 0,
	'page'           => 1,
	'posts_per_page' => 10,
	'cat'            => '',
	'date'           => '',
	'date_bgn'       => '',
	'date_end'       =>  ''
];

$nt_store   = new Store( NT_URL, NT_DIR, NT_DIR_DATA, $nt_config );
$nt_session = new Session( NT_URL_ADMIN, NT_DIR_DATA, NT_DIR_SESSION );

if ( $nt_session->start() ) {
	$la = $nt_session->getLangAdmin();
	if ( $la ) {
		$nt_config['lang_admin'] = $la;
		$nt_res = load_resource( NT_DIR_ADMIN, $nt_config['lang_admin'] );
	}
} else {
	header( 'Location: ' . NT_URL_ADMIN . 'login.php' );
	exit( 1 );
}
