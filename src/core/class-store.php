<?php
/**
 * Store
 *
 * @author Takuto Yanagida
 * @version 2024-03-26
 */

namespace nt;

require_once( __DIR__ . '/class-logger.php' );
require_once( __DIR__ . '/class-indexer.php' );
require_once( __DIR__ . '/class-post.php' );
require_once( __DIR__ . '/class-type.php' );
require_once( __DIR__ . '/class-taxonomy.php' );
require_once( __DIR__ . '/class-query.php' );

class Store {

	/**
	 * URL.
	 *
	 * @var string
	 */
	protected $_dirUrl;

	/**
	 * Root directory.
	 *
	 * @var string
	 */
	protected $_dirRoot;

	/**
	 * Configuration.
	 *
	 * @var array<string, mixed>
	 */
	protected $_conf;

	/**
	 * Types.
	 *
	 * @var Type
	 */
	protected $_type;

	/**
	 * Taxonomies.
	 *
	 * @var Taxonomy
	 */
	protected $_taxonomy;

	/**
	 * Constructor for the class.
	 *
	 * @param string $ntUrl   The URL of the directory.
	 * @param string $ntDir   The root directory.
	 * @param string $dataDir The data directory.
	 * @param array<string, mixed> $conf The configuration for the constructor.
	 */
	public function __construct( string $ntUrl, string $ntDir, string $dataDir, array $conf ) {
		$this->_dirUrl  = $ntUrl;
		$this->_dirRoot = $ntDir;
		$this->_conf    = $conf;

		$this->_type     = new Type( $dataDir, $conf );
		$this->_taxonomy = new Taxonomy( $dataDir, $conf );
	}

	/**
	 * Gets the type of the post.
	 *
	 * @return Type The type of the post.
	 */
	public function type(): Type { return $this->_type; }

	/**
	 * Gets the taxonomy of the post.
	 *
	 * @return Taxonomy The taxonomy of the post.
	 */
	public function taxonomy(): Taxonomy { return $this->_taxonomy; }


	// ------------------------------------------------------------------------


	/**
	 * Gets the URL of the post.
	 *
	 * @param string  $id      The ID of the post.
	 * @param ?string $subPath The subpath of the post.
	 * @return string The URL of the post.
	 */
	public function getPostUrl( string $id, ?string $subPath ): string {
		if ( $subPath === null ) $subPath = $this->getSubPath( $id );
		return $this->_dirUrl . $subPath . $id . '/';
	}

	/**
	 * Gets the directory of the post.
	 *
	 * @param string  $id      The ID of the post.
	 * @param ?string $subPath The subpath of the post.
	 * @return string The directory of the post.
	 */
	public function getPostDir( string $id, ?string $subPath ): string {
		if ( $subPath === null ) $subPath = $this->getSubPath( $id );
		return $this->_dirRoot . $subPath . $id . '/';
	}

	/**
	 * Creates the archive and sub-path of the post.
	 *
	 * @param string $type            The type of the post.
	 * @param string $dateRaw         The raw date of the post.
	 * @param bool   $ensureExistence Whether to ensure the existence of the directory.
	 * @return string[] The directory and sub-path of the post.
	 */
	public function createArchAndSubPath( string $type, string $dateRaw, bool $ensureExistence = false ): array {
		$ret = $this->_conf['archive_by_type'] ? "$type/" : 'post/';
		if ( $this->_conf['archive_by_year'] ) {
			$year = substr( $dateRaw, 0, 4 );
			$ret .= "$year/";
		}
		if ( $ensureExistence ) {
			$path = $this->_dirRoot . rtrim( $ret, '/' );
			if ( ! is_dir( $path ) ) mkdir( $path, NT_MODE_DIR, true );
		}
		return [ $this->_dirRoot . $ret, $ret ];
	}

	/**
	 * Gets the subpath of the post.
	 *
	 * @param string $id The ID of the post.
	 * @return ?string The subpath of the post.
	 */
	public function getSubPath( string $id ): ?string {
		$ds = $this->_getSubPaths();
		foreach ( $ds as $d ) {
			if ( is_dir( $this->_dirRoot . $d . $id ) ) return $d;
		}
		return null;
	}

