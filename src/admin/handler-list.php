<?php
/**
 * Handler - List
 *
 * @author Takuto Yanagida
 * @version 2022-04-13
 */

namespace nt;

require_once( __DIR__ . '/index.php' );
require_once( __DIR__ . '/../core/class-store.php' );
require_once( __DIR__ . '/../core/util/date-format.php' );
require_once( __DIR__ . '/../core/util/param.php' );
require_once( __DIR__ . '/../core/util/query-string.php' );
require_once( __DIR__ . '/../core/util/template.php' );

start_session( true );

function handle_query(): array {
	global $nt_config, $nt_store, $nt_session;
	$list_url = NT_URL_ADMIN . 'list.php';
	$post_url = NT_URL_ADMIN . 'post.php';

	$orig_query = \nt\parse_query_string();

	$query    = _rearrange_query( $orig_query );
	$types    = $nt_store->type()->getTypeAll();
	$def_type = array_keys( $types )[0];

	$type    = $query['type']     ?? $def_type;
	$perPage = $query['per_page'] ?? 10;
	$date    = $query['date']     ?? null;
	$page    = $query['page']     ?? null;
	$status  = $query['status']   ?? null;
	$error   = $query['error']    ?? null;
	unset( $query['error'] );

	if ( $nt_session->checkNonce() ) {
		if ( isset( $orig_query['remove_id'] ) ) {
			$nt_store->removePost( $orig_query['remove_id'] );
		} else if ( isset( $orig_query['restore_id'] ) ) {
			$nt_store->restorePost( $orig_query['restore_id'] );
		} else if ( isset( $orig_query['empty_trash'] ) ) {
			$nt_store->emptyTrash( $type );
			$nt_store->emptyTemporaryDirectories( $type );
		}
	}
	$query['nonce'] = $nt_session->getNonce();

	$args = [ 'status' => $status, 'type' => $type, 'per_page' => $perPage ];
	if ( $date ) $args += [ 'date_query' => [ [ 'date' => $date ] ] ];
	if ( $page ) $args += [ 'page'       => $page ];

	if ( ! empty( $query['taxonomy'] ) ) {
		\nt\create_tax_query_from_taxonomy_to_terms( $query['taxonomy'], $args );
	}
	$ret = $nt_store->getPosts( $args );
	$is_trash = $status === '_trash';
	$ps = array_map( function ( $p ) use ( $query, $list_url, $post_url, $is_trash ) {
		return _process_post_for_view( $p, $query, $list_url, $post_url, $is_trash );
	}, $ret['posts'] );

	$msgs = [
		'new'    => _ht( 'A new post could not be created.' ),
		'update' => _ht( 'The post could not be updated.' ),
		'view'   => _ht( 'The post could not be viewed.' ),
		'lock'   => _ht( 'The post is being edited by another user.' ),
	];
	$msg = $msgs[ $error ] ?? '';
	return [
		'posts'            => $ps,
		'pagination'       => _create_pagination_view( $query, $ret['page_count'], $list_url ),
		'meta@cols'        => _create_header_meta_cols( $type ),
		'taxonomy@cols'    => _create_header_taxonomy_cols( $type ),
		'taxonomy@cancels' => _create_header_taxonomy_cancels( $query, $list_url ),
		'filter'           => [
			'type'     => _create_type_filter_view( $query, $types, $list_url ),
			'date'     => _create_date_filter_view( $query, $type, 'month', $list_url ),
			'per_page' => _create_per_page_filter_view( $query, [10, 20, 50, 100], $list_url ),
			'new'      => _create_new_filter_view( $query, $types, $post_url ),
		],
		'list_trash'  => $is_trash ? '#' : \nt\create_canonical_url( $list_url, $query, [ 'status' => '_trash' ] ),
		'list_all'    => $is_trash ? \nt\create_canonical_url( $list_url, $query, [ 'status' => null ] ) : '#',
		'empty_trash' => $is_trash ? \nt\create_canonical_url( $list_url, $query, [ 'empty_trash' => true, 'status' => null ] ) : '#',
		'message'     => $msg,
		'nonce'       => $nt_session->getNonce(),
	];
}


// -----------------------------------------------------------------------------


