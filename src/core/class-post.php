<?php
namespace nt;
/**
 *
 * Post
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @version 2020-07-26
 *
 */


require_once( __DIR__ . '/lib/simple_html_dom.php' );
require_once( __DIR__ . '/class-indexer.php' );
require_once( __DIR__ . '/class-logger.php' );


class Post {

	const MODE_DIR  = 0755;
	const MODE_FILE = 0640;

	const INFO_FILE_NAME = 'info.json';
	const CONT_FILE_NAME = 'content.html';
	const BIGM_FILE_NAME = 'bigm.txt';
	const MEDIA_DIR_NAME = 'media';

	const STATUS_PUBLISH = 'publish';
	const STATUS_FUTURE  = 'future';
	const STATUS_DRAFT   = 'draft';

	const DATE_STATUS_UPCOMING = 'upcoming';
	const DATE_STATUS_ONGOING  = 'ongoing';
	const DATE_STATUS_FINISHED = 'finished';

	static function compareDate( Post $a, Post $b ): bool {
		return $b->_date <=> $a->_date;
	}

	static function compareIndexScore( Post $a, Post $b ): bool {
		return $b->_indexScore <=> $a->_indexScore;
	}

	static function parseDate( string $date ): string {
		return preg_replace( '/(\d{4})(\d{2})(\d{2})/', '$1-$2-$3', $date );
	}

	static function packDate( string $date ): string {
		return preg_replace( '/(\d{4})-(\d{2})-(\d{2})/', '$1$2$3', $date );
	}

	static function parseDateTime( string $dateTime ): string {
		return preg_replace( '/(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})/', '$1-$2-$3 $4:$5:$6', $dateTime );
	}

	static function packDateTime( string $dateTime ): string {
		return preg_replace( '/(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/', '$1$2$3$4$5$6', $dateTime );
	}


	// ------------------------------------------------------------------------


	private $_id;
	private $_subPath;

	function __construct( string $id, string $subPath = '' ) {
		$this->_id = $id;
		$this->_subPath = $subPath;
	}

	function getId(): string {
		return $this->_id;
	}

	function setId( string $id ) {
		$this->_id = $id;
	}

	function load( array $info = null ): bool {
		global $nt_store;
		$path = $nt_store->getPostDir( $this->_id, $this->_subPath );

		$ret = $this->_readInfo( $path, $info );
		if ( $ret === true ) $this->_updateStatus( $path );
		return $ret;
	}

	function save() {
		global $nt_store;
		$path = $nt_store->getPostDir( $this->_id, $this->_subPath );

		if ( ! file_exists( $path ) ) {
			mkdir( $path, self::MODE_DIR );
		}
		$this->_writeInfo( $path, true );
		$this->_writeContent( $path );
		$this->_updateSearchIndex( $path );
	}

	private function _updateStatus( string $postDir ) {
		if ( $this->_status === self::STATUS_DRAFT ) return;
		$s = $this->canPublished() ? self::STATUS_PUBLISH : self::STATUS_FUTURE;
		if ( $s !== $this->_status ) {
			$this->_status = $s;
			$this->_writeInfo( $postDir );
		}
	}

	private function _updateSearchIndex( string $postDir ): bool {
		$text = strip_tags( $this->_title ) . ' ' . strip_tags( $this->_content );
		$path = $postDir . self::BIGM_FILE_NAME;
		return Indexer::updateSearchIndex( $text, $path, self::MODE_FILE );
	}

	// ----

	function assign( array $vals, string $urlPrivate ) {
		$this->setTitle( $vals['post_title'] );
		$this->setStatus( $vals['post_status'] );

		$date = empty( $vals['post_date'] ) ? 'now' : Post::packDateTime( $vals['post_date'] );
		$this->setDate( $date );
		$this->setModified( 'now' );

		$type = $this->getType();
		$this->_assignTaxonomy( $type, $vals );
		$this->_assignMeta( $type, $vals );

		$this->setContent( $vals['post_content'], $urlPrivate );
	}