	/**
	 * Gets the sub-paths of the post.
	 *
	 * @param ?string $type The type of the post.
	 * @return string[] The sub-paths of the post.
	 */
	private function _getSubPaths( ?string $type = null ): array {
		$typeDirs = $type ? [ "$type/" ] : $this->_type->getTypeDirAll();
		if ( ! $this->_conf['archive_by_year'] ) return $typeDirs;

		$ret = [];
		foreach ( $typeDirs as $t ) {
			if ( ! is_dir( $this->_dirRoot . $t ) ) continue;
			$dir = dir( $this->_dirRoot . $t );
			if ( $dir instanceof \Directory ) {
				while ( false !== ( $fn = $dir->read() ) ) {
					if ( $fn[0] === '.' || $fn[0] === '_' || $fn[0] === '-' ) continue;
					if ( preg_match( '/[^0-9]/', $fn ) ) continue;
					if ( strlen( $fn ) !== 4 ) continue;
					if ( is_file( $t . $fn ) ) continue;
					$ret[] = $t . $fn . '/';
				}
				$dir->close();
			}
		}
		$ret = array_merge( $ret, $typeDirs );  // For option compatibility
		return $ret;
	}


	// ------------------------------------------------------------------------


	/**
	 * Gets the post.
	 *
	 * @param ?string $id The ID of the post.
	 * @return ?Post The post.
	 */
	public function getPost( ?string $id ): ?Post {
		if ( null === $id ) return null;
		$sp = $this->getSubPath( $id );
		if ( null === $sp ) return null;
		$p = new Post( $id, $sp );
		if ( ! $p->load() ) return null;
		return $p;
	}

	/**
	 * Gets the post with next and previous posts.
	 *
	 * @param string|null          $id   The ID of the post.
	 * @param array<string, mixed> $args The arguments.
	 * @return array{Post|null, Post|null, Post|null}|null The post with next and previous posts.
	 */
	public function getPostWithNextAndPrevious( ?string $id, array $args = [] ): ?array {
		if ( $id === null ) return null;
		$posts = $this->_getPosts( $args );
		$idIndex = null;
		for ($i = 0; $i < count( $posts ); $i += 1 ) {
			$p = $posts[ $i ];
			if ( $p->getId() === $id ) {
				$idIndex = $i;
				break;
			}
		}
		if ( $idIndex === null ) return null;

		$prev = ( $idIndex > 0 ) ? $posts[ $idIndex - 1 ] : null;
		$next = ( $idIndex < count( $posts ) - 1) ? $posts[ $idIndex + 1 ] : null;
		return [ $prev, $posts[ $idIndex ], $next ];
	}

	/**
	 * Gets the posts.
	 *
	 * @param array<string, mixed> $args The arguments.
	 * @return array<string, mixed> The posts.
	 */
	public function getPosts( array $args = [] ): array {
		$page = $args['page'] ?? 1;

		$posts = $this->_getPosts( $args );

		$size    = count( $posts );
		$pageIdx = intval( $page ) - 1;
		$perPage = intval( $args['per_page'] ?? $this->_conf['per_page'] );
		$offset  = $perPage * $pageIdx;

		if ( $size < $offset ) {
			$offset  = 0;
			$pageIdx = 0;
		}
		$ret = array_slice( $posts, $offset, 0 < $perPage ? $perPage : NULL );
		return ['posts' => $ret, 'size' => $size, 'page' => $pageIdx + 1, 'page_count' => ceil( $size / $perPage ) ];
	}

