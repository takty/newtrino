<?php
namespace nt;
/**
 *
 * Store
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @version 2020-07-03
 *
 */


require_once(__DIR__ . '/class-logger.php');
require_once(__DIR__ . '/class-indexer.php');
require_once(__DIR__ . '/class-post.php');
require_once(__DIR__ . '/class-type.php');
require_once(__DIR__ . '/class-taxonomy.php');
require_once(__DIR__ . '/class-query.php');


class Store {

	public function __construct( string $urlPost, string $dirPost, string $dirData, array $conf ) {
		$this->_urlPost = $urlPost;
		$this->_dirPost = $dirPost;
		$this->_conf    = $conf;

		$this->_dirRoot = NT_DIR;
		$this->_dirUrl  = NT_URL;

		$this->_type     = new Type( $dirData, $conf );
		$this->_taxonomy = new Taxonomy( $dirData, $conf );
	}

	public function type()     { return $this->_type; }
	public function taxonomy() { return $this->_taxonomy; }


	// ------------------------------------------------------------------------


	public function getPostDir( $id, $subPath ) {
		return $this->_dirRoot . $subPath . $id . '/';
	}

	public function getPostUrl( $id, $subPath ) {
		return $this->_dirUrl . $subPath . $id . '/';
	}

	public function createSubPath( $type ) {

	}


	// ------------------------------------------------------------------------


	public function getPost( string $id ) {
		$post = new Post( $this->_urlPost, $id );
		if ( ! $post->load() ) return false;
		return $post;
	}

	public function getPostWithNextAndPrevious( string $id, $cond = [] ) {
		$posts = $this->_getPosts( $cond );
		$idIndex = null;
		for ($i = 0; $i < count( $posts ); $i += 1 ) {
			$p = $posts[ $i ];
			if ( $p->getId() === $id ) {
				$idIndex = $i;
				break;
			}
		}
		if ( $idIndex === null ) return false;

		$prev = ( $idIndex > 0 ) ? $posts[ $idIndex - 1 ] : null;
		$next = ( $idIndex < count( $posts ) - 1) ? $posts[ $idIndex + 1 ] : null;
		return [ $prev, $posts[ $idIndex ], $next ];
	}

	public function getPosts( $cond = [] ) {
		$posts_per_page = isset( $cond['posts_per_page'] ) ? $cond['posts_per_page'] : $this->_conf['posts_per_page'];
		$page = isset( $cond['page'] ) ? $cond['page'] : 1;

		$posts = $this->_getPosts( $cond );

		$size    = count( $posts );
		$pageIdx = intval( $page ) - 1;
		$ppp     = intval( $posts_per_page );
		$offset  = $ppp * $pageIdx;

		if ( $size < $offset ) {
			$offset  = 0;
			$pageIdx = 0;
		}
		$ret = array_slice( $posts, $offset, 0 < $ppp ? $ppp : NULL );
		return ['posts' => $ret, 'size' => $size, 'page' => $pageIdx + 1, 'page_count' => ceil( $size / $ppp ) ];
	}

