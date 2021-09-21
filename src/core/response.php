<?php
namespace nt;
/**
 *
 * Response
 *
 * @author Takuto Yanagida
 * @version 2021-09-21
 *
 */


require_once( __DIR__ . '/index.php' );
require_once( __DIR__ . '/class-store.php' );
require_once( __DIR__ . '/util/date-format.php' );
require_once( __DIR__ . '/util/param.php' );

$nt_config = load_config( NT_DIR_DATA );


// -----------------------------------------------------------------------------


function create_response_archive( array $query, array $filter, array $option = [] ): array {
	global $nt_store, $nt_config;
	$nt_config += $option;
	$nt_store  = new Store( NT_URL, NT_DIR, NT_DIR_DATA, $nt_config );

	if ( ! isset( $query[0] ) ) {
		$query = [ $query ];
	}
	$posts = [];
	foreach ( $query as $q ) {
		$args  = _create_args( $q );
		$ret   = $nt_store->getPosts( $args );
		$posts = array_merge( $posts, array_map( '\nt\_create_post_data', $ret['posts'] ) );
	}
	$res = [
		'status'     => 'success',
		'posts'      => $posts,
		'page_count' => $ret['page_count'],
	];
	$res += _create_archive_data( _rearrange_filter( $filter ) );
	return $res;
}

function create_response_single( array $query, array $filter, array $option = [] ): array {
	global $nt_store, $nt_config;
	$nt_config += $option;
	$nt_store  = new Store( NT_URL, NT_DIR, NT_DIR_DATA, $nt_config );

	$args  = [];
	$query = _rearrange_query( $query );

	if ( isset( $query['type'] ) ) $args['type'] = $query['type'];

	if ( ! empty( $query['taxonomy'] ) ) {
		\nt\create_tax_query_from_taxonomy_to_terms( $query['taxonomy'], $args );
	}
	$ret = $nt_store->getPostWithNextAndPrevious( $query['id'] ?? null, $args );

	$res = [
		'status' => 'success',
		'post'   => _create_post_data( $ret ? $ret[1] : null, true ),
		'adjacent_post' => [
			'previous' => _create_post_data( $ret ? $ret[0] : null ),
			'next'     => _create_post_data( $ret ? $ret[2] : null ),
		]
	];
	$res += _create_archive_data( _rearrange_filter( $filter ) );
	return $res;
}


// -----------------------------------------------------------------------------


function _create_args( array $query ): array {
	$args = [];

	if ( isset( $query['tax_query'] ) )  $args['tax_query']  = $query['tax_query'];
	if ( isset( $query['date_query'] ) ) $args['date_query'] = $query['date_query'];
	if ( isset( $query['meta_query'] ) ) $args['meta_query'] = $query['meta_query'];

	$query = _rearrange_query( $query );

	if ( isset( $query['type'] ) )    $args['type']       = $query['type'];
	if ( isset( $query['search'] ) )  $args['search']     = $query['search'];
	if ( isset( $query['page'] ) )    $args['page']       = $query['page'];
	if ( isset( $query['perPage'] ) ) $args['per_page']   = $query['perPage'];
	if ( isset( $query['date'] ) )    $args['date_query'] = [ [ 'date' => $query['date'] ] ];

	if ( ! empty( $query['taxonomy'] ) ) {
		\nt\create_tax_query_from_taxonomy_to_terms( $query['taxonomy'], $args );
	}
	return $args;
}

function _rearrange_query( array $query ): array {
	return \nt\get_query_vars( $query, [
		'id'       => 'int',
		'type'     => 'slug',
		'page'     => 'int',
		'per_page' => 'int',
		'date'     => 'slug',
		'search'   => 'string',
	], 'taxonomy' );
}

function _rearrange_filter( array $filter ): array {
	return \nt\get_query_vars( $filter, [
		'date'        => 'slug',
		'date_format' => 'string',
		'taxonomy'    => 'slug_array',
	] );
}


// -----------------------------------------------------------------------------


