<?php
/**
 * Taxonomy
 *
 * @author Takuto Yanagida
 * @version 2024-03-26
 */

namespace nt;

require_once( __DIR__ . '/class-logger.php' );
require_once( __DIR__ . '/util/label.php' );

class Taxonomy {

	/**
	 * Directory of data.
	 *
	 * @var string
	 */
	private $_dir  = '';

	/**
	 * Language.
	 *
	 * @var string
	 */
	private $_lang = '';

	/**
	 * Data.
	 *
	 * @var array<string, mixed>
	 */
	private $_data = array();

	/**
	 * Constructor for the class.
	 *
	 * @param string               $data_dir The directory of the data.
	 * @param array<string, mixed> $args     The arguments for the constructor.
	 */
	public function __construct( string $data_dir, array $args = [] ) {
		$this->_dir = $data_dir;

		$langKey = ( defined( 'NT_ADMIN' ) && ! defined( 'NT_ADMIN_PREVIEW' ) ) ? 'lang_admin' : 'lang';
		$args = array_merge( [ $langKey => 'en' ], $args );

		$this->_lang = $args[ $langKey ];
	}


	// -------------------------------------------------------------------------


	/**
	 * Gets the label of the term.
	 *
	 * @param string $tax_slug    The slug of the taxonomy.
	 * @param string $term_slug   The slug of the term.
	 * @param bool   $is_singular Whether to get the singular label.
	 * @return string The label of the term.
	 */
	public function getTermLabel( string $tax_slug, string $term_slug, bool $is_singular = false ): string {
		$t = $this->getTerm( $tax_slug, $term_slug );
		if ( ! $t ) return '';
		return $t[ $is_singular ? 'sg_label' : 'label' ];
	}

	/**
	 * Gets the term.
	 *
	 * @param string $tax_slug  The slug of the taxonomy.
	 * @param string $term_slug The slug of the term.
	 * @return ?array<string, mixed> The term.
	 */
	public function getTerm( string $tax_slug, string $term_slug ): ?array {
		$tax = $this->getTaxonomy( $tax_slug );
		if ( $tax === null ) return null;

		if ( isset( $tax['#terms'][ $term_slug ] ) ) {
			$i = $tax['#terms'][ $term_slug ];
			return $tax['terms'][ $i ];
		}
		return null;
	}

	/**
	 * Gets all terms.
	 *
	 * @param string    $tax_slug           The slug of the taxonomy.
	 * @param ?string[] $current_term_slugs The current term slugs.
	 * @return array<string, mixed>[] All terms.
	 */
	public function getTermAll( string $tax_slug, ?array $current_term_slugs = null ): array {
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

	/**
	 * Gets the taxonomy.
	 *
	 * @param string $tax_slug The slug of the taxonomy.
	 * @return ?array<string, mixed> The taxonomy.
	 */
	public function getTaxonomy( string $tax_slug ): ?array {
		$taxes = $this->_loadData();
		if ( isset( $taxes[ $tax_slug ] ) ) {
			return $taxes[ $tax_slug ];
		}
		return null;
	}

	/**
	 * Gets all taxonomies.
	 *
	 * @return array<string, mixed> All taxonomies.
	 */
	public function getTaxonomyAll(): array {
		return $this->_loadData();
	}


	// -------------------------------------------------------------------------


	/**
	 * Loads the data.
	 *
	 * @return array<string, mixed>[] The data.
	 */
	private function _loadData(): array {
		if ( $this->_data ) return $this->_data;

		$path = $this->_dir . 'taxonomy.json';
		if ( ! is_readable( $path ) ) return $this->_data = [];

		$json = file_get_contents( $path );
		if ( $json === false ) {
			Logger::error( __METHOD__, 'Cannot read the taxonomy definition', $path );
			exit;
		}
		$data = json_decode( $json, true );
		if ( $data === null ) {
			Logger::error( __METHOD__, 'The taxonomy definition is invalid', $path );
			exit;
		}
		return $this->_data = $this->_processData( $data );;
	}

	/**
	 * Processes the data.
	 *
	 * @param array<string, mixed>[] $data The data to process.
	 * @return array<string, mixed> The processed data.
	 */
	private function _processData( array $data ): array {
		$ret = [];

		foreach ( $data as $d ) {
			if ( ! is_string( $d['slug'] ?? null ) ) continue;
			\nt\normalize_label( $d, $this->_lang );
			$ti = [];
			foreach ( $d['terms'] as $idx => &$t ) {
				if ( ! is_string( $t['slug'] ?? null ) ) continue;
				\nt\normalize_label( $t, $this->_lang );
				$ti[ $t['slug'] ] = $idx;
			}
			$ret[ $d['slug'] ] = $d;
			$ret[ $d['slug'] ]['#terms'] = $ti;
		}
		return $ret;
	}

}
