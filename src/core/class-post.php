<?php
namespace nt;
/**
 *
 * Post
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @version 2020-06-26
 *
 */


require_once(__DIR__ . '/lib/simple_html_dom.php');
require_once(__DIR__ . '/class-indexer.php');
require_once(__DIR__ . '/class-logger.php');
require_once(__DIR__ . '/class-media.php');


class Post {

	const MODE_DIR = 0755;
	const MODE_FILE = 0640;

	const META_FILE_NAME = 'meta.json';
	const CONT_FILE_NAME = 'content.html';
	const WORD_FILE_NAME = 'word.txt';

	const STATUS_PUBLISHED = 'published';
	const STATUS_RESERVED  = 'reserved';
	const STATUS_DRAFT     = 'draft';

	static function compareDate($a, $b) {
		$da = $a->getDateTimeNumber();
		$db = $b->getDateTimeNumber();
		if ($da === $db) return 0;
		return ($da > $db) ? -1 : 1;
	}

	static function compareIndexScore($a, $b) {
		$da = $a->_indexScore;
		$db = $b->_indexScore;
		if ($da === $db) return 0;
		return ($da > $db) ? -1 : 1;
	}

	static function parseDateTime( $dateTime ) {
		return preg_replace( '/(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})/', '$1-$2-$3 $4:$5:$6', $dataTime );
	}

	static function numberifyDateTime( $dateTime ) {
		return preg_replace( '/(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/', '$1$2$3$4$5$6', $dataTime );
	}

	static function parseDate( $date ) {
		return preg_replace( '/(\d{4})(\d{2})(\d{2})/', '$1-$2-$3', $data );
	}

	static function numberifyDate( $date ) {
		return preg_replace( '/(\d{4})-(\d{2})-(\d{2})/', '$1$2$3', $dataTime );
	}

	static function parseTime( $time ) {
		return preg_replace( '/(\d{2})(\d{2})(\d{2})/', '$1:$2:$3', $time );
	}

	static function numberifyTime( $time ) {
		return preg_replace( '/(\d{2}):(\d{2}):(\d{2})/', '$1$2$3', $time );
	}

	// ------------------------------------------------------------------------

	private $_postPath = '';
	private $_id;

	function __construct($urlPost, $id, $urlPrivate = false ) {
		$this->_urlPost = $urlPost;
		$this->_id = $id;
		$this->_urlPrivate = $urlPrivate;  // Only In Private Area
	}

	function getId() {
		return $this->_id;
	}

	function setId($id) {
		$oldId = $this->_id;
		$this->_id = $id;
	}

	function assign( $vals ) {
		$modDate = date( 'Y-m-d H:i:s' );
		// $creDate = empty( $vals['post_created_date'] )   ? $modDate : $vals['post_created_date'];
		$pubDate = empty( $vals['post_published_date'] ) ? $modDate : $vals['post_published_date'];

		$this->setTitle($vals['post_title']);

		global $nt_store;
		$taxes = $nt_store->taxonomy()->getTaxonomyAll();
		foreach ( $taxes as $tax => $data ) {
			if ( ! isset( $vals["taxonomy:$tax"] ) ) continue;
			$ts = is_array( $vals["taxonomy:$tax"] ) ? $vals["taxonomy:$tax"] : [ $vals["taxonomy:$tax"] ];
			$this->setTermSlugs( '$tax', $ts );
		}
		// $this->setCreatedDate($creDate);
		$this->setModifiedDate($modDate);
		$this->setPublishedDate($pubDate);
		$this->setState($vals['post_status']);
		$this->setContent($vals['post_content']);
		$this->_assignCustom($vals);
	}

	function load( $storePath, $meta = false ) {
		$this->_postPath = $storePath . $this->_id . '/';
		$ret = $this->_readMeta( $this->_postPath, $meta );
		return $ret;
	}

	function save($storePath) {
		if (!file_exists($storePath . $this->_id)) {
			mkdir($storePath . $this->_id, self::MODE_DIR);
		}
		$this->setModifiedDate(date('Y-m-d H:i:s'));
		$this->_postPath = $storePath . $this->_id . '/';
		$this->_writeMeta($this->_postPath);
		if ($this->_content === null) $this->_readContent();
		$this->_writeContent();
		$this->_updateSearchIndex();
	}

	private function _updateSearchIndex() {
		$text = $this->_title . strip_tags($this->_content);
		$path = $this->_postPath . self::WORD_FILE_NAME;
		return Indexer::updateSearchIndex($text, $path, self::MODE_FILE);
	}

	// Meta Data --------------------------------------------------------------

	private $_title = '';
	private $_taxonomy = [];
	private $_datePublished = '';
	private $_dateCreated = '';
	private $_dateModified = '';
	private $_isPublished = false;
	private $_status = self::STATUS_PUBLISHED;

