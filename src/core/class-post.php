<?php
/**
 * Post
 *
 * @author Takuto Yanagida
 * @version 2024-03-26
 */

namespace nt;

require_once( __DIR__ . '/class-indexer.php' );
require_once( __DIR__ . '/class-logger.php' );
require_once( __DIR__ . '/util/date-format.php' );
require_once( __DIR__ . '/lib/simple_html_dom.php' );

class Post {

	/**
	 * The name of the info file.
	 *
	 * @const string
	 */
	public const INFO_FILE_NAME = 'info.json';

	/**
	 * The name of the content file.
	 *
	 * @const string
	 */
	public const CONT_FILE_NAME = 'content.html';

	/**
	 * The name of the bigm file.
	 *
	 * @const string
	 */
	public const BIGM_FILE_NAME = 'bigm.txt';

	/**
	 * The name of the media directory.
	 *
	 * @const string
	 */
	public const MEDIA_DIR_NAME = 'media';

	/**
	 * The status of the post when it is published.
	 *
	 * @const string
	 */
	public const STATUS_PUBLISH = 'publish';

	/**
	 * The status of the post when it is scheduled for future publication.
	 *
	 * @const string
	 */
	public const STATUS_FUTURE  = 'future';

	/**
	 * The status of the post when it is a draft.
	 *
	 * @const string
	 */
	public const STATUS_DRAFT   = 'draft';

	/**
	 * The status of the date when it is upcoming.
	 *
	 * @const string
	 */
	public const DATE_STATUS_UPCOMING = 'upcoming';

	/**
	 * The status of the date when it is ongoing.
	 *
	 * @const string
	 */
	public const DATE_STATUS_ONGOING  = 'ongoing';

	/**
	 * The status of the date when it is finished.
	 *
	 * @const string
	 */
	public const DATE_STATUS_FINISHED = 'finished';

	/**
	 * Compares the dates of two posts.
	 *
	 * @param Post $a The first post.
	 * @param Post $b The second post.
	 * @return int The comparison result.
	 */
	static public function compareDate( Post $a, Post $b ): int {
		return $b->_date <=> $a->_date;
	}

	/**
	 * Compares the index scores of two posts.
	 *
	 * @param Post $a The first post.
	 * @param Post $b The second post.
	 * @return int The comparison result.
	 */
	static public function compareIndexScore( Post $a, Post $b ): int {
		return $b->_indexScore <=> $a->_indexScore;
	}


	// ------------------------------------------------------------------------


	/**
	 * ID.
	 *
	 * @var string
	 */
	private $_id;

	/**
	 * Sub-path.
	 *
	 * @var string
	 */
	private $_subPath;

	/**
	 * Type.
	 *
	 * @var string
	 */
	private $_type = '';

	/**
	 * Title.
	 *
	 * @var string
	 */
	private $_title = '';

	/**
	 * Status.
	 *
	 * @var string
	 */
	private $_status = self::STATUS_PUBLISH;

	/**
	 * Created Date.
	 *
	 * @var string
	 */
	private $_date = '';

	/**
	 * Modified Date.
	 *
	 * @var string
	 */
	private $_modified = '';

	/**
	 * Array of taxonomy to slugs.
	 *
	 * @var array<string, string[]>
	 */
	private $_taxonomy = [];

	/**
	 * Array of meta key to meta values.
	 *
	 * @var array<string, string>
	 */
	private $_meta = [];

	/**
	 * Index Score.
	 *
	 * @var float
	 */
	private $_indexScore = 0;

	/**
	 * Content.
	 *
	 * @var string|null
	 */
	private $_content = null;

	/**
	 * Constructs a new post.
	 *
	 * @param string $id      The ID of the post.
	 * @param string $subPath The sub-path of the post.
	 */
	public function __construct( string $id, string $subPath = '' ) {
		$this->_id      = $id;
		$this->_subPath = $subPath;
	}

