<?php
namespace nt;
/**
 *
 * View (PHP)
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @version 2020-07-12
 *
 */


require_once( __DIR__ . '/response.php' );
require_once( __DIR__ . '/util/template.php' );
require_once( __DIR__ . '/util/query-string.php' );


function query( array $args = [] ): array {
	$filter = [ 'date' => 'year', 'taxonomy' => [ 'category' ] ];  // TODO
	if ( isset( $args['filter'] ) ) $filter = array_merge( $filter, $args['filter'] );

	$option   = isset( $args['option'] )   ? $args['option']   : [];
	$base_url = isset( $args['base_url'] ) ? $args['base_url'] : null;

	if ( ! $base_url ) $base_url = parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH );

	$msg = [
		'query'  => parse_query_string( 'id' ),
		'filter' => $filter,
		'option' => $option
	];
	if ( isset( $msg['query']['id'] ) ) {
		return _create_view_single( $msg, $base_url );
	} else {
		return _create_view_archive( $msg, $base_url );
	}
}

function query_recent_posts( array $args = [] ): array {
	$option   = isset( $args['option'] )   ? $args['option']   : [];
	$count    = isset( $args['count'] )    ? $args['count']    : 10;
	$base_url = isset( $args['base_url'] ) ? $args['base_url'] : null;

	if ( ! $base_url ) $base_url = parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH );

	$msg = [
		'query'  => [ 'per_page' => $count ],
		'filter' => [],
		'option' => $option
	];
	return _create_view_archive( $msg, $base_url );
}


// -----------------------------------------------------------------------------


function _create_view_archive( array $msg, string $base_url ): array {
	$res = create_response_archive( $msg['query'], $msg['filter'], $msg['option'] );
	if ( $res['status'] !== 'success' ) $res['posts'] = [];
	$df = isset( $msg['option']['date_format'] ) ? $msg['option']['date_format'] : null;

	$view = [];
	$view['posts'] = _process_posts_for_view( $res['posts'], $df, $base_url );
	$view['navigation'] = [];
	$view['navigation']['pagination'] = _create_pagination_view( $msg, $res['page_count'], $base_url );
	$view['filter'] = _create_filter_view( $msg, $res, $base_url );
	return $view;
}

function _create_view_single( array $msg, string $base_url ): array {
	$res = create_response_single( $msg['query'], $msg['filter'], $msg['option'] );
	if ( $res['status'] !== 'success' ) $res['post'] = null;
	$df = isset( $msg['option']['date_format'] ) ? $msg['option']['date_format'] : null;

	$view = [];
	list( $view['post'] ) = _process_posts_for_view( [ $res['post'] ], $df, $base_url );
	$view['navigation'] = [];
	$view['navigation']['post_navigation'] = _create_post_navigation_view( $msg, $res['adjacent_post'], $base_url );
	$view['filter'] = _create_filter_view( $msg, $res, $base_url );
	return $view;
}


// -----------------------------------------------------------------------------


function _process_posts_for_view( array $items, ?string $date_format, string $base_url ): array {
	foreach ( $items as &$p ) {
		if ( ! $p ) continue;
		if ( isset( $p['taxonomy'] ) ) {
			foreach ( $p['taxonomy'] as $tax_slug => $terms ) {
				$a = [];
				foreach ( $terms as $term ) $a[ $term['slug'] ] = true;
				$p['taxonomy'][ "$tax_slug@has" ] = $a;
			}
		}
		$p['url'] = $base_url . '?' . urlencode( $p['id'] );
		if ( $date_format ) {
			$p['date']     = date_create( $p['date'] )->format( $date_format );
			$p['modified'] = date_create( $p['modified'] )->format( $date_format );
		}
		if ( isset( $p['meta'] ) ) {
			foreach ( $p['meta'] as $key => &$val ) {
				if ( strpos( $key, '@' ) !== false ) continue;
				if ( ! isset( $p['meta']["$key@type"] ) ) continue;
				if ( $p['meta']["$key@type"] === 'date' ) {
					$val = date_create( $val )->format( $date_format );
				}
				if ( $p['meta']["$key@type"] === 'date-range' ) {
					$val[0] = date_create( $val[0] )->format( $date_format );
					$val[1] = date_create( $val[1] )->format( $date_format );
				}
			}
		}
		if ( ! empty( $p['class'] ) ) {
			$cs = implode( ' ', $p['class'] );
			$p['class@joined'] = $cs;
		}
	}
	return $items;
}

