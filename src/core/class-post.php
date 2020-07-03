<?php
namespace nt;
/**
 *
 * Post
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @version 2020-07-03
 *
 */


require_once(__DIR__ . '/lib/simple_html_dom.php');
require_once(__DIR__ . '/class-indexer.php');
require_once(__DIR__ . '/class-logger.php');


class Post {

	const MODE_DIR  = 0755;
	const MODE_FILE = 0640;

	const META_FILE_NAME = 'meta.json';
	const CONT_FILE_NAME = 'content.html';
	const WORD_FILE_NAME = 'word.txt';
	const MEDIA_DIR_NAME = 'media';

	const STATUS_PUBLISHED = 'published';
	const STATUS_RESERVED  = 'reserved';
	const STATUS_DRAFT     = 'draft';

	static function compareDate( $a, $b ) {
		$da = $a->_date;
		$db = $b->_date;
		if ( $da === $db ) return 0;
		return ( $da > $db ) ? -1 : 1;
	}

	static function compareIndexScore( $a, $b ) {
		$da = $a->_indexScore;
		$db = $b->_indexScore;
		if ( $da === $db ) return 0;
		return ( $da > $db ) ? -1 : 1;
	}

	static function parseDate( $date ) {
		return preg_replace( '/(\d{4})(\d{2})(\d{2})/', '$1-$2-$3', $date );
	}

	static function numberifyDate( $date ) {
		return preg_replace( '/(\d{4})-(\d{2})-(\d{2})/', '$1$2$3', $date );
	}

	static function parseDateTime( $dateTime ) {
		return preg_replace( '/(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})/', '$1-$2-$3 $4:$5:$6', $dateTime );
	}

	static function numberifyDateTime( $dateTime ) {
		return preg_replace( '/(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/', '$1$2$3$4$5$6', $dateTime );
	}


	// ------------------------------------------------------------------------


	private $_postPath = '';
	private $_id;

	function __construct( $urlPost, $id ) {
		$this->_urlPost = $urlPost;
		$this->_id = $id;
	}

	function getId() {
		return $this->_id;
	}

	function setId( $id ) {
		$this->_id = $id;
	}

	function getType() {
		return $this->_type;
	}

	function setType( $type ) {
		$this->_type = $type;
	}

	function assign( $vals, $urlPrivate ) {
		$type = $this->getType();
		$this->setTitle( $vals['post_title'] );
		$this->setState( $vals['post_status'] );

		$mod  = date( 'YmdHis' );
		$date = empty( $vals['post_date'] ) ? $mod : Post::numberifyDateTime( $vals['post_date'] );
		$this->setDate( $date );
		$this->setModified( $mod );

		global $nt_store;
		$taxes = $nt_store->type()->getTaxonomySlugAll( $type );
		foreach ( $taxes as $tax ) {
			if ( ! isset( $vals["taxonomy:$tax"] ) ) continue;
			$ts = is_array( $vals["taxonomy:$tax"] ) ? $vals["taxonomy:$tax"] : [ $vals["taxonomy:$tax"] ];
			$this->setTermSlugs( $tax, $ts );
		}

		$ms = $nt_store->type()->getMetaAll( $type );
		foreach ( $ms as $m ) {
			$key = isset( $m['key'] ) ? $m['key'] : '';
			$type = isset( $m['type'] ) ? $m['type'] : '';
			if ( empty( $key ) || empty( $type ) ) continue;
			if ( ! isset( $vals["meta:$key"] ) ) continue;
			$vals["meta:$key"] = array_map( function ( $e ) { return self::numberifyDate( $e ); }, $vals["meta:$key"] );
			$this->setMetaValue( $key, $vals["meta:$key"] );
		}

		$this->setContent( $vals['post_content'], $urlPrivate );
	}

	function load( $storePath, $meta = false ) {
		$this->_postPath = $storePath . $this->_id . '/';
		$ret = $this->_readMeta( $this->_postPath, $meta );
		return $ret;
	}

	function save( $storePath ) {
		if ( ! file_exists( $storePath . $this->_id ) ) {
			mkdir( $storePath . $this->_id, self::MODE_DIR );
		}
		$this->setModified( 'now' );
		$this->_postPath = $storePath . $this->_id . '/';
		$this->_writeMeta( $this->_postPath );
		if ( $this->_content === null ) $this->_readContent();
		$this->_writeContent();
		$this->_updateSearchIndex();
	}

