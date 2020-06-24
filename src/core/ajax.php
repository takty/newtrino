<?php
namespace nt;
/**
 *
 * Ajax API
 *
 * @author Takuto Yanagida
 * @version 2020-06-24
 *
 */


require_once( __DIR__ . '/response.php' );

$query  = ( isset( $_POST['query' ] ) ) ? json_decode( $_POST['query' ], true ) : [];
$filter = ( isset( $_POST['filter'] ) ) ? json_decode( $_POST['filter'], true ) : [];

if ( isset( $query['id'] ) ) {
	$d = create_response_single( $query, $filter );
} else {
	$d = create_response_archive( $query, $filter );
}

header( 'Content-Type: text/html; charset=UTF-8' );
echo json_encode( $d, JSON_UNESCAPED_UNICODE );
