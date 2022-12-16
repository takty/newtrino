<?php
/**
 * Index (PHP)
 *
 * @author Takuto Yanagida
 * @version 2021-09-21
 */

namespace nt;

$is_direct = ( count( get_included_files() ) === 1 );

require_once( __DIR__ . '/core/response.php' );
require_once( __DIR__ . '/core/util/template.php' );
require_once( __DIR__ . '/core/util/query-string.php' );
require_once( __DIR__ . '/core/util/file-sender.php' );

if ( $is_direct ) {
	if ( ! empty( $_POST ) ) query_ajax( $_POST );
	else if ( ! empty( $_GET ) ) query_media( $_GET );
	else http_response_code( 404 );
}


// -----------------------------------------------------------------------------


function query_ajax( array $req ): void {
	$query  = isset( $req['query' ] ) ? ( json_decode( $req['query' ], true ) ?? [] ) : [];
	$filter = isset( $req['filter'] ) ? ( json_decode( $req['filter'], true ) ?? [] ) : [];
	$option = isset( $req['option'] ) ? ( json_decode( $req['option'], true ) ?? [] ) : [];

	if ( isset( $query['id'] ) ) {
		$d = create_response_single( $query, $filter, $option );
	} else {
		$d = create_response_archive( $query, $filter, $option );
	}

	header( 'Content-Type: text/html; charset=UTF-8' );
	echo json_encode( $d, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
}

function query_media( array $req ): void {
	$id    = null;
	$media = null;

	foreach ( $_GET as $key => $val ) {
		$id    = strval( $key );
		$media = strval( $val );
		break;
	}
	if ( empty( $id ) || empty( $media ) ) {
		http_response_code( 404 );
		exit;
	}

	$nt_config = load_config( NT_DIR_DATA );
	$nt_store  = new Store( NT_URL, NT_DIR, NT_DIR_DATA, $nt_config );

	if ( $id[0] === '_' ) {  // Temporary ID
		$ok = false;
		if ( file_exists( __DIR__ . '/admin/class-session.php' ) ) {
			require_once( __DIR__ . '/admin/class-session.php' );
			if ( Session::canStart( false ) ) $ok = true;
		}
		if ( ! $ok ) {
			http_response_code( 404 );
			exit;
		}
	}
	if ( $id[0] === '-' ) {  // Trash
		http_response_code( 404 );
		exit;
	}
	$postDir = $nt_store->getPostDir( $id, null );
	if ( ! is_dir( $postDir ) ) {
		http_response_code( 404 );
		exit;
	}
	\nt\send_file( $postDir . 'media/' . $media );
}


// -----------------------------------------------------------------------------


function query( array $args = [] ): array {
	$query    = $args['query']    ?? [];
	$filter   = $args['filter']   ?? [];
	$option   = $args['option']   ?? [];
	$base_url = $args['base_url'] ?? null;

	$query  += \nt\parse_query_string( 'id' );
	$filter += [ 'date' => 'year' ];

	if ( ! $base_url ) {
		$base_url = ( empty( $_SERVER['HTTPS'] ) ? 'http://' : 'https://' ) . $_SERVER['HTTP_HOST'];
		$base_url .= parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH );
	}
	$msg = [
		'query'  => $query,
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
	$count    = $args['count']    ?? 10;
	$query    = $args['query']    ?? [];
	$option   = $args['option']   ?? [];
	$base_url = $args['base_url'] ?? null;

	if ( ! $base_url ) {
		$base_url = parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH );
	}
	if ( isset( $query[0] ) ) {
		foreach ( $query as &$q ) {
			if ( ! isset( $q['per_page'] ) ) {
				$q['per_page'] = $count;
			}
		}
	} else {
		if ( ! isset( $query['per_page'] ) ) {
			$query['per_page'] = $count;
		}
	}
	$msg = [
		'query'  => $query,
		'filter' => [],
		'option' => $option
	];
	return _create_view_archive( $msg, $base_url, $count );
}


// -----------------------------------------------------------------------------


function _create_view_archive( array $msg, string $base_url, int $count = -1 ): array {
	$res = create_response_archive( $msg['query'], $msg['filter'], $msg['option'] );
	if ( $res['status'] !== 'success' ) $res['posts'] = [];
	$df = isset( $msg['option']['date_format'] ) ? $msg['option']['date_format'] : null;

	$view = [];
	$view['posts'] = _process_posts_for_view( $res['posts'], $df, $base_url );
	$view['navigation'] = [];
	$view['navigation']['pagination'] = _create_pagination_view( $msg, $res['page_count'], $base_url );
	$view['filter'] = _create_filter_view( $msg, $res, $base_url );

	if ( 0 < $count && $count < count( $view['posts'] ) ) {
		$view['posts'] = array_slice( $view['posts'], 0, $count );
	}
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
			$p['date']     = date_create( $p['date']     )->format( $date_format );
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
					$val['from'] = isset( $val['from'] ) ? date_create( $val['from'] )->format( $date_format ) : '';
					$val['to']   = isset( $val['to']   ) ? date_create( $val['to']   )->format( $date_format ) : '';
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

function _create_pagination_view( array $msg, int $page_count, string $base_url ): ?array {
	$c = intval( $msg['query']['page'] ?? 1 );
	$cur = max( 1, min( $c, $page_count ) );
	$pages = [];
	for ( $i = 1; $i <= $page_count; $i += 1 ) {
		$url = \nt\create_canonical_url( $base_url, $msg['query'], [ 'page' => $i ] );
		$p = [ 'label' => $i, 'url' => $url ];
		if ( $i === $cur ) $p['is_selected'] = true;
		$pages[] = $p;
	}
	if ( count( $pages ) === 1 ) return null;
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
		'keyword' => $msg['query']['search'] ?? ''
	];
	return $v;
}

function _create_date_filter_view( array $msg, string $type, array $dates, string $base_url ): array {
	$cur = $msg['query']['date'] ?? '';
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
		$url = \nt\create_canonical_url( $base_url, [ 'date' => $date['slug'] ] );
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
	$cur = $msg['query'][ $tax ] ?? '';
	$as = [];
	foreach ( $terms as $term ) {
		$url = \nt\create_canonical_url( $base_url, [ $tax => $term['slug'] ] );
		$p = [ 'label' => $term['label'], 'url' => $url ];
		if ( $term['slug'] === $cur ) $p['is_selected'] = true;
		$as[] = $p;
	}
	return [ $tax => $as ];
}
