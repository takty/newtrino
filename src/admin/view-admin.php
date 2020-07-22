<?php
namespace nt;
/**
 *
 * View
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @version 2020-07-22
 *
 */


require_once( __DIR__ . '/index.php' );
require_once( __DIR__ . '/../core/util/template.php' );
require_once( __DIR__ . '/../core/util/query-string.php' );
require_once( __DIR__ . '/../core/util/param.php' );


function query() {
	$url = parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH );
	if ( strpos( $url, 'list.php' ) !== false ) {
		$list_url = $url;
		$post_url = str_replace( 'list.php', 'post.php', $url );
	} else {
		$list_url = str_replace( 'post.php', 'list.php', $url );
		$post_url = $url;
	}
	$query = parse_query_string();
	if ( isset( $query['del_id'] ) ) {
		global $nt_store;
		$nt_store->delete( $query['del_id'] );
	}
	if ( $url === $post_url ) {
		return _create_view_edit( $query, $list_url, $post_url );
	} else {
		return _create_view_list( $query, $list_url, $post_url );
	}
}

function _create_view_edit( $query, $list_url, $post_url ) {
	$query = _rearrange_query( $query );
	return [
		'list'    => create_canonical_url( $list_url, $query ),
		'update'  => create_canonical_url( $post_url, $query, [ 'mode' => 'update' ] ),
		'preview' => create_canonical_url( 'preview.php', $query, [ 'mode' => 'preview' ] ),
		'media'   => create_canonical_url( 'media.php', [ 'id' => $query['id'] ] ),
	];
}

function _create_view_list( $query, $list_url, $post_url ) {
	global $nt_store, $nt_config;

	$query    = _rearrange_query( $query );
	$types    = $nt_store->type()->getTypeAll();
	$def_type = array_keys( $types )[0];

	$type    = get_param( 'type',     $def_type, $query );
	$perPage = get_param( 'per_page', 10,        $query );
	$date    = get_param( 'date',     null,      $query );
	$page    = get_param( 'page',     null,      $query );

	$args = [ 'status' => null, 'type' => $type, 'per_page' => $perPage ];
	if ( $date ) $args += [ 'date_query' => [ [ 'date' => $date ] ] ];
	if ( $page ) $args += [ 'page'       => $page ];

	if ( ! empty( $query['taxonomy'] ) ) {
		createTaxQueryFromTaxonomyToTerms( $query['taxonomy'], $args );
	}
	$ret = $nt_store->getPosts( $args );
	$ps = array_map( function ( $p ) use ( $query, $list_url, $post_url ) {
		return _process_post_for_view( $p, $query, $list_url, $post_url );
	}, $ret['posts'] );

	return [
		'posts'            => $ps,
		'pagination'       => _create_pagination_view( $query, $ret['page_count'], $list_url ),
		'taxonomy@cols'    => _create_header_taxonomy_cols( $type ),
		'taxonomy@cancels' => _create_header_taxonomy_cancels( $query, $list_url ),
		'filter'           => [
			'type'     => _create_type_filter_view( $query, $types, $list_url ),
			'date'     => _create_date_filter_view( $query, $type, 'month', $list_url ),
			'per_page' => _create_per_page_filter_view( $query, [10, 20, 50, 100], $list_url ),
			'new'      => _create_new_filter_view( $query, $types, $post_url ),
		],
	];
}


// -----------------------------------------------------------------------------


function _rearrange_query( array $query ): array {
	return get_query_vars( $query, [
		'id'       => 'int',
		'page'     => 'int',
		'per_page' => 'int',
		'date'     => 'slug',
		'type'     => 'slug',
	], 'taxonomy' );
}


// -----------------------------------------------------------------------------


function _create_type_filter_view( array $query, array $types, string $list_url ): array {
	$cur = $query['type'] ?? '';
	$as = [];
	foreach ( $types as $slug => $d ) {
		$url = create_canonical_url( $list_url, $query, [ 'type' => $slug ] );
		$p = [ 'label' => $d['label'], 'url' => $url ];
		if ( $slug === $cur ) $p['is_selected'] = true;
		$as[] = $p;
	}
	return $as;
}