	/**
	 * Gets the count by date.
	 *
	 * @param string               $type The type of the post.
	 * @param array<string, mixed> $args The arguments.
	 * @return array<string, mixed>[] The count by date.
	 */
	public function getCountByDate( string $type, array $args ): array {
		$args += [ 'status' => Post::STATUS_PUBLISH ];
		$paths = $this->_getPostTypeSubDirs( $args );
		$ms = [];
		$this->_loadMatchedInfoAll( $this->_dirRoot, $paths, $args, $ms );

		$digit = 4;
		switch ( $type ) {
			case 'year':  $digit = 4; break;
			case 'month': $digit = 6; break;
			case 'day':   $digit = 8; break;
		}
		$count = [];
		foreach ( $ms as $m ) {
			$date = $m['info']['date'];
			$key = substr( $date, 0, $digit );
			if ( ! isset( $count[ $key ] ) ) $count[ $key ] = 0;
			$count[ $key ] += 1;
		}
		krsort( $count );
		$ret = [];
		foreach ( $count as $key => $val ) {
			$ret[] = [ 'slug' => $key, 'count' => $val ];
		}
		return $ret;
	}


	// -------------------------------------------------------------------------


	/**
	 * Gets the posts.
	 *
	 * @param array<string, mixed> $args The arguments.
	 * @return Post[] The posts.
	 */
	private function _getPosts( array $args ): array {
		$args += [ 'status' => Post::STATUS_PUBLISH ];
		$posts = [];
		$this->_loadMatchedPostAll( $this->_dirRoot, $args, $posts );

		usort( $posts, '\nt\Post::compareDate' );
		if ( ! empty( $args['search'] ) ) {
			usort( $posts, '\nt\Post::compareIndexScore' );
		}
		return $posts;
	}

	/**
	 * Loads the matched posts.
	 *
	 * @param string                 $root   The root directory.
	 * @param array<string, mixed>   $args   The arguments.
	 * @param array<string, mixed>[] &$posts The posts.
	 */
	private function _loadMatchedPostAll( string $root, array $args, array &$posts = [] ): void {
		$paths = $this->_getPostTypeSubDirs( $args );
		$ret = [];
		$this->_loadMatchedInfoAll( $root, $paths, $args, $ret );
		foreach ( $ret as $m ) {
			$p = new Post( $m['id'], $m['subPath'] );
			$p->load( $m['info'] );
			$posts[] = $p;
		}
	}

	/**
	 * Gets the post type subdirectories.
	 *
	 * @param array<string, mixed> $args The arguments.
	 * @return string[] The post type subdirectories.
	 */
	private function _getPostTypeSubDirs( array $args ): array {
		if ( $this->_conf['archive_by_type'] ) {
			if ( empty( $args['type'] ) ) {
				return $this->_type->getTypeDirAll();
			} else {
				$ats = is_array( $args['type'] ) ? $args['type'] : [ $args['type'] ];
				if ( ! in_array( 'post', $ats, true ) ) $ats[] = 'post';  // For option compatibility
				return array_map( function ( $e ) { return "$e/"; }, $ats );
			}
		}
		return ['post/'];
	}

	/**
	 * Loads the matched information.
	 *
	 * @param string                 $root  The root directory.
	 * @param string[]               $paths The paths.
	 * @param array<string, mixed>   $args  The arguments.
	 * @param array<string, mixed>[] &$ret  The return value.
	 */
	private function _loadMatchedInfoAll( string $root, array $paths, array $args, array &$ret = [] ): void {
		$is_trash = ( isset( $args['status'] ) && $args['status'] === '_trash' );
		if ( $is_trash ) unset( $args['status'] );
		$query = new Query( $args );
		$this->_loadMatchedInfoAllInternal( $root, $paths, $query, $is_trash, $ret );
	}

