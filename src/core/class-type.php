<?php
namespace nt;
/**
 *
 * Type
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @version 2020-08-12
 *
 */


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
		return ['post/'];
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
			Logger::output( 'error', "(Type::_loadData file_get_contents) [$path]" );
			die;
		}
		$data = json_decode( $json, true );
		if ( $data === null ) {
			Logger::output( 'error', "(Type::_loadData json_decode) [$path]" );
			die;
		}
		return $this->_data = $this->_processData( $data );;
	}

	private function _processData( array $data ): array {
		$ret = [];

		foreach ( $data as $d ) {
			normalize_label( $d, $this->_lang );
			if ( isset( $d['meta'] ) ) {
				$ms = [];
				foreach ( $d['meta'] as $m ) {
					if ( empty( $m['type'] || ( empty( $m['key'] ) && $m['type'] !== 'gruop' ) ) ) continue;
					normalize_label( $m, $this->_lang );
					$ms[] = $m;
				}
				$d['meta'] = $ms;
			}
			$ret[ $d['slug'] ] = $d;
		}
		return $ret;
	}

}
