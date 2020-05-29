<?php
namespace nt;
/**
 *
 * Ajax API
 *
 * @author Takuto Yanagida
 * @version 2020-05-29
 *
 */


require_once(__DIR__ . '/init.php');

$action = ( isset( $_POST['action'] ) ) ? $_POST['action'] : '';

switch ( $action ) {
	case 'recent':
		$count = get_param_int( 'count', 10 );
		$cat   = get_param_slug( 'category', '' );
		$ofe   = get_param_bool( 'omit-finished-event', false );
		$ps = get_recent( $count, $cat, $ofe );
		$ds = [];
		foreach ( $ps as $p ) $ds[] = create_post_data( $p );
		header( 'Content-Type: text/html; charset=UTF-8' );
		echo json_encode( [ 'status' => 'success', 'posts' => $ds ], JSON_UNESCAPED_UNICODE );
		break;
	default:
		header( 'Content-Type: text/html; charset=UTF-8' );
		echo json_encode( [ 'status' => 'failure' ], JSON_UNESCAPED_UNICODE );
		return;
}


// -----------------------------------------------------------------------------


function create_post_data( $p ) {
	$d = [
		'slug'      => '',  // preserved
		'type'      => '',  // preserved
		'author'    => '',  // preserved
		'title'     => $p->getTitle( true ),
		'date'      => $p->getPublishedDateTime(),
		'modified'  => $p->getModifiedDateTime(),
		'excerpt'   => $p->getExcerpt( 60 ),
		'meta'      => [],
		'taxonomy'  => [],
		'permalink' => get_permalink(NT_URL_BASE . 'view.php', $p),
		'state'     => $p->getStateClasses(),  // temporary
	];
	$d['taxonomy']['category'] = [
		[
			'slug'  => $p->getCategory(),
			'label' => translate($p->getCategoryName(), 'category'),
		]
	];
	if ($p->getCategory() === 'event') {
		$d['meta']['event_state']    = $p->getEventState();
		$d['meta']['event_date_bgn'] = $p->getEventDateBgn();
		$d['meta']['event_date_end'] = $p->getEventDateEnd();
	}
	return $d;
}


// -----------------------------------------------------------------------------


function get_param_int( string$key, $default ) {
	if ( isset( $_POST[ $key ] ) && ! preg_match( '/[^0-9]/', $_POST[ $key ] ) ) {
		return intval( $_POST[ $key ] );
	}
	return $default;
}

function get_param_bool( $key, $default ) {
	if ( isset( $_POST[ $key ] ) && ! preg_match( '/[^0-1]/', $_POST[ $key ] ) ) {
		return intval( $_POST[ $key ] ) === 1;
	}
	return $default;
}

function get_param_slug( $key, $default ) {
	if ( isset( $_POST[ $key ] ) && ! preg_match( '/[^a-zA-Z0-9-_]/', $_POST[ $key ] ) ) {
		return $_POST[ $key ];
	}
	return $default;
}