	/**
	 * Loads the matched information internally.
	 *
	 * @param string                 $root    The root directory.
	 * @param string[]               $paths   The paths.
	 * @param Query                  $query   The query.
	 * @param bool                   $isTrash Whether the post is in trash.
	 * @param array<string, mixed>[] &$ret    The return value.
	 */
	private function _loadMatchedInfoAllInternal( string $root, array $paths, Query $query, bool $isTrash, array &$ret = [] ): void {
		foreach ( $paths as $path ) {
			if ( ! is_dir( $root . $path ) ) {
				continue;
			}
			$dir = dir( $root . $path );
			if ( ! ( $dir instanceof \Directory ) ) {
				continue;
			}
			while ( false !== ( $fn = $dir->read() ) ) {
				if ( $fn[0] === '.' || $fn[0] === '_' ) continue;
				if ( ! $isTrash && $fn[0] === '-' ) continue;
				if ( is_file( $root . $path . $fn ) ) continue;
				if ( strlen( $fn ) === 4 ) {
					$this->_loadMatchedInfoAllInternal( $root, ["$path$fn/"], $query, $isTrash, $ret );
					continue;
				}
				if ( $isTrash && $fn[0] !== '-' ) continue;
				$info = $this->_loadInfo( $root . $path . $fn, $fn );
				if ( $info === null ) continue;
				if ( $query->match( $info, "$root$path$fn/" . Post::BIGM_FILE_NAME ) ) {
					$ret[] = [ 'id' => $fn, 'subPath' => $path, 'info' => $info ];
				}
			}
			$dir->close();
		}
	}

	/**
	 * Loads the information.
	 *
	 * @param string $postDir The directory of the post.
	 * @param string $pid     The ID of the post.
	 * @return ?array<string, mixed> The information.
	 */
	private function _loadInfo( string $postDir, string $pid ): ?array {
		$infoPath = $postDir . '/' . Post::INFO_FILE_NAME;
		$json     = false;
		if ( file_exists( $infoPath ) ) {
			$json = file_get_contents( $infoPath );
		}
		if ( $json === false ) {
			Logger::error( __METHOD__, 'Cannot read the info data', $pid );
			return null;
		}
		$data = json_decode( $json, true );
		if ( $data === null ) {
			Logger::error( __METHOD__, 'The info data is invalid', $pid );
		}
		return $data;
	}


	// ------------------------------------------------------------------------


	/**
	 * Creates a new post.
	 *
	 * @param string $type The type of the post.
	 * @return ?Post The new post.
	 */
	public function createNewPost( string $type = 'post' ): ?Post {
		$dateRaw = date( 'YmdHis' );
		list( $archPath, $subPath ) = $this->createArchAndSubPath( $type, $dateRaw, true );

		if ( ! is_writable( $this->_dirRoot . $subPath ) ) {
			Logger::error( __METHOD__, 'The post directory is not writable', $subPath );
			return null;
		}
		if ( $dir = opendir( $archPath ) ) {
			flock( $dir, LOCK_EX );
			$id = $this->_ensureUniquePostId( $archPath, $dateRaw );
			if ( $id === null ) return null;
			$p = new Post( '_' . $id, $subPath );  // Temporary ID
			$p->setType( $type );
			$p->setDate();
			$p->save();
			flock( $dir, LOCK_UN );
			closedir( $dir );
			return $p;
		}
		return null;
	}

	/**
	 * Ensures the uniqueness of the post ID.
	 *
	 * @param string $archPath The path of the archive.
	 * @param string $dateRaw  The raw date.
	 * @return ?string The unique post ID.
	 */
	private function _ensureUniquePostId( string $archPath, string $dateRaw ): ?string {
		$id = $dateRaw;
		if ( ! $this->_checkIdExists( $archPath, $id ) ) return $id;
		for ( $i = 1; $i < 10; $i += 1 ) {
			$id = $dateRaw . '_' . $i;
			if ( ! $this->_checkIdExists( $archPath, $id ) ) return $id;
		}
		return null;
	}

	/**
	 * Checks if the ID exists.
	 *
	 * @param string $archPath The path of the archive.
	 * @param string $id       The ID.
	 * @return bool Whether the ID exists.
	 */
	private function _checkIdExists( string $archPath, string $id ): bool {
		return
			file_exists( $archPath       . $id ) ||
			file_exists( $archPath . '_' . $id ) ||
			file_exists( $archPath . '-' . $id );
	}


	// ------------------------------------------------------------------------


	/**
	 * Writes the post.
	 *
	 * @param Post $post The post.
	 * @return Post The post.
	 */
	public function writePost( Post $post ): Post {
		$post->save();
		$id = $post->getId();
		if ( $id[0] === '_' ) {
			$newId   = substr( $id, 1 );
			$subPath = $this->getSubPath( $id );
			rename( $this->getPostDir( $id, $subPath ), $this->getPostDir( $newId, $subPath ) );
			$post->save( $newId );
		}
		return $post;
	}

