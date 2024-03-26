<?php
/**
 * Type
 *
 * @author Takuto Yanagida
 * @version 2024-03-26
 */

namespace nt;

require_once( __DIR__ . '/class-logger.php' );
require_once( __DIR__ . '/util/label.php' );

class Type {

	/**
	 * Directory of data.
	 *
	 * @var string
	 */
	private $_dir = '';

	/**
	 * Language.
	 *
	 * @var string
	 */
	private $_lang = '';

	/**
	 * Whether to archive by type.
	 *
	 * @var bool
	 */
	private $_byType = false;

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

		$this->_lang   = $args[ $langKey ];
		$this->_byType = $args['archive_by_type'];
	}


	// -------------------------------------------------------------------------


	/**
	 * Gets the type of the post.
	 *
	 * @param string $type_slug The slug of the type.
	 * @return ?array<string, mixed> The type of the post.
	 */
	public function getType( string $type_slug ): ?array {
		$types = $this->_loadData();
		if ( isset( $types[ $type_slug ] ) ) {
			return $types[ $type_slug ];
		}
		return null;
	}

	/**
	 * Gets all types of the posts.
	 *
	 * @return array<string, mixed> All types of the posts.
	 */
	public function getTypeAll(): array {
		return $this->_loadData();
	}

	/**
	 * Gets all type directories of the posts.
	 *
	 * @return string[] All type directories of the posts.
	 */
	public function getTypeDirAll(): array {
		if ( $this->_byType ) {
			$ts = array_keys( $this->getTypeAll() );
			return array_map( function ( $e ) { return "$e/"; }, $ts );
		}
		return [ 'post/' ];
	}


	// -------------------------------------------------------------------------


	/**
	 * Gets all taxonomy slugs of the post.
	 *
	 * @param string $type_slug The slug of the type.
	 * @return string[] All taxonomy slugs of the post.
	 */
	public function getTaxonomySlugAll( string $type_slug ): array {
		$type = $this->getType( $type_slug );
		if ( isset( $type['taxonomy'] ) ) {
			return $type['taxonomy'];
		}
		return [];
	}

	/**
	 * Gets all meta data of the post.
	 *
	 * @param string $type_slug The slug of the type.
	 * @return array<string, mixed> All meta data of the post.
	 */
	public function getMetaAll( string $type_slug ): array {
		$type = $this->getType( $type_slug );
		if ( ! isset( $type['meta'] ) ) return [];
		return $type['meta'];
	}


	// -------------------------------------------------------------------------


	/**
	 * Loads the data of the post.
	 *
	 * @return array<string, mixed> The data of the post.
	 */
	private function _loadData(): array {
		if ( $this->_data ) return $this->_data;

		$path = $this->_dir . 'type.json';
		if ( ! is_readable( $path ) ) {
			return $this->_data = $this->_processData( [
				[ 'slug' => 'post', 'label' => 'Post' ]
			] );
		}

		$json = file_get_contents( $path );
		if ( $json === false ) {
			Logger::error( __METHOD__, 'Cannot read the type definition', $path );
			exit;
		}
		$data = json_decode( $json, true );
		if ( $data === null ) {
			Logger::error( __METHOD__, 'The type definition is invalid', $path );
			exit;
		}
		return $this->_data = $this->_processData( $data );;
	}

	/**
	 * Processes the data of the post.
	 *
	 * @param array<string, mixed>[] $data The data to process.
	 * @return array<string, mixed> The processed data.
	 */
	private function _processData( array $data ): array {
		$ret = [];

		foreach ( $data as $d ) {
			if ( ! is_string( $d['slug'] ?? null ) ) continue;
			\nt\normalize_label( $d, $this->_lang );
			if ( isset( $d['meta'] ) ) {
				$d['meta'] = $this->_normalizeMetaDefinitions( $d['meta'] );
			}
			$ret[ $d['slug'] ] = $d;
		}
		return $ret;
	}

	/**
	 * Normalizes the meta definitions of the post.
	 *
	 * @param array<string, mixed> $ms The meta definitions to normalize.
	 * @return array<string, mixed>[] The normalized meta definitions.
	 */
	private function _normalizeMetaDefinitions( array $ms ): array {
		$ret = [];

		foreach ( $ms as $m ) {
			if ( empty( $m['type'] ) ) {
				continue;
			}
			// Replace hyphens with underscores.
			$m['type'] = str_replace( '-', '_', $m['type'] );

			if ( 'group' !== $m['type'] && empty( $m['key'] ) ) {
				continue;
			}
			\nt\normalize_label( $m, $this->_lang );
			if ( 'group' === $m['type'] && isset( $m['items'] ) && is_array( $m['items'] ) ) {
				$m['items'] = $this->_normalizeMetaDefinitions( $m['items'] );
			}
			$ret[] = $m;
		}
		return $ret;
	}

}
