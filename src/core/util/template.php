<?php
/**
 * Functions for Template Engine
 *
 * @author Takuto Yanagida
 * @version 2022-12-20
 */

namespace nt;

require_once( __DIR__ . '/../lib/Mustache/Autoloader.php' );
\Mustache_Autoloader::register();

$mustache_engine = null;
$view_stack      = [];

function begin( ?array $view = null, bool $condition = true ): void {
	global $mustache_engine, $view_stack;

	if ( null === $mustache_engine ) {
		$mustache_engine = new \Mustache_Engine( [ 'entity_flags' => ENT_QUOTES ] );
	}
	if ( null !== $view ) {
		$view_stack[] = [ $view, $condition ];
	}
	ob_start();
}

function end( ?array $view = null, bool $condition = true ): void {
	global $mustache_engine, $view_stack;

	$tmpl = ob_get_contents();
	ob_end_clean();
	if ( null === $view ) {
		list( $view, $condition ) = array_pop( $view_stack );
	}
	if ( $condition ) {
		echo $mustache_engine->render( $tmpl, $view );
	}
}