	/**
	 * Removes the post.
	 *
	 * @param string $id The ID of the post.
	 */
	public function removePost( string $id ): void {
		if ( $id[0] === '-' ) {
			$pd = $this->getPostDir( $id, null );
			self::deleteAll( rtrim( $pd, '/' ) );
			return;
		}
		$subPath = $this->getSubPath( $id );
		$removed_id = "-$id";
		$srcPath = $this->getPostDir( $id, $subPath );
		if ( ! is_dir( $srcPath ) ) {
			Logger::error( __METHOD__, 'The post directory does not exist', $id );
			return;
		}
		if ( ! is_writable( $this->_dirRoot . $subPath ) ) {
			Logger::error( __METHOD__, 'The post directory is not writable', $id );
			return;
		}
		if ( rename( $srcPath, $this->getPostDir( $removed_id, $subPath ) ) ) {
			Logger::info( __METHOD__, 'Post removing succeeded', $id );
		} else {
			Logger::error( __METHOD__, 'Post removing failed', $id );
		}
	}

	/**
	 * Restores the post.
	 *
	 * @param string $removed_id The ID of the removed post.
	 */
	public function restorePost( string $removed_id ): void {
		if ( $removed_id[0] !== '-' ) return;
		$subPath = $this->getSubPath( $removed_id );
		$id = substr( $removed_id, 1 );
		$srcPath = $this->getPostDir( $removed_id, $subPath );
		if ( ! is_dir( $srcPath ) ) {
			Logger::error( __METHOD__, 'The post directory does not exist', $removed_id );
			return;
		}
		if ( rename( $srcPath, $this->getPostDir( $id, $subPath ) ) ) {
			Logger::info( __METHOD__, 'Post restoring succeeded', $id );
		} else {
			Logger::error( __METHOD__, 'Post restoring failed', $id );
		}
	}

	/**
	 * Empties the trash.
	 *
	 * @param string $type The type of the post.
	 */
	public function emptyTrash( string $type ): void {
		$ps = $this->_getPosts( [ 'type' => $type, 'status' => '_trash' ] );
		foreach ( $ps as $p ) {
			$pd = $this->getPostDir( $p->getId(), null );
			self::deleteAll( rtrim( $pd, '/' ) );
		}
	}

	/**
	 * Empties the temporary directories.
	 *
	 * @param string $type The type of the post.
	 */
	public function emptyTemporaryDirectories( string $type ): void {
		global $nt_session;
		$temps = $nt_session->listTemporaryDirectories();

		$ds = $this->_getSubPaths( $type );
		foreach ( $ds as $d ) {
			$dir =  $this->_dirRoot . $d;
			if ( ! is_dir( $dir ) ) {
				continue;
			}
			$fns = scandir( $dir );
			if ( false === $fns ) {
				continue;
			}
			foreach ( $fns as $fn ) {
				if ( $fn !== '.' && $fn !== '..' && $fn[0] === '_' ) {
					$t = "$dir$fn/";
					if ( is_dir( $t ) && ! in_array( $t, $temps, true ) ) {
						self::deleteAll( rtrim( $t, '/' ) );
					}
				}
			}
		}
	}

	/**
	 * Deletes all files and directories in the specified directory.
	 *
	 * @param string $dir The directory to delete.
	 */
	static public function deleteAll( string $dir ): void {
		$dir = rtrim( $dir, '/' );
		if ( ! is_dir( $dir ) ) {
			Logger::error( __METHOD__, 'The post directory does not exist', $dir );
			return;
		}
		$fns = scandir( $dir );
		if ( is_array( $fns ) ) {
			foreach ( $fns as $fn ) {
				if ( $fn !== '.' && $fn !== '..' ) {
					if ( is_dir( "$dir/$fn" ) ) {
						self::deleteAll( "$dir/$fn" );
					} else {
						unlink( "$dir/$fn" );
					}
				}
			}
		}
		rmdir( $dir );
	}

}
