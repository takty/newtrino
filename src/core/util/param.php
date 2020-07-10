<?php
namespace nt;
/**
 *
 * Functions for Parameters
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @version 2020-07-10
 *
 */


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
