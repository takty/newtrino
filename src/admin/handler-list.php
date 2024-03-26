<?php
/**
 * Handler - List
 *
 * @author Takuto Yanagida
 * @version 2024-03-26
 */

namespace nt;

require_once( __DIR__ . '/index.php' );
require_once( __DIR__ . '/../core/class-store.php' );
require_once( __DIR__ . '/../core/util/date-format.php' );
require_once( __DIR__ . '/../core/util/param.php' );
require_once( __DIR__ . '/../core/util/query-string.php' );
require_once( __DIR__ . '/../core/util/template.php' );

start_session( true );

/**
 * Handles the query list.
 *
 * @global array<string, mixed> $nt_config  The NT configuration array.
 * @global Store                $nt_store   The NT store object.
 * @global Session              $nt_session The NT session object.
 *
 * @return array<string, mixed> An array containing the processed posts and other related information.
 */
function handle_query_list(): array {
	global $nt_config, $nt_store, $nt_session;
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
			if ( $nt_session->lock( $orig_query['remove_id'] ) ) {
				$nt_store->removePost( $orig_query['remove_id'] );
			} else {
				$error = 'lock';
			}
		} elseif ( isset( $orig_query['restore_id'] ) ) {
			$nt_store->restorePost( $orig_query['restore_id'] );
		} elseif ( isset( $orig_query['empty_trash'] ) ) {
			$nt_store->emptyTrash( $type );
			$nt_store->emptyTemporaryDirectories( $type );
		}
	}
	$args = [ 'status' => $status, 'type' => $type, 'per_page' => $perPage ];
	if ( $date ) {
		$args += [ 'date_query' => [ [ 'date' => $date ] ] ];
	}
	if ( $page ) {
		$args += [ 'page' => $page ];
	}
	if ( ! empty( $query['taxonomy'] ) ) {
		\nt\create_tax_query_from_taxonomy_to_terms( $query['taxonomy'], $args );
	}
	$ret = $nt_store->getPosts( $args );
	$ps  = array_map(
		function ( $p ) use ( $query, $post_url, $status ) {
			return _process_post_for_view( $p, $query, '', $post_url, $status === '_trash' );
		},
		$ret['posts']
	);

	$ntc = [
		'new'    => _ht( 'A new post could not be created.' ),
		'update' => _ht( 'The post could not be updated.' ),
		'view'   => _ht( 'The post could not be viewed.' ),
		'lock'   => _ht( 'The post is being edited by another user.' ),
	][ $error ] ?? '';

	$is_trash = $status === '_trash';
	return [
		'posts'            => $ps,
		'pagination'       => _create_pagination_view( $query, $ret['page_count'], '' ),
		'meta@cols'        => _create_header_meta_cols( $type ),
		'taxonomy@cols'    => _create_header_taxonomy_cols( $type ),
		'taxonomy@cancels' => _create_header_taxonomy_cancels( $query, '' ),
		'filter'           => [
			'type'     => _create_type_filter_view( $query, $types, '' ),
			'date'     => _create_date_filter_view( $query, $type, 'month', '' ),
			'per_page' => _create_per_page_filter_view( $query, [10, 20, 50, 100], '' ),
			'new'      => _create_new_filter_view( $query, $types, $post_url ),
		],
		'list_trash'  => $is_trash ? '#' : \nt\create_canonical_url( '', $query, [ 'status' => '_trash' ] ),
		'list_all'    => $is_trash ? \nt\create_canonical_url( '', $query, [ 'status' => null ] ) : '#',
		'empty_trash' => $is_trash ? \nt\create_canonical_url( '', $query, [ 'empty_trash' => true, 'status' => null ] ) : '#',
		'ntc'         => $ntc,
		'nonce'       => $nt_session->getNonce(),
	];
}


// -----------------------------------------------------------------------------


/**
 * Rearranges the query.
 *
 * @param array<string, mixed> $query The original query array.
 * @return array<string, mixed> The rearranged query array.
 */
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


/**
 * Creates the type filter view.
 *
 * @param array<string, mixed> $query    The query array.
 * @param array<string, mixed> $types    The types array.
 * @param string               $list_url The list URL.
 * @return array<string, mixed>[] The type filter view array.
 */
 function _create_type_filter_view( array $query, array $types, string $list_url ): array {
	$cur = $query['type'] ?? '';
	$as  = [];
	foreach ( $types as $slug => $d ) {
		$url = \nt\create_canonical_url( $list_url, $query, [ 'type' => $slug, 'date' => null, 'page' => null ] );
		$p   = [ 'label' => $d['label'], 'url' => $url ];
		if ( $slug === $cur ) {
			$p['is_selected'] = true;
		}
		$as[] = $p;
	}
	return $as;
}

/**
 * Creates the date filter view.
 *
 * @param array<string, mixed> $query     The query array.
 * @param string               $type      The type string.
 * @param string               $date_type The dateType string.
 * @param string               $list_url  The list URL.
 * @return array<string, mixed> The date filter view array.
 */