	public function getCountByDate( $type = 'year' ) {
		$ms = [];
		$this->_loadMatchedInfoAll( $this->_dirRoot, 'post/', [], $ms );

		$digit = 4;
		switch ( $type ) {
			case 'year':  $digit = 4; break;
			case 'month': $digit = 6; break;
			case 'day':   $digit = 8; break;
		}
		$count = [];
		foreach ( $ms as $m ) {
			$date = $m['meta']['date'];
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


	private function _getPosts( $args ) {
		$args += [
			'post_type' => 'post',  // reserved
			'status'    => Post::STATUS_PUBLISHED,
		];
		$posts = [];
		$this->_loadMatchedPostAll( $this->_dirRoot, 'post/', $args, $posts );

		usort( $posts, '\nt\Post::compareDate' );
		if ( ! empty( $args['search'] ) ) {
			usort( $posts, '\nt\Post::compareIndexScore' );
		}
		return $posts;
	}

	private function _loadMatchedPostAll( $root, $path, $args, &$posts = [] ) {
		$ret = [];
		$this->_loadMatchedInfoAll( $root, $path, $args, $ret );
		foreach ( $ret as $m ) {
			$p = new Post( $m['path'], $m['id'], $m['subPath'] );
			$p->load( $m['info'] );
			$posts[] = $p;
		}
	}

	private function _loadMatchedInfoAll( $root, $path, $args, &$ret = [] ) {
		$query = new Query( $args );
		if ( $dir = opendir( $root . $path ) ) {
			while ( ( $fn = readdir( $dir ) ) !== false ) {
				if ( strpos( $fn, '.' ) === 0 || is_file( $root . $path . $fn ) ) continue;
				if ( strlen( $fn ) === 4 ) {
					$this->_loadMatchedInfoAll( $root, "$path$fn/", $args, $ret );
					continue;
				}
				$info = $this->_loadInfo( $root . $path . $fn );
				if ( $query->match( $info, "$root$path$fn/" . Post::WORD_FILE_NAME ) ) {
					$ret[] = [ 'path' => $path, 'id' => $fn, 'info' => $info, 'subPath' => $path ];
				}
			}
			closedir( $dir );
		}
	}

	private function _loadInfo( $path ) {
		$info_path = $path . '/' . Post::INFO_FILE_NAME;
		try {
			$json = file_get_contents( $info_path );
		} catch ( Error $e ) {
			$json = false;
		}
		if ( $json === false ) {
			Logger::output( "Error (Post::_loadInfo file_get_contents) [$info_path]" );
			return null;
		}
		return json_decode( $json, true );
	}


	// ------------------------------------------------------------------------


	public function createNewPost( string $type = 'post' ) {
		if ( $dir = opendir( $this->_dirPost ) ) {
			$date = date( 'YmdHis' );
			$id = $date;
			flock( $dir, LOCK_EX );
			if ( $this->_checkIdExists( $id ) ) {
				for ( $i = 1; $i < 10; $i += 1 ) {
					$id = $date . '_' . $i;
					if ( ! $this->_checkIdExists( $id ) ) break;
				}
			}
			if ( $this->_checkIdExists( $id ) ) return false;  // when the loop finished without break
			$post = new Post( '.', $id );
			$post->setType( $type );
			$post->setDate();
			$post->save();
			flock( $dir, LOCK_UN );
			return $post;
		}
		return false;
	}

	private function _checkIdExists( $id ) {
		return file_exists( $this->_dirPost . $id ) || file_exists( $this->_dirPost . '.' . $id );
	}


	// ------------------------------------------------------------------------


	public function writePost( $post ) {
		$post->save();
		if ( strpos( $post->getId(), '.' ) === 0 ) {
			$newId = substr( $post->getId(), 1 );
			rename( $this->_dirPost . $post->getId(), $this->_dirPost . $newId );
			$post->setId( $newId );
			$post->save();
		}
		return $post;
	}

	public function delete( $id ) {
		// We plan to add Trash function
		$pdir = $this->_dirPost . $id;
		self::deleteAll( $pdir );
	}

	static public function deleteAll( $dir ) {
		if ( ! file_exists( $dir ) ) {
			Logger::output( 'File Does Not Exist (Store::deleteAll file_exists) [' . $dir . ']' );
			return false;
		}
		if ( $handle = opendir( $dir ) ) {
			while ( false !== ( $item = readdir( $handle ) ) ) {
				if ( $item !== '.' && $item !== '..' ) {
					if ( is_dir( $dir . '/' . $item ) ) {
						self::deleteAll( $dir . '/' . $item );
					} else {
						unlink( $dir . '/' . $item );
					}
				}
			}
			closedir( $handle );
			rmdir( $dir );
		}
	}

}