	private function _readMeta( $postDir, $preloadedMeta = false ) {
		if ( $preloadedMeta ) {
			$metaAssoc = $preloadedMeta;
			if ( isset( $metaAssoc['_index_score'] ) ) $this->_indexScore = $metaAssoc['_index_score'];
		} else {
			$metaPath = $postDir . self::META_FILE_NAME;
			$metaStr = @file_get_contents($metaPath);
			if ($metaStr === false) {
				Logger::output('Error (Post::_readMeta file_get_contents) [' . $metaPath . ']');
				return false;
			}
			$metaAssoc = json_decode($metaStr, true);
		}

		$metaAssoc += [ 'title' => '', 'taxonomy' => [], 'status' => self::STATUS_PUBLISHED ];

		$this->_title         = $metaAssoc['title'];
		$this->_status        = $metaAssoc['status'];
		$this->_taxonomy      = $metaAssoc['taxonomy'];
		// $this->_dateCreated   = $metaAssoc['created_date'];
		$this->_dateModified  = $metaAssoc['modified_date'];
		$this->_datePublished = $metaAssoc['published_date'];
		$this->_readCustomMeta($metaAssoc);

		if ($this->_status !== self::STATUS_DRAFT) {
			$newState = $this->canPublished() ? self::STATUS_PUBLISHED : self::STATUS_RESERVED;
			if ($newState !== $this->_status) {
				$this->_status = $newState;
				$this->_writeMeta($postDir);
			}
		}
		return true;
	}

