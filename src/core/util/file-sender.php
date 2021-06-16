<?php
namespace nt;
/**
 *
 * Function for Sending Files
 *
 * @author Takuto Yanagida
 * @version 2021-06-16
 *
 */


function sendFile( string $path, ?string $mimeType = null, bool $isDownload = false ): void {
	if ( ! is_file( $path ) || ! is_readable( $path ) ) {
		http_response_code( 404 );
		die( 1 );
	}
	$mime = $mime ?? ( new \finfo( FILEINFO_MIME_TYPE ) )->file( $path );
	if ( ! preg_match( '/\A\S+?\/\S+/', $mime ) ) {
		$mime = 'application/octet-stream';
	}
	$size = filesize( $path );
	$name = rawurlencode( pathinfo( $path, PATHINFO_BASENAME ) );
	$time = date( 'r', filemtime( $path ) );
	$at   = $isDownload ? ' attachment;' : '';

	$max  = 10000000;  // 10 MB
	$from = 0;
	$to   = $size - 1;
	if ( ! empty( $_SERVER['HTTP_RANGE'] ) ) {
		if ( preg_match( '/bytes=(\d*)-(\d*)/i', $_SERVER['HTTP_RANGE'], $ms ) ) {
			if ( ! empty( $ms[1] ) ) $from = intval( $ms[1] );
			if ( ! empty( $ms[2] ) ) $to   = intval( $ms[2] );
		}
		if ( $max < $to - $from + 1 ) $to = $from + $max - 1;
		if ( $size - 1 < $to ) $to = $size - 1;
	}

	while ( ob_get_level() ) ob_end_clean();

	if ( 0 < $from || $to < $size - 1 ) {
		http_response_code( 206 );  // Partial Content
		header( "Content-Range: bytes $from-$to/$size" );
		header( 'Content-Length: ' . ( $to - $from + 1 ) );
	} else {
		http_response_code( 200 );  // OK
		header( "Content-Length: $size" );
	}

	header( 'Accept-Ranges: bytes' );
	header( "Content-Type: $mime" );
	header( "Content-Disposition:$at filename*=UTF-8''$name;" );
  	header( "Last-Modified: $time" );
  	header( "X-Content-Type-Options: nosniff" );  // Suppress MIME type inference by browsers

	if ( 0 < $from || $to < $size - 1 ) {
		$fp = fopen( $path, 'rb' );
		fseek( $fp, $from );
		echo fread( $fp, $to - $from + 1 );
		fclose( $fp );
	} else {
		readfile( $path );
	}
	exit;
}
