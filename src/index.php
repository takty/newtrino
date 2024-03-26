<?php
/**
 * Index (PHP)
 *
 * @author Takuto Yanagida
 * @version 2024-03-26
 */

namespace nt;

$is_direct = ( count( get_included_files() ) === 1 );

require_once( __DIR__ . '/core/response.php' );
require_once( __DIR__ . '/core/util/template.php' );
require_once( __DIR__ . '/core/util/query-string.php' );
require_once( __DIR__ . '/core/util/file-sender.php' );

if ( $is_direct ) {
	if ( ! empty( $_POST ) ) query_ajax( $_POST );
	elseif ( ! empty( $_GET ) ) query_media( $_GET );
	else http_response_code( 404 );
}


// -----------------------------------------------------------------------------


/**
 * Handles an AJAX query.
 *
 * @param array<string, mixed> $req The request array.
 */
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

/**
 * Handles a media query.
 *
 * @param array<string, mixed> $req The request array.
 */
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


/**
 * Handles a query.
 *
 * @param array<string, mixed> $args The arguments array.
 * @return array<string, mixed> The view array.
 */
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

/**
 * Handles a recent posts query.
 *
 * @param array<string, mixed> $args The arguments array.
 * @return array<string, mixed> The view array.
 */
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


/**
 * Creates the view archive.
 *
 * @param array<string, mixed> $msg      The message array.
 * @param string               $base_url The base URL.
 * @param int                  $count    The count integer.
 * @return array<string, mixed> The view archive array.
 */
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

/**
 * Creates the view single.
 *
 * @param array<string, mixed> $msg      The message array.
 * @param string               $base_url The base URL.
 * @return array<string, mixed> The view single array.
 */
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


/**
 * Processes the posts for view.
 *
 * @param array<string, mixed>[] $items       The items array.
 * @param string|null            $date_format The date format string.
 * @param string                 $base_url    The base URL.
 * @return array<string, mixed>[] The processed posts for view array.
 */
function _process_posts_for_view( array $items, ?string $date_format, string $base_url ): array {
	foreach ( $items as &$p ) {
		if ( ! $p || ! is_string( $p['id'] ?? null ) ) continue;
		if ( isset( $p['taxonomy'] ) ) {
			foreach ( $p['taxonomy'] as $tax_slug => $terms ) {
				$a = [];
				foreach ( $terms as $term ) $a[ $term['slug'] ] = true;
				$p['taxonomy'][ "$tax_slug@has" ] = $a;
			}
		}
		$p['url'] = $base_url . '?' . urlencode( $p['id'] );
		if ( $date_format ) {
			$p['date']     = date_create_format( $p['date'],     $date_format );
			$p['modified'] = date_create_format( $p['modified'], $date_format );
		}
		if ( isset( $p['meta'] ) ) {
			foreach ( $p['meta'] as $key => &$val ) {
				if ( strpos( $key, '@' ) !== false ) continue;
				if ( ! isset( $p['meta']["$key@type"] ) ) continue;
				if ( $date_format ) {
					if ( $p['meta']["$key@type"] === 'date' ) {
						$val = date_create_format( $val, $date_format );
					}
					if ( $p['meta']["$key@type"] === 'date_range' ) {
						$val['from'] = is_string( $val['from'] ?? null ) ? date_create_format( $val['from'], $date_format ) : '';
						$val['to']   = is_string( $val['to']   ?? null ) ? date_create_format( $val['to'],   $date_format ) : '';
					}
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

/**
 * Creates the pagination view.
 *
 * @param array<string, mixed> $msg        The message array.
 * @param int                  $page_count The page count integer.
 * @param string               $base_url   The base URL.
 * @return array<string, mixed>|null The pagination view array or null if there is only one page.
 */
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

/**
 * Creates the post navigation view.
 *
 * @param array<string, mixed> $msg            The message array.
 * @param array<string, mixed> $adjacent_posts The adjacent posts array.
 * @param string               $base_url       The base URL.
 * @return array<string, mixed> The post navigation view array.
 */
function _create_post_navigation_view( array $msg, array $adjacent_posts, string $base_url ): array {
	$df = isset( $msg['option']['date_format'] ) ? $msg['option']['date_format'] : null;
	$ps = _process_posts_for_view( [ $adjacent_posts['previous'], $adjacent_posts['next'] ], $df, $base_url );
	return [
		'previous' => $ps[0],
		'next'     => $ps[1],
	];
}


// -----------------------------------------------------------------------------


/**
 * Creates the filter view.
 *
 * @param array<string, mixed> $msg      The message array.
 * @param array<string, mixed> $res      The result array.
 * @param string               $base_url The base URL.
 * @return array<string, mixed> The filter view array.
 */
function _create_filter_view( array $msg, array $res, string $base_url ): array {
	$v = [];
	if ( isset( $res['date'] ) ) {
		$keys = array_keys( $res['date'] );
		foreach ( $res['date'] as $type => $dates ) {
			$v['date'] = _create_filter_view_date( $msg, $type, $dates, $base_url );
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

/**
 * Creates the filter view date.
 *
 * @param array<string, mixed>   $msg      The message array.
 * @param string                 $type     The type string.
 * @param array<string, mixed>[] $dates    The dates array.
 * @param string                 $base_url The base URL.
 * @return array<string, mixed> The filter view date array.
 */
function _create_filter_view_date( array $msg, string $type, array $dates, string $base_url ): array {
	$cur = $msg['query']['date'] ?? '';
	if ( isset( $msg['filter']['date_format'] ) ) {
		$df = $msg['filter']['date_format'];
	} else {
		$df = '';
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

/**
 * Formats the date label.
 *
 * @param string $slug The slug string.
 * @param string $df   The date format string.
 * @return string The formatted date label.
 */
function _format_date_label( string $slug, string $df ): string {
	$y = substr( $slug, 0, 4 );
	$m = substr( $slug, 4, 2 );
	$d = substr( $slug, 6, 2 );
	$date = ( $y ? $y : '1970' ) . '-' . ( $m ? $m : '01' ) . '-' . ( $d ? $d : '01' );
	return date_create_format( $date, $df );
}

/**
 * Creates the taxonomy filter view.
 *
 * @param array<string, mixed>   $msg      The message array.
 * @param string                 $tax      The taxonomy string.
 * @param array<string, mixed>[] $terms    The terms array.
 * @param string                 $base_url The base URL.
 * @return array<string, mixed> The taxonomy filter view array.
 */
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
