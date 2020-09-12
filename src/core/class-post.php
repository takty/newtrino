<?php
namespace nt;
/**
 *
 * Post
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @version 2020-09-12
 *
 */


require_once( __DIR__ . '/class-indexer.php' );
require_once( __DIR__ . '/class-logger.php' );
require_once( __DIR__ . '/util/date-format.php' );
require_once( __DIR__ . '/lib/simple_html_dom.php' );


class Post {

	public const INFO_FILE_NAME = 'info.json';
	public const CONT_FILE_NAME = 'content.html';
	public const BIGM_FILE_NAME = 'bigm.txt';
	public const MEDIA_DIR_NAME = 'media';

	public const STATUS_PUBLISH = 'publish';
	public const STATUS_FUTURE  = 'future';
	public const STATUS_DRAFT   = 'draft';

	public const DATE_STATUS_UPCOMING = 'upcoming';
	public const DATE_STATUS_ONGOING  = 'ongoing';
	public const DATE_STATUS_FINISHED = 'finished';

	static public function compareDate( Post $a, Post $b ): int {
		return $b->_date <=> $a->_date;
	}

	static public function compareIndexScore( Post $a, Post $b ): int {
		return $b->_indexScore <=> $a->_indexScore;
	}


	// ------------------------------------------------------------------------


	private $_id;
	private $_subPath;

	private $_type     = '';
	private $_title    = '';
	private $_status   = self::STATUS_PUBLISH;
	private $_date     = '';
	private $_modified = '';
	private $_taxonomy = [];
	private $_meta     = [];

	private $_content = null;

	public function __construct( string $id, string $subPath = '' ) {
		$this->_id = $id;
		$this->_subPath = $subPath;
	}

	public function getId(): string {
		return $this->_id;
	}

	public function load( array $info = null ): bool {
		global $nt_store;
		$path = $nt_store->getPostDir( $this->_id, $this->_subPath );

		$ret = $this->_readInfo( $path, $info );
		if ( $ret === true ) $this->_updateStatus( $path );
		return $ret;
	}

	public function save( string $id = null ): void {
		if ( ! defined( 'NT_ADMIN' ) ) die;
		if ( ! empty( $id ) ) $this->_id = $id;

		global $nt_store;
		$path = $nt_store->getPostDir( $this->_id, $this->_subPath );
		if ( ! is_dir( $path ) ) mkdir( $path, NT_MODE_DIR );
		if ( is_dir( $path ) ) chmod( $path, NT_MODE_DIR );

		$this->_writeInfo( $path, true );
		$this->_writeContent( $path );
		$this->_writeSearchIndex( $path );
	}

	private function _updateStatus( string $postDir ): void {
		if ( $this->_status === self::STATUS_DRAFT ) return;
		$s = $this->canPublished() ? self::STATUS_PUBLISH : self::STATUS_FUTURE;
		if ( $s !== $this->_status ) {
			$this->_status = $s;
			$this->_writeInfo( $postDir );
		}
	}

	private function _writeSearchIndex( string $postDir ): bool {
		$text = strip_tags( $this->_title ) . ' ' . strip_tags( $this->_content );
		$path = $postDir . self::BIGM_FILE_NAME;

		$idx = Indexer::createSearchIndex( $text );
		$ret = file_put_contents( $path, $idx, LOCK_EX );
		if ( $ret === false ) {
			Logger::output( 'error', "(Post::_writeSearchIndex file_put_contents) [$path]" );
			return false;
		}
		chmod( $path, NT_MODE_FILE );
		return true;
	}


	// -------------------------------------------------------------------------


	public function assign( array $vals ): void {
		if ( ! defined( 'NT_ADMIN' ) ) die;
		$this->setTitle( $vals['post_title'] );
		$this->setStatus( $vals['post_status'] );

		$date = empty( $vals['post_date'] ) ? 'now' : packDateTime( $vals['post_date'] );
		$this->setDate( $date );
		$this->setModified( 'now' );

		$type = $this->getType();
		$this->_assignTaxonomy( $type, $vals );
		$this->_assignMeta( $type, $vals );

		$this->_setContent( $vals['post_content'] );
	}

	private function _assignTaxonomy( string $type, array $vals ): void {
		global $nt_store;
		$taxes = $nt_store->type()->getTaxonomySlugAll( $type );

		foreach ( $taxes as $tax ) {
			if ( ! isset( $vals["taxonomy:$tax"] ) ) continue;
			$ts = is_array( $vals["taxonomy:$tax"] ) ? $vals["taxonomy:$tax"] : [ $vals["taxonomy:$tax"] ];
			$this->setTermSlugs( $tax, $ts );
		}
	}

