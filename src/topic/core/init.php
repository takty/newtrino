<?php
namespace nt;
/**
 *
 * Init
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @version 2018-10-17
 *
 */


require_once(__DIR__ . '/lang.php');
require_once(__DIR__ . '/function.php');
require_once(__DIR__ . '/tag.php');
require_once(__DIR__ . '/../private/php/Store.php');


reject_direct_access(__FILE__, 2);

define('POST_PATH', __DIR__ . '/../post/');
define('SERVER_HOST_URL', (empty($_SERVER['HTTPS']) ? 'http://' : 'https://') . $_SERVER['HTTP_HOST']);
$purl = SERVER_HOST_URL . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . '/post/';
define('POST_URL', $purl);

setLocaleSetting();
prepareDefaultQuery(['id' => '', 'page' => '1', 'cat' => '', 'date' => '', 'search_word' => '', 'new_day' => 7]);

$store = new Store(POST_PATH, POST_URL);

if (!empty($q['id'])) {
	$ret = $store->getPostWithNextAndPrevious($q['id'], ['cat' => $q['cat'], 'date' => $q['date'], 'search_word' => $q['search_word']]);

	global $nt_prev_post, $nt_next_post, $nt_post;
	if ($ret === false) {
		$nt_post = false;
	} else {
		$nt_prev_post = $ret[0];
		$nt_post      = $ret[1];
		$nt_next_post = $ret[2];
	}
} else if (!empty($q['page'])) {
	$ppp = defined('NT_POSTS_PER_PAGE') ? NT_POSTS_PER_PAGE : 10;
	$ret = $store->getPostsByPage($q['page'] - 1, $ppp, ['cat' => $q['cat'], 'date' => $q['date'], 'search_word' => $q['search_word']], 7);

	global $nt_posts, $nt_size, $nt_page;
	$nt_posts = $ret['posts'];
	$nt_size  = $ret['size'];
	$nt_page  = $ret['page'];
}
