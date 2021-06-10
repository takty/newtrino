<?php
namespace nt;
/**
 *
 * Taxonomy
 *
 * @author Takuto Yanagida
 * @version 2021-06-10
 *
 */


require_once( __DIR__ . '/class-logger.php' );
require_once( __DIR__ . '/util/label.php' );


class Taxonomy {

	private $_dir  = '';
	private $_lang = '';
	private $_data = '';

	public function __construct( string $data_dir, array $args = [] ) {
		$this->_dir = $data_dir;

		$langKey = ( defined( 'NT_ADMIN' ) && ! defined( 'NT_ADMIN_PREVIEW' ) ) ? 'lang_admin' : 'lang';
		$args = array_merge( [ $langKey => 'en' ], $args );

		$this->_lang = $args[ $langKey ];
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
				$t['is_selected'] = in_array( $t['slug'], $current_term_slugs, true );
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
		if ( ! is_readable( $path ) ) return $this->_data = [];

		$json = file_get_contents( $path );
		if ( $json === false ) {
			Logger::output( 'error', "(Taxonomy::_loadData) Cannot read the taxonomy definition [$path]" );
			die;
		}
		$data = json_decode( $json, true );
		if ( $data === null ) {
			Logger::output( 'error', "(Taxonomy::_loadData) The taxonomy definition is invalid [$path]" );
			die;
		}
		return $this->_data = $this->_processData( $data );;
	}

	private function _processData( array $data ): array {
		$ret = [];

		foreach ( $data as $d ) {
			normalize_label( $d, $this->_lang );
			$ti = [];
			foreach ( $d['terms'] as $idx => &$t ) {
				normalize_label( $t, $this->_lang );
				$ti[ $t['slug'] ] = $idx;
			}
			$ret[ $d['slug'] ] = $d;
			$ret[ $d['slug'] ]['#terms'] = $ti;
		}
		return $ret;
	}

}