	private function _writeMeta($postDir) {
		$metaAssoc = [];

		$metaAssoc['title']          = $this->_title;
		$metaAssoc['status']         = $this->_status;
		$metaAssoc['taxonomy']       = $this->_taxonomy;
		// $metaAssoc['created_date']   = $this->_dateCreated;
		$metaAssoc['modified_date']  = $this->_dateModified;
		$metaAssoc['published_date'] = $this->_datePublished;
		$this->_writeCustomMeta($metaAssoc);

		$metaStr = json_encode($metaAssoc, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

		$metaPath = $postDir . self::META_FILE_NAME;
		$suc = file_put_contents($metaPath, $metaStr, LOCK_EX);
		if ($suc === false) {
			Logger::output('Error (Post::_writeMeta file_put_contents) [' . $metaPath . ']');
			return false;
		}
		chmod($metaPath, self::MODE_FILE);
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

	function getPublishedDate() {
		$dt = explode(' ', $this->_datePublished);
		return $dt[0];
	}

	function getPublishedTime() {
		$dt = explode(' ', $this->_datePublished);
		return $dt[1];
	}

	function getPublishedDateTime() {
		return $this->_datePublished;
	}

	function getDateTimeNumber() {
		return str_replace(['-', '/', ':', ' '], '', $this->_datePublished);
	}

	function getModifiedDateTime() {
		return $this->_dateModified;
	}

	// function getCreatedDateTime() {
	// 	return $this->_dateCreated;
	// }

	function getState() {
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
		$now = date('YmdHis');
		$pd = str_pad($this->getDateTimeNumber(), 14, '0');
		return $pd < $now;
	}

	function setTitle($val) {
		$this->_title = $val;
	}


	function setPublishedDate($val) {
		if ($val === 'now') $val = date('Y-m-d H:i:s');
		$this->_datePublished = $val;
	}

	function setCreatedDate($val) {
		$this->_dateCreated = $val;
	}

	function setModifiedDate($val) {
		$this->_dateModified = $val;
	}

	function setState($val) {
		if ($val !== self::STATUS_PUBLISHED && $val !== self::STATUS_RESERVED && $val !== self::STATUS_DRAFT) return;
		$this->_status = $val;
	}

	// Custom Meta Data -------------------------------------------------------

	const EVENT_STATUS_SCHEDULED = 'scheduled';
	const EVENT_STATUS_HELD      = 'held';
	const EVENT_STATUS_FINISHED  = 'finished';
	const EVENT_DATE_NAN = 'NaN-aN-aN';

	protected function _assignCustom($vals) {
		if (!empty($vals['date_bgn'])) $this->setEventDateBgn($vals['date_bgn']);
		if (!empty($vals['date_end'])) $this->setEventDateEnd($vals['date_end']);
	}

	protected function _readCustomMeta($metaAssoc) {
		$this->_dateEventBgn = empty($metaAssoc['meta']['date_bgn']) ? null : $metaAssoc['meta']['date_bgn'];
		$this->_dateEventEnd = empty($metaAssoc['meta']['date_end']) ? null : $metaAssoc['meta']['date_end'];
	}

	protected function _writeCustomMeta(&$metaAssoc) {
		if (!empty($this->_dateEventBgn)) $metaAssoc['meta']['date_bgn'] = $this->_dateEventBgn;
		if (!empty($this->_dateEventEnd)) $metaAssoc['meta']['date_end'] = $this->_dateEventEnd;
	}

	function getEventDateBgn() {
		return (isset($this->_dateEventBgn) && $this->_dateEventBgn !== self::EVENT_DATE_NAN) ? $this->_dateEventBgn : '';
	}

	function setEventDateBgn($val) {
		$this->_dateEventBgn = ($val === self::EVENT_DATE_NAN) ? '' : $val;
	}

	function getEventDateEnd() {
		return (isset($this->_dateEventEnd) && $this->_dateEventEnd !== self::EVENT_DATE_NAN) ? $this->_dateEventEnd : '';
	}

	function setEventDateEnd($val) {
		$this->_dateEventEnd = ($val === self::EVENT_DATE_NAN) ? '' : $val;
	}

	function getEventState() {
		$bgn = str_replace(['-'], '', empty($this->_dateEventBgn) ? 0 : $this->_dateEventBgn);
		$end = str_replace(['-'], '', empty($this->_dateEventEnd) ? 99999999 : $this->_dateEventEnd);
		$now = date('Ymd');
		if ($now < $bgn) return self::EVENT_STATUS_SCHEDULED;
		if ($end < $now) return self::EVENT_STATUS_FINISHED;
		return self::EVENT_STATUS_HELD;
	}

	// Content ----------------------------------------------------------------

	private $_content = null;

	private function _readContent() {
		$contPath = $this->_postPath . self::CONT_FILE_NAME;
		$contStr = @file_get_contents($contPath);
		if ($contPath === false) {
			Logger::output('Error (Post::_readContent file_get_contents) [' . $contPath . ']');
			return false;
		}
		$this->_content = $contStr;
		return true;
	}

	private function _writeContent() {
		$contPath = $this->_postPath . self::CONT_FILE_NAME;
		$suc = file_put_contents($contPath, $this->_content, LOCK_EX);
		if ($suc === false) {
			Logger::output('Error (Post::_writeContent file_put_contents) [' . $contPath . ']');
			return false;
		}
		chmod($contPath, self::MODE_FILE);
		return true;
	}

	function getContent() {
		if ($this->_content === null) {
			$res = $this->_readContent();
			if ($res === false) {
				$this->_content = '';
			}
		}
		if (empty($this->_content)) return '';

		$dom = str_get_html($this->_content);
		foreach($dom->find('img') as &$elm) {
			$elm->src = $this->convertToActualUrl($elm->src);
		}
		foreach($dom->find('a') as &$elm) {
			$elm->href = $this->convertToActualUrl($elm->href);
		}
		$content = $dom->save();
		$dom->clear();
		unset($dom);
		return $content;
	}

	function setContent($val) {
		if (empty($val)) {
			$this->_content = '';
			return;
		}
		$dom = str_get_html($val);
		foreach($dom->find('img') as &$elm) {
			$elm->src = $this->convertToPortableUrl($elm->src);
		}
		foreach($dom->find('a') as &$elm) {
			$elm->href = $this->convertToPortableUrl($elm->href);
		}
		$this->_content = $dom->save();
		$dom->clear();
		unset($dom);
	}

	private function convertToActualUrl($url) {
		$sp = strpos($url, '/');
		if ($sp === 0) {
			$sub = substr($url, 1);
			return $this->_urlPost . $sub;
		} else {
			if (strpos($url, Media::MEDIA_DIR_NAME . '/') === 0){
				return $this->_urlPost . $this->_id . '/' . $url;
			}
		}
		return $url;
	}

	private function convertToPortableUrl($url) {
		$url = resolve_url($url, $this->_urlPrivate);
		if (strpos($url, $this->_urlPost) === 0) {
			$url = substr($url, strlen($this->_urlPost) - 1);
			$pu = '/' . $this->_id . '/' . Media::MEDIA_DIR_NAME . '/';
			if (strpos($url, $pu) === 0) {
				$url = substr($url, strlen($pu) - strlen(Media::MEDIA_DIR_NAME . '/'));
			}
		}
		return $url;
	}

	function getExcerpt($len) {
		$str = strip_tags($this->getContent());
		$str = mb_substr($str, 0, $len);
		if ($str < mb_strlen($this->getContent())) $str .= '...';
		return $str;
	}

	// Unstored Meta ----------------------------------------------------------

	private $_isNewItem  = false;
	private $_indexScore = null;

	function setNewItem($val) {
		$this->_isNewItem = $val;
	}

	function isNewItem() {
		return $this->_isNewItem;
	}

	function updateIndexScore($words) {
		$this->_indexScore = Indexer::calcIndexScore($words, $this->_postPath . self::WORD_FILE_NAME);
		return $this->_indexScore;
	}

	// Utility Methods --------------------------------------------------------

	function getStateClasses() {
		$cs = [];
		if ($this->isNewItem()) $cs[] = 'new';
		if ( $this->hasTerm( 'category', 'event' ) ) $cs[] = $this->getEventState();
		return implode(' ', $cs);
	}

}
