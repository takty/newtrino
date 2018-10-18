<?php
namespace nt;
/**
 *
 * Init
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @version 2018-10-18
 *
 */


if (!defined('NT_LANG')) define('NT_LANG', 'en');

require_once(__DIR__ . '/define.php');
require_once(__DIR__ . '/function.php');
require_once(__DIR__ . '/tag.php');
require_once(__DIR__ . '/class-store.php');

reject_direct_access(__FILE__, 2);

define('SERVER_HOST_URL', (empty($_SERVER['HTTPS']) ? 'http://' : 'https://') . $_SERVER['HTTP_HOST']);
define('POST_URL', SERVER_HOST_URL . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . '/post/');

setLocaleSetting();
loadResource();

$nt_q     = prepareDefaultQuery(['id' => '', 'page' => '1', 'cat' => '', 'date' => '', 'search_word' => '', 'new_day' => 7]);
$nt_store = new Store(POST_URL);

if (!empty($nt_q['id'])) {
	$ret = $nt_store->getPostWithNextAndPrevious($nt_q['id'], ['cat' => $nt_q['cat'], 'date' => $nt_q['date'], 'search_word' => $nt_q['search_word']]);

	global $nt_prev_post, $nt_next_post, $nt_post;
	if ($ret === false) {
		$nt_post = false;
	} else {
		$nt_prev_post = $ret[0];
		$nt_post      = $ret[1];
		$nt_next_post = $ret[2];
	}
} else if (!empty($nt_q['page'])) {
	$ppp = defined('NT_POSTS_PER_PAGE') ? NT_POSTS_PER_PAGE : 10;
	$ret = $nt_store->getPostsByPage($nt_q['page'] - 1, $ppp, ['cat' => $nt_q['cat'], 'date' => $nt_q['date'], 'search_word' => $nt_q['search_word']], 7);

	global $nt_posts, $nt_size, $nt_page;
	$nt_posts = $ret['posts'];
	$nt_size  = $ret['size'];
	$nt_page  = $ret['page'];
}
