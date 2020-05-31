<?php
namespace nt;
/**
 *
 * Ajax API
 *
 * @author Takuto Yanagida
 * @version 2020-05-31
 *
 */


require_once( __DIR__ . '/define.php' );
require_once( __DIR__ . '/function.php' );
require_once( __DIR__ . '/class-store.php' );

set_locale_setting();

$nt_config = load_config( NT_DIR_DATA );
$nt_res    = load_resource( NT_DIR_DATA, $nt_config['language'] );
$nt_store  = new Store( NT_URL_POST, NT_DIR_POST, NT_DIR_DATA, $nt_config );


// -----------------------------------------------------------------------------


$action = ( isset( $_POST['action'] ) ) ? $_POST['action'] : '';
$filter = ( isset( $_POST['filter'] ) ) ? json_decode( $_POST['filter'], true ) : [];
$query  = ( isset( $_POST['query' ] ) ) ? json_decode( $_POST['query' ], true ) : [];

switch ( $action ) {
	case 'recent' : $d = process_request_recent( $filter, $query );  break;
	case 'archive': $d = process_request_archive( $filter, $query ); break;
	case 'single' : $d = process_request_single( $filter, $query );  break;
	default       : $d = [ 'status' => 'failure' ]; break;
}
header( 'Content-Type: text/html; charset=UTF-8' );
echo json_encode( $d, JSON_UNESCAPED_UNICODE );


// -----------------------------------------------------------------------------

/*
	res {
		status:
		post:
		posts:
		page_count:
		adjacent_post: {
			previous: p
			next: p
		}
		date: {
			month: [
				date:
				count:
				label:
			]
		}
		taxonomy: {
			category: [
				slug: str
				label: str
			]
		}
	}
 */

function process_request_recent( $filter, $query ) {
	global $nt_store;

	$count = get_param_int( 'count', 10, $_POST );
	$cat   = get_param_slug( 'category', '', $_POST );
	$ofe   = get_param_bool( 'omit-finished-event', false, $_POST );

	$ret = $nt_store->getPosts(0, $count, ['cat' => $cat, 'omit_finished_event' => $ofe]);

	$posts = [];
	foreach ( $ret['posts'] as $p ) $posts[] = create_post_data( $p );

	return [
		'status' => 'success',
		'posts'  => $posts,
	];
}

function process_request_archive( $filter, $query ) {
	global $nt_store, $nt_config;

	$date   = get_param_slug( 'date', '', $query );
	$cat    = get_param_slug( 'category', '', $query );
	$search = get_param_string( 'search', '', $query );
	$page   = get_param_int( 'page', 1, $query );

	$ret = $nt_store->getPostsByPage( $page - 1, $nt_config['posts_per_page'], [ 'cat' => $cat, 'date' => $date, 'search_word' => $search ] );
	$posts = [];
	foreach ( $ret['posts'] as $p ) $posts[] = create_post_data( $p );

	return [
		'status'     => 'success',
		'posts'      => $posts,
		'page_count' => ceil( $ret['size'] / $nt_config['posts_per_page'] ),
		'date'      => get_date_archive( get_param_slug( 'date', '', $filter ) ),
		'taxonomy' => get_taxonomy_archive( isset( $filter['taxonomy'] ) ? $filter['taxonomy'] : [] ),
	];
}

function process_request_single( $filter, $query ) {
	global $nt_store;

	$id     = get_param_string( 'id', '', $query );
	$cat    = get_param_slug( 'category', '', $query );
	$date   = get_param_slug( 'date', '', $query );
	$search = get_param_string( 'search', '', $query );

	$ret = $nt_store->getPostWithNextAndPrevious( $id, [ 'cat' => $cat, 'date' => $date, 'search_word' => $search ] );

	return [
		'status' => 'success',
		'post'   => create_post_data( $ret ? $ret[1] : false, true ),
		'adjacent_post' => [
			'previous' => create_post_data( $ret ? $ret[0] : false ),
			'next'     => create_post_data( $ret ? $ret[2] : false ),
		],
		'date' => get_date_archive( get_param_slug( 'date', '', $filter ) ),
		'taxonomy' => get_taxonomy_archive( isset( $filter['taxonomy'] ) ? $filter['taxonomy'] : [] ),
	];
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


function get_param_int( string $key, $default, $assoc ) {
	if ( isset( $assoc[ $key ] ) && ! preg_match( '/[^0-9]/', $assoc[ $key ] ) ) {
		return intval( $assoc[ $key ] );
	}
	return $default;
}

function get_param_bool( string $key, $default, $assoc ) {
	if ( isset( $assoc[ $key ] ) && ! preg_match( '/[^0-1]/', $assoc[ $key ] ) ) {
		return intval( $assoc[ $key ] ) === 1;
	}
	return $default;
}

function get_param_slug( string $key, $default, $assoc ) {
	if ( isset( $assoc[ $key ] ) && ! preg_match( '/[^a-zA-Z0-9-_]/', $assoc[ $key ] ) ) {
		return $assoc[ $key ];
	}
	return $default;
}

function get_param_string( string $key, $default, $assoc ) {
	if ( isset( $assoc[ $key ] ) ) {
		return $assoc[ $key ];
	}
	return $default;
}
