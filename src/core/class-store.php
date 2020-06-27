<?php
namespace nt;
/**
 *
 * Store
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @version 2020-06-27
 *
 */


require_once(__DIR__ . '/class-logger.php');
require_once(__DIR__ . '/class-indexer.php');
require_once(__DIR__ . '/class-post.php');
require_once(__DIR__ . '/class-taxonomy.php');
require_once(__DIR__ . '/class-query.php');


class Store {

	public function __construct($urlPost, $dirPost, $dirData, $conf, $urlPrivate = false) {
		$this->_urlPost = $urlPost;
		$this->_dirPost = $dirPost;
		$this->_conf    = $conf;

		$this->_urlPrivate = $urlPrivate;  // Only In Private Area

		$this->_taxonomy = new Taxonomy( $dirData, $this->_conf );
	}

	public function taxonomy() { return $this->_taxonomy; }


	// ------------------------------------------------------------------------


	public function getPost( $id ) {
		$post = new Post( $this->_urlPost, $id, $this->_urlPrivate );
		if (!$post->load( $this->_dirPost ) ) return false;

		$pd = date( 'YmdHis' ) - $post->getDateTimeNumber();
		if ( $this->_conf['newly_arrived_day'] > 0 ) {
			$post->setNewItem( $pd < $this->_conf['newly_arrived_day'] * 1000000 );
		}
		return $post;
	}

	public function getPostWithNextAndPrevious($id, $cond = []) {
		$posts = $this->_getPosts( $cond );
		$idIndex = null;
		for ($i = 0; $i < count($posts); $i += 1) {
			$p = $posts[$i];
			if ( $p->getId() === $id ) {
				$idIndex = $i;
				break;
			}
		}
		if ($idIndex === null) return false;

		$prev = ($idIndex > 0) ? $posts[$idIndex - 1] : null;
		$next = ($idIndex < count($posts) - 1) ? $posts[$idIndex + 1] : null;
		return [$prev, $posts[$idIndex], $next];
	}

	public function getPostsByPage( $cond = [] ) {
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
		$this->_loadMatchedMetaAll( $this->_dirPost, [], $ms );

		$digit = 4;
		switch ( $type ) {
			case 'year':  $digit = 4; break;
			case 'month': $digit = 6; break;
			case 'day':   $digit = 8; break;
		}
		$count = [];
		foreach ( $ms as $m ) {
			$date = $m['meta']['published_date'];
			$date = str_replace(['-', '/', ':', ' '], '', $date );

			$key = substr( $date, 0, $digit );
			if ( ! isset( $count[ $key ] ) ) $count[ $key ] = 0;
			$count[ $key ] += 1;
		}
		$ret = [];
		foreach ( $count as $key => $val ) {
			$label = $this->formatDate( $key );
			$ret[] = [ 'slug' => $key, 'label' => $label, 'count' => $val ];
		}
		return $ret;
	}

	public function formatDate( string $dateNum ): string {
		global $nt_config;
		$lang = $nt_config['lang'];
		$dateNum = str_replace( '-', '', $dateNum );
		if ( strpos( $dateNum, ' ' ) !== false ) $dateNum = explode( ' ', $dateNum )[0];
		$len = strlen( $dateNum );

		$fmt = 'Y-m-d';
		if ( $len === 4 ) {
			if      ( isset( $nt_config["date_format_y@$lang"] ) ) $fmt = $nt_config["date_format_y@$lang"];
			else if ( isset( $nt_config["date_format_y"]       ) ) $fmt = $nt_config["date_format_y"];
			else if ( isset( $nt_config["date_format@$lang"]   ) ) $fmt = $nt_config["date_format@$lang"];
			else if ( isset( $nt_config["date_format"]         ) ) $fmt = $nt_config["date_format"];
			$dateNum .= '0101';
		} else if ( $len === 6 ) {
			if      ( isset( $nt_config["date_format_ym@$lang"] ) ) $fmt = $nt_config["date_format_ym@$lang"];
			else if ( isset( $nt_config["date_format_ym"]       ) ) $fmt = $nt_config["date_format_ym"];
			else if ( isset( $nt_config["date_format@$lang"]    ) ) $fmt = $nt_config["date_format@$lang"];
			else if ( isset( $nt_config["date_format"]          ) ) $fmt = $nt_config["date_format"];
			$dateNum .= '01';
		} else if ( $len === 8 ) {
			if      ( isset( $nt_config["date_format_ymd@$lang"] ) ) $fmt = $nt_config["date_format_ymd@$lang"];
			else if ( isset( $nt_config["date_format_ymd"]       ) ) $fmt = $nt_config["date_format_ymd"];
			else if ( isset( $nt_config["date_format@$lang"]     ) ) $fmt = $nt_config["date_format@$lang"];
			else if ( isset( $nt_config["date_format"]           ) ) $fmt = $nt_config["date_format"];
		}
		$nd = preg_replace( '/(\d{4})(\d{2})(\d{2})/', '$1-$2-$3', $dateNum );
		$date = date_create( $nd );
		return $date->format( $fmt );
	}