function _create_date_filter_view( array $query, string $type, string $date_type, string $list_url ): array {
	global $nt_store;
	$dates = $nt_store->getCountByDate( $date_type, [ 'type' => $type, 'status' => $query['status'] ?? null ] );

	$cur = $query['date'] ?? '';
	$df  = '';
	switch ( $date_type ) {
		case 'year' : $df = 'Y';     break;
		case 'month': $df = 'Y-m';   break;
		case 'day'  : $df = 'Y-m-d'; break;
	}
	$as = [];

	$url = \nt\create_canonical_url( $list_url, $query, [ 'date' => null ] );
	$p   = [ 'label' => translate( 'All' ), 'url' => $url ];
	if ( $cur === '' ) {
		$p['is_selected'] = true;
	}
	$as[] = $p;

	foreach ( $dates as $date ) {
		$url   = \nt\create_canonical_url( $list_url, $query, [ 'date' => $date['slug'], 'page' => null ] );
		$label = _format_date_label( $date['slug'], $df );
		$p     = [ 'label' => $label, 'url' => $url ];
		if ( strval( $date['slug'] ) === $cur ) {
			$p['is_selected'] = true;
		}
		$as[] = $p;
	}
	return [ $date_type => $as ];
}

/**
 * Formats the date label.
 *
 * @param string $slug The slug string.
 * @param string $df   The df string.
 * @return string The formatted date label.
 */
function _format_date_label( string $slug, string $df ): string {
	$y    = substr( $slug, 0, 4 );
	$m    = substr( $slug, 4, 2 );
	$d    = substr( $slug, 6, 2 );
	$date = ( $y ? $y : '1970' ) . '-' . ( $m ? $m : '01' ) . '-' . ( $d ? $d : '01' );
	return date_create_format( $date, $df );
}

/**
 * Creates the per page filter view.
 *
 * @param array<string, mixed> $query    The query array.
 * @param int[]                $per_s    The array of per page.
 * @param string               $list_url The list URL.
 * @return array<string, mixed>[] The per page filter view array.
 */
function _create_per_page_filter_view( array $query, array $per_s, string $list_url ): array {
	$cur = $query['per_page'] ?? '';
	$as  = [];
	foreach ( $per_s as $per ) {
		$url = \nt\create_canonical_url( $list_url, $query, [ 'per_page' => $per, 'page' => null ] );
		$p   = [ 'label' => $per, 'url' => $url ];
		if ( $per == $cur ) {
			$p['is_selected'] = true;
		}
		$as[] = $p;
	}
	return $as;
}

/**
 * Creates the new filter view.
 *
 * @param array<string, mixed> $query    The query array.
 * @param array<string, mixed> $types    The types array.
 * @param string               $post_url The post URL.
 * @return array<string, mixed>[] The new filter view array.
 */
function _create_new_filter_view( array $query, array $types, string $post_url ): array {
	$as = [];
	foreach ( $types as $slug => $d ) {
		$url  = \nt\create_canonical_url( $post_url, $query, [ 'type' => $slug, 'mode' => 'new' ] );
		$p    = [ 'label' => $d['label'], 'url' => $url ];
		$as[] = $p;
	}
	return $as;
}


// -----------------------------------------------------------------------------


/**
 * Creates the pagination view.
 *
 * @param array<string, mixed> $query      The query array.
 * @param int                  $page_count The page count.
 * @param string               $list_url   The list URL.
 * @return array<string, mixed>|null The pagination view array or null if the page count is less than or equal to 1.
 */
function _create_pagination_view( array $query, int $page_count, string $list_url ): ?array {
	$cur   = isset( $query['page'] ) ? max( 1, min( intval( $query['page'] ), $page_count ) ) : 1;
	$pages = [];
	for ( $i = 1; $i <= $page_count; $i += 1 ) {
		$url = \nt\create_canonical_url( $list_url, $query, [ 'page' => $i ] );
		$p   = [ 'label' => $i, 'url' => $url ];
		if ( $i === $cur ) {
			$p['is_selected'] = true;
		}
		$pages[] = $p;
	}
	if ( count( $pages ) <= 1 ) {
		return null;
	}
	return [
		'previous' => ( ( 1 < $cur ) ? $pages[ $cur - 2 ]['url'] : '' ),
		'next'     => ( ( $cur < $page_count ) ? $pages[ $cur ]['url'] : '' ),
		'pages'    => $pages
	];
}

/**
 * Creates the header taxonomy columns.
 *
 * @param string $type The type string.
 * @return array<string, mixed>[] The header taxonomy columns array.
 */
function _create_header_taxonomy_cols( string $type ): array {
	global $nt_store;
	$labs  = [];
	$taxes = $nt_store->type()->getTaxonomySlugAll( $type );
	foreach ( $taxes as $tax ) {
		$labs[] = [ 'label' => $nt_store->taxonomy()->getTaxonomy( $tax )['label'] ];
	}
	return $labs;
}

/**
 * Creates the header meta columns.
 *
 * @param string $type The type string.
 * @return array<string, mixed>[] The header meta columns array.
 */