function _create_date_filter_view( array $query, string $type, string $dateType, string $list_url ): array {
	global $nt_store;
	$dates = $nt_store->getCountByDate( $dateType, [ 'type' => $type ] );

	$cur = $query['date'] ?? '';
	switch ( $dateType ) {
		case 'year':  $df = 'Y';     break;
		case 'month': $df = 'Y-m';   break;
		case 'day':   $df = 'Y-m-d'; break;
	}
	$as = [];

	$url = create_canonical_url( $list_url, $query, [ 'date' => null ] );
	$p = [ 'label' => translate( 'All' ), 'url' => $url ];
	if ( $cur === '' ) $p['is_selected'] = true;
	$as[] = $p;

	foreach ( $dates as $date ) {
		$url = create_canonical_url( $list_url, $query, [ 'date' => $date['slug'] ] );
		$label = _format_date_label( $date['slug'], $df );
		$p = [ 'label' => $label, 'url' => $url ];
		if ( strval( $date['slug'] ) === $cur ) $p['is_selected'] = true;
		$as[] = $p;
	}
	return [ $dateType => $as ];
}

function _format_date_label( string $slug, string $df ): string {
	$y = substr( $slug, 0, 4 );
	$m = substr( $slug, 4, 2 );
	$d = substr( $slug, 6, 2 );
	$date = ( $y ? $y : '1970' ) . '-' . ( $m ? $m : '01' ) . '-' . ( $d ? $d : '01' );
	$date = date_create( $date );
	return $date->format( $df );
}

function _create_per_page_filter_view( array $query, array $pers, string $list_url ): array {
	$cur = $query['per_page'] ?? '';
	$as = [];
	foreach ( $pers as $per ) {
		$url = create_canonical_url( $list_url, $query, [ 'per_page' => $per ] );
		$p = [ 'label' => $per, 'url' => $url ];
		if ( $per == $cur ) $p['is_selected'] = true;
		$as[] = $p;
	}
	return $as;
}

function _create_new_filter_view( array $query, array $types, string $post_url ): array {
	$as = [];
	foreach ( $types as $slug => $d ) {
		$url = create_canonical_url( $post_url, $query, [ 'type' => $slug, 'mode' => 'new' ] );
		$p = [ 'label' => $d['label'], 'url' => $url ];
		$as[] = $p;
	}
	return $as;
}


// -----------------------------------------------------------------------------


