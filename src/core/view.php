<?php
namespace nt;
/**
 *
 * View (PHP)
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @version 2020-06-25
 *
 */


require_once( __DIR__ . '/response.php' );
require_once( __DIR__ . '/lib/mustache/Autoloader.php' );
\Mustache_Autoloader::register();

function query( $filter = [ 'date' => 'month', 'taxonomy' => [ 'category' ] ], $base_url = false ) {
	if ( ! $base_url ) $base_url = parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH );

	$msg = [ 'query' => parse_query_string( 'id' ), 'filter' => $filter ];
	if ( isset( $msg['query']['id'] ) ) {
		return _create_view_single( $msg, $base_url );
	} else {
		return _create_view_archive( $msg, $base_url );
	}
}

function query_recent_posts( $count = 10, $base_url = false ) {
	if ( ! $base_url ) $base_url = parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH );

	$msg = [ 'query' => [ 'posts_per_page' => $count ], 'filter' => [] ];
	return _create_view_archive( $msg, $base_url );
}


// -----------------------------------------------------------------------------


function _create_view_archive( $msg, $base_url ) {
	$res = create_response_archive( $msg['query'], $msg['filter'] );
	if ( $res['status'] !== 'success' ) $res['posts'] = [];

	$view = [];
	$view['posts'] = _process_posts_for_view( $res['posts'], $base_url );
	$view['navigation'] = [];
	$view['navigation']['pagination'] = _create_pagination_view( $msg, $res['page_count'], $base_url );
	$view['filter'] = _create_filter_view( $msg, $res, $base_url );
	return $view;
}

function _create_view_single( $msg, $base_url ) {
	$res = create_response_single( $msg['query'], $msg['filter'] );
	if ( $res['status'] !== 'success' ) $res['post'] = null;

	$view = [];
	list( $view['post'] ) = _process_posts_for_view( [ $res['post'] ], $base_url );
	$view['navigation'] = [];
	$view['navigation']['post_navigation'] = _create_post_navigation_view( $msg, $res['adjacent_post'], $base_url );
	$view['filter'] = _create_filter_view( $msg, $res, $base_url );
	return $view;
}


// -----------------------------------------------------------------------------


function _process_posts_for_view( $items, $base_url ) {
	foreach ( $items as &$p ) {
		if ( ! $p ) continue;
		if ( isset( $p['taxonomy'] ) ) {
			foreach ( $p['taxonomy'] as $tax_slug => $terms ) {
				$a = [];
				foreach ( $terms as $term ) $a[ $term['slug'] ] = true;
				$p['taxonomy'][ '$' . $tax_slug ] = $a;
			}
		}
		$p['url'] = $base_url . '?' . urlencode( $p['id'] );
	}
	return $items;
}

function _create_pagination_view( $msg, $page_count, $base_url ) {
	$cur = isset( $msg['query']['page'] ) ? max( 1, min( $msg['query']['page'], $page_count ) ) : 1;
	$pages = [];
	for ( $i = 1; $i <= $page_count; $i += 1 ) {
		$cq = _create_canonical_query( $msg['query'], [ 'page' => $i ] );
		$url = $base_url . ( ! empty( $cq ) ? ('?' . $cq) : '');
		$p = [ 'label' => $i, 'url' => $url ];
		if ( $i === $cur ) $p['is_current'] = true;
		$pages[] = $p;
	}
	return [
		'previous' => ( ( 1 < $cur ) ? $pages[ $cur - 2 ]['url'] : '' ),
		'next'     => ( ( $cur < $page_count ) ? $pages[ $cur ]['url'] : '' ),
		'pages'    => $pages
	];
}

function _create_post_navigation_view( $msg, $adjacent_posts, $base_url ) {
	$ps = _process_posts_for_view( [ $adjacent_posts['previous'], $adjacent_posts['next'] ], $base_url );
	return [
		'previous' => $ps[0],
		'next'     => $ps[1],
	];
}


// -----------------------------------------------------------------------------


