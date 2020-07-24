<?php
namespace nt;
/**
 *
 * Handler - Preview
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @version 2020-07-25
 *
 */


require_once( __DIR__ . '/index.php' );
require_once( __DIR__ . '/../core/class-store.php' );
require_once( __DIR__ . '/../core/util/template.php' );

start_session( true, true );


function handle_query( array $q ): array {
	$q_title   = $q['post_title']   ?? '';
	$q_date    = $q['post_date']    ?? '';
	$q_content = $q['post_content'] ?? '';

	global $nt_store;
	$taxes = array_keys( $nt_store->taxonomy()->getTaxonomyAll() );
	$taxes = [];
	foreach ( $taxes as $tax ) {
		if ( ! isset( $q["taxonomy:$tax"] ) ) continue;
		$ts = is_array( $q["taxonomy:$tax"] ) ? $q["taxonomy:$tax"] : [ $q["taxonomy:$tax"] ];
		$ls = [];
		foreach ( $ts as $t ) {
			$ls[] = _h( $nt_store->taxonomy()->getTermLabel( $tax, $t ) );
		}
		if ( ! empty( $ls ) ) $taxes[] = [ 'taxonomy' => $tax, 'term_labels' => $ls ];
	}

	return [
		'title'      => $q_title,
		'date'       => $q_date,
		'content'    => $q_content,
		'taxonomies' => $taxes,
	];
}