function _create_pagination_view( $query, $page_count, $list_url ) {
	$cur = isset( $query['page'] ) ? max( 1, min( $query['page'], $page_count ) ) : 1;
	$pages = [];
	for ( $i = 1; $i <= $page_count; $i += 1 ) {
		$url = create_canonical_url( $list_url, $query, [ 'page' => $i ] );
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

function _create_header_taxonomy_cols( $type ) {
	global $nt_store;
	$labs = [];
	$taxes = $nt_store->type()->getTaxonomySlugAll( $type );
	foreach ( $taxes as $tax ) {
		$labs[] = [ 'label' => $nt_store->taxonomy()->getTaxonomy( $tax )['label'] ];
	}
	return $labs;
}

function _create_header_taxonomy_cancels( $query, $list_url ) {
	if ( ! isset( $query['taxonomy'] ) ) return null;

	global $nt_store;
	$tts = [];
	foreach ( $query['taxonomy'] as $tax => $ts ) {
		foreach ( $ts as $slug ) {
			$lab = $nt_store->taxonomy()->getTermLabel( $tax, $slug );
			$url = create_canonical_url( $list_url, $query, [ $tax => null ] );
			$tts[] = [ 'label' => $lab, 'url' => $url ];
		}
	}
	return $tts;
}


// -----------------------------------------------------------------------------


function _process_post_for_view( ?Post $p, array $query, string $list_url, string $post_url ): array {
	if ( $p === null ) return null;
	return [
		'id'       => $p->getId(),
		'type'     => $p->getType(),
		'title'    => $p->getTitle( true ),
		'status'   => $p->getStatus(),
		'date'     => $p->getDate(),
		'date@sep' => explode( ' ', $p->getDate() ),
		'modified' => $p->getModified(),
		'url'      => create_canonical_url( $post_url, $query, [ 'id' => $p->getId() ] ),

		'delete'        => create_canonical_url( $list_url, $query, [ 'del_id' => $p->getId() ] ),
		'status@select' => _create_status_select( $p ),
		'taxonomy@cols' => _create_taxonomy_cols( $p, $query, $list_url ),
		'meta@cols'     => _create_meta_cols( $p ),
	];
}

function _create_status_select( Post $p ): array {
	$ss = [];
	if ( $p->canPublished() ) {
		$s = [ 'slug' => 'publish', 'label' => translate( 'Published' ) ];
		if ( $p->isStatus( 'publish' ) ) $s['is_selected'] = true;
		$ss[] = $s;
	} else {
		$s = [ 'slug' => 'future', 'label' => translate( 'Scheduled' ) ];
		if ( $p->isStatus( 'future' ) ) $s['is_selected'] = true;
		$ss[] = $s;
	}
	$s = [ 'slug' => 'draft', 'label' => translate( 'Draft' ) ];
	if ( $p->isStatus( 'draft' ) ) $s['is_selected'] = true;
	$ss[] = $s;
	return $ss;
}

function _create_taxonomy_cols( Post $p, array $query, string $list_url ): array {
	global $nt_store;
	$taxes = $nt_store->type()->getTaxonomySlugAll( $p->getType() );

	$cols = [];
	foreach ( $taxes as $tax ) {
		if ( strpos( $tax, '@' ) !== false ) continue;
		$ts = $p->getTermSlugs( $tax );

		$tts = [];
		foreach ( $ts as $slug ) {
			$lab = $nt_store->taxonomy()->getTermLabel( $tax, $slug );
			$url = create_canonical_url( $list_url, $query, [ $tax => $slug ] );
			$tts[] = [ 'label' => $lab, 'url' => $url ];
		}
		$cols[] = [ 'taxonomy' => $tax, 'terms' => $tts ];
	}
	return $cols;
}

function _create_meta_cols( Post $p ): array {
	// if ( isset( $p['meta'] ) ) {
	// 	foreach ( $p['meta'] as $key => &$val ) {
	// 		if ( strpos( $key, '@' ) !== false ) continue;
	// 		if ( ! isset( $p['meta']["$key@type"] ) ) continue;
	// 		if ( $p['meta']["$key@type"] === 'date-range' ) {
	// 			$val[0] = date_create( $val[0] )->format( $date_format );
	// 			$val[1] = date_create( $val[1] )->format( $date_format );
	// 		}
	// 	}
	// }
	return [];
}

function _get_meta( Post $p, array &$cls ): array {
	global $nt_store;
	$ms = $nt_store->type()->getMetaAll( $p->getType() );
	$fs = [];
	_flatten_meta_structure( $ms, $fs );

	$ret = [];
	foreach ( $fs as $m ) {
		$key  = $m['key'];
		$type = $m['type'];
		$val  = $p->getMetaValue( $key );
		if ( $type !== 'group' && $val === null ) continue;

		switch ( $type ) {
			case 'date-range':
				$es = Post::DATE_STATUS_ONGOING;
				$now = date( 'Ymd' );
				if ( $now < $val[0] ) $es = Post::DATE_STATUS_UPCOMING;
				else if ( $val[1] < $now ) $es = Post::DATE_STATUS_FINISHED;
				$ret[ "$key@status" ] = $es;
				$cls[] = "$key-$es";
				$ret[ $key ] = array_map( function ( $e ) { return Post::parseDate( $e ); }, $val );
				break;
		}
		$ret[ "$key@type" ] = $type;
	}
	return $ret;
}

function _flatten_meta_structure( array $ms, array &$ret ) {
	foreach ( $ms as $m ) {
		$type = $m['type'];
		if ( $type === 'group' ) {
			$children = $m['children'];
			_flatten_meta_structure( $children, $ret );
		} else {
			$ret[] = $m;
		}
	}
}
