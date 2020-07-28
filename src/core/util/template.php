<?php
namespace nt;
/**
 *
 * Functions for Template Engine
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @version 2020-07-28
 *
 */


require_once( __DIR__ . '/../lib/Mustache/Autoloader.php' );
\Mustache_Autoloader::register();


$mustache_engine = null;

function begin() {
	global $mustache_engine;
	if ( $mustache_engine === null ) {
		$mustache_engine = new \Mustache_Engine( [ 'entity_flags' => ENT_QUOTES ] );
	}
	ob_start();
}

function end( array $view, bool $condition = true ) {
	global $mustache_engine;
	$tmpl = ob_get_contents();
	ob_end_clean();
	if ( $condition ) echo $mustache_engine->render( $tmpl, $view );
}
