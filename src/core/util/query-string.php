<?php
/**
 * Functions for Query Strings
 *
 * @author Takuto Yanagida
 * @version 2024-03-26
 */

namespace nt;

/**
 * Parses the query string from the global $_SERVER variable and returns it as an associative array.
 * If a default key is provided, it will be used for keys in the query string that have no value.
 *
 * @param string|null $default_key The default key to use for keys with no value.
 * @return array<string, mixed> The parsed query string as an associative array.
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

/**
 * Creates a canonical URL by appending a canonical query string to a base URL.
 *
 * @param string               $base_url  The base URL.
 * @param array<string, mixed> $ps        The parameters for the query string.
 * @param array<string, mixed> $overwrite Any parameters that should overwrite existing ones in $ps.
 * @return string The canonical URL.
 */
function create_canonical_url( string $base_url, array $ps, array $overwrite = [] ): string {
	$cq = \nt\create_canonical_query( $ps, $overwrite );
	return $base_url . ( empty( $cq ) ? '' : "?$cq" );
}

/**
 * Creates a canonical query string from an array of parameters.
 * Certain keys are handled specially and others are treated as taxonomies.
 *
 * @param array<string, mixed> $ps        The parameters for the query string.
 * @param array<string, mixed> $overwrite Any parameters that should overwrite existing ones in $ps.
 * @return string The canonical query string.
 */
function create_canonical_query( array $ps, array $overwrite = [] ): string {
	$th_keys = [ 'id', 'type', 'date', 'search', 'per_page', 'empty_trash' ];

	$ps = array_merge( [], $ps, $overwrite );
	$qs = [];
	foreach ( $th_keys as $k ) {
		if ( isset( $ps[ $k ] ) ) $qs[] = [ $k, $ps[ $k ] ];
	}

	$keys = [ 'id', 'type', 'date', 'search', 'per_page', 'empty_trash', 'page', 'taxonomy' ];
	foreach ( $ps as $tax => $terms ) {  // Taxonomies
		if ( in_array( $tax, $keys, true ) ) continue;
		$terms = \nt\is_string_array( $terms );
		if ( empty( $terms ) ) continue;
		$ts   = implode( ',', $terms );
		$qs[] = [ $tax, $ts ];
	}
	if ( isset( $ps['page'] ) && 1 < $ps['page'] ) $qs[] = [ 'page', $ps['page'] ];
	return \nt\create_query_string( $qs );
}

/**
 * Creates a query string from an array of key-value pairs.
 *
 * @param array{string, mixed}[] $params The parameters for the query string.
 * @return string The query string.
 */
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

/**
 * Checks if a value or each value in an array is a string and returns an array of the string values.
 *
 * @param array<mixed>|string $val The value or array of values to check.
 * @return string[] An array of the string values.
 */
function is_string_array( $val ): array {
	$fvs  = [];
	$vals = is_array( $val ) ? $val : [ $val ];
	foreach ( $vals as $v ) {
		if ( is_string( $val ) ) {
			$fvs[] = $v;
		}
	}
	return $fvs;
}