	private function _assignMeta( string $type, array $vals ): void {
		global $nt_store;
		$ms = $nt_store->type()->getMetaAll( $type );
		$this->_assignMetaInternal( $ms, $vals );
	}

	private function _assignMetaInternal( array $ms, array $vals ): void {
		foreach ( $ms as $m ) {
			if ( $m['type'] === 'group' ) {
				$this->_assignMetaInternal( $m['items'], $vals );
				continue;
			}
			$key = $m['key'];
			if ( empty( $vals["meta:$key"] ) ) {
				$this->setMetaValue( $key, null );
				continue;
			}
			if ( $m['type'] === 'date' ) {
				$vals["meta:$key"] = packDate( $vals["meta:$key"] );
			}
			if ( $m['type'] === 'date-range' ) {
				$json = $vals["meta:$key"];
				$d = json_decode( $json, true );
				if ( $d !== null ) {
					$d['from'] = packDate( $d['from'] );
					$d['to']   = packDate( $d['to']   );
					$vals["meta:$key"] = $d;
				}
			}
			if ( $m['type'] === 'media' || $m['type'] === 'media-image' ) {
				$json = $vals["meta:$key"];
				$d = json_decode( $json, true );
				if ( $d !== null ) $vals["meta:$key"] = $d;
			}
			$this->setMetaValue( $key, $vals["meta:$key"] );
		}
	}


	// Info Data --------------------------------------------------------------


	private function _readInfo( string $postDir, ?array $preloadedInfo = null ): bool {
		if ( $preloadedInfo ) {
			$info = $preloadedInfo;
		} else {
			$info = $this->_readInfoFile( $postDir );
			if ( $info === null ) return false;
		}
		$info += [
			'type'     => 'post',
			'title'    => '',
			'status'   => self::STATUS_PUBLISH,
			'taxonomy' => [],
			'meta'     => [],
		];
		$this->_type     = $info['type'];
		$this->_title    = $info['title'];
		$this->_status   = $info['status'];
		$this->_date     = $info['date'];
		$this->_modified = $info['modified'];
		$this->_taxonomy = $info['taxonomy'];
		$this->_meta     = $info['meta'];
		if ( isset( $info['_index_score'] ) ) $this->_indexScore = $info['_index_score'];
		return true;
	}

	private function _writeInfo( string $postDir, bool $updateModified = false ): bool {
		if ( $updateModified ) $this->setModified();
		$info = [
			'type'     => $this->_type,
			'title'    => $this->_title,
			'status'   => $this->_status,
			'date'     => $this->_date,
			'modified' => $this->_modified,
			'taxonomy' => $this->_taxonomy,
			'meta'     => $this->_meta,
		];
		return $this->_writeInfoFile( $postDir, $info );
	}

	private function _readInfoFile( string $postDir ): ?array {
		$path = $postDir . self::INFO_FILE_NAME;
		$json = file_get_contents( $path );
		if ( $json === false ) {
			Logger::output( 'error', "(Post::_readInfoFile file_get_contents) [$path]" );
			return null;
		}
		return json_decode( $json, true );
	}

