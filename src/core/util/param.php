<?php
namespace nt;
/**
 *
 * Functions for Parameters
 *
 * @author Takuto Yanagida
 * @version 2021-09-21
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
		$fv = \nt\filter_param( $val, $filters[ $key ] );
		if ( $fv !== null ) {
			$ret[ $key ] = $val;
		}
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
		if ( ! empty( $cs ) ) {
			$ret[ $collection ] = $cs;
		}
	}
	return $ret;
}

function filter_param( $val, string $type ) {
	switch ( $type ) {
		case 'string':
			if ( is_string( $val ) ) {
				return $val;
			}
			break;
		case 'slug':
			if ( is_string( $val ) && preg_match( '/^[-\w]+$/', $val ) ) {
				return $val;
			}
			break;
		case 'int':
			if ( is_int( $val ) || ( is_string( $val ) && preg_match( '/^[\d]+$/', $val ) ) ) {
				return (int) $val;
			}
			break;
		case 'string_array':
			$fvs  = [];
			$vals = is_array( $val ) ? $val : [ $val ];
			foreach ( $vals as $v ) {
				if ( is_string( $v ) ) {
					$fvs[] = $v;
				}
			}
			return $fvs;
		case 'slug_array':
			$fvs  = [];
			$vals = is_array( $val ) ? $val : [ $val ];
			foreach ( $vals as $v ) {
				if ( is_string( $v ) && preg_match( '/^[-\w]+$/', $v ) ) {
					$fvs[] = $v;
				}
			}
			return $fvs;
		case 'int_array':
			$fvs  = [];
			$vals = is_array( $val ) ? $val : [ $val ];
			foreach ( $vals as $v ) {
				if ( is_int( $v ) || ( is_string( $v ) && preg_match( '/^[\d]+$/', $v ) ) ) {
					$fvs[] = (int) $v;
				}
			}
			return $fvs;
	}
	return null;
}

function create_tax_query_from_taxonomy_to_terms( array $tt, array &$args ): void {
	$tq = [];
	foreach ( $tt as $tax => $ts ) {
		$tq[] = [ 'taxonomy' => $tax, 'terms' => $ts ];
	}
	if ( ! empty( $tq ) ) {
		$args['tax_query'] = $tq;
	}
}
