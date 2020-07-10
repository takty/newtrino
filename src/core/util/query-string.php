<?php
namespace nt;
/**
 *
 * Functions for Query Strings
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @version 2020-07-10
 *
 */


function parse_query_string( ?string $default_key = null ): array {
	$ps = [];
	$default_val = '';
	foreach ( $_GET as $key => $val ) {
		if ( $default_key !== null && empty( $val ) ) {
			$default_val = $key;
		} else {
			$ps[ $key ] = $val;
		}
	}
	if ( $default_key !== null && ! empty( $default_val ) ) $ps[ $default_key ] = $default_val;
	return $ps;
}

function create_query_string( array $params ): string {
	$kvs = [];
	if ( is_array( $params ) ) {
		foreach ( $params as $kv ) {
			$_key = urlencode( $kv[0] );
			$v = $kv[1];
			if ( is_array( $v ) ) $v = json_encode( $v, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
			$_val = urlencode( $v );
			$kvs[] = $_key . '=' . $_val;
		}
	}
	return implode( '&', $kvs );
}
