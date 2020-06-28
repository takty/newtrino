<?php
namespace nt;
/**
 *
 * Response
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @version 2020-06-28
 *
 */


require_once( __DIR__ . '/index.php' );
require_once( __DIR__ . '/class-store.php' );

set_locale_setting();

global $nt_config;
$nt_config = load_config( NT_DIR_DATA );


// -----------------------------------------------------------------------------


function create_response_archive( $query, $filter, $option = [] ) {
	global $nt_store, $nt_config;
	$nt_config += $option;
	$nt_store  = new Store( NT_URL_POST, NT_DIR_POST, NT_DIR_DATA, $nt_config );

	$query  = _rearrange_query( $query );
	$filter = _rearrange_filter( $filter );

	$page   = _get_param( 'page',           null, $query );
	$ppp    = _get_param( 'posts_per_page', null, $query );
	$date   = _get_param( 'date',           null, $query );
	$search = _get_param( 'search',         null, $query );

	$args = [];
	if ( $page )   $args['page']           = $page;
	if ( $search ) $args['search']         = $search;
	if ( $ppp )    $args['posts_per_page'] = $ppp;
	if ( $date )   $args['date_query']     = [ [ 'date' => $date ] ];

	if ( ! empty( $query['taxonomy'] ) ) {
		$tq = [];
		foreach ( $query['taxonomy'] as $tax => $ts ) {
			$tq[] = [ 'taxonomy' => $tax, 'terms' => $ts ];
		}
		if ( ! empty( $tq ) ) $args['tax_query'] = $tq;
	}
	$ret = $nt_store->getPostsByPage( $args );
	$posts = array_map( '\nt\_create_post_data', $ret['posts'] );

	$res = [
		'status'     => 'success',
		'posts'      => $posts,
		'page_count' => $ret['page_count'],
	];
	$res += _create_archive_data( $filter );
	return $res;
}

function create_response_single( $query, $filter, $option = [] ) {
	global $nt_store, $nt_config;
	$nt_config += $option;
	$nt_store  = new Store( NT_URL_POST, NT_DIR_POST, NT_DIR_DATA, $nt_config );

	$query  = _rearrange_query( $query );
	$filter = _rearrange_filter( $filter );

	$id = _get_param( 'id', null, $query );

	$args = [];
	if ( ! empty( $query['taxonomy'] ) ) {
		$tq = [];
		foreach ( $query['taxonomy'] as $tax => $ts ) {
			$tq[] = [ 'taxonomy' => $tax, 'terms' => $ts ];
		}
		if ( ! empty( $tq ) ) $args['tax_query'] = $tq;
	}
	$ret = $nt_store->getPostWithNextAndPrevious( $id, $args );

	$res = [
		'status' => 'success',
		'post'   => _create_post_data( $ret ? $ret[1] : false, true ),
		'adjacent_post' => [
			'previous' => _create_post_data( $ret ? $ret[0] : false ),
			'next'     => _create_post_data( $ret ? $ret[2] : false ),
		]
	];
	$res += _create_archive_data( $filter );
	return $res;
}


// -----------------------------------------------------------------------------


function _rearrange_query( $query ) {
	$query_vars = [
		'id'             => 'int',
		'page'           => 'int',
		'posts_per_page' => 'int',
		'date'           => 'slug',
		'search'         => 'string',
	];
	$ret = [];
	$tcs = [];
	foreach ( $query as $key => $val ) {
		if ( ! isset( $query_vars[ $key ] ) ) {
			$tcs[] = $key;
			continue;
		}
		$fval = _filter_param( $val, $query_vars[ $key ] );
		if ( $fval !== null ) $ret[ $key ] = $val;
	}
	global $nt_store;
	$existing_taxes = array_keys( $nt_store->taxonomy()->getTaxonomyAll() );
	foreach( $tcs as $tc ) {
		if ( in_array( $tc, $existing_taxes, true ) ) {
			if ( ! isset( $ret['taxonomy'] ) ) $ret['taxonomy'] = [];
			$ts = array_map( 'trim', explode( ',', $query[ $key ] ) );
			$ret['taxonomy'][ $tc ] = $ts;
		}
	}
	return $ret;
}