	private function _writeInfoFile( string $postDir, array $info ): bool {
		$path = $postDir . self::INFO_FILE_NAME;
		$json = json_encode( $info, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		$res = file_put_contents( $path, $json, LOCK_EX );
		if ( $res === false ) {
			Logger::output( 'error', "(Post::_writeInfoFile file_put_contents) [$path]" );
			return false;
		}
		chmod( $path, NT_MODE_FILE );
		return true;
	}

	// ----

	public function getType(): string {
		return $this->_type;
	}

	public function setType( string $val ): void {
		$this->_type = $val;
	}

	public function getTitle(): string {
		return $this->_title;
	}

	public function setTitle( string $val ): void {
		$this->_title = $val;
	}

	public function getStatus(): string {
		return $this->_status;
	}

	public function isStatus( string $val ): bool {
		return $this->_status === $val;
	}

	public function canPublished(): bool {
		return intval( $this->_date ) < intval( date( 'YmdHis' ) );
	}

	public function setStatus( string $val ): void {
		if ( ! in_array( $val, [ self::STATUS_PUBLISH, self::STATUS_FUTURE, self::STATUS_DRAFT ], true ) ) return;
		$this->_status = $val;
	}

	public function getDate(): string {
		return parseDateTime( $this->_date );
	}

	public function getModified(): string {
		return parseDateTime( $this->_modified );
	}

	public function getDateRaw(): string {
		return $this->_date;
	}

	public function getModifiedRaw(): string {
		return $this->_modified;
	}

	public function setDate( string $val = 'now' ): void {
		if ( $val === 'now' ) $val = date( 'YmdHis' );
		$this->_date = $val;
	}

	public function setModified( string $val = 'now' ): void {
		if ( $val === 'now' ) $val = date( 'YmdHis' );
		$this->_modified = $val;
	}

	// ----

	public function hasTerm( string $tax_slug, string $term_slug ): bool {
		return in_array( $term_slug, $this->getTermSlugs( $tax_slug ), true );
	}

	public function getTermSlugs( string $tax_slug ): array {
		return isset( $this->_taxonomy[ $tax_slug ] ) ? $this->_taxonomy[ $tax_slug ] : [];
	}

	public function getTaxonomyToTermSlugs(): array {
		return $this->_taxonomy;
	}

	public function setTermSlugs( string $tax_slug, array $term_slugs ): void {
		$this->_taxonomy[ $tax_slug ] = array_values( array_unique( $term_slugs ) );
	}

	// ----

	public function getMeta(): array {
		return $this->_meta;
	}

	public function getMetaValue( string $key ) {
		if ( isset( $this->_meta[ $key ] ) ) return $this->_meta[ $key ];
		return null;
	}

	public function setMetaValue( string $key, $val ): void {
		if ( $val === null ) {
			unset( $this->_meta[ $key ] );
		} else {
			$this->_meta[ $key ] = $val;
		}
	}


	// Content ----------------------------------------------------------------


	private function _readContent( string $postDir ): bool {
		$path = $postDir . self::CONT_FILE_NAME;
		if ( ! is_file( $path ) || ! is_readable( $path ) ) return false;

		$cont = file_get_contents( $path );
		if ( $cont === false ) {
			Logger::output( 'error', "(Post::_readContent file_get_contents) [$path]" );
			return false;
		}
		$this->_content = $cont;
		return true;
	}

	private function _writeContent( string $postDir ): bool {
		if ( $this->_content === null ) $this->_readContent( $postDir );

		$path = $postDir . self::CONT_FILE_NAME;
		$res = file_put_contents( $path, $this->_content, LOCK_EX );
		if ( $res === false ) {
			Logger::output( 'error', "(Post::_writeContent file_put_contents) [$path]" );
			return false;
		}
		chmod( $path, NT_MODE_FILE );
		return true;
	}

	static private function _convertToActualUrl( string $postUrl, string $url ): string {
		$sp = strpos( $url, '//' );
		if ( $sp === 0 ) {
			$sub = substr( $url, 2 );
			return NT_URL . $sub;
		}
		return $url;
	}

	static private function _convertToPortableUrl( string $postUrl, string $url ): string {
		$url = resolve_url( $url, NT_URL_ADMIN );
		if ( strpos( $url, NT_URL ) === 0 ) {
			$url = '/' . substr( $url, strlen( NT_URL ) - 1 );
			$url = str_replace( '//?_', '//?', $url );
		}
		return $url;
	}

	static private function _convertContent( string $val, callable $fn ): string {
		$dom = str_get_html( $val );
		foreach ( $dom->find( 'img' ) as &$elm ) {
			$elm->src = call_user_func( $fn, $elm->src );
			if ( $elm->srcset ) {
				$ss = explode( ',', $elm->srcset );
				$ss = array_map( 'trim', $ss );
				foreach ( $ss as &$s ) $s = call_user_func( $fn, $s );
				$elm->srcset = implode( ', ', $ss );
			}
		}
		foreach ( $dom->find( 'a' ) as &$elm ) {
			$elm->href = call_user_func( $fn, $elm->href );
		}
		$ret = $dom->save();
		$dom->clear();
		unset( $dom );
		return $ret;
	}

	private function _setContent( string $val ): void {
		if ( empty( $val ) ) {
			$this->_content = '';
			return;
		}
		global $nt_store;
		$url = $nt_store->getPostUrl( $this->_id, $this->_subPath );
		$c = self::_convertContent( $val, function ( $tar ) use ( $url ) {
			return self::_convertToPortableUrl( $url, $tar );
		} );
		$this->_content = $c;
	}

	public function getContent(): string {
		global $nt_store;
		if ( $this->_content === null ) {
			$dir = $nt_store->getPostDir( $this->_id, $this->_subPath );
			$res = $this->_readContent( $dir );
			if ( $res === false ) $this->_content = '';
		}
		if ( empty( $this->_content ) ) return '';

		$url = $nt_store->getPostUrl( $this->_id, $this->_subPath );
		$c = self::_convertContent( $this->_content, function ( $tar ) use ( $url ) {
			return self::_convertToActualUrl( $url, $tar );
		} );
		return $c;
	}

	public function getExcerpt( int $len ): string {
		$str = strip_tags( $this->getContent() );
		$exc = mb_substr( $str, 0, $len );
		if ( mb_strlen( $exc ) < mb_strlen( $str ) ) $exc .= '...';
		return $exc;
	}

}
