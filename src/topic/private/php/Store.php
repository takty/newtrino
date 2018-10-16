<?php
namespace nt;
/**
 *
 * Store
 *
 * @author Space-Time Inc.
 * @version 2018-10-16
 *
 */


require_once(__DIR__ . '/Logger.php');
require_once(__DIR__ . '/Indexer.php');
require_once(__DIR__ . '/Post.php');


class Store {

	static function dateToNumber($date) {
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

	static function deleteAll($dir) {
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

	function __construct($postPath, $postUrl, $dataPath) {
		$this->_postPath = $postPath;
		$this->_postUrl = $postUrl;
		$this->_dataPath = $dataPath;
	}

	// ------------------------------------------------------------------------

	public function getPost($id, $newDay = 0) {
		$post = new Post($this->_postUrl, $id);
		if (!$post->load($this->_postPath)) return false;
		$post->setCategoryName($this->categorySlugToName($post->getCategory()));
		$pd = date('YmdHis') - $post->getDateTimeNumber();
		if ($newDay > 0) $post->setNewItem($pd < $newDay * 1000000);
		return $post;
	}

	public function getPostWithNextAndPrevious($id, $cond = [], $newDay = 0) {
		$posts = $this->_getPostsByCond($cond, $newDay);
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

	public function getPosts($start, $count, $cond = [], $newDay = 0, $omitFinishedEvent = false) {
		$posts = $this->_getPostsByCond($cond, $newDay, $omitFinishedEvent);

		$size = count($posts);
		$start = intval($start);
		$count = intval($count);

		if ($size < $start) {
			$start = 0;
		}
		$ret = array_slice($posts, $start, 0 < $count ? $count : NULL);
		return ['posts' => $ret, 'size' => $size, 'start' => $start];
	}

	public function getPostsByPage($page, $count, $cond = [], $newDay = 0) {
		$posts = $this->_getPostsByCond($cond, $newDay);

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

	private function _getPostsByCond($cond, $newDay = 0, $omitFinishedEvent = false) {
		$cond += ['cat' => '', 'date' => '', 'date_bgn' => '', 'date_end' => '', 'published_only' => true, 'search_word' => ''];
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
						if ($omitFinishedEvent && $p->getEventState() === Post::EVENT_STATE_FINISHED) continue;
						$temp[] = $p;
					}
				} else {
					if ($p->getCategory() === $cat) {
						if ($omitFinishedEvent && $p->getEventState() === Post::EVENT_STATE_FINISHED) continue;
						$temp[] = $p;
					}
				}
			}
			$posts = $temp;
		}
		if ($newDay > 0) {
			$now = date('YmdHis');
			foreach ($posts as $p) {
				$pd = $now - $p->getDateTimeNumber();
				$p->setNewItem($pd < $newDay * 1000000);
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
		usort($ret, ['Post', 'compareIndexScore']);
		return $ret;
	}

	private function _loadPostsAll($published_only) {
		$posts = [];
		if ($dir = opendir($this->_postPath)) {
			while (($fn = readdir($dir)) !== false) {
				if (strpos($fn, '.') !== 0 && is_dir($this->_postPath . $fn)) {
					$post = new Post($this->_postUrl, $fn);
					$post->load($this->_postPath);
					$post->setCategoryName($this->categorySlugToName($post->getCategory()));
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

	public function getCategoryData($curCat = '') {
		if (empty($curCat)) return $this->_loadCategoryData();
		$data = [];
		foreach ($this->_catData as $a) {
			$data[] = ['slug' => $a['slug'], 'name' => $a['name'], 'cur' => ($a['slug'] === $curCat)];
		}
		return $data;
	}

	public function categorySlugToName($slug) {
		$catName = '';
		$cd = $this->_loadCategoryData();
		foreach ($cd as $c) {
			if ($c['slug'] === $slug) return $c['name'];
		}
		return '';
	}

	private function _loadCategoryData() {
		if (isset($this->_catData)) return $this->_catData;
		$path = $this->_dataPath . 'category';
		$lines = @file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		if ($lines === false) {
			Logger::output('Error (Store::getCategoryData file) [' . $path . ']');
			return false;
		}
		$data = [];
		foreach ($lines as $line) {
			$a = explode("\t", $line);
			$data[] = ['slug' => $a[0], 'name' => $a[1], 'cur' => false];
		}
		return $this->_catData = $data;
	}

	// ------------------------------------------------------------------------

	public function createNewPost() {
		if ($dir = opendir($this->_postPath)) {
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
			$post = new Post($this->_postUrl, '.' . $id);
			$post->setPublishedDate('now');
			$post->save($this->_postPath);
			flock($dir, LOCK_UN);
			return $post;
		}
		return false;
	}

	private function _checkIdExists($id) {
		return file_exists($this->_postPath . $id) || file_exists($this->_postPath . '.' . $id);
	}

	// ------------------------------------------------------------------------

	public function writePost($post) {
		$post->save($this->_postPath);
		if (strpos($post->getId(), '.') === 0) {
			$newId = substr($post->getId(), 1);
			rename($this->_postPath . $post->getId(), $this->_postPath . $newId);
			$post->setId($newId);
			$post->save($this->_postPath);
		}
		return $post;
	}

	public function delete($id) {
		// We plan to add Trash function
		$pdir = $this->_postPath . $id;
		self::deleteAll($pdir);
	}

}
