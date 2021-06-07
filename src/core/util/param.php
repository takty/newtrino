<?php
namespace nt;
/**
 *
 * Functions for Parameters
 *
 * @author Takuto Yanagida
 * @version 2020-07-31
 *
 */


function get_query_vars( array $query, array $filters, ?string $collection = null ): array {
	$ret = [];
	$tcs = [];
	foreach ( $query as $key => $val ) {
		if ( ! isset( $filters[ $key ] ) ) {
			$tcs[] = $key;
			continue;
		}
		$fval = filter_param( $val, $filters[ $key ] );
		if ( $fval !== null ) $ret[ $key ] = $val;
	}
	if ( $collection ) {
		global $nt_store;
		$existing_taxes = array_keys( $nt_store->taxonomy()->getTaxonomyAll() );
		$cs = [];
		foreach( $tcs as $tc ) {
			if ( in_array( $tc, $existing_taxes, true ) ) {
				$ts = array_map( 'trim', explode( ',', $query[ $tc ] ) );
				$cs[ $tc ] = $ts;
			}
		}
		if ( ! empty( $cs ) ) $ret[ $collection ] = $cs;
	}
	return $ret;
}

function filter_param( $val, string $type ) {
	switch ( $type ) {
		case 'string':
			return $val;
		case 'slug':
			if ( preg_match( '/[^a-zA-Z0-9-_]/', $val ) ) break;
			return $val;
		case 'int':
			if ( preg_match( '/[^0-9]/', $val ) ) break;
			return intval( $val );
		case 'string_array':
			return is_array( $val ) ? $val : [ $val ];
		case 'slug_array':
			$fval = [];
			$vals = is_array( $val ) ? $val : [ $val ];
			foreach ( $vals as $v ) {
				if ( preg_match( '/[^a-zA-Z0-9-_]/', $v ) ) continue;
				$fval[] = $v;
			}
			return $fval;
		case 'int_array':
			$fval = [];
			$vals = is_array( $val ) ? $val : [ $val ];
			foreach ( $vals as $v ) {
				if ( preg_match( '/[^0-9]/', $v ) ) continue;
				$fval[] = intval( $v );
			}
			return $fval;
	}
	return null;
}

function get_param( string $key, $default, array $assoc ) {
	if ( isset( $assoc[ $key ] ) ) {
		return $assoc[ $key ];
	}
	return $default;
}

function createTaxQueryFromTaxonomyToTerms( array $tt, array &$args ): void {
	$tq = [];
	foreach ( $tt as $tax => $ts ) {
		$tq[] = [ 'taxonomy' => $tax, 'terms' => $ts ];
	}
	if ( ! empty( $tq ) ) $args['tax_query'] = $tq;
}
