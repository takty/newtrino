<?php
/**
 * Handler - Media
 *
 * @author Takuto Yanagida
 * @version 2023-06-22
 */

namespace nt;

require_once( __DIR__ . '/index.php' );
require_once( __DIR__ . '/class-media.php' );
require_once( __DIR__ . '/../core/class-store.php' );
require_once( __DIR__ . '/../core/util/template.php' );

start_session( true, true );

function handle_query_media( array $q ): array {
	$q_id   = $q['id']   ?? null;
	$q_mode = $q['mode'] ?? '';

	$q_filter = $q['filter'] ?? null;
	$q_target = $q['target'] ?? '';
	$q_size   = $q['size']   ?? '';

	$media = new Media( $q_id, Post::MEDIA_DIR_NAME, NT_URL );
	$ntc   = '';

	switch ( $q_mode ) {
		case 'upload':
			if ( ! isset( $_FILES['upload_file']['error'] ) || ! is_int( $_FILES['upload_file']['error'] ) ) {
				Logger::error( __FUNCTION__, 'Parameters are invalid' );
				break;
			}
			$err = $_FILES['upload_file']['error'];
			if ( $err === UPLOAD_ERR_OK ) {
				$media->upload( $_FILES['upload_file'] );
			} elseif ( $err === UPLOAD_ERR_NO_FILE ) {
				Logger::error( __FUNCTION__, 'No file was uploaded [UPLOAD_ERR_NO_FILE]' );
				$ntc = _ht( 'No file was uploaded.' );
			} elseif ( $err === UPLOAD_ERR_INI_SIZE || $err === UPLOAD_ERR_FORM_SIZE ) {
				Logger::error( __FUNCTION__, 'The uploaded file exceeds the max file size [UPLOAD_ERR_INI_SIZE or UPLOAD_ERR_FORM_SIZE]' );
				$ntc = _ht( 'The uploaded file exceeds the max file size.' );
			} else {
				Logger::error( __FUNCTION__, 'An unknown error occurred' );
				$ntc = _ht( 'An unknown error occurred.' );
			}
			break;
		case 'delete':
			if ( ! empty( $q['delete_file'] ) ) {
				$media->remove( $q['delete_file'] );
			}
			break;
	}

	$ft = '';
	switch ( $q_filter ) {
		case 'image': $ft = _ht( 'Images' ); break;
	}
	global $nt_config;
	$size_width = 0;
	if ( $q_size && isset( $nt_config['image_sizes'][ $q_size ] ) ) {
		$size_width = $nt_config['image_sizes'][ $q_size ]['width'];
	}

	return [
		'id'            => $q_id,
		'items'         => $media->getItemList( $q_filter ),
		'aligns'        => _create_align_options(),
		'sizes'         => _create_size_options(),
		'ntc'           => $ntc,
		'max_file_size' => _get_max_file_size(),
		'button_label'  => translate( $q_target ? 'Select' : 'Insert Into Post' ),

		'filter_type'     => $ft,
		'meta_target'     => $q_target,
		'meta_size_width' => $size_width,
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