function _rearrange_filter( $filter ) {
	$filter_vars = [
		'date'        => 'slug',
		'date_format' => 'string',
		'taxonomy'    => 'slug_array',
	];
	$ret = [];
	foreach ( $filter as $key => $val ) {
		$fval = _filter_param( $val, $filter_vars[ $key ] );
		if ( $fval !== null ) $ret[ $key ] = $val;
	}
	return $ret;
}

function _filter_param( $val, $type ) {
	$fval = null;
	switch ( $type ) {
		case 'int':
			if ( preg_match( '/[^0-9]/', $val ) ) break;
			$fval = intval( $val );
			break;
		case 'slug':
			if ( preg_match( '/[^a-zA-Z0-9-_]/', $val ) ) break;
			$fval = $val;
			break;
		case 'string':
			$fval = $val;
			break;
		case 'slug_array':
			$fval = [];
			$vals = is_array( $val ) ? $val : [ $val ];
			foreach ( $val as $v ) {
				if ( preg_match( '/[^a-zA-Z0-9-_]/', $v ) ) continue;
				$fval[] = $v;
			}
			break;
	}
	return $fval;
}

function _get_param( $key, $default, $assoc ) {
	if ( isset( $assoc[ $key ] ) ) {
		return $assoc[ $key ];
	}
	return $default;
}


// -----------------------------------------------------------------------------


function _create_post_data( $p, $include_content = false ) {
	global $nt_store;
	if ( ! $p ) return false;
	$d = [
		'id'        => $p->getId(),
		'slug'      => '',  // preserved
		'type'      => '',  // preserved
		'title'     => $p->getTitle( true ),
		'date'      => $p->getDate(),
		'modified'  => $p->getModified(),
		'excerpt'   => $p->getExcerpt( 60 ),
		'meta'      => [],
		'taxonomy'  => [],
		'status'    => $p->getStateClasses(),  // temporary
	];
	if ( $include_content ) $d['content'] = $p->getContent();

	foreach ( $p->getTaxonomyToTermSlugs() as $tax => $ts ) {
		$ls = [];
		foreach ( $ts as $t ) {
			$l = $nt_store->taxonomy()->getTermLabel( $tax, $t );
			$ls[] = [ 'slug' => $t, 'label' => $l ];
		}
		$d['taxonomy'][ $tax ] = $ls;
	}

	if ( $p->hasTerm( 'category', 'event' ) ) {
		$d['meta']['event_state'] = $p->getEventState();
		$d['meta']['date_bgn']    = $p->getEventDateBgn();
		$d['meta']['date_end']    = $p->getEventDateEnd();
	}
	return $d;
}

function _create_archive_data( $filter ) {
	if ( empty( $filter ) ) return [];
	$res = [];
	if ( isset( $filter['date'] ) ) {
		$_date  = _get_param( 'date', '', $filter );
		$res += [ 'date' => _get_date_archive( $_date ) ];
	}
	if ( isset( $filter['taxonomy'] ) ) {
		$_taxes = _get_param( 'taxonomy', [], $filter );
		$res += [ 'taxonomy' => _get_taxonomy_archive( $_taxes ) ];
	}
	return $res;
}

function _get_date_archive( $type ) {
	global $nt_store;
	$ds = $nt_store->getCountByDate( $type );
	$cs = [];
	foreach ( $ds as $d ) {
		$cs[] = [ 'slug' => $d['slug'], 'count' => $d['count'] ];
	}
	return [ $type => $cs ];
}

function _get_taxonomy_archive( $taxes ) {
	global $nt_store;
	$ret = [];
	foreach ( $taxes as $tax ) {
		$ts = $nt_store->taxonomy()->getTermAll( $tax );
		$cs = [];
		foreach ( $ts as $t ) {
			$cs[] = [ 'slug' => $t['slug'], 'label' => $t['label'] ];
		}
		$ret[ $tax ] = $cs;
	}
	return $ret;
}