	/**
	 * Gets the ID of the post.
	 *
	 * @return string The ID of the post.
	 */
	public function getId(): string {
		return $this->_id;
	}

	/**
	 * Loads the post.
	 *
	 * @param ?array<string, mixed> $info The info of the post.
	 * @return bool Whether the post was loaded successfully.
	 */
	public function load( ?array $info = null ): bool {
		global $nt_store;
		$path = $nt_store->getPostDir( $this->_id, $this->_subPath );

		$ret = $this->_readInfo( $path, $info );
		if ( $ret === true ) $this->_updateStatus( $path );
		return $ret;
	}

	/**
	 * Saves the post.
	 *
	 * @param ?string $id The ID of the post.
	 */
	public function save( ?string $id = null ): void {
		if ( ! defined( 'NT_ADMIN' ) ) exit;
		if ( ! empty( $id ) ) $this->_id = $id;

		global $nt_store;
		$path = $nt_store->getPostDir( $this->_id, $this->_subPath );
		if ( ! is_dir( $path ) ) mkdir( $path, NT_MODE_DIR );
		if ( is_dir( $path ) ) @chmod( $path, NT_MODE_DIR );

		if (
			$this->_writeInfo( $path, true ) &&
			$this->_writeContent( $path ) &&
			$this->_writeSearchIndex( $path )
		) {
			Logger::info( __METHOD__, 'Post saving succeeded', $this->_id );
		}
	}

	/**
	 * Updates the status of the post.
	 *
	 * @param string $postDir The directory of the post.
	 */
	private function _updateStatus( string $postDir ): void {
		if ( $this->_status === self::STATUS_DRAFT ) return;
		$s = $this->canPublished() ? self::STATUS_PUBLISH : self::STATUS_FUTURE;
		if ( $s !== $this->_status ) {
			$this->_status = $s;
			$this->_writeInfo( $postDir );
		}
	}

	/**
	 * Writes the search index of the post.
	 *
	 * @param string $postDir The directory of the post.
	 * @return bool Whether the search index was written successfully.
	 */
	private function _writeSearchIndex( string $postDir ): bool {
		$text = strip_tags( $this->_title ) . ' ' . strip_tags( $this->_content ?? '' );
		$path = $postDir . self::BIGM_FILE_NAME;

		$idx = Indexer::createSearchIndex( $text );
		$ret = file_put_contents( $path, $idx, LOCK_EX );
		if ( $ret === false ) {
			Logger::error( __METHOD__, 'Cannot write the index data', $this->_id );
			return false;
		}
		@chmod( $path, NT_MODE_FILE );
		return true;
	}


	// -------------------------------------------------------------------------


	/**
	 * Assigns values to the post.
	 *
	 * @param array<string, mixed> $vals The values to assign.
	 */
	public function assign( array $vals ): void {
		if ( ! defined( 'NT_ADMIN' ) ) exit;
		$this->setTitle( $vals['post_title'] );
		$this->setStatus( $vals['post_status'] );

		$date = empty( $vals['post_date'] ) ? 'now' : pack_date_time( $vals['post_date'] );
		$this->setDate( $date );
		$this->setModified( 'now' );

		$type = $this->getType();
		$this->_assignTaxonomy( $type, $vals );
		$this->_assignMeta( $type, $vals );

		$this->_setContent( $vals['post_content'] );
	}

	/**
	 * Assigns taxonomy to the post.
	 *
	 * @param string               $type The type of the post.
	 * @param array<string, mixed> $vals The values to assign.
	 */
	private function _assignTaxonomy( string $type, array $vals ): void {
		global $nt_store;
		$taxes = $nt_store->type()->getTaxonomySlugAll( $type );

		foreach ( $taxes as $tax ) {
			if ( ! isset( $vals["taxonomy:$tax"] ) ) continue;
			$ts = is_array( $vals["taxonomy:$tax"] ) ? $vals["taxonomy:$tax"] : [ $vals["taxonomy:$tax"] ];
			$this->setTermSlugs( $tax, $ts );
		}
	}

