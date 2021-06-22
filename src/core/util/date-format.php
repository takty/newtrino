<?php
namespace nt;
/**
 *
 * Function for Date Format
 *
 * @author Takuto Yanagida
 * @version 2021-06-23
 *
 */


function parse_date( string $date ): string {
	return preg_replace( '/(\d{4})(\d{2})(\d{2})/', '$1-$2-$3', $date );
}

function pack_date( string $date ): string {
	return preg_replace( '/(\d{4})-(\d{2})-(\d{2})/', '$1$2$3', $date );
}

function parse_date_time( string $dateTime ): string {
	return preg_replace( '/(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})/', '$1-$2-$3 $4:$5:$6', $dateTime );
}

function pack_date_time( string $dateTime ): string {
	return preg_replace( '/(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/', '$1$2$3$4$5$6', $dateTime );
}
