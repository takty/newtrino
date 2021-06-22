<?php
namespace nt;
/**
 *
 * Functions for Query Strings
 *
 * @author Takuto Yanagida
 * @version 2021-06-23
 *
 */


function parse_query_string( ?string $default_key = null ): array {
	$str = $_SERVER['QUERY_STRING'];

	$ps = [];
	$default_val = '';
	foreach ( $_REQUEST as $key => $val ) {
		if ( $default_key !== null && empty( $val ) && strpos( $str, $key . '=' ) === false ) {
			$default_val = $key;
		} else {
			$ps[ $key ] = $val;
		}
	}
	if ( $default_key !== null && ! empty( $default_val ) ) $ps[ $default_key ] = $default_val;
	return $ps;
}

function create_canonical_url( string $base_url, array $ps, array $overwrite = [] ): string {
	$cq = \nt\create_canonical_query( $ps, $overwrite );
	return $base_url . ( empty( $cq ) ? '' : "?$cq" );
}

function create_canonical_query( array $ps, array $overwrite = [] ): string {
	$ps = array_merge( [], $ps, $overwrite );
	$qs = [];
	if ( isset( $ps['id']       ) ) $qs[] = [ 'id',       $ps['id']       ];
	if ( isset( $ps['type']     ) ) $qs[] = [ 'type',     $ps['type']     ];
	if ( isset( $ps['date']     ) ) $qs[] = [ 'date',     $ps['date']     ];
	if ( isset( $ps['search']   ) ) $qs[] = [ 'search',   $ps['search']   ];
	if ( isset( $ps['per_page'] ) ) $qs[] = [ 'per_page', $ps['per_page'] ];

	$keys = [ 'id', 'type', 'date', 'search', 'per_page', 'page', 'taxonomy' ];
	foreach ( $ps as $tax => $terms ) {  // Taxonomies
		if ( in_array( $tax, $keys, true ) ) continue;
		if ( empty( $terms ) ) continue;
		$ts = is_array( $terms ) ? implode( ',', $terms ) : $terms;
		$qs[] = [ $tax, $ts ];
	}
	if ( isset( $ps['page'] ) && 1 < $ps['page'] ) $qs[] = [ 'page', $ps['page'] ];
	return \nt\create_query_string( $qs );
}

function create_query_string( array $params ): string {
	$kvs = [];
	foreach ( $params as $kv ) {
		$_key = urlencode( $kv[0] );
		$v = $kv[1];
		if ( is_array( $v ) ) $v = json_encode( $v, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		$_val = urlencode( $v );
		$kvs[] = $_key . '=' . $_val;
	}
	return implode( '&', $kvs );
}