	private function _assignTaxonomy( string $type, array $vals ) {
		global $nt_store;
		$taxes = $nt_store->type()->getTaxonomySlugAll( $type );

		foreach ( $taxes as $tax ) {
			if ( ! isset( $vals["taxonomy:$tax"] ) ) continue;
			$ts = is_array( $vals["taxonomy:$tax"] ) ? $vals["taxonomy:$tax"] : [ $vals["taxonomy:$tax"] ];
			$this->setTermSlugs( $tax, $ts );
		}
	}

	private function _assignMeta( string $type, array $vals ) {
		global $nt_store;
		$ms = $nt_store->type()->getMetaAll( $type );

		foreach ( $ms as $m ) {
			$key = $m['key'];
			if ( ! isset( $vals["meta:$key"] ) ) continue;
			if ( $m['type'] === 'date' ) {
				$vals["meta:$key"] = self::packDate( $vals["meta:$key"] );
			}
			if ( $m['type'] === 'date-range' ) {
				$vals["meta:$key"] = array_map( function ( $e ) { return self::packDate( $e ); }, $vals["meta:$key"] );
			}
			$this->setMetaValue( $key, $vals["meta:$key"] );
		}
	}


	// Info Data --------------------------------------------------------------


	private $_type     = '';
	private $_title    = '';
	private $_status   = self::STATUS_PUBLISH;
	private $_date     = '';
	private $_modified = '';
	private $_taxonomy = [];
	private $_meta     = [];

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
		$json = @file_get_contents( $path );
		if ( $json === false ) {
			Logger::output( "Error (Post::_readInfoFile file_get_contents) [$path]" );
			return null;
		}
		return json_decode( $json, true );
	}

	private function _writeInfoFile( string $postDir, array $info ): bool {
		$path = $postDir . self::INFO_FILE_NAME;
		$json = json_encode( $info, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		$res = file_put_contents( $path, $json, LOCK_EX );
		if ( $res === false ) {
			Logger::output( "Error (Post::_writeInfoFile file_put_contents) [$path]" );
			return false;
		}
		chmod( $path, self::MODE_FILE );
		return true;
	}

	// ----

	function getType(): string {
		return $this->_type;
	}

	function setType( string $val ) {
		$this->_type = $val;
	}

	function getTitle(): string {
		return $this->_title;
	}

	function setTitle( string $val ) {
		$this->_title = $val;
	}

	function getStatus(): string {
		return $this->_status;
	}

	function isStatus( string $val ): bool {
		return $this->_status === $val;
	}

	function canPublished(): bool {
		return intval( $this->_date ) < intval( date( 'YmdHis' ) );
	}

	function setStatus( string $val ) {
		if ( ! in_array( $val, [ self::STATUS_PUBLISH, self::STATUS_FUTURE, self::STATUS_DRAFT ], true ) ) return;
		$this->_status = $val;
	}

	function getDate(): string {
		return Post::parseDateTime( $this->_date );
	}

	function getModified(): string {
		return Post::parseDateTime( $this->_modified );
	}

	function getDateRaw(): string {
		return $this->_date;
	}

	function getModifiedRaw(): string {
		return $this->_modified;
	}

	function setDate( string $val = 'now' ) {
		if ( $val === 'now' ) $val = date( 'YmdHis' );
		$this->_date = $val;
	}

	function setModified( string $val = 'now' ) {
		if ( $val === 'now' ) $val = date( 'YmdHis' );
		$this->_modified = $val;
	}

	// ----

	function hasTerm( string $tax_slug, string $term_slug ): bool {
		return in_array( $term_slug, $this->getTermSlugs( $tax_slug ), true );
	}

	function getTermSlugs( string $tax_slug ): array {
		return isset( $this->_taxonomy[ $tax_slug ] ) ? $this->_taxonomy[ $tax_slug ] : [];
	}

	function getTaxonomyToTermSlugs(): array {
		return $this->_taxonomy;
	}

	function setTermSlugs( string $tax_slug, array $term_slugs ) {
		$this->_taxonomy[ $tax_slug ] = array_values( array_unique( $term_slugs ) );
	}

	// ----

	function getMeta(): array {
		return $this->_meta;
	}

	function getMetaValue( string $key ) {
		if ( isset( $this->_meta[ $key ] ) ) return $this->_meta[ $key ];
		return null;
	}

	function setMetaValue( string $key, $val ) {
		$this->_meta[ $key ] = $val;
	}


	// Content ----------------------------------------------------------------


	private $_content = null;

	private function _readContent( string $postDir ): bool {
		$path = $postDir . self::CONT_FILE_NAME;
		$cont = @file_get_contents( $path );
		if ( $cont === false ) {
			Logger::output( "Error (Post::_readContent file_get_contents) [$path]" );
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
			Logger::output( "Error (Post::_writeContent file_put_contents) [$path]" );
			return false;
		}
		chmod( $path, self::MODE_FILE );
		return true;
	}

	private function _convertToActualUrl( string $postUrl, string $url ): string {
		$sp = strpos( $url, '/' );
		if ( $sp === 0 ) {
			$sub = substr( $url, 1 );
			return $postUrl . $sub;
		} else {
			if ( strpos( $url, self::MEDIA_DIR_NAME . '/' ) === 0 ) {
				return $postUrl . $this->_id . '/' . $url;
			}
		}
		return $url;
	}

	private function _convertToPortableUrl( string $postUrl, string $url, string $urlPrivate ): string {
		$url = resolve_url( $url, $urlPrivate );
		if ( strpos( $url, $postUrl ) === 0 ) {
			$url = substr( $url, strlen( $postUrl ) - 1 );
			$pu = '/' . $this->_id . '/' . self::MEDIA_DIR_NAME . '/';
			if ( strpos( $url, $pu ) === 0 ) {
				$url = substr( $url, strlen( $pu ) - strlen( self::MEDIA_DIR_NAME . '/' ) );
			}
		}
		return $url;
	}

	function getContent(): string {
		global $nt_store;
		$dir = $nt_store->getPostDir( $this->_id, $this->_subPath );
		$url = $nt_store->getPostUrl( $this->_id, $this->_subPath );

		if ( $this->_content === null ) {
			$res = $this->_readContent( $dir );
			if ( $res === false ) $this->_content = '';
		}
		if ( empty( $this->_content ) ) return '';

		$dom = str_get_html( $this->_content );
		foreach ( $dom->find( 'img' ) as &$elm ) {
			$elm->src = $this->_convertToActualUrl( $url, $elm->src );
		}
		foreach ( $dom->find( 'a' ) as &$elm ) {
			$elm->href = $this->_convertToActualUrl( $url, $elm->href );
		}
		$content = $dom->save();
		$dom->clear();
		unset( $dom );
		return $content;
	}

	function setContent( string $val, string $urlPrivate ) {
		if ( empty( $val ) ) {
			$this->_content = '';
			return;
		}
		global $nt_store;
		$url = $nt_store->getPostUrl( $this->_id, $this->_subPath );

		$dom = str_get_html($val);
		foreach ( $dom->find( 'img' ) as &$elm ) {
			$elm->src = $this->_convertToPortableUrl( $url, $elm->src, $urlPrivate );
		}
		foreach ( $dom->find( 'a' ) as &$elm ) {
			$elm->href = $this->_convertToPortableUrl( $url, $elm->href, $urlPrivate );
		}
		$this->_content = $dom->save();
		$dom->clear();
		unset( $dom );
	}

	function getExcerpt( int $len ): string {
		$str = strip_tags( $this->getContent() );
		$exc = mb_substr( $str, 0, $len );
		if ( mb_strlen( $exc ) < mb_strlen( $str ) ) $exc .= '...';
		return $exc;
	}

}