function _create_header_meta_cols( string $type ): array {
	global $nt_store;
	$labs = [];
	$ms   = $nt_store->type()->getMetaAll( $type );
	foreach ( $ms as $m ) {
		if ( ! ( $m['do_show_column'] ?? false ) ) {
			continue;
		}
		$labs[] = [ 'label' => $m['label'] ];
	}
	return $labs;
}

/**
 * Creates the header taxonomy cancels.
 *
 * @param array{ taxonomy?: array<string, string[]> } $query    The query array.
 * @param string                                      $list_url The list URL.
 * @return array<string, mixed>[]|null The header taxonomy cancels array or null if the taxonomy is not set in the query.
 */
function _create_header_taxonomy_cancels( array $query, string $list_url ): ?array {
	if ( ! isset( $query['taxonomy'] ) ) {
		return null;
	}
	global $nt_store;
	$tts = [];
	foreach ( $query['taxonomy'] as $tax => $ts ) {
		foreach ( $ts as $slug ) {
			$lab   = $nt_store->taxonomy()->getTermLabel( $tax, $slug );
			$url   = \nt\create_canonical_url( $list_url, $query, [ $tax => null ] );
			$tts[] = [ 'label' => $lab, 'url' => $url ];
		}
	}
	return $tts;
}


// -----------------------------------------------------------------------------


/**
 * Processes the post for view.
 *
 * @param Post|null            $p        The post object.
 * @param array<string, mixed> $query    The query array.
 * @param string               $list_url The list URL.
 * @param string               $post_url The post URL.
 * @param bool                 $is_trash The is_trash boolean.
 * @return array<string, mixed>|null The processed post for view array or null if the post object is null.
 */
function _process_post_for_view( ?Post $p, array $query, string $list_url, string $post_url, bool $is_trash ): ?array {
	if ( $p === null ) {
		return null;
	}
	$ret = [
		'id'       => $p->getId(),
		'type'     => $p->getType(),
		'title'    => $p->getTitle(),
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

/**
 * Creates the status select.
 *
 * @param Post $p The post object.
 * @return array<string, mixed>[] The status select array.
 */
function _create_status_select( Post $p ): array {
	$ss = [];
	if ( $p->canPublished() ) {
		$s = [ 'slug' => 'publish', 'label' => translate( 'Published' ) ];
		if ( $p->isStatus( 'publish' ) ) {
			$s['is_selected'] = true;
		}
		$ss[] = $s;
	} else {
		$s = [ 'slug' => 'future', 'label' => translate( 'Scheduled' ) ];
		if ( $p->isStatus( 'future' ) ) {
			$s['is_selected'] = true;
		}
		$ss[] = $s;
	}
	$s = [ 'slug' => 'draft', 'label' => translate( 'Draft' ) ];
	if ( $p->isStatus( 'draft' ) ) {
		$s['is_selected'] = true;
	}
	$ss[] = $s;
	return $ss;
}

/**
 * Creates the taxonomy columns.
 *
 * @param Post                 $p        The post object.
 * @param array<string, mixed> $query    The query array.
 * @param string               $list_url The list URL.
 * @return array<string, mixed>[] The taxonomy columns array.
 */
function _create_taxonomy_cols( Post $p, array $query, string $list_url ): array {
	global $nt_store;
	/**
	 * Taxonomy slugs.
	 *
	 * @var string[]
	 */
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

/**
 * Creates the meta columns.
 *
 * @param Post $p The post object.
 * @return array<string, mixed>[] The meta columns array.
 */
function _create_meta_cols( Post $p ): array {
	global $nt_store;
	$cols = [];
	$ms   = $nt_store->type()->getMetaAll( $p->getType() );
	foreach ( $ms as $m ) {
		if ( ! ( $m['do_show_column'] ?? false ) ) {
			continue;
		}
		$key  = $m['key'];
		$type = $m['type'];
		$val  = $p->getMetaValue( $key );
		if ( $val === null ) {
			$_lab = '';
		} else {
			if ( $type === 'date' ) {
				$_lab = _h( \nt\parse_date( $val ) );
			} elseif ( $type === 'date_range' ) {
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

/**
 * Gets the meta data of a post.
 *
 * @param Post     $p    The post object.
 * @param string[] &$cls The classes array passed by reference.
 * @return array<string, mixed> The processed meta data array.
 */
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
		if ( $type !== 'group' && $val === null ) {
			continue;
		}
		switch ( $type ) {
			case 'date':
				$ret[ $key ] = \nt\parse_date( $val );
				break;
			case 'date_range':
				$es  = Post::DATE_STATUS_ONGOING;
				$now = date( 'Ymd' );
				if ( ! isset( $val['from'] ) || ! isset( $val['to'] ) ) {
					break;
				}
				if ( $now < $val['from'] ) {
					$es = Post::DATE_STATUS_UPCOMING;
				} elseif ( $val['to'] < $now ) {
					$es = Post::DATE_STATUS_FINISHED;
				}
				$ret[ "$key@status" ] = $es;

				$cls[]       = "$key-$es";
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

/**
 * Flattens the meta structure.
 *
 * @param array<string, mixed>[] $ms   The meta structure array.
 * @param array<string, mixed>[] &$ret The result array passed by reference.
 */
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
