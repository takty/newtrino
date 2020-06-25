<?php
namespace nt;
/**
 *
 * Response
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @version 2020-06-25
 *
 */


require_once( __DIR__ . '/define.php' );
require_once( __DIR__ . '/function.php' );
require_once( __DIR__ . '/class-store.php' );

set_locale_setting();

global $nt_config, $nt_res, $nt_store;

$nt_config = load_config( NT_DIR_DATA );
$nt_res    = load_resource( NT_DIR_DATA, $nt_config['language'] );
$nt_store  = new Store( NT_URL_POST, NT_DIR_POST, NT_DIR_DATA, $nt_config );


// -----------------------------------------------------------------------------


function create_response_archive( $query, $filter ) {
	global $nt_store, $nt_config;

	$query  = _rearrange_query( $query );
	$filter = _rearrange_filter( $filter );

	$_page           = _get_param( 'page', 1, $query );
	$_posts_per_page = _get_param( 'posts_per_page', $nt_config['posts_per_page'], $query );
	$_cat            = isset( $query['taxonomy']['category'] ) ? $query['taxonomy']['category'] : '';
	$_date           = _get_param( 'date', '', $query );
	$_search         = _get_param( 'search', '', $query );

	$ret = $nt_store->getPostsByPage( $_page - 1, $_posts_per_page, [ 'cat' => $_cat, 'date' => $_date, 'search_word' => $_search ] );
	$posts = [];
	foreach ( $ret['posts'] as $p ) $posts[] = _create_post_data( $p );

	$res = [
		'status'     => 'success',
		'posts'      => $posts,
		'page_count' => ceil( $ret['size'] / $_posts_per_page ),
	];
	$res += _create_archive_data( $filter );
	return $res;
}

function create_response_single( $query, $filter ) {
	global $nt_store;

	$query  = _rearrange_query( $query );
	$filter = _rearrange_filter( $filter );

	$_id     = _get_param( 'id', '', $query );
	$_cat    = isset( $query['taxonomy']['category'] ) ? $query['taxonomy']['category'] : '';
	$_date   = _get_param( 'date', '', $query );
	$_search = _get_param( 'search', '', $query );

	$ret = $nt_store->getPostWithNextAndPrevious( $_id, [ 'cat' => $_cat, 'date' => $_date, 'search_word' => $_search ] );

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
	$taxonomies = [ 'category' ];  // TODO
	foreach( $tcs as $tc ) {
		if ( in_array( $tc, $taxonomies, true ) ) {
			if ( ! isset( $ret['taxonomy'] ) ) $ret['taxonomy'] = [];
			$ret['taxonomy'][ $tc ] = $query[ $key ];
		}
	}
	return $ret;
}

function _rearrange_filter( $filter ) {
	$filter_vars = [
		'date'     => 'slug',
		'taxonomy' => 'slug_array',
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
	if ( ! $p ) return false;
	$d = [
		'id'        => $p->getId(),
		'slug'      => '',  // preserved
		'type'      => '',  // preserved
		'author'    => '',  // preserved
		'title'     => $p->getTitle( true ),
		'date'      => $p->getPublishedDateTime(),
		'modified'  => $p->getModifiedDateTime(),
		'excerpt'   => $p->getExcerpt( 60 ),
		'meta'      => [],
		'taxonomy'  => [],
		'state'     => $p->getStateClasses(),  // temporary
	];
	if ( $include_content ) {
		$d['content'] = $p->getContent();
	}
	$d['taxonomy']['category'] = [
		[
			'slug'  => $p->getCategory(),
			'label' => $p->getCategoryName(),
		]
	];
	if ($p->getCategory() === 'event') {
		$d['meta']['event_state']    = $p->getEventState();
		$d['meta']['event_date_bgn'] = $p->getEventDateBgn();
		$d['meta']['event_date_end'] = $p->getEventDateEnd();
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
	if ( $type === 'month' ) {
		$dates = $nt_store->getCountByDate();
		foreach ( $dates as &$date ) {
			$date['label'] = $date['name'];
			$date['slug'] = $date['date'];
			unset($date['name']);
			unset($date['cur']);
			unset($date['date']);
		}
		return [ $type => $dates ];
	}
}

function _get_taxonomy_archive( $taxes ) {
	global $nt_store;
	$ret = [];
	foreach ( $taxes as $tax ) {
		$ts = $nt_store->taxonomy()->getTerms( $tax );
		$cs = [];
		foreach ( $ts as $t ) {
			$cs[] = [ 'slug' => $t['slug'], 'label' => $t['label'] ];
		}
		$ret[ $tax ] = $cs;
	}
	return $ret;
}
