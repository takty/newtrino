<?php
/**
 * Functions for Labels
 *
 * @author Takuto Yanagida
 * @version 2024-03-26
 */

namespace nt;

/**
 * Normalizes the labels in the given array.
 *
 * @param array<string, string> $d The array to normalize. This is passed by reference.
 * @param string                $l The suffix for language.
 */
function normalize_label( array &$d, string $l ): void {
	if ( isset( $d[ "label@$l" ] ) ) {
		$d['label'] = $d[ "label@$l" ];
	}
	if ( isset( $d[ "sg_label@$l" ] ) ) {
		$d['sg_label'] = $d[ "sg_label@$l" ];
	}

	if ( ! isset( $d['label'] ) && isset( $d['sg_label'] ) ) {
		$d['label'] = $d['sg_label'];
	} elseif ( isset( $d['label'] ) && ! isset( $d['sg_label'] ) ) {
		$d['sg_label'] = $d['label'];
	} elseif ( ! isset( $d['label'] ) && ! isset( $d['sg_label'] ) && isset( $d['slug'] ) ) {
		$t = ucwords( str_replace( '_', ' ', $d['slug'] ) );
		$d['label']    = $t;
		$d['sg_label'] = $t;
	}
}