	private function _updateSearchIndex() {
		$text = $this->_title . strip_tags( $this->_content );
		$path = $this->_postPath . self::WORD_FILE_NAME;
		return Indexer::updateSearchIndex( $text, $path, self::MODE_FILE );
	}


	// Meta Data --------------------------------------------------------------


	private $_type     = '';
	private $_title    = '';
	private $_status   = self::STATUS_PUBLISHED;
	private $_date     = '';
	private $_modified = '';
	private $_taxonomy = [];
	private $_meta     = [];

	private function _readMeta( $postDir, $preloadedMeta = false ) {
		if ( $preloadedMeta ) {
			$metaAssoc = $preloadedMeta;
			if ( isset( $metaAssoc['_index_score'] ) ) $this->_indexScore = $metaAssoc['_index_score'];
		} else {
			$metaPath = $postDir . self::META_FILE_NAME;
			$metaStr = @file_get_contents( $metaPath );
			if ( $metaStr === false ) {
				Logger::output( 'Error (Post::_readMeta file_get_contents) [' . $metaPath . ']' );
				return false;
			}
			$metaAssoc = json_decode( $metaStr, true );
		}

		$metaAssoc += [ 'type' => 'post', 'title' => '', 'taxonomy' => [], 'meta' => [], 'status' => self::STATUS_PUBLISHED ];

		$this->_type     = $metaAssoc['type'];
		$this->_title    = $metaAssoc['title'];
		$this->_status   = $metaAssoc['status'];
		$this->_date     = $metaAssoc['date'];
		$this->_modified = $metaAssoc['modified'];
		$this->_taxonomy = $metaAssoc['taxonomy'];
		$this->_meta     = $metaAssoc['meta'];

		if ( $this->_status !== self::STATUS_DRAFT ) {
			$newState = $this->canPublished() ? self::STATUS_PUBLISHED : self::STATUS_RESERVED;
			if ( $newState !== $this->_status ) {
				$this->_status = $newState;
				$this->_writeMeta( $postDir );
			}
		}
		return true;
	}

	private function _writeMeta( $postDir ) {
		$metaAssoc = [];

		$metaAssoc['type']     = $this->_type;
		$metaAssoc['title']    = $this->_title;
		$metaAssoc['status']   = $this->_status;
		$metaAssoc['date']     = $this->_date;
		$metaAssoc['modified'] = $this->_modified;
		$metaAssoc['taxonomy'] = $this->_taxonomy;
		$metaAssoc['meta']     = $this->_meta;

		$metaStr = json_encode( $metaAssoc, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );

		$metaPath = $postDir . self::META_FILE_NAME;
		$suc = file_put_contents( $metaPath, $metaStr, LOCK_EX );
		if ( $suc === false ) {
			Logger::output( 'Error (Post::_writeMeta file_put_contents) [' . $metaPath . ']' );
			return false;
		}
		chmod( $metaPath, self::MODE_FILE );
		return true;
	}

	function getTitle() {
		return $this->_title;
	}

	// ----

	function hasTerm( string $tax_slug, string $term_slug ) {
		return in_array( $term_slug, $this->getTermSlugs( $tax_slug ), true );
	}

	function getTermSlugs( string $tax_slug ) {
		return isset( $this->_taxonomy[ $tax_slug ] ) ? $this->_taxonomy[ $tax_slug ] : [];
	}

	function getTaxonomyToTermSlugs() {
		return $this->_taxonomy;
	}

	function setTermSlugs( string $tax_slug, array $term_slugs ) {
		$this->_taxonomy[ $tax_slug ] = array_values( array_unique( $term_slugs ) );
	}

	// ----

	function getMeta() {
		return $this->_meta;
	}

	function setMetaValue( $key, $val ) {
		$this->_meta[ $key ] = $val;
	}

	// ----

	function getDate() {
		return Post::parseDateTime( $this->_date );
	}

	function getModified() {
		return Post::parseDateTime( $this->_modified );
	}

	function getDateRaw() {
		return $this->_date;
	}

	function getModifiedRaw() {
		return $this->_modified;
	}

	function getStatus() {
		return $this->_status;
	}

	function isPublished() {
		return $this->_status === self::STATUS_PUBLISHED;
	}

	function isReserved() {
		return $this->_status === self::STATUS_RESERVED;
	}

