<?php
/**
 * Init for Admin
 *
 * @author Takuto Yanagida
 * @version 2024-03-25
 */

namespace nt;

define( 'NT_ADMIN', true );

require_once( __DIR__ . '/util/tqs.php' );
require_once( __DIR__ . '/../core/index.php' );
require_once( __DIR__ . '/class-session.php' );

$nt_config  = load_config( NT_DIR_DATA );
$nt_res     = load_resource( NT_DIR_ADMIN_RES, $nt_config['lang_admin'] );
$nt_session = new Session( NT_DIR_SESSION );


// Functions for Initial Process -----------------------------------------------


/**
 * Sends headers to prevent caching.
 */
function nocache_headers(): void {
	header( 'Expires: Wed, 11 Jan 1984 05:00:00 GMT' );
	header( 'Cache-Control: no-cache, must-revalidate, max-age=0' );
	header_remove( 'Last-Modified' );
}

/**
 * Loads a resource file.
 *
 * @param string $dirData The directory of the data.
 * @param string $lang    The language of the resource.
 * @return array<string, mixed> The loaded resource.
 */
function load_resource( string $dirData, string $lang ): array {
	$path = $dirData . $lang . '.json';
	if ( is_file( $path ) && is_readable( $path ) ) {
		$json = file_get_contents( $path );
		if ( $json === false ) return [];
		$res = json_decode( $json, true );
		if ( $res !== null ) return $res;
	}
	return [];
}

/**
 * Starts a session.
 *
 * @param bool $create_store Whether to create a store.
 * @param bool $is_dialog    Whether it is a dialog.
 */
function start_session( bool $create_store, bool $is_dialog = false ): void {
	global $nt_session, $nt_config, $nt_res;

	if ( $nt_session->start() ) {
		$la = $nt_session->getLanguage();
		if ( $la ) {
			$nt_config['lang_admin'] = $la;
			$nt_res = load_resource( NT_DIR_ADMIN_RES, $nt_config['lang_admin'] );
		}
		if ( $create_store ) {
			global $nt_store;
			$nt_store = new Store( NT_URL, NT_DIR, NT_DIR_DATA, $nt_config );
		}
	} elseif ( $is_dialog ) {
		close_dialog_frame( true );
		exit;
	} else {
		nocache_headers();
		header( 'Location: ' . NT_URL_ADMIN . 'login.php', true, 302 );
		exit;
	}
}

/**
 * Starts an AJAX session.
 *
 * @param bool $create_store Whether to create a store.
 * @return bool Whether the session started successfully.
 */
function start_ajax_session( bool $create_store ) {
	global $nt_session;

	if ( $nt_session->start( true ) ) {
		if ( $create_store ) {
			global $nt_store, $nt_config;
			$nt_store = new Store( NT_URL, NT_DIR, NT_DIR_DATA, $nt_config );
		}
		return true;
	}
	return false;
}

/**
 * Closes a dialog frame.
 *
 * @param bool $do_re_login Whether to re-login.
 */
function close_dialog_frame( $do_re_login = false ): void {
	$f = $do_re_login ? 'true' : '';
	header( 'Content-Type: text/html;charset=utf-8' );
	echo "<!DOCTYPE html><html><head><script>window.parent.closeDialog($f);</script></head><body></body></html>";
}


// Utilities -------------------------------------------------------------------


/**
 * Gets the URL of an asset.
 *
 * @param string[] $fs The file names of the assets.
 * @return string|null The URL of the asset.
 */
function get_asset_url( $fs ): ?string {
	foreach ( $fs as $f ) {
		if ( is_file( NT_DIR_DATA . $f ) && is_readable( NT_DIR_DATA . $f ) ) {
			return NT_URL . 'data/' . $f;
		}
	}
	return null;
}

/**
 * Translates a string.
 *
 * @param string $str The string to translate.
 * @return string The translated string.
 */
function translate( string $str ): string {
	global $nt_res;
	foreach ( $nt_res as $key => $vals ) {
		if ( isset( $vals[ $str ] ) ) return $vals[ $str ];
	}
	return $str;
}

/**
 * Escapes HTML special characters in a string.
 *
 * @param string $str The string to escape.
 * @return string The escaped string.
 */
function _h( string $str ): string {
	return htmlspecialchars( $str, ENT_QUOTES, 'UTF-8' );
}

/**
 * Translates and escapes HTML special characters in a string.
 *
 * @param string $str The string to translate and escape.
 * @return string The translated and escaped string.
 */
function _ht( string $str ): string {
	return htmlspecialchars( translate( $str ), ENT_QUOTES, 'UTF-8' );
}
