<?php
/**
 * Function for Sending Files
 *
 * @author Takuto Yanagida
 * @version 2024-03-25
 */

namespace nt;

/**
 * Sends a file.
 *
 * @param string      $path The   path of the file.
 * @param string|null $mimeType   The MIME type of the file.
 * @param bool        $isDownload The download flag.
 */
function send_file( string $path, ?string $mimeType = null, bool $isDownload = false ): void {
	if ( ! is_file( $path ) || ! is_readable( $path ) ) {
		http_response_code( 404 );
		die( 1 );
	}
	$mime = $mimeType ?? ( new \finfo( \FILEINFO_MIME_TYPE ) )->file( $path );
	if ( ! is_string( $mime ) || ! preg_match( '/\A\S+?\/\S+/', $mime ) ) {
		$mime = 'application/octet-stream';
	}
	$size = filesize( $path );
	$name = rawurlencode( pathinfo( $path, PATHINFO_BASENAME ) );
	$ft   = filemtime( $path );
	$time = is_int( $ft ) ? date( 'r', $ft ) : date( 'r' );
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

	if ( ( 0 < $from || $to < $size - 1 ) && 0 < $to - $from + 1 ) {
		$fp = fopen( $path, 'rb' );
		if ( is_resource( $fp ) ) {
			fseek( $fp, $from );
			$cont = fread( $fp, $to - $from + 1 );
			if ( false !== $cont ) {
				echo $cont;
			}
			fclose( $fp );
		}
	} else {
		readfile( $path );
	}
	exit;
}