	/**
	 * Assigns meta data to the post.
	 *
	 * @param string               $type The type of the post.
	 * @param array<string, mixed> $vals The values to assign.
	 */
	private function _assignMeta( string $type, array $vals ): void {
		global $nt_store;
		$ms = $nt_store->type()->getMetaAll( $type );
		$this->_assignMetaInternal( $ms, $vals );
	}

	/**
	 * Internally assigns meta data to the post.
	 *
	 * @param array<string, mixed> $ms   The meta data.
	 * @param array<string, mixed> $vals The values to assign.
	 */
	private function _assignMetaInternal( array $ms, array $vals ): void {
		global $nt_store;
		$url = $nt_store->getPostUrl( $this->_id, $this->_subPath );

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
			if ( $m['type'] === 'checkbox' ) {
				$vals["meta:$key"] = (bool) $vals["meta:$key"];
			}
			if ( $m['type'] === 'date' ) {
				$vals["meta:$key"] = \nt\pack_date( $vals["meta:$key"] );
			}
			if ( $m['type'] === 'date_range' ) {
				$json = $vals["meta:$key"];
				$d = json_decode( $json, true );
				if ( $d !== null ) {
					$d['from'] = \nt\pack_date( $d['from'] );
					$d['to']   = \nt\pack_date( $d['to']   );
					$vals["meta:$key"] = $d;
				}
			}
			if ( $m['type'] === 'media' || $m['type'] === 'media_image' ) {
				$json = $vals["meta:$key"];
				$d = json_decode( $json, true );
				if ( empty( $d ) || ! is_array( $d ) ) continue;
				$d = self::_convertMediaUrl( $d, function ( $tar ) use ( $url ) {
					$tar = self::_convertToPortableUrl( $url, $tar );
					return self::_convertToActualUrl( $url, $tar );
				} );
				if ( $d !== null ) $vals["meta:$key"] = $d;
			}
			$this->setMetaValue( $key, $vals["meta:$key"] );
		}
	}


	// Info Data --------------------------------------------------------------


	/**
	 * Reads the info of the post.
	 *
	 * @param string                $postDir       The directory of the post.
	 * @param ?array<string, mixed> $preloadedInfo The preloaded info of the post.
	 * @return bool Whether the info was read successfully.
	 */
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
		$this->_meta     = $this->_convertMediaUrlToActual( $postDir, $info['type'], $info['meta'] );
		if ( isset( $info['_index_score'] ) ) $this->_indexScore = $info['_index_score'];
		return true;
	}

	/**
	 * Writes the info of the post.
	 *
	 * @param string $postDir        The directory of the post.
	 * @param bool   $updateModified Whether to update the modified date.
	 * @return bool Whether the info was written successfully.
	 */
	private function _writeInfo( string $postDir, bool $updateModified = false ): bool {
		if ( $updateModified ) $this->setModified();
		$info = [
			'type'     => $this->_type,
			'title'    => $this->_title,
			'status'   => $this->_status,
			'date'     => $this->_date,
			'modified' => $this->_modified,
			'taxonomy' => $this->_taxonomy,
			'meta'     => $this->_convertMediaUrlToPortable( $postDir, $this->_type, $this->_meta ),
		];
		return $this->_writeInfoFile( $postDir, $info );
	}

	/**
	 * Reads the info file of the post.
	 *
	 * @param string $postDir The directory of the post.
	 * @return ?array<string, mixed> The info of the post.
	 */
	private function _readInfoFile( string $postDir ): ?array {
		$path = $postDir . self::INFO_FILE_NAME;
		$json = file_get_contents( $path );
		if ( $json === false ) {
			Logger::error( __METHOD__, 'Cannot read the info data', $this->_id );
			return null;
		}
		$data = json_decode( $json, true );
		if ( $data === null ) {
			Logger::error( __METHOD__, 'The info data is invalid', $this->_id );
		}
		return $data;
	}

	/**
	 * Writes the info file of the post.
	 *
	 * @param string               $postDir The directory of the post.
	 * @param array<string, mixed> $info    The info of the post.
	 * @return bool Whether the info file was written successfully.
	 */
	private function _writeInfoFile( string $postDir, array $info ): bool {
		$path = $postDir . self::INFO_FILE_NAME;
		$json = json_encode( $info, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		$res = file_put_contents( $path, $json, LOCK_EX );
		if ( $res === false ) {
			Logger::error( __METHOD__, 'Cannot write the info data', $this->_id );
			return false;
		}
		@chmod( $path, NT_MODE_FILE );
		return true;
	}

	/**
	 * Converts media URLs to actual URLs.
	 *
	 * @param string               $postDir The directory of the post.
	 * @param string               $type    The type of the post.
	 * @param array<string, mixed> $meta    The meta data of the post.
	 * @return array<string, mixed> The converted media URLs.
	 */
	private function _convertMediaUrlToActual( string $postDir, string $type, array $meta ) {
		return self::_convertMediaUrls( $type, $meta, function ( $tar ) use ( $postDir ) {
			return self::_convertToActualUrl( $postDir, $tar );
		} );
	}

	/**
	 * Converts media URLs to portable URLs.
	 *
	 * @param string               $postDir The directory of the post.
	 * @param string               $type    The type of the post.
	 * @param array<string, mixed> $meta    The meta data of the post.
	 * @return array<string, mixed> The converted media URLs.
	 */
	private function _convertMediaUrlToPortable( string $postDir, string $type, array $meta ) {
		return self::_convertMediaUrls( $type, $meta, function ( $tar ) use ( $postDir ) {
			return self::_convertToPortableUrl( $postDir, $tar );
		} );
	}

	/**
	 * Converts media URLs.
	 *
	 * @param string               $type The type of the post.
	 * @param array<string, mixed> $meta The meta data of the post.
	 * @param callable             $fn   The function to convert the URLs.
	 * @return array<string, mixed> The converted media URLs.
	 */
	static private function _convertMediaUrls( string $type, array $meta, callable $fn ) {
		global $nt_store;
		$ms = $nt_store->type()->getMetaAll( $type );
		foreach ( $ms as $m ) {
			if ( empty( $m['key'] ) ) {
				continue;
			}
			if ( empty( $meta[ $m['key'] ] ) || ! is_array( $meta[ $m['key'] ] ) ) {
				continue;
			}
			if ( $m['type'] === 'media' || $m['type'] === 'media_image' ) {
				$meta[ $m['key'] ] = self::_convertMediaUrl( $meta[ $m['key'] ], $fn );
			}
		}
		return $meta;
	}

	/**
	 * Converts a media URL.
	 *
	 * @param array<string, mixed> $d  The media URL.
	 * @param callable             $fn The function to convert the URL.
	 * @return array<string, mixed> The converted media URL.
	 */
	static private function _convertMediaUrl( array $d, callable $fn ) {
		if ( isset( $d['url'] ) )    $d['url']    = call_user_func( $fn, $d['url'] );
		if ( isset( $d['minUrl'] ) ) $d['minUrl'] = call_user_func( $fn, $d['minUrl'] );
		if ( isset( $d['srcset'] ) ) {
			$ss = explode( ',', $d['srcset'] );
			$ss = array_map( 'trim', $ss );
			$ss = array_map( $fn, $ss );
			$d['srcset'] = implode( ', ', $ss );
		}
		return $d;
	}

	// ----

	/**
	 * Gets the type of the post.
	 *
	 * @return string The type of the post.
	 */
	public function getType(): string {
		return $this->_type;
	}

	/**
	 * Sets the type of the post.
	 *
	 * @param string $val The type of the post.
	 */
	public function setType( string $val ): void {
		$this->_type = $val;
	}

	/**
	 * Gets the title of the post.
	 *
	 * @return string The title of the post.
	 */
	public function getTitle(): string {
		return $this->_title;
	}

	/**
	 * Sets the title of the post.
	 *
	 * @param string $val The title of the post.
	 */
	public function setTitle( string $val ): void {
		$this->_title = $val;
	}

	/**
	 * Gets the status of the post.
	 *
	 * @return string The status of the post.
	 */
	public function getStatus(): string {
		return $this->_status;
	}

	/**
	 * Checks if the status of the post is a specific value.
	 *
	 * @param string $val The status to check.
	 * @return bool Whether the status of the post is the specific value.
	 */
	public function isStatus( string $val ): bool {
		return $this->_status === $val;
	}

	/**
	 * Checks if the post can be published.
	 *
	 * @return bool Whether the post can be published.
	 */
	public function canPublished(): bool {
		return intval( $this->_date ) < intval( date( 'YmdHis' ) );
	}

	/**
	 * Sets the status of the post.
	 *
	 * @param string $val The status of the post.
	 */
	public function setStatus( string $val ): void {
		if ( ! in_array( $val, [ self::STATUS_PUBLISH, self::STATUS_FUTURE, self::STATUS_DRAFT ], true ) ) return;
		$this->_status = $val;
	}

	/**
	 * Gets the date of the post.
	 *
	 * @return string The date of the post.
	 */
	public function getDate(): string {
		return \nt\parse_date_time( $this->_date );
	}

	/**
	 * Gets the modified date of the post.
	 *
	 * @return string The modified date of the post.
	 */
	public function getModified(): string {
		return \nt\parse_date_time( $this->_modified );
	}

	/**
	 * Gets the raw date of the post.
	 *
	 * @return string The raw date of the post.
	 */
	public function getDateRaw(): string {
		return $this->_date;
	}

	/**
	 * Gets the raw modified date of the post.
	 *
	 * @return string The raw modified date of the post.
	 */
	public function getModifiedRaw(): string {
		return $this->_modified;
	}

	/**
	 * Sets the date of the post.
	 *
	 * @param string $val The date of the post.
	 */
	public function setDate( string $val = 'now' ): void {
		if ( $val === 'now' ) $val = date( 'YmdHis' );
		$this->_date = $val;
	}

	/**
	 * Sets the modified date of the post.
	 *
	 * @param string $val The modified date of the post.
	 */
	public function setModified( string $val = 'now' ): void {
		if ( $val === 'now' ) $val = date( 'YmdHis' );
		$this->_modified = $val;
	}

	// ----

	/**
	 * Checks if the post has a specific term.
	 *
	 * @param string $tax_slug  The taxonomy slug.
	 * @param string $term_slug The term slug.
	 * @return bool Whether the post has the specific term.
	 */
	public function hasTerm( string $tax_slug, string $term_slug ): bool {
		return in_array( $term_slug, $this->getTermSlugs( $tax_slug ), true );
	}

	/**
	 * Gets the term slugs of the post.
	 *
	 * @param string $tax_slug The taxonomy slug.
	 * @return string[] The term slugs of the post.
	 */
	public function getTermSlugs( string $tax_slug ): array {
		return isset( $this->_taxonomy[ $tax_slug ] ) ? $this->_taxonomy[ $tax_slug ] : [];
	}

	/**
	 * Gets the taxonomy to term slugs of the post.
	 *
	 * @return array<string, string[]> The taxonomy to term slugs of the post.
	 */
	public function getTaxonomyToTermSlugs(): array {
		return $this->_taxonomy;
	}

	/**
	 * Sets the term slugs of the post.
	 *
	 * @param string   $tax_slug   The taxonomy slug.
	 * @param string[] $term_slugs The term slugs.
	 */
	public function setTermSlugs( string $tax_slug, array $term_slugs ): void {
		$this->_taxonomy[ $tax_slug ] = array_values( array_unique( $term_slugs ) );
	}

	// ----

	/**
	 * Gets the meta data of the post.
	 *
	 * @return array<string, mixed> The meta data of the post.
	 */
	public function getMeta(): array {
		return $this->_meta;
	}

	/**
	 * Gets the meta value of the post.
	 *
	 * @param string $key The key of the meta data.
	 * @return mixed The meta value of the post.
	 */
	public function getMetaValue( string $key ) {
		if ( isset( $this->_meta[ $key ] ) ) return $this->_meta[ $key ];
		return null;
	}

	/**
	 * Sets the meta value of the post.
	 *
	 * @param string $key The key of the meta data.
	 * @param mixed  $val The value of the meta data.
	 */
	public function setMetaValue( string $key, $val ): void {
		if ( $val === null ) {
			unset( $this->_meta[ $key ] );
		} else {
			$this->_meta[ $key ] = $val;
		}
	}


	// Content ----------------------------------------------------------------


	/**
	 * Reads the content of the post.
	 *
	 * @param string $postDir The directory of the post.
	 * @return bool Whether the content was read successfully.
	 */
	private function _readContent( string $postDir ): bool {
		$path = $postDir . self::CONT_FILE_NAME;
		if ( ! is_file( $path ) || ! is_readable( $path ) ) return false;

		$cont = file_get_contents( $path );
		if ( $cont === false ) {
			Logger::error( __METHOD__, 'Cannot read the post', $this->_id );
			return false;
		}
		$this->_content = $cont;
		return true;
	}

	/**
	 * Writes the content of the post.
	 *
	 * @param string $postDir The directory of the post.
	 * @return bool Whether the content was written successfully.
	 */
	private function _writeContent( string $postDir ): bool {
		if ( $this->_content === null ) $this->_readContent( $postDir );

		$path = $postDir . self::CONT_FILE_NAME;
		$res = file_put_contents( $path, $this->_content, LOCK_EX );
		if ( $res === false ) {
			Logger::error( __METHOD__, 'Cannot write the post', $this->_id );
			return false;
		}
		@chmod( $path, NT_MODE_FILE );
		return true;
	}

	/**
	 * Converts a URL to an actual URL.
	 *
	 * @param string $postUrl The URL of the post.
	 * @param string $url     The URL to convert.
	 * @return string The converted URL.
	 */
	static private function _convertToActualUrl( string $postUrl, string $url ): string {
		$sp = strpos( $url, '//' );
		if ( $sp === 0 ) {
			$sub = substr( $url, 2 );
			return NT_URL . $sub;
		}
		return $url;
	}

	/**
	 * Converts a URL to a portable URL.
	 *
	 * @param string $postUrl The URL of the post.
	 * @param string $url     The URL to convert.
	 * @return string The converted URL.
	 */
	static private function _convertToPortableUrl( string $postUrl, string $url ): string {
		$url = resolve_url( $url, NT_URL_ADMIN );
		if ( strpos( $url, NT_URL ) === 0 ) {
			$url = '/' . substr( $url, strlen( NT_URL ) - 1 );
			$url = str_replace( '//?_', '//?', $url );
		}
		return $url;
	}

	/**
	 * Converts the content of the post.
	 *
	 * @param string   $val The content to convert.
	 * @param callable $fn  The function to convert the content.
	 * @return string The converted content.
	 */
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

	/**
	 * Sets the content of the post.
	 *
	 * @param string $val The content of the post.
	 */
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

	/**
	 * Gets the content of the post.
	 *
	 * @return string The content of the post.
	 */
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

	/**
	 * Gets the excerpt of the post.
	 *
	 * @param int $len The length of the excerpt.
	 * @return string The excerpt of the post.
	 */
	public function getExcerpt( int $len ): string {
		$str = strip_tags( $this->getContent() );
		$exc = mb_substr( $str, 0, $len );
		if ( mb_strlen( $exc ) < mb_strlen( $str ) ) $exc .= '...';
		return $exc;
	}

}