function _create_filter_view( $msg, $res, $base_url ) {
	$v = [];
	if ( isset( $res['date'] ) ) {
		$keys = array_keys( $res['date'] );
		foreach ( $res['date'] as $type => $dates ) {
			$v['date'] = _create_date_filter_view( $msg, $type, $dates, $base_url );
			break;
		}
	}
	$v['taxonomy'] = [];
	if ( isset( $res['taxonomy'] ) ) {
		foreach ( $res['taxonomy'] as $tax => $terms ) {
			$v['taxonomy'] += _create_taxonomy_filter_view( $msg, $tax, $terms, $base_url );
		}
	}
	$v['search'] = [
		'keyword' => isset( $msg['query']['search'] ) ? $msg['query']['search'] : ''
	];
	return $v;
}

function _create_date_filter_view( $msg, $type, $dates, $base_url ) {
	$cur = isset( $msg['query']['date'] ) ? $msg['query']['date'] : '';
	$as = [];
	foreach ( $dates as $date ) {
		$cq = _create_canonical_query( [ 'date' => $date['slug'] ] );
		$url = $base_url . (! empty( $cq ) ? ('?' . $cq) : '');
		$p = [ 'label' => $date['label'], 'url' => $url ];
		if ( $date['slug'] === $cur . '' ) $p['is_current'] = true;
		$as[] = $p;
	}
	return [
		$type => $as
	];
}

function _create_taxonomy_filter_view( $msg, $taxonomy, $terms, $base_url ) {
	$cur = isset( $msg['query'][ $taxonomy ] ) ? $msg['query'][ $taxonomy ] : '';
	$as = [];
	foreach ( $terms as $term ) {
		$cq = _create_canonical_query( [ $taxonomy => $term['slug'] ] );
		$url = $base_url . (! empty( $cq ) ? ('?' . $cq) : '');
		$p = [ 'label' => $term['label'], 'url' => $url ];
		if ( $term['slug'] === $cur ) $p['is_current'] = true;
		$as[] = $p;
	}
	return [
		$taxonomy => $as
	];
}


// -----------------------------------------------------------------------------


function _create_canonical_query( $ps, $overwrite = [] ) {
	$ps = array_merge( [], $ps, $overwrite );
	$qs = [];
	if ( isset( $ps['id'] ) ) {
		$qs[] = [ 'id', $ps['id'] ];
	} else if ( isset( $ps['date'] ) ) {
		$qs[] = ['date', $ps['date'] ];
	} else if ( isset( $ps['search'] ) ) {
		$qs[] = ['search', $ps['search'] ];
	} else {  // taxonomy
		foreach ( $ps as $tax => $terms ) {
			if ( $tax === 'id' || $tax === 'date' || $tax === 'search' || $tax === 'page' ) continue;
			$ts = is_array( $terms ) ? implode( ',', $terms ) : $terms;
			$qs[] = [ $tax, $ts ];
		}
	}
	if ( isset( $ps['page'] ) ) {
		if ( 1 < $ps['page'] ) $qs[] = [ 'page', $ps['page'] ];
	}
	return create_query_string( $qs );
}


// -----------------------------------------------------------------------------


$mustache_engine = null;

function begin() {
	global $mustache_engine;
	if ( $mustache_engine === null ) {
		$mustache_engine = new \Mustache_Engine( [ 'entity_flags' => ENT_QUOTES ] );
	}
	ob_start();
}

function end( $view, $condition = true ) {
	global $mustache_engine;
	$tmpl = ob_get_contents();
	ob_end_clean();
	if ( $condition ) echo $mustache_engine->render( $tmpl, $view );
}


// -----------------------------------------------------------------------------


function parse_query_string( $default_key ) {
	$ps = [];
	$default_val = '';
	foreach ( $_GET as $key => $val ) {
		if ( empty( $val ) ) {
			$default_val = $key;
		} else {
			$ps[ $key ] = $val;
		}
	}
	if ( ! empty( $default_val ) ) $ps[ $default_key ] = $default_val;
	return $ps;
}

function create_query_string( $params ) {
	$kvs = [];
	if ( is_array( $params ) ) {
		foreach ( $params as $kv ) {
			$_key = urlencode( $kv[0] );
			$v = $kv[1];
			if ( is_array( $v ) ) $v = json_encode( $v, JSON_UNESCAPED_UNICODE );
			$_val = urlencode( $v );
			$kvs[] = $_key . '=' . $_val;
		}
	}
	return implode( '&', $kvs );
}
