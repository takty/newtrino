<?php
namespace nt;
/**
 *
 * Store
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @version 2020-07-10
 *
 */


require_once(__DIR__ . '/class-logger.php');
require_once(__DIR__ . '/class-indexer.php');
require_once(__DIR__ . '/class-post.php');
require_once(__DIR__ . '/class-type.php');
require_once(__DIR__ . '/class-taxonomy.php');
require_once(__DIR__ . '/class-query.php');


class Store {

	public function __construct( string $ntUrl, string $ntDir, string $dataDir, array $conf ) {
		$this->_dirUrl  = $ntUrl;
		$this->_dirRoot = $ntDir;
		$this->_conf    = $conf;

		$this->_type     = new Type( $dataDir, $conf );
		$this->_taxonomy = new Taxonomy( $dataDir, $conf );
	}

	public function type()     { return $this->_type; }
	public function taxonomy() { return $this->_taxonomy; }


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
			$year = substr( $dateRow, 0, 4 );
			$ret .= "$year/";
		}
		if ( $ensureExistence ) {
			mkdir( $this->_dirRoot . rtrim( $ret, '/' ), 0777, true );
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

	private function _getSubPaths(): array {
		$typeDirs = $this->_type->getTypeDirAll();
		if ( ! $this->_conf['archive_by_year'] ) return $typeDirs;

		$ret = [];
		foreach ( $typeDirs as $t ) {
			$ds = scandir( $this->_dirRoot . $t );
			foreach ( $ds as $d ) {
				if ( $d[0] === '.' ) continue;
				if ( preg_match( '/[^0-9]/', $d ) ) continue;
				if ( is_file( $t . $d ) ) continue;
				$ret[] = $t . $d . '/';
			}
		}
		return $ret;
	}


	// ------------------------------------------------------------------------


	public function getPost( string $id ): ?Post {
		$p = new Post( $id, $this->getSubPath( $id ) );
		if ( ! $p->load() ) return null;
		return $p;
	}

	public function getPostWithNextAndPrevious( string $id, array $cond = [] ): ?array {
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
		$page = isset( $cond['page'] ) ? $cond['page'] : 1;

		$posts = $this->_getPosts( $cond );

		$size    = count( $posts );
		$pageIdx = intval( $page ) - 1;
		$perPage = intval( isset( $cond['per_page'] ) ? $cond['per_page'] : $this->_conf['per_page'] );
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
		$args += [
			'status' => Post::STATUS_PUBLISHED,
		];
		$posts = [];
		$this->_loadMatchedPostAll( $this->_dirRoot, $args, $posts );

		usort( $posts, '\nt\Post::compareDate' );
		if ( ! empty( $args['search'] ) ) {
			usort( $posts, '\nt\Post::compareIndexScore' );
		}
		return $posts;
	}

	private function _loadMatchedPostAll( string $root, array $args, array &$posts = [] ) {
		$paths = $this->_getPostTypeSubDirs( $args );
		$ret = [];
		$this->_loadMatchedInfoAll( $root, $paths, $args, $ret );
		foreach ( $ret as $m ) {
			$p = new Post( $m['id'], $m['subPath'] );
			$p->load( $m['info'] );
			$posts[] = $p;
		}
	}

	private function _getPostTypeSubDirs( array $args ) {
		if ( $this->_conf['archive_by_type'] ) {
			if ( empty( $args['type'] ) ) {
				return $this->_type->getTypeDirAll();
			} else {
				$ats = is_array( $args['type'] ) ? $args['type'] : [ $args['type'] ];
				return array_map( function ( $e ) { return "$e/"; }, $ats );
			}
		}
		return ['post/'];
	}

	private function _loadMatchedInfoAll( string $root, array $paths, array $args, array &$ret = [] ) {
		$query = new Query( $args );

		foreach ( $paths as $path ) {
			$dir = dir( $root . $path );
			while ( false !== ( $fn = $dir->read() ) ) {
				if ( strpos( $fn, '.' ) === 0 || is_file( $root . $path . $fn ) ) continue;
				if ( strlen( $fn ) === 4 ) {
					$this->_loadMatchedInfoAll( $root, ["$path$fn/"], $args, $ret );
					continue;
				}
				$info = $this->_loadInfo( $root . $path . $fn );
				if ( $query->match( $info, "$root$path$fn/" . Post::WORD_FILE_NAME ) ) {
					$ret[] = [ 'id' => $fn, 'subPath' => $path, 'info' => $info ];
				}
			}
			$dir->close();
		}
	}

	private function _loadInfo( string $postDir ): array {
		$infoPath = $postDir . '/' . Post::INFO_FILE_NAME;
		try {
			$json = file_get_contents( $infoPath );
		} catch ( Error $e ) {
			$json = false;
		}
		if ( $json === false ) {
			Logger::output( "Error (Post::_loadInfo file_get_contents) [$infoPath]" );
			return null;
		}
		return json_decode( $json, true );
	}


	// ------------------------------------------------------------------------


	public function createNewPost( string $type = 'post' ): ?Post {
		$dateRaw = date( 'YmdHis' );
		list( $archPath, $subPath ) = $this->createArchAndSubPath( $type, $dateRaw, true );

		if ( $dir = opendir( $archPath ) ) {
			flock( $dir, LOCK_EX );
			$id = $this->_ensureUniquePostId( $archPath, $dateRaw );
			if ( $id === null ) return null;
			$p = new Post( $id, $subPath );
			$p->setType( $type );
			$p->setDate();
			$p->save();
			flock( $dir, LOCK_UN );
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
		return file_exists( $archPath . $id ) || file_exists( $archPath . '.' . $id );
	}


	// ------------------------------------------------------------------------


	public function writePost( Post $post ): Post {
		$post->save();
		if ( strpos( $post->getId(), '.' ) === 0 ) {
			$newId = substr( $post->getId(), 1 );
			$subPath = $post->getSubPath();
			rename( $this->getPostDir( $post->getId(), $subPath ), $this->getPostDir( $newId, $subPath ) );
			$post->setId( $newId );
			$post->save();
		}
		return $post;
	}

	public function delete( string $id ) {
		// TODO Planning to add Trash function
		$pd = $this->getPostDir( $id, null );
		self::deleteAll( rtrim( $pd, '/' ) );
	}

	static public function deleteAll( string $dir ) {
		if ( ! file_exists( $dir ) ) {
			Logger::output( "File Does Not Exist (Store::deleteAll file_exists) [$dir]" );
			return false;
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