function _create_pagination_view( array $msg, int $page_count, string $base_url ): array {
	$cur = isset( $msg['query']['page'] ) ? max( 1, min( $msg['query']['page'], $page_count ) ) : 1;
	$pages = [];
	for ( $i = 1; $i <= $page_count; $i += 1 ) {
		$cq = create_canonical_query( $msg['query'], [ 'page' => $i ] );
		$url = $base_url . ( ! empty( $cq ) ? ('?' . $cq) : '');
		$p = [ 'label' => $i, 'url' => $url ];
		if ( $i === $cur ) $p['is_selected'] = true;
		$pages[] = $p;
	}
	return [
		'previous' => ( ( 1 < $cur ) ? $pages[ $cur - 2 ]['url'] : '' ),
		'next'     => ( ( $cur < $page_count ) ? $pages[ $cur ]['url'] : '' ),
		'pages'    => $pages
	];
}

function _create_post_navigation_view( array $msg, array $adjacent_posts, string $base_url ): array {
	$df = isset( $msg['option']['date_format'] ) ? $msg['option']['date_format'] : null;
	$ps = _process_posts_for_view( [ $adjacent_posts['previous'], $adjacent_posts['next'] ], $df, $base_url );
	return [
		'previous' => $ps[0],
		'next'     => $ps[1],
	];
}


// -----------------------------------------------------------------------------


function _create_filter_view( array $msg, array $res, string $base_url ): array {
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

function _create_date_filter_view( array $msg, string $type, array $dates, string $base_url ): array {
	$cur = isset( $msg['query']['date'] ) ? $msg['query']['date'] : '';
	if ( isset( $msg['filter']['date_format'] ) ) {
		$df = $msg['filter']['date_format'];
	} else {
		switch ( $type ) {
			case 'year':  $df = 'Y';     break;
			case 'month': $df = 'Y-m';   break;
			case 'day':   $df = 'Y-m-d'; break;
		}
	}
	$as = [];
	foreach ( $dates as $date ) {
		$cq = create_canonical_query( [ 'date' => $date['slug'] ] );
		$url = $base_url . ( empty( $cq ) ? '' : "?$cq" );
		$label = _format_date_label( $date['slug'], $df );
		$p = [ 'label' => $label, 'url' => $url ];
		if ( strval( $date['slug'] ) === $cur ) $p['is_selected'] = true;
		$as[] = $p;
	}
	return [ $type => $as ];
}

function _format_date_label( string $slug, string $df ): string {
	$y = substr( $slug, 0, 4 );
	$m = substr( $slug, 4, 2 );
	$d = substr( $slug, 6, 2 );
	$date = ( $y ? $y : '1970' ) . '-' . ( $m ? $m : '01' ) . '-' . ( $d ? $d : '01' );
	$date = date_create( $date );
	return $date->format( $df );
}

function _create_taxonomy_filter_view( array $msg, string $tax, array $terms, string $base_url ): array {
	$cur = isset( $msg['query'][ $tax ] ) ? $msg['query'][ $tax ] : '';
	$as = [];
	foreach ( $terms as $term ) {
		$cq = create_canonical_query( [ $tax => $term['slug'] ] );
		$url = $base_url . ( empty( $cq ) ? '' : "?$cq" );
		$p = [ 'label' => $term['label'], 'url' => $url ];
		if ( $term['slug'] === $cur ) $p['is_selected'] = true;
		$as[] = $p;
	}
	return [ $tax => $as ];
}
