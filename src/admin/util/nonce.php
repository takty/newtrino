<?php
namespace nt;
/**
 *
 * Function for Nonce
 *
 * @author Takuto Yanagida
 * @version 2021-06-23
 *
 */


function create_nonce( $bytes ): string {
	return bin2hex( openssl_random_pseudo_bytes( $bytes ) );
}