	// 	$posts = $this->_loadPostsAll($published_only);
	// 	if (!empty($date_bgn) || !empty($date_end)) {
	// 		$posts = $this->_filterPostsByDateRange($posts, $date_bgn, $date_end);
	// 	} else if (!empty($date)) {
	// 		$posts = $this->_filterPostsByDate($posts, $date);
	// 	}
	// 	if (!empty($cat)) {
	// 		$temp = [];
	// 		foreach ($posts as $p) {
	// 			// TODO Allow multiple categories to be specified
	// 			if ($cat[0] === '-') {
	// 				if ( ! $p->hasTerm( 'category', substr( $cat, 1 ) ) ) {
	// 					if ($cond['omit_finished_event'] && $p->getEventState() === Post::EVENT_STATE_FINISHED) continue;
	// 					$temp[] = $p;
	// 				}
	// 			} else {
	// 				if ( $p->hasTerm( 'category', $cat ) ) {
	// 					if ($cond['omit_finished_event'] && $p->getEventState() === Post::EVENT_STATE_FINISHED) continue;
	// 					$temp[] = $p;
	// 				}
	// 			}
	// 		}
	// 		$posts = $temp;
	// 	}
	// 	if ($this->_conf['newly_arrived_day'] > 0) {
	// 		$now = date('YmdHis');
	// 		foreach ($posts as $p) {
	// 			$pd = $now - $p->getDateTimeNumber();
	// 			$p->setNewItem($pd < $this->_conf['newly_arrived_day'] * 1000000);
	// 		}
	// 	}


	// -------------------------------------------------------------------------


	private function _getPosts( $args ) {
		$args += [
			'post_type' => 'post',  // reserved
			'status'    => Post::STATUS_PUBLISHED,
		];
		$posts = [];
		$this->_loadMatchedPostAll( $this->_dirPost, $args, $posts );

		usort( $posts, '\nt\Post::compareDate' );
		if ( ! empty( $args['search'] ) ) {
			usort( $posts, '\nt\Post::compareIndexScore' );
		}
		return $posts;
	}

	private function _loadMatchedPostAll( $path, $args, &$posts = [] ) {
		$ret = [];
		$this->_loadMatchedMetaAll( $path, $args, $ret );
		foreach ( $ret as $m ) {
			$p = new Post( $m['path'], $m['id'], $this->_urlPrivate );
			$p->load( $this->_dirPost, $m['meta'] );
			$posts[] = $p;
		}
	}

	private function _loadMatchedMetaAll( $path, $args, &$ret = [] ) {
		$query = new Query( $args );
		if ( $dir = opendir( $path ) ) {
			while ( ( $fn = readdir( $dir ) ) !== false ) {
				if ( strpos( $fn, '.' ) === 0 || is_file( $path . $fn ) ) continue;
				if ( strlen( $fn ) === 4 ) {
					$this->_loadMatchedMetaAll( "$path$fn/", $args, $ret );
					continue;
				}
				$meta = $this->_loadMeta( $path . $fn );
				if ( $query->match( $meta, "$path$fn/" . Post::WORD_FILE_NAME ) ) {
					$ret[] = [ 'path' => $path, 'id' => $fn, 'meta' => $meta ];
				}
			}
			closedir( $dir );
		}
	}

	private function _loadMeta( $path ) {
		$meta_path = $path . '/' . Post::META_FILE_NAME;
		try {
			$json = file_get_contents( $meta_path );
		} catch ( Error $e ) {
			$json = false;
		}
		if ( $json === false ) {
			Logger::output('Error (Post::_loadMeta file_get_contents) [' . $meta_path . ']');
			return null;
		}
		return json_decode( $json, true );
	}


	// ------------------------------------------------------------------------


	public function createNewPost() {
		if ($dir = opendir($this->_dirPost)) {
			$date = date('YmdHis');
			$id = $date;
			flock($dir, LOCK_EX);
			if ($this->_checkIdExists($id)) {
				for ($i = 1; $i < 10; $i += 1) {
					$id = $date . '_' . $i;
					if (!$this->_checkIdExists($id)) break;
				}
			}
			if ($this->_checkIdExists($id)) return false;  // when the loop finished without break
			$post = new Post( '.', $id );
			$post->setPublishedDate('now');
			$post->save($this->_dirPost);
			flock($dir, LOCK_UN);
			return $post;
		}
		return false;
	}

	private function _checkIdExists($id) {
		return file_exists($this->_dirPost . $id) || file_exists($this->_dirPost . '.' . $id);
	}


	// ------------------------------------------------------------------------


	public function writePost($post) {
		$post->save($this->_dirPost);
		if (strpos($post->getId(), '.') === 0) {
			$newId = substr($post->getId(), 1);
			rename($this->_dirPost . $post->getId(), $this->_dirPost . $newId);
			$post->setId($newId);
			$post->save($this->_dirPost);
		}
		return $post;
	}

	public function delete($id) {
		// We plan to add Trash function
		$pdir = $this->_dirPost . $id;
		self::deleteAll($pdir);
	}

	static public function deleteAll($dir) {
		if (!file_exists($dir)) {
			Logger::output('File Does Not Exist (Store::deleteAll file_exists) [' . $dir . ']');
			return false;
		}
		if ($handle = opendir($dir)) {
			while (false !== ($item = readdir($handle))) {
				if ($item !== '.' && $item !== '..') {
					if (is_dir($dir . '/' . $item)) {
						self::deleteAll($dir . '/' . $item);
					} else {
						unlink($dir . '/' . $item);
					}
				}
			}
			closedir($handle);
			rmdir($dir);
		}
	}

}
