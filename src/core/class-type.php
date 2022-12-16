<?php
/**
 * Type
 *
 * @author Takuto Yanagida
 * @version 2021-09-11
 */

namespace nt;

require_once( __DIR__ . '/class-logger.php' );
require_once( __DIR__ . '/util/label.php' );

class Type {

	private $_dir  = '';
	private $_lang = '';
	private $_data = '';

	public function __construct( string $data_dir, array $args = [] ) {
		$this->_dir = $data_dir;

		$langKey = ( defined( 'NT_ADMIN' ) && ! defined( 'NT_ADMIN_PREVIEW' ) ) ? 'lang_admin' : 'lang';
		$args = array_merge( [ $langKey => 'en' ], $args );

		$this->_lang   = $args[ $langKey ];
		$this->_byType = $args['archive_by_type'];
	}


	// -------------------------------------------------------------------------


	public function getType( string $type_slug ): ?array {
		$types = $this->_loadData();
		if ( isset( $types[ $type_slug ] ) ) {
			return $types[ $type_slug ];
		}
		return null;
	}

	public function getTypeAll(): array {
		return $this->_loadData();
	}

	public function getTypeDirAll(): array {
		if ( $this->_byType ) {
			$ts = array_keys( $this->getTypeAll() );
			return array_map( function ( $e ) { return "$e/"; }, $ts );
		}
		return [ 'post/' ];
	}


	// -------------------------------------------------------------------------


	public function getTaxonomySlugAll( string $type_slug ): array {
		$type = $this->getType( $type_slug );
		if ( isset( $type['taxonomy'] ) ) {
			return $type['taxonomy'];
		}
		return [];
	}

	public function getMetaAll( string $type_slug ): array {
		$type = $this->getType( $type_slug );
		if ( ! isset( $type['meta'] ) ) return [];
		return $type['meta'];
	}


	// -------------------------------------------------------------------------


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

	private function _processData( array $data ): array {
		$ret = [];

		foreach ( $data as $d ) {
			\nt\normalize_label( $d, $this->_lang );
			if ( isset( $d['meta'] ) ) {
				$d['meta'] = $this->_normalizeMetaLabels( $d['meta'] );
			}
			$ret[ $d['slug'] ] = $d;
		}
		return $ret;
	}

	private function _normalizeMetaLabels( array $ms ): array {
		$ret = [];

		foreach ( $ms as $m ) {
			if ( empty( $m['type'] ) ) {
				continue;
			}
			if ( 'group' !== $m['type'] && empty( $m['key'] ) ) {
				continue;
			}
			\nt\normalize_label( $m, $this->_lang );
			if ( 'group' === $m['type'] && isset( $m['items'] ) && is_array( $m['items'] ) ) {
				$m['items'] = $this->_normalizeMetaLabels( $m['items'] );
			}
			$ret[] = $m;
		}
		return $ret;
	}

}