function _create_post_data( ?Post $p, bool $include_content = false ): ?array {
	global $nt_store;
	if ( $p === null ) return null;
	$cls = [];
	$d = [
		'id'       => $p->getId(),
		'type'     => $p->getType(),
		'title'    => $p->getTitle( true ),
		'status'   => $p->getStatus(),
		'date'     => $p->getDate(),
		'modified' => $p->getModified(),
		'excerpt'  => $p->getExcerpt( 60 ),
		'taxonomy' => _get_taxonomy( $p ),
		'meta'     => _get_meta( $p, $cls ),  // Must before _get_class
		'class'    => _get_class( $p, $cls ),
	];
	if ( $include_content ) $d['content'] = $p->getContent();
	return $d;
}

function _get_taxonomy( Post $p ): array {
	global $nt_store;
	$ret = [];
	foreach ( $p->getTaxonomyToTermSlugs() as $tax => $ts ) {
		$ls = [];
		foreach ( $ts as $t ) {
			$l = $nt_store->taxonomy()->getTermLabel( $tax, $t );
			$ls[] = [ 'slug' => $t, 'label' => $l ];
		}
		$ret[ $tax ] = $ls;
	}
	return $ret;
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
			case 'date':
				$es                   = _get_date_status( $val, $val );
				$ret[ "$key@status" ] = $es;
				$cls[]                = "$key-$es";
				$ret[ $key ]          = \nt\parse_date( $val );
				break;
			case 'date-range':
				if ( ! isset( $val['from'] ) || ! isset( $val['to'] ) ) break;
				$es                   = _get_date_status( $val['from'], $val['to'] );
				$ret[ "$key@status" ] = $es;
				$cls[]                = "$key-$es";
				$ret[ $key ]          = [
					'from' => \nt\parse_date( $val['from'] ),
					'to'   => \nt\parse_date( $val['to'] ),
				];
				break;
			default:
				$ret[ $key ] = $val;
				break;
		}
		$ret[ "$key@type" ] = $type;
	}
	return $ret;
}

function _flatten_meta_structure( array $ms, array &$ret ): void {
	foreach ( $ms as $m ) {
		$type = $m['type'];
		if ( $type === 'group' ) {
			$items = $m['items'];
			_flatten_meta_structure( $items, $ret );
		} else {
			$ret[] = $m;
		}
	}
}

function _get_date_status( string $bgn, string $end ): string {
	$now = date( 'Ymd' );
	$es = Post::DATE_STATUS_ONGOING;
	if ( $now < $bgn ) $es = Post::DATE_STATUS_UPCOMING;
	else if ( $end < $now ) $es = Post::DATE_STATUS_FINISHED;
	return $es;
}

function _get_class( Post $p, array $cls ): array {
	global $nt_config;
	if ( $nt_config['new_arrival_period'] > 0 ) {
		$now  = date_create( date( 'Y-m-d' ) );
		$data = date_create( substr( $p->getDate(), 0, 10 ) );
		$int  = date_diff( $now, $data );
		if ( $int->invert === 1 && $int->days <= $nt_config['new_arrival_period'] ) $cls[] = 'new';
	}
	$cls[] = 'status-' . $p->getStatus();
	$cls[] = 'type-' . $p->getType();
	return $cls;
}


// -----------------------------------------------------------------------------


function _create_archive_data( array $filter ): array {
	if ( empty( $filter ) ) return [];
	$res = [];
	if ( isset( $filter['date'] ) ) {
		$_date = $filter['date'];
		$res += [ 'date' => _get_date_archive( $_date, $filter ) ];
	}
	if ( isset( $filter['taxonomy'] ) ) {
		$_taxes = $filter['taxonomy'];
		$res += [ 'taxonomy' => _get_taxonomy_archive( $_taxes ) ];
	}
	return $res;
}

function _get_date_archive( string $type, array $filter ): array {
	global $nt_store;
	$ds = $nt_store->getCountByDate( $type, $filter );
	$cs = [];
	foreach ( $ds as $d ) {
		$cs[] = [ 'slug' => $d['slug'], 'count' => $d['count'] ];
	}
	return [ $type => $cs ];
}

function _get_taxonomy_archive( array $taxes ): array {
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
