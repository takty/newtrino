<?php
namespace nt;
/**
 *
 * Store
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @version 2020-06-25
 *
 */


require_once(__DIR__ . '/compat.php');
require_once(__DIR__ . '/class-logger.php');
require_once(__DIR__ . '/class-indexer.php');
require_once(__DIR__ . '/class-post.php');
require_once(__DIR__ . '/class-taxonomy.php');


class Store {

	static public function dateToNumber($date) {
		if (mb_eregi("^(\d+)[/|-](.*)", $date, $regs)) {
			$ret = sprintf('%04d', $regs[1]);;
			$lv = $regs[2];
			if (mb_eregi("^(\d+)[/|-](.*)", $lv, $regs)) {
				$ret .= sprintf('%02d%02d', $regs[1], $regs[2]);
			} else {
				$ret .= sprintf('%02d', $lv);
			}
			return $ret;
		}
		return $date;
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

	// ------------------------------------------------------------------------

	public function __construct($urlPost, $dirPost, $dirData, $conf, $urlPrivate = false) {
		$this->_urlPost = $urlPost;
		$this->_dirPost = $dirPost;
		$this->_dirData = $dirData;

		$this->_conf = $conf;
		$this->_urlPrivate = $urlPrivate;  // Only In Private Area

		$this->_taxonomy = new Taxonomy( $dirData, [ 'lang' => 'en' ] );
	}

	public function taxonomy() { return $this->_taxonomy; }

	private function createPost($id) {
		return new Post($this->_urlPost, $id, $this->_urlPrivate);
	}

	// ------------------------------------------------------------------------

	public function getPost($id) {
		$post = $this->createPost($id);
		if (!$post->load($this->_dirPost)) return false;
		$post->setCategoryName( $this->taxonomy()->getTermLabel( 'category', $post->getCategory() ) );

		$pd = date('YmdHis') - $post->getDateTimeNumber();
		if ($this->_conf['newly_arrived_day'] > 0) {
			$post->setNewItem($pd < $this->_conf['newly_arrived_day'] * 1000000);
		}
		return $post;
	}

	public function getPostWithNextAndPrevious($id, $cond = []) {
		$posts = $this->_getPostsByCond($cond);
		$idIndex = null;
		for ($i = 0; $i < count($posts); $i += 1) {
			$p = $posts[$i];
			if ($p->getId() === $id) {
				$idIndex = $i;
				break;
			}
		}
		if ($idIndex === null) return false;

		$prev = ($idIndex > 0) ? $posts[$idIndex - 1] : null;
		$next = ($idIndex < count($posts) - 1) ? $posts[$idIndex + 1] : null;
		return [$prev, $posts[$idIndex], $next];
	}

	public function getPosts($start, $count, $cond = []) {
		$posts = $this->_getPostsByCond($cond);

		$size = count($posts);
		$start = intval($start);
		$count = intval($count);

		if ($size < $start) {
			$start = 0;
		}
		$ret = array_slice($posts, $start, 0 < $count ? $count : NULL);
		return ['posts' => $ret, 'size' => $size, 'start' => $start];
	}

	public function getPostsByPage($page, $count, $cond = []) {
		$posts = $this->_getPostsByCond($cond);

		$size = count($posts);
		$page = intval($page);
		$count = intval($count);
		$start = $count * $page;

		if ($size < $start) {
			$start = 0;
			$page = 0;
		}
		$ret = array_slice($posts, $start, 0 < $count ? $count : NULL);
		return ['posts' => $ret, 'size' => $size, 'page' => $page];
	}

	private function _getPostsByCond($cond) {
		$cond += ['cat' => '', 'date' => '', 'date_bgn' => '', 'date_end' => '', 'published_only' => true, 'search_word' => '', 'omit_finished_event' => false];
		$cat = $cond['cat'];
		$date = self::dateToNumber($cond['date']);
		$date_bgn = self::dateToNumber($cond['date_bgn']);
		$date_end = self::dateToNumber($cond['date_end']);
		$published_only = $cond['published_only'];
		$searchWord = $cond['search_word'];

		$posts = $this->_loadPostsAll($published_only);
		if (!empty($date_bgn) || !empty($date_end)) {
			$posts = $this->_filterPostsByDateRange($posts, $date_bgn, $date_end);
		} else if (!empty($date)) {
			$posts = $this->_filterPostsByDate($posts, $date);
		}
		if (!empty($cat)) {
			$temp = [];
			foreach ($posts as $p) {
				// TODO Allow multiple categories to be specified
				if ($cat[0] === '-') {
					if ($p->getCategory() !== substr($cat, 1)) {
						if ($cond['omit_finished_event'] && $p->getEventState() === Post::EVENT_STATE_FINISHED) continue;
						$temp[] = $p;
					}
				} else {
					if ($p->getCategory() === $cat) {
						if ($cond['omit_finished_event'] && $p->getEventState() === Post::EVENT_STATE_FINISHED) continue;
						$temp[] = $p;
					}
				}
			}
			$posts = $temp;
		}
		if ($this->_conf['newly_arrived_day'] > 0) {
			$now = date('YmdHis');
			foreach ($posts as $p) {
				$pd = $now - $p->getDateTimeNumber();
				$p->setNewItem($pd < $this->_conf['newly_arrived_day'] * 1000000);
			}
		}
		usort($posts, '\nt\Post::compareDate');
		if (!empty($searchWord)) {
			$posts = $this->_filterBySearchWord($posts, $searchWord);
		}
		return $posts;
	}

	private function _filterPostsByDateRange($posts, $dateStrBgn, $dateStrEnd) {
		$dateStrBgn = str_pad($dateStrBgn, 14, '0');
		$dateStrEnd = str_pad($dateStrEnd, 14, '9');
		$ret = [];
		foreach ($posts as $post) {
			$dn = $post->getDateTimeNumber();
			if ($dateStrBgn <= $dn && $dn <= $dateStrEnd) {
				$ret[] = $post;
			}
		}
		return $ret;
	}

	private function _filterPostsByDate($posts, $dateStr) {
		$ret = [];
		foreach ($posts as $post) {
			$dn = $post->getDateTimeNumber();
			if (strpos($dn, $dateStr) === 0) {
				$ret[] = $post;
			}
		}
		return $ret;
	}

	private function _filterBySearchWord($posts, $searchQuery) {
		$ws = Indexer::segmentSearchQuery($searchQuery);
		$ret = [];
		foreach ($posts as $p) {
			if ($p->updateIndexScore($ws) > 0) $ret[] = $p;
		}
		usort($ret, ['\nt\Post', 'compareIndexScore']);
		return $ret;
	}

	private function _loadPostsAll($published_only) {
		$posts = [];
		if ($dir = opendir($this->_dirPost)) {
			while (($fn = readdir($dir)) !== false) {
				if (strpos($fn, '.') !== 0 && is_dir($this->_dirPost . $fn)) {
					$post = $this->createPost($fn);
					$post->load($this->_dirPost);
					$post->setCategoryName( $this->taxonomy()->getTermLabel( 'category', $post->getCategory() ) );
					if (!$published_only || $post->isPublished()) $posts[] = $post;
				}
			}
			closedir($dir);
		}
		return $posts;
	}

	// ------------------------------------------------------------------------

	public function getCountByDate($curDate = '', $published_only = true) {
		$posts = $this->_loadPostsAll($published_only);
		$arr = [];
		foreach ($posts as $post) {
			if ($post->isPublished()) {
				$key = substr($post->getDateTimeNumber(), 0, 6);  // $key is integer
				if (!isset($arr[$key])) {
					$arr[$key] = 1;
				} else {
					$arr[$key]++;
				}
			}
		}
		$ret = [];
		foreach ($arr as $key => $val) {
			$name = substr($key, 0, 4) . '-' . substr($key, 4, 2);
			$ret[] = ['date' => $key, 'count' => $val, 'name' => $name, 'cur' => (intval($curDate) === $key)];
		}
		return $ret;
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
			$post = $this->createPost('.' . $id);
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

}