function _rearrange_query( array $query ): array {
	return \nt\get_query_vars( $query, [
		'id'       => 'int',
		'page'     => 'int',
		'per_page' => 'int',
		'date'     => 'slug',
		'type'     => 'slug',
		'status'   => 'slug',
		'error'    => 'string',
	], 'taxonomy' );
}


// -----------------------------------------------------------------------------


function _create_type_filter_view( array $query, array $types, string $list_url ): array {
	$cur = $query['type'] ?? '';
	$as = [];
	foreach ( $types as $slug => $d ) {
		$url = \nt\create_canonical_url( $list_url, $query, [ 'type' => $slug, 'date' => null, 'page' => null ] );
		$p = [ 'label' => $d['label'], 'url' => $url ];
		if ( $slug === $cur ) $p['is_selected'] = true;
		$as[] = $p;
	}
	return $as;
}

function _create_date_filter_view( array $query, string $type, string $dateType, string $list_url ): array {
	global $nt_store;
	$dates = $nt_store->getCountByDate( $dateType, [ 'type' => $type, 'status' => $query['status'] ?? null ] );

	$cur = $query['date'] ?? '';
	switch ( $dateType ) {
		case 'year':  $df = 'Y';     break;
		case 'month': $df = 'Y-m';   break;
		case 'day':   $df = 'Y-m-d'; break;
	}
	$as = [];

	$url = \nt\create_canonical_url( $list_url, $query, [ 'date' => null ] );
	$p = [ 'label' => translate( 'All' ), 'url' => $url ];
	if ( $cur === '' ) $p['is_selected'] = true;
	$as[] = $p;

	foreach ( $dates as $date ) {
		$url = \nt\create_canonical_url( $list_url, $query, [ 'date' => $date['slug'], 'page' => null ] );
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
		$url = \nt\create_canonical_url( $list_url, $query, [ 'per_page' => $per, 'page' => null ] );
		$p = [ 'label' => $per, 'url' => $url ];
		if ( $per == $cur ) $p['is_selected'] = true;
		$as[] = $p;
	}
	return $as;
}

function _create_new_filter_view( array $query, array $types, string $post_url ): array {
	$as = [];
	foreach ( $types as $slug => $d ) {
		$url = \nt\create_canonical_url( $post_url, $query, [ 'type' => $slug, 'mode' => 'new' ] );
		$p = [ 'label' => $d['label'], 'url' => $url ];
		$as[] = $p;
	}
	return $as;
}


// -----------------------------------------------------------------------------


function _create_pagination_view( array $query, int $page_count, string $list_url ): ?array {
	$cur = isset( $query['page'] ) ? max( 1, min( intval( $query['page'] ), $page_count ) ) : 1;
	$pages = [];
	for ( $i = 1; $i <= $page_count; $i += 1 ) {
		$url = \nt\create_canonical_url( $list_url, $query, [ 'page' => $i ] );
		$p = [ 'label' => $i, 'url' => $url ];
		if ( $i === $cur ) $p['is_selected'] = true;
		$pages[] = $p;
	}
	if ( count( $pages ) <= 1 ) return null;
	return [
		'previous' => ( ( 1 < $cur ) ? $pages[ $cur - 2 ]['url'] : '' ),
		'next'     => ( ( $cur < $page_count ) ? $pages[ $cur ]['url'] : '' ),
		'pages'    => $pages
	];
}

function _create_header_taxonomy_cols( string $type ): array {
	global $nt_store;
	$labs = [];
	$taxes = $nt_store->type()->getTaxonomySlugAll( $type );
	foreach ( $taxes as $tax ) {
		$labs[] = [ 'label' => $nt_store->taxonomy()->getTaxonomy( $tax )['label'] ];
	}
	return $labs;
}

function _create_header_meta_cols( string $type ): array {
	global $nt_store;
	$labs = [];
	$ms = $nt_store->type()->getMetaAll( $type );
	foreach ( $ms as $m ) {
		if ( ! ( $m['do_show_column'] ?? false ) ) continue;
		$labs[] = [ 'label' => $m['label'] ];
	}
	return $labs;
}

