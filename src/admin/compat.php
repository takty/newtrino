<?php
namespace nt;
/**
 *
 * Compatibility Utilities
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @version 2020-07-26
 *
 */


require_once( __DIR__ . '/index.php' );

convert_category_file( NT_DIR_DATA );
convert_post_file( NT_DIR_POST );


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
	$out_path = $dir_data . 'taxonomy.json';
	$json = json_encode( $taxonomy, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	$res = file_put_contents( $out_path, $json, LOCK_EX );

	if ( $res === false ) {
		Logger::output( 'Error (convert_category_file) [' . $out_path . ']' );
		return false;
	}
	echo "<p>convert_category_file: ok</p>";
	return true;
}

function convert_post_file( $dirPost ) {
	if ( $dir = opendir( $dirPost ) ) {
		while ( ( $fn = readdir( $dir ) ) !== false ) {
			if ( strpos( $fn, '.' ) !== 0 && is_dir( $dirPost . $fn ) ) {
				convert_post_info( $dirPost, $fn );
				convert_post_search_index( $dirPost, $fn );
			}
		}
		closedir($dir);
	}
	echo "<p>convert_post_file: ok</p>";
}

function convert_post_info( $dirPost, $fn ) {
	$old  = $dirPost . $fn . '/' . 'meta.json';
	$path = $dirPost . $fn . '/' . Post::INFO_FILE_NAME;

	if ( ! is_file( $old ) ) return;

	rename( $old, $path );

	$json = file_get_contents( $path );
	if ( $json === false ) {
		Logger::output( 'Error (convert_post_file file_get_contents) [' . $path . ']' );
		return false;
	}
	$d = json_decode( $json, true );

	if ( isset( $d['state'] ) ) {
		$d['status'] = $d['state'];
		unset( $d['state'] );
	}

	if ( isset( $d['category'] ) ) {
		$d['taxonomy'] = [ 'category' => [ $d['category'] ] ];
		unset( $d['category'] );
	}

	if ( isset( $d['published_date'] ) ) {
		$date = str_replace( [ '-', '/', ':', ' ' ], '', $d['published_date'] );
		$d['date'] = $date;
		unset( $d['published_date'] );
	}
	if ( isset( $d['modified_date'] ) ) {
		$date = str_replace( [ '-', '/', ':', ' ' ], '', $d['modified_date'] );
		$d['modified'] = $date;
		unset( $d['modified_date'] );
	}
	if ( isset( $d['created_date'] ) ) unset( $d['created_date'] );

	$meta = [];
	if ( isset( $d['event_date_bgn'] ) ) {
		$meta['date_bgn'] = $d['event_date_bgn'];
		unset( $d['event_date_bgn'] );
	}
	if ( isset( $d['event_date_end'] ) ) {
		$meta['date_end'] = $d['event_date_end'];
		unset( $d['event_date_end'] );
	}
	if ( ! empty( $meta ) ) $d['meta'] = $meta;

	if ( isset( $d['meta']['date_bgn'] ) ) {
		$date = str_replace( [ '-', '/', ':', ' ' ], '', $d['meta']['date_bgn'] );
		$d['meta']['date_bgn'] = $date;
	}
	if ( isset( $d['meta']['date_end'] ) ) {
		$date = str_replace( [ '-', '/', ':', ' ' ], '', $d['meta']['date_end'] );
		$d['meta']['date_end'] = $date;
	}
	if ( isset( $d['meta']['date_bgn'] ) || isset( $d['meta']['date_end'] ) ) {
		$bgn = $d['meta']['date_bgn'];
		$end = $d['meta']['date_end'];
		$d['meta']['duration'] = [ $bgn, $end ];
		unset( $d['meta']['date_bgn'] );
		unset( $d['meta']['date_end'] );
	}
	if ( isset( $d['meta']['duration'] ) ) {
		$d['type'] = 'event';
	}

	$json = json_encode( $d, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	$res = file_put_contents( $path, $json, LOCK_EX );

	if ( $res === false ) {
		Logger::output( 'Error (convert_post_file file_put_contents) [' . $out_path . ']' );
		return false;
	}
}

function convert_post_search_index( $dirPost, $fn ) {
	$old  = $dirPost . $fn . '/' . 'word.txt';
	$path = $dirPost . $fn . '/' . Post::BIGM_FILE_NAME;

	if ( ! is_file( $old ) ) return;
	rename( $old, $path );

	$pathInfo = $dirPost . $fn . '/' . Post::INFO_FILE_NAME;
	$json = file_get_contents( $pathInfo );
	$info = json_decode( $json, true );
	$title = $info['title'];

	$pathCont = $dirPost . $fn . '/' . Post::CONT_FILE_NAME;
	$cont = file_get_contents( $pathCont );

	$text = strip_tags( $title ) . ' ' . strip_tags( $cont );
	$path = $dirPost . $fn . '/' . Post::BIGM_FILE_NAME;
	return Indexer::updateSearchIndex( $text, $path, Post::MODE_FILE );
}
