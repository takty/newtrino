<?php
/**
 * Function for Date Format
 *
 * @author Takuto Yanagida
 * @version 2024-03-26
 */

namespace nt;

/**
 * Parses a date string.
 *
 * @param string $date The date string in the format 'YYYYMMDD'.
 * @return string The parsed date string in the format 'YYYY-MM-DD'.
 */
function parse_date( string $date ): string {
	return preg_replace(
		'/(\d{4})(\d{2})(\d{2})/',
		'$1-$2-$3',
		$date
	) ?? '';
}

/**
 * Packs a date string.
 *
 * @param string $date The date string in the format 'YYYY-MM-DD'.
 * @return string The packed date string in the format 'YYYYMMDD'.
 */
function pack_date( string $date ): string {
	return preg_replace(
		'/(\d{4})-(\d{2})-(\d{2})/',
		'$1$2$3',
		$date
	) ?? '';
}

/**
 * Parses a date time string.
 *
 * @param string $dateTime The date time string in the format 'YYYYMMDDHHMMSS'.
 * @return string The parsed date time string in the format 'YYYY-MM-DD HH:MM:SS'.
 */
function parse_date_time( string $dateTime ): string {
	return preg_replace(
		'/(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})/',
		'$1-$2-$3 $4:$5:$6',
		$dateTime
	) ?? '';
}

/**
 * Packs a date time string.
 *
 * @param string $dateTime The date time string in the format 'YYYY-MM-DD HH:MM:SS'.
 * @return string The packed date time string in the format 'YYYYMMDDHHMMSS'.
 */
function pack_date_time( string $dateTime ): string {
	return preg_replace(
		'/(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/',
		'$1$2$3$4$5$6',
		$dateTime
	) ?? '';
}


// -----------------------------------------------------------------------------


/**
 * Formats a date string.
 *
 * @param string $datetime The datetime string.
 * @param string $format The format string.
 * @return string The formatted date string.
 */
function date_create_format( string $datetime, string $format ): string {
	$dt = date_create( $datetime );
	if ( $dt instanceof \DateTime ) {
		return $dt->format( $format );
	}
	return '';
}
