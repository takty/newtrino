<?php
/**
 * Handler - Preview
 *
 * @author Takuto Yanagida
 * @version 2020-08-12
 */

namespace nt;

define( 'NT_ADMIN_PREVIEW', true );

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
	$tax2tls = [];
	foreach ( $taxes as $tax ) {
		if ( ! isset( $q["taxonomy:$tax"] ) ) continue;
		$ts = is_array( $q["taxonomy:$tax"] ) ? $q["taxonomy:$tax"] : [ $q["taxonomy:$tax"] ];
		$ls = [];
		foreach ( $ts as $t ) {
			$ls[] = _h( $nt_store->taxonomy()->getTermLabel( $tax, $t ) );
		}
		if ( ! empty( $ls ) ) $tax2tls[] = [ 'taxonomy' => $tax, 'term_labels' => $ls ];
	}

	$ret = [
		'title'      => $q_title,
		'date'       => $q_date,
		'content'    => $q_content,
		'taxonomies' => $tax2tls,
		'css'        => get_asset_url( [ 'preview.min.css', 'preview.css' ] ),
		'js'         => get_asset_url( [ 'preview.min.js', 'preview.js' ] ),
	];
	return $ret;
}
