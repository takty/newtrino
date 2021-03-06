<?php
namespace nt;
/**
 *
 * Function for Sending Files
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @version 2020-08-04
 *
 */


function sendFile( string $path, ?string $mimeType = null, bool $isDownload = false ): void {
	if ( ! is_file( $path ) || ! is_readable( $path ) ) {
		header( 'HTTP/1.1 404 Not Found' );
		die( 1 );
	}
	$mimeType = $mimeType ?? ( new \finfo( FILEINFO_MIME_TYPE ) )->file( $path );
	if ( ! preg_match( '/\A\S+?\/\S+/', $mimeType ) ) {
		$mimeType = 'application/octet-stream';
	}
	$fileSize = filesize( $path );
	$fileName = pathinfo( $path, PATHINFO_BASENAME );
	$at = $isDownload ? ' attachment;' : '';

	while ( ob_get_level() ) ob_end_clean();

	header( "Content-Type: $mimeType" );
	header( "Content-Length: $fileSize" );
	header( "Content-Disposition:$at filename=\"$fileName\"" );
	header( 'Connection: close' );
	header( "X-Content-Type-Options: nosniff" );  // Suppress MIME type inference by browsers

	readfile( $path );
	exit;
}
