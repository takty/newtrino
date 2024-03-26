<?php
/**
 * Functions for Template Engine
 *
 * @author Takuto Yanagida
 * @version 2024-03-26
 */

namespace nt;

require_once( __DIR__ . '/../lib/Mustache/Autoloader.php' );
\Mustache_Autoloader::register();

$mustache_engine = null;
$view_stack      = [];

/**
 * Begins a new output buffer and optionally pushes a view onto the view stack.
 * If the Mustache engine is not initialized, it initializes it.
 *
 * @param ?array $view      The view to be pushed onto the view stack. Default is null.
 * @param bool   $condition The condition associated with the view. Default is true.
 */
function begin( ?array $view = null, bool $condition = true ): void {  // @phpstan-ignore-line
	global $mustache_engine, $view_stack;

	if ( null === $mustache_engine ) {
		$mustache_engine = new \Mustache_Engine( [ 'entity_flags' => ENT_QUOTES ] );
	}
	if ( null !== $view ) {
		$view_stack[] = [ $view, $condition ];
	}
	ob_start();
}

/**
 * Ends the output buffer and renders the template with the Mustache engine.
 * If a view is not provided, it pops a view from the view stack.
 *
 * @param ?array $view      The view to be used for rendering. Default is null.
 * @param bool   $condition The condition associated with the view. Default is true.
 */
function end( ?array $view = null, bool $condition = true ): void {  // @phpstan-ignore-line
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
