<?php
/**
 * Functions for Labels
 *
 * @author Takuto Yanagida
 * @version 2021-09-11
 */

namespace nt;

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
