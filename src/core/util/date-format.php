<?php
namespace nt;
/**
 *
 * Function for Date Format
 *
 * @author Takuto Yanagida
 * @version 2020-08-04
 *
 */


function parseDate( string $date ): string {
	return preg_replace( '/(\d{4})(\d{2})(\d{2})/', '$1-$2-$3', $date );
}

function packDate( string $date ): string {
	return preg_replace( '/(\d{4})-(\d{2})-(\d{2})/', '$1$2$3', $date );
}

function parseDateTime( string $dateTime ): string {
	return preg_replace( '/(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})/', '$1-$2-$3 $4:$5:$6', $dateTime );
}

function packDateTime( string $dateTime ): string {
	return preg_replace( '/(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/', '$1$2$3$4$5$6', $dateTime );
}
