<?php
namespace nt;
/**
 *
 * Taxonomy
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @version 2020-06-25
 *
 */


require_once(__DIR__ . '/compat.php');
require_once(__DIR__ . '/class-logger.php');


class Taxonomy {

	private $_dir  = '';
	private $_data = '';

	public function __construct( $dirData, $args = [] ) {
		$this->_dir = $dirData;
		$args = array_merge( [
			'lang' => 'en',
		], $args );
		$this->_lang = $args['lang'];
	}

	public function getTermLabel( $taxonomy, $term, $is_singular = false ) {
		$t = $this->getTerm( $taxonomy, $term );
		if ( ! $t ) return '';
		return $t[ $is_singular ? 'sg_label' : 'label' ];
	}

	public function getTerm( $taxonomy, $term ) {
		$tax = $this->getTaxonomy( $taxonomy );
		if ( $tax === null ) return null;

		if ( isset( $tax['#terms'][ $term ] ) ) {
			$i = $tax['#terms'][ $term ];
			return $tax['terms'][ $i ];
		}
		return null;
	}

	public function getTerms( $taxonomy, $current_term = false ) {
		$tax = $this->getTaxonomy( $taxonomy );
		if ( $tax === null ) return [];

		$ret = [];
		foreach ( $tax['terms'] as $idx => $t ) {
			if ( $current_term ) $t['is_current'] = ( $t['slug'] === $current_term );
			$ret[] = $t;
		}
		return $ret;
	}

	public function getTaxonomy( $taxonomy ) {
		$taxes = $this->_loadData();
		if ( $taxes !== false && isset( $taxes[ $taxonomy ] ) ) {
			return $taxes[ $taxonomy ];
		}
		return null;
	}

	private function _loadData() {
		if ( $this->_data ) return $this->_data;

		$path = $this->_dir . 'taxonomy.json';

		if ( ! is_file( $path ) ) \nt\convert_category_file( $this->_dir );

		$json = file_get_contents( $path );
		if ( $json === false ) {
			Logger::output('Error (Taxonomy::_loadData file_get_contents) [' . $path . ']');
			return false;
		}
		$data = json_decode( $json, true );
		$ret = [];
		global $nt_config;
		foreach ( $data as $d ) {
			$this->_normalizeLabel( $d );
			$ti = [];
			foreach ( $d['terms'] as $idx => &$t ) {
				$this->_normalizeLabel( $t );
				$ti[ $t['slug'] ] = $idx;
			}
			$ret[ $d['slug'] ] = $d;
			$ret[ $d['slug'] ]['#terms'] = $ti;
		}
		// var_dump( $ret );
		return $this->_data = $ret;
	}

	private function _normalizeLabel( &$d ) {
		$l = $this->_lang;
		if ( ! isset( $d['label'] ) ) {
			if ( isset( $d[ "label@$l" ] ) ) $d['label'] = $d[ "label@$l" ];
		}
		if ( ! isset( $d['sg_label'] ) ) {
			if ( isset( $d[ "sg_label@$l" ] ) ) $d['sg_label'] = $d[ "sg_label@$l" ];
		}
		if ( ! isset( $d['label'] ) && isset( $d['sg_label'] ) ) {
			$d['label'] = $d['sg_label'];
		} else if ( isset( $d['label'] ) && ! isset( $d['sg_label'] ) ) {
			$d['sg_label'] = $d['label'];
		} else if ( ! isset( $d['label'] ) && ! isset( $d['sg_label'] ) ) {
			$l = ucwords( str_replace( '_', ' ', $d['slug'] ) );
			$d['label']    = $l;
			$d['sg_label'] = $l;
		}
	}

}