function _create_header_taxonomy_cancels( array $query, string $list_url ): ?array {
	if ( ! isset( $query['taxonomy'] ) ) return null;

	global $nt_store;
	$tts = [];
	foreach ( $query['taxonomy'] as $tax => $ts ) {
		foreach ( $ts as $slug ) {
			$lab = $nt_store->taxonomy()->getTermLabel( $tax, $slug );
			$url = \nt\create_canonical_url( $list_url, $query, [ $tax => null ] );
			$tts[] = [ 'label' => $lab, 'url' => $url ];
		}
	}
	return $tts;
}


// -----------------------------------------------------------------------------


function _process_post_for_view( ?Post $p, array $query, string $list_url, string $post_url, bool $is_trash ): ?array {
	if ( $p === null ) return null;
	$ret = [
		'id'       => $p->getId(),
		'type'     => $p->getType(),
		'title'    => $p->getTitle( true ),
		'status'   => $p->getStatus(),
		'date'     => $p->getDate(),
		'date@sep' => explode( ' ', $p->getDate() ),
		'modified' => $p->getModified(),

		'url_remove'    => \nt\create_canonical_url( $list_url, $query, [ 'remove_id' => $p->getId() ] ),
		'meta@cols'     => _create_meta_cols( $p ),
		'taxonomy@cols' => _create_taxonomy_cols( $p, $query, $list_url ),

	];
	if ( $is_trash ) {
		$ret += [
			'url'     => '#',
			'trash'   => $is_trash,
			'restore' => \nt\create_canonical_url( $list_url, $query, [ 'restore_id' => $p->getId() ] ),
		];
	} else {
		$ret += [
			'url'           => \nt\create_canonical_url( $post_url, $query, [ 'id' => $p->getId() ] ),
			'status@select' => _create_status_select( $p ),
		];
	}
	return $ret;
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
		$ts  = $p->getTermSlugs( $tax );
		$tts = [];
		foreach ( $ts as $slug ) {
			$lab   = $nt_store->taxonomy()->getTermLabel( $tax, $slug );
			$url   = \nt\create_canonical_url( $list_url, $query, [ $tax => $slug ] );
			$tts[] = [ 'label' => $lab, 'url' => $url ];
		}
		$cols[] = [ 'taxonomy' => $tax, 'terms' => $tts ];
	}
	return $cols;
}

function _create_meta_cols( Post $p ): array {
	global $nt_store;
	$cols = [];
	$ms = $nt_store->type()->getMetaAll( $p->getType() );
	foreach ( $ms as $m ) {
		if ( ! ( $m['do_show_column'] ?? false ) ) continue;
		$key  = $m['key'];
		$type = $m['type'];
		$val  = $p->getMetaValue( $key );
		if ( $val === null ) {
			$_lab = '';
		} else {
			if ( $type === 'date' ) {
				$_lab = _h( \nt\parse_date( $val ) );
			} else if ( $type === 'date-range' ) {
				$_bgn = isset( $val['from'] ) ? _h( \nt\parse_date( $val['from'] ) ) : '';
				$_end = isset( $val['to']   ) ? _h( \nt\parse_date( $val['to']   ) ) : '';
				if ( $_bgn === $_end ) {
					$_lab = "<span>$_bgn</span>";
				} else {
					$_lab = "<span>$_bgn</span><span>- $_end</span>";
				}
			} else {
				$_lab = _h( $val );
			}
		}
		$cols[] = [ '_label' => $_lab, 'type' => $type ];
	}
	return $cols;
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
				$ret[ $key ] = \nt\parse_date( $val );
				break;
			case 'date-range':
				$es = Post::DATE_STATUS_ONGOING;
				$now = date( 'Ymd' );
				if ( ! isset( $val['from'] ) || ! isset( $val['to'] ) ) break;
				if ( $now < $val['from'] ) $es = Post::DATE_STATUS_UPCOMING;
				else if ( $val['to'] < $now ) $es = Post::DATE_STATUS_FINISHED;
				$ret[ "$key@status" ] = $es;
				$cls[] = "$key-$es";
				$ret[ $key ] = [
					'from' => \nt\parse_date( $val['from'] ),
					'to'   => \nt\parse_date( $val['to']   ),
				];
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
