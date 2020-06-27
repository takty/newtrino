<?php
namespace nt;
/**
 *
 * Taxonomy
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @version 2020-06-27
 *
 */


require_once(__DIR__ . '/class-logger.php');


class Taxonomy {

	private $_dir  = '';
	private $_data = '';

	public function __construct( string $data_dir, array $args = [] ) {
		$this->_dir = $data_dir;
		$args = array_merge( [
			'lang' => 'en',
		], $args );
		$this->_lang = $args['lang'];
	}


	// -------------------------------------------------------------------------


	public function getTermLabel( string $tax_slug, string $term_slug, bool $is_singular = false ): string {
		$t = $this->getTerm( $tax_slug, $term_slug );
		if ( ! $t ) return '';
		return $t[ $is_singular ? 'sg_label' : 'label' ];
	}

	public function getTerm( string $tax_slug, string $term_slug ): ?array {
		$tax = $this->getTaxonomy( $tax_slug );
		if ( $tax === null ) return null;

		if ( isset( $tax['#terms'][ $term_slug ] ) ) {
			$i = $tax['#terms'][ $term_slug ];
			return $tax['terms'][ $i ];
		}
		return null;
	}

	public function getTermAll( string $tax_slug, array $current_term_slugs = null ): array {
		$tax = $this->getTaxonomy( $tax_slug );
		if ( empty( $tax ) ) return [];

		$ret = [];
		foreach ( $tax['terms'] as $idx => $t ) {
			if ( $current_term_slugs !== null ) {
				$t['is_current'] = in_array( $t['slug'], $current_term_slugs, true );
			}
			$ret[] = $t;
		}
		return $ret;
	}

	public function getTaxonomy( string $tax_slug ): ?array {
		$taxes = $this->_loadData();
		if ( isset( $taxes[ $tax_slug ] ) ) {
			return $taxes[ $tax_slug ];
		}
		return null;
	}

	public function getTaxonomyAll(): array {
		return $this->_loadData();
	}


	// -------------------------------------------------------------------------


	private function _loadData(): array {
		if ( $this->_data ) return $this->_data;

		$path = $this->_dir . 'taxonomy.json';
		$json = file_get_contents( $path );
		if ( $json === false ) {
			Logger::output('Error (Taxonomy::_loadData file_get_contents) [' . $path . ']');
			return [];
		}
		$data = json_decode( $json, true );
		$ret = [];

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
		return $this->_data = $ret;
	}

	private function _normalizeLabel( array &$d ) {
		$l = $this->_lang;
		if ( isset( $d[ "label@$l" ] ) )    $d['label']    = $d[ "label@$l" ];
		if ( isset( $d[ "sg_label@$l" ] ) ) $d['sg_label'] = $d[ "sg_label@$l" ];

		if ( ! isset( $d['label'] ) && isset( $d['sg_label'] ) ) {
			$d['label'] = $d['sg_label'];
		} else if ( isset( $d['label'] ) && ! isset( $d['sg_label'] ) ) {
			$d['sg_label'] = $d['label'];
		} else if ( ! isset( $d['label'] ) && ! isset( $d['sg_label'] ) ) {
			$t = ucwords( str_replace( '_', ' ', $d['slug'] ) );
			$d['label']    = $t;
			$d['sg_label'] = $t;
		}
	}

}
