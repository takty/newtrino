<?php
namespace nt;
/**
 *
 * Handler - Media
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @version 2020-07-25
 *
 */


require_once( __DIR__ . '/index.php' );
require_once( __DIR__ . '/class-media.php' );
require_once( __DIR__ . '/../core/class-store.php' );
require_once( __DIR__ . '/../core/util/template.php' );

start_session( true, true );


function handle_query( array $q ): array {
	$q_id   = $q['id']   ?? 0;
	$q_mode = $q['mode'] ?? '';
	$msg	= '';

	$media = new Media( $q_id, Post::MEDIA_DIR_NAME, NT_URL );

	switch ( $q_mode ) {
		case 'upload':
			if ( ! isset( $_FILES['upload_file']['error'] ) || ! is_int( $_FILES['upload_file']['error'] ) ) {
				Logger::output( 'Error (handler-media: $_FILES) Invalid Parameters' );
				break;
			}
			$err = $_FILES['upload_file']['error'];
			if ( $err === UPLOAD_ERR_OK ) {
				$media->upload( $_FILES['upload_file'] );
			} else if ( $err === UPLOAD_ERR_NO_FILE ) {
				Logger::output( 'Error (handler-media: $_FILES) UPLOAD_ERR_NO_FILE' );
				$msg = _ht( 'No file was uploaded.' );
			} else if ( $err === UPLOAD_ERR_INI_SIZE || $err === UPLOAD_ERR_FORM_SIZE ) {
				Logger::output( 'Error (handler-media: $_FILES) UPLOAD_ERR_INI_SIZE or UPLOAD_ERR_FORM_SIZE' );
				$msg = _ht( 'The uploaded file exceeds the max file size.' );
			} else {
				Logger::output( 'Error (handler-media: $_FILES)' );
				$msg = _ht( 'An unknown error occurred.' );
			}
			break;
		case 'delete':
			if ( ! empty( $q['delete_file'] ) ) {
				$media->remove( $q['delete_file'] );
			}
			break;
	}

	$items = $media->getItemList();
	foreach ( $items as $idx => &$it ) $it['index'] = $idx;

	return [
		'id'            => $q_id,
		'items'         => $items,
		'aligns'        => _create_align_options(),
		'sizes'         => _create_size_options(),
		'message'       => $msg,
		'max_file_size' => _get_max_file_size(),
	];
}

function _create_align_options(): array {
	return [
		[ 'value' => 'alignleft',   'label' => _ht( 'Left' ) ],
		[ 'value' => 'aligncenter', 'label' => _ht( 'Center' ), 'selected' => ' selected' ],
		[ 'value' => 'alignright',  'label' => _ht( 'Right' ) ],
		[ 'value' => 'alignnone',   'label' => _ht( 'None' ) ],
	];
}

function _create_size_options(): array {
	global $nt_config;
	$ret = [];
	foreach ( $nt_config['image_sizes'] as $key => $d ) {
		$ret[] = [
			'value' => str_replace( '_', '-', $key ),
			'label' => _ht( $d['label'] ),
		];
	}
	$ret[] = [ 'value' => 'full', 'label' => _ht( 'Full Size' ) ];
	return $ret;
}

function _get_max_file_size(): int {
	$a = _return_bytes( ini_get( 'post_max_size' ) );
	$b = _return_bytes( ini_get( 'upload_max_filesize' ) );
	return min( $a, $b );
}

function _return_bytes( string $val ): int {
	$val = trim( $val );
	$unit = strtolower( $val[ strlen( $val ) - 1 ] );
	$v = intval( $val );
	switch ( $unit ) {
		case 'g': $v *= 1024;
		case 'm': $v *= 1024;
		case 'k': $v *= 1024;
	}
	return $v;
}
