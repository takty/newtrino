<?php
namespace nt;
/**
 *
 * Ajax API
 *
 * @author Takuto Yanagida
 * @version 2020-06-27
 *
 */


require_once( __DIR__ . '/response.php' );

$query  = ( isset( $_POST['query' ] ) ) ? json_decode( $_POST['query' ], true ) : [];
$filter = ( isset( $_POST['filter'] ) ) ? json_decode( $_POST['filter'], true ) : [];
$config = ( isset( $_POST['config'] ) ) ? json_decode( $_POST['config'], true ) : [];

if ( isset( $query['id'] ) ) {
	$d = create_response_single( $query, $filter, $config );
} else {
	$d = create_response_archive( $query, $filter, $config );
}

header( 'Content-Type: text/html; charset=UTF-8' );
echo json_encode( $d, JSON_UNESCAPED_UNICODE );
