<?php
namespace nt;
/**
 *
 * Store
 *
 * @author Takuto Yanagida
 * @version 2021-06-25
 *
 */


require_once( __DIR__ . '/class-logger.php' );
require_once( __DIR__ . '/class-indexer.php' );
require_once( __DIR__ . '/class-post.php' );
require_once( __DIR__ . '/class-type.php' );
require_once( __DIR__ . '/class-taxonomy.php' );
require_once( __DIR__ . '/class-query.php' );


class Store {

	public function __construct( string $ntUrl, string $ntDir, string $dataDir, array $conf ) {
		$this->_dirUrl  = $ntUrl;
		$this->_dirRoot = $ntDir;
		$this->_conf    = $conf;

		$this->_type     = new Type( $dataDir, $conf );
		$this->_taxonomy = new Taxonomy( $dataDir, $conf );
	}

	public function type()    : Type     { return $this->_type; }
	public function taxonomy(): Taxonomy { return $this->_taxonomy; }


	// ------------------------------------------------------------------------


	public function getPostUrl( string $id, ?string $subPath ): string {
		if ( $subPath === null ) $subPath = $this->getSubPath( $id );
		return $this->_dirUrl . $subPath . $id . '/';
	}

	public function getPostDir( string $id, ?string $subPath ): string {
		if ( $subPath === null ) $subPath = $this->getSubPath( $id );
		return $this->_dirRoot . $subPath . $id . '/';
	}

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

	public function getSubPath( string $id ): ?string {
		$ds = $this->_getSubPaths();
		foreach ( $ds as $d ) {
			if ( is_dir( $this->_dirRoot . $d . $id ) ) return $d;
		}
		return null;
	}

	private function _getSubPaths( ?string $type = null ): array {
		$typeDirs = $type ? [ "$type/" ] : $this->_type->getTypeDirAll();
		if ( ! $this->_conf['archive_by_year'] ) return $typeDirs;

		$ret = [];
		foreach ( $typeDirs as $t ) {
			if ( ! is_dir( $this->_dirRoot . $t ) ) continue;
			$dir = dir( $this->_dirRoot . $t );
			while ( false !== ( $fn = $dir->read() ) ) {
				if ( $fn[0] === '.' || $fn[0] === '_' || $fn[0] === '-' ) continue;
				if ( preg_match( '/[^0-9]/', $fn ) ) continue;
				if ( strlen( $fn ) !== 4 ) continue;
				if ( is_file( $t . $fn ) ) continue;
				$ret[] = $t . $fn . '/';
			}
			$dir->close();
		}
		$ret = array_merge( $ret, $typeDirs );  // For option compatibility
		return $ret;
	}


	// ------------------------------------------------------------------------


	public function getPost( ?string $id ): ?Post {
		if ( $id === null ) return null;
		$p = new Post( $id, $this->getSubPath( $id, null ) );
		if ( ! $p->load() ) return null;
		return $p;
	}

	public function getPostWithNextAndPrevious( ?string $id, array $cond = [] ): ?array {
		if ( $id === null ) return null;
		$posts = $this->_getPosts( $cond );
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

	public function getPosts( array $cond = [] ): array {
		$page = $cond['page'] ?? 1;

		$posts = $this->_getPosts( $cond );

		$size    = count( $posts );
		$pageIdx = intval( $page ) - 1;
		$perPage = intval( $cond['per_page'] ?? $this->_conf['per_page'] );
		$offset  = $perPage * $pageIdx;

		if ( $size < $offset ) {
			$offset  = 0;
			$pageIdx = 0;
		}
		$ret = array_slice( $posts, $offset, 0 < $perPage ? $perPage : NULL );
		return ['posts' => $ret, 'size' => $size, 'page' => $pageIdx + 1, 'page_count' => ceil( $size / $perPage ) ];
	}

	public function getCountByDate( string $type = 'year', array $args ): array {
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
		$ret = [];
		foreach ( $count as $key => $val ) {
			$ret[] = [ 'slug' => $key, 'count' => $val ];
		}
		return $ret;
	}


	// -------------------------------------------------------------------------


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

	private function _loadMatchedInfoAll( string $root, array $paths, array $args, array &$ret = [] ): void {
		$is_trash = ( isset( $args['status'] ) && $args['status'] === '_trash' );
		if ( $is_trash ) unset( $args['status'] );
		$query = new Query( $args );
		$this->_loadMatchedInfoAllInternal( $root, $paths, $query, $is_trash, $ret );
	}

	private function _loadMatchedInfoAllInternal( string $root, array $paths, Query $query, bool $isTrash, array &$ret = [] ): void {
		foreach ( $paths as $path ) {
			if ( ! is_dir( $root . $path ) ) continue;
			$dir = dir( $root . $path );
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

	private function _loadInfo( string $postDir, string $pid ): ?array {
		$infoPath = $postDir . '/' . Post::INFO_FILE_NAME;
		$json     = false;
		try {
			if ( file_exists( $infoPath ) ) {
				$json = file_get_contents( $infoPath );
			}
		} catch ( Error $e ) {
			// Do nothing
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

	private function _ensureUniquePostId( string $archPath, string $dateRaw ): ?string {
		$id = $dateRaw;
		if ( ! $this->_checkIdExists( $archPath, $id ) ) return $id;
		for ( $i = 1; $i < 10; $i += 1 ) {
			$id = $dateRaw . '_' . $i;
			if ( ! $this->_checkIdExists( $archPath, $id ) ) return $id;
		}
		return null;
	}

	private function _checkIdExists( string $archPath, string $id ): bool {
		return
			file_exists( $archPath       . $id ) ||
			file_exists( $archPath . '_' . $id ) ||
			file_exists( $archPath . '-' . $id );
	}


	// ------------------------------------------------------------------------


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

	public function emptyTrash( string $type ): void {
		$ps = $this->_getPosts( [ 'type' => $type, 'status' => '_trash' ] );
		foreach ( $ps as $p ) {
			$pd = $this->getPostDir( $p->getId(), null );
			self::deleteAll( rtrim( $pd, '/' ) );
		}
	}

	public function emptyTemporaryDirectories( string $type ): void {
		global $nt_session;
		$temps = $nt_session->listTemporaryDirectories();

		$ds = $this->_getSubPaths( $type );
		foreach ( $ds as $d ) {
			$dir =  $this->_dirRoot . $d;
			if ( ! is_dir( $dir ) ) continue;
			foreach ( scandir( $dir ) as $fn ) {
				if ( $fn !== '.' && $fn !== '..' && $fn[0] === '_' ) {
					$t = "$dir$fn/";
					if ( is_dir( $t ) && ! in_array( $t, $temps, true ) ) {
						self::deleteAll( rtrim( $t, '/' ) );
					}
				}
			}
		}
	}

	static public function deleteAll( string $dir ): void {
		$dir = rtrim( $dir, '/' );
		if ( ! is_dir( $dir ) ) {
			Logger::error( __METHOD__, 'The post directory does not exist', $dir );
			return;
		}
		foreach ( scandir( $dir ) as $fn ) {
			if ( $fn !== '.' && $fn !== '..' ) {
				if ( is_dir( "$dir/$fn" ) ) {
					self::deleteAll( "$dir/$fn" );
				} else {
					unlink( "$dir/$fn" );
				}
			}
		}
		rmdir( $dir );
	}

}
