<?php
namespace nt;
/**
 *
 * Response
 *
 * @author Takuto Yanagida
 * @version 2020-06-07
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

	$_page           = get_param_int( 'page', 1, $query );
	$_posts_per_page = get_param_int( 'posts_per_page', $nt_config['posts_per_page'], $query );
	$_cat            = get_param_slug( 'category', '', $query );
	$_date           = get_param_slug( 'date', '', $query );
	$_search         = get_param_string( 'search', '', $query );

	$ret = $nt_store->getPostsByPage( $_page - 1, $_posts_per_page, [ 'cat' => $_cat, 'date' => $_date, 'search_word' => $_search ] );
	$posts = [];
	foreach ( $ret['posts'] as $p ) $posts[] = create_post_data( $p );

	$res = [
		'status'     => 'success',
		'posts'      => $posts,
		'page_count' => ceil( $ret['size'] / $_posts_per_page ),
	];
	$res += create_archive_data( $filter );
	return $res;
}

function create_response_single( $query, $filter ) {
	global $nt_store;

	$_id     = get_param_slug( 'id', '', $query );
	$_cat    = get_param_slug( 'category', '', $query );
	$_date   = get_param_slug( 'date', '', $query );
	$_search = get_param_string( 'search', '', $query );

	$ret = $nt_store->getPostWithNextAndPrevious( $_id, [ 'cat' => $_cat, 'date' => $_date, 'search_word' => $_search ] );

	$res = [
		'status' => 'success',
		'post'   => create_post_data( $ret ? $ret[1] : false, true ),
		'adjacent_post' => [
			'previous' => create_post_data( $ret ? $ret[0] : false ),
			'next'     => create_post_data( $ret ? $ret[2] : false ),
		]
	];
	$res += create_archive_data( $filter );
	return $res;
}


// -----------------------------------------------------------------------------


function create_post_data( $p, $include_content = false ) {
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
			'label' => translate($p->getCategoryName(), 'category'),
		]
	];
	if ($p->getCategory() === 'event') {
		$d['meta']['event_state']    = $p->getEventState();
		$d['meta']['event_date_bgn'] = $p->getEventDateBgn();
		$d['meta']['event_date_end'] = $p->getEventDateEnd();
	}
	return $d;
}

function create_archive_data( $filter ) {
	if ( empty( $filter ) ) return [];
	$res = [];
	if ( isset( $filter['date'] ) ) {
		$_date  = get_param_slug( 'date', '', $filter );
		$res += [ 'date' => get_date_archive( $_date ) ];
	}
	if ( isset( $filter['taxonomy'] ) ) {
		$_taxes = get_param_slug_array( 'taxonomy', [], $filter );
		$res += [ 'taxonomy' => get_taxonomy_archive( $_taxes ) ];
	}
	return $res;
}

function get_date_archive( $type ) {
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

function get_taxonomy_archive( $taxonomy_s ) {
	global $nt_store;
	$ret = [];
	foreach ( $taxonomy_s as $taxonomy ) {
		if ( $taxonomy === 'category' ) {
			$terms = $nt_store->getCategoryData();
			foreach ( $terms as &$term ) {
				$term['label'] = $term['name'];
				unset($term['name']);
				unset($term['cur']);
			}
			$ret[ $taxonomy ] = $terms;
		}
	}
	return $ret;
}


// -----------------------------------------------------------------------------


function get_param_int( $key, $default, $assoc ) {
	if ( isset( $assoc[ $key ] ) && ! preg_match( '/[^0-9]/', $assoc[ $key ] ) ) {
		return intval( $assoc[ $key ] );
	}
	return $default;
}

function get_param_slug( $key, $default, $assoc ) {
	if ( isset( $assoc[ $key ] ) && ! preg_match( '/[^a-zA-Z0-9-_]/', $assoc[ $key ] ) ) {
		return $assoc[ $key ];
	}
	return $default;
}

function get_param_string( $key, $default, $assoc ) {
	if ( isset( $assoc[ $key ] ) ) {
		return $assoc[ $key ];
	}
	return $default;
}

function get_param_slug_array( $key, $default, $assoc ) {
	if ( ! isset( $assoc[ $key ] ) ) return $default;
	$ret = [];
	if ( is_array( $assoc[ $key ] ) ) {
		$vs = $assoc[ $key ];
		foreach ( $vs as $v ) {
			if ( ! preg_match( '/[^a-zA-Z0-9-_]/', $v ) ) {
				$ret[] = $v;
			}
		}
	} else {
		$v = $assoc[ $key ];
		if ( ! preg_match( '/[^a-zA-Z0-9-_]/', $v ) ) {
			$ret[] = $v;
		}
	}
	return $ret;
}
