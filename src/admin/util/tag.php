<?php
namespace nt;
/**
 *
 * Functions for Output
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @version 2020-07-23
 *
 */


function _h( string $str ): string {
	return htmlspecialchars( $str, ENT_QUOTES, 'UTF-8' );
}

function _eh( string $str ) {
	echo htmlspecialchars( $str, ENT_QUOTES, 'UTF-8' );
}

function _ht( string $str, string $context = 'default' ): string {
	return htmlspecialchars( translate( $str, $context ), ENT_QUOTES, 'UTF-8' );
}

function translate( string $str, string $context = 'default' ): string {
	if ( defined( 'NT_ADMIN' ) && $context === 'default' ) {
		$context = 'admin';
	}
	global $nt_res;
	if ( isset( $nt_res[ $context ][ $str ] ) ) {
		return $nt_res[ $context ][ $str ];
	}
	if ( $context !== 'default' ) {
		if ( isset( $nt_res['default'][ $str ] ) ) {
			return $nt_res['default'][ $str ];
		}
	}
	return $str;
}
