<?php
namespace nt;
/**
 *
 * Definitions of Constants and Functions
 *
 * @author Takuto Yanagida
 * @version 2021-06-28
 *
 */


error_reporting( E_ALL );
ini_set( 'display_errors', 'On' );
setlocale( LC_ALL, 'en_US.UTF-8' );  // for pathinfo

require_once( __DIR__ . '/util/url.php' );
require_once( __DIR__ . '/class-logger.php' );


define( 'NT_DIR', pathinfo( __DIR__, PATHINFO_DIRNAME ) . '/' );
define( 'NT_DIR_DATA', NT_DIR . 'data/' );
define( 'NT_DIR_POST', NT_DIR . 'post/' );

define( 'NT_URL_HOST', ( empty( $_SERVER['HTTPS'] ) ? 'http://' : 'https://' ) . $_SERVER['HTTP_HOST'] );
define( 'NT_URL', NT_URL_HOST . get_url_from_path( NT_DIR ) . '/' );
define( 'NT_URL_POST', NT_URL_HOST . get_url_from_path( NT_DIR_POST ) . '/' );

if ( defined( 'NT_ADMIN' ) ) {
	define( 'NT_DIR_ADMIN', NT_DIR . 'admin/' );
	define( 'NT_DIR_ADMIN_RES', NT_DIR . 'admin/res/' );
	define( 'NT_DIR_AUTH', NT_DIR_ADMIN . 'var/auth/' );
	define( 'NT_DIR_SESSION', NT_DIR_ADMIN . 'var/session/' );

	define( 'NT_URL_ADMIN', NT_URL_HOST . get_url_from_path( NT_DIR_ADMIN ) . '/' );
}

if ( file_exists( NT_DIR_DATA . 'mode.php' ) ) require_once( NT_DIR_DATA . 'mode.php' );  // for backward compatible
if ( file_exists( NT_DIR_DATA . 'config.php' ) ) require_once( NT_DIR_DATA . 'config.php' );

if ( ! defined( 'NT_MODE_DIR'  ) ) define( 'NT_MODE_DIR',  0770 );
if ( ! defined( 'NT_MODE_FILE' ) ) define( 'NT_MODE_FILE', 0660 );
if ( ! defined( 'NT_DEBUG'     ) ) define( 'NT_DEBUG', false );

Logger::$debug = NT_DEBUG;


// Functions Used in Initial Process -------------------------------------------


function load_config( string $dirData ): array {
	$conf = [];
	$path = $dirData . 'config.json';
	if ( is_file( $path ) && is_readable( $path ) ) {
		$json = file_get_contents( $path );
		if ( $json !== false ) {
			$conf = json_decode( $json, true ) ?? [];
		}
	}
	// Default Config
	$conf += [
		'timezone'           => 'Asia/Tokyo',
		'lang'               => 'en',
		'lang_admin'         => 'en',
		'per_page'           => 10,
		'new_arrival_period' => 7,
		'date_format'        => 'Y-m-d',
		'archive_by_year'    => true,
		'archive_by_type'    => true,
		'image_sizes' => [
			'small'        => [ 'width' =>  128, 'label' => 'Small' ],
			'medium_small' => [ 'width' =>  256, 'label' => 'Medium Small' ],
			'medium'       => [ 'width' =>  384, 'label' => 'Medium' ],
			'medium_large' => [ 'width' =>  512, 'label' => 'Medium Large' ],
			'large'        => [ 'width' =>  768, 'label' => 'Large' ],
			'extra_large'  => [ 'width' => 1024, 'label' => 'Extra Large' ],
			'huge'         => [ 'width' => 1536, 'label' => 'Huge' ],
		]
	];
	date_default_timezone_set( $conf['timezone'] );
	mb_internal_encoding( 'utf-8' );
	return $conf;
}
