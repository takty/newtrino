<?php
namespace nt;
/**
 *
 * Init
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @version 2018-10-19
 *
 */


require_once(__DIR__ . '/define.php');
require_once(__DIR__ . '/function.php');
require_once(__DIR__ . '/tag.php');
require_once(__DIR__ . '/class-store.php');

reject_direct_access(NT_URL_HOST, __FILE__, 2);
set_locale_setting();

$nt_config = load_config(NT_DIR_DATA);
$nt_res    = load_resource(NT_DIR_DATA, $nt_config['language']);
$nt_q      = prepare_query(['id' => '', 'page' => '1', 'cat' => '', 'date' => '', 'search_word' => '', 'new_day' => 7]);
$nt_store  = new Store(NT_URL_POST, NT_DIR_POST, NT_DIR_DATA, $nt_config);

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
	$ret = $nt_store->getPostsByPage($nt_q['page'] - 1, $nt_config['posts_per_page'], ['cat' => $nt_q['cat'], 'date' => $nt_q['date'], 'search_word' => $nt_q['search_word']]);

	global $nt_posts, $nt_size, $nt_page;
	$nt_posts = $ret['posts'];
	$nt_size  = $ret['size'];
	$nt_page  = $ret['page'];
}
