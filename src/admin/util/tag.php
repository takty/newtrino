<?php
namespace nt;
/**
 *
 * Functions for Output
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @version 2020-07-24
 *
 */


function _h( string $str ): string {
	return htmlspecialchars( $str, ENT_QUOTES, 'UTF-8' );
}

function _eh( string $str ) {
	echo htmlspecialchars( $str, ENT_QUOTES, 'UTF-8' );
}

function _ht( string $str ): string {
	return htmlspecialchars( translate( $str ), ENT_QUOTES, 'UTF-8' );
}

function translate( string $str ): string {
	global $nt_res;
	foreach ( $nt_res as $key => $vals ) {
		if ( isset( $vals[ $str ] ) ) {
			return $vals[ $str ];
		}
	}
	return $str;
}