	function isDraft() {
		return $this->_status === self::STATUS_DRAFT;
	}

	function canPublished() {
		return intval( substr( $this->_date, 0, 8 ) ) < intval( date( 'Ymd' ) );
	}

	function setTitle( $val ) {
		$this->_title = $val;
	}

	function setDate( $val ) {
		if ( $val === 'now' ) $val = date( 'YmdHis' );
		$this->_date = $val;
	}

	function setModified( $val ) {
		if ( $val === 'now' ) $val = date( 'YmdHis' );
		$this->_modified = $val;
	}

	function setState( $val ) {
		if ( ! in_array( $val, [ self::STATUS_PUBLISHED, self::STATUS_RESERVED, self::STATUS_DRAFT ], true ) ) return;
		$this->_status = $val;
	}


	// Custom Meta Data -------------------------------------------------------


	const EVENT_STATUS_SCHEDULED = 'scheduled';
	const EVENT_STATUS_HELD      = 'held';
	const EVENT_STATUS_FINISHED  = 'finished';


	// Content ----------------------------------------------------------------


	private $_content = null;

	private function _readContent() {
		$contPath = $this->_postPath . self::CONT_FILE_NAME;
		$contStr = @file_get_contents( $contPath );
		if ( $contPath === false ) {
			Logger::output( 'Error (Post::_readContent file_get_contents) [' . $contPath . ']' );
			return false;
		}
		$this->_content = $contStr;
		return true;
	}

	private function _writeContent() {
		$contPath = $this->_postPath . self::CONT_FILE_NAME;
		$suc = file_put_contents( $contPath, $this->_content, LOCK_EX );
		if ( $suc === false ) {
			Logger::output( 'Error (Post::_writeContent file_put_contents) [' . $contPath . ']' );
			return false;
		}
		chmod( $contPath, self::MODE_FILE );
		return true;
	}

	function getContent() {
		if ( $this->_content === null ) {
			$res = $this->_readContent();
			if ( $res === false ) {
				$this->_content = '';
			}
		}
		if ( empty( $this->_content ) ) return '';

		$dom = str_get_html( $this->_content );
		foreach ( $dom->find( 'img' ) as &$elm ) {
			$elm->src = $this->convertToActualUrl( $elm->src );
		}
		foreach ( $dom->find( 'a' ) as &$elm ) {
			$elm->href = $this->convertToActualUrl( $elm->href );
		}
		$content = $dom->save();
		$dom->clear();
		unset( $dom );
		return $content;
	}

	function setContent( $val, $urlPrivate ) {
		if ( empty( $val ) ) {
			$this->_content = '';
			return;
		}
		$dom = str_get_html($val);
		foreach ( $dom->find( 'img' ) as &$elm ) {
			$elm->src = $this->convertToPortableUrl( $elm->src, $urlPrivate );
		}
		foreach ( $dom->find( 'a' ) as &$elm ) {
			$elm->href = $this->convertToPortableUrl( $elm->href, $urlPrivate );
		}
		$this->_content = $dom->save();
		$dom->clear();
		unset( $dom );
	}

	private function convertToActualUrl( $url ) {
		$sp = strpos( $url, '/' );
		if ( $sp === 0 ) {
			$sub = substr( $url, 1 );
			return $this->_urlPost . $sub;
		} else {
			if ( strpos( $url, self::MEDIA_DIR_NAME . '/' ) === 0 ) {
				return $this->_urlPost . $this->_id . '/' . $url;
			}
		}
		return $url;
	}

	private function convertToPortableUrl( $url, $urlPrivate ) {
		$url = resolve_url( $url, $urlPrivate );
		if ( strpos( $url, $this->_urlPost ) === 0 ) {
			$url = substr( $url, strlen( $this->_urlPost ) - 1 );
			$pu = '/' . $this->_id . '/' . self::MEDIA_DIR_NAME . '/';
			if ( strpos( $url, $pu ) === 0 ) {
				$url = substr( $url, strlen( $pu ) - strlen( self::MEDIA_DIR_NAME . '/' ) );
			}
		}
		return $url;
	}

	function getExcerpt( $len ) {
		$str = strip_tags( $this->getContent() );
		$str = mb_substr( $str, 0, $len );
		if ( $str < mb_strlen( $str ) ) $str .= '...';
		return $str;
	}

}
