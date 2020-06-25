<?php
namespace nt;
/**
 *
 * Compatibility Utilities
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @version 2020-06-25
 *
 */


require_once(__DIR__ . '/class-logger.php');


function convert_category_file( $dir_data ) {
	$in_path = $dir_data . 'category';
	if ( ! is_file( $in_path ) ) return;

	$lines = file( $in_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
	if ( $lines === false ) {
		Logger::output( 'Error (convert_category_file) [' . $in_path . ']' );
		return false;
	}
	$terms = [];
	foreach ( $lines as $line ) {
		$a = explode( "\t", $line );
		$terms[] = [
			'slug'     => $a[0],
			'label$en' => $a[1],
			'label$ja' => translate( $a[1], 'category' )
		];
	}
	$taxonomy = [];
	$taxonomy[] = [
		'slug'      => 'category',
		'label'     => 'Categories',
		'sg_label'  => 'Category',
		'is_exclusive' => true,
		'post_types' => [ 'post' ],
		'terms'     => $terms
	];
	$json = json_encode( $taxonomy, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	$out_path = $dir_data . 'taxonomy.json';
	$res = file_put_contents( $out_path, $json, LOCK_EX );

	if ( $res === false ) {
		Logger::output( 'Error (convert_category_file) [' . $out_path . ']' );
		return false;
	}
	return true;
}
