<?php
/**
 * 
 * Index
 * 
 * @author Space-Time Inc.
 * @version 2018-10-15
 *
 */


require_once(dirname(__FILE__) . '/_init.php');
require_once(dirname(__FILE__) . '/_navigation.php');


const PPP = 10, NEW_DAY = 7;
$ret = $store->getPostsByPage($q['page'] - 1, PPP, ['cat' => $q['cat'], 'date' => $q['date'], 'search_word' => $q['search_word']], NEW_DAY);

$t_posts = $ret['posts'];
$t_pUrl = 'view.php?id=<>' . query_str($q, ['page', 'date', 'cat', 'search_word']);

$t_cur = $ret['page'] + 1;
$t_size = $ret['size'];
$t_pgUrl = 'index.php?page=<>' . query_str($q, ['date', 'cat', 'search_word']);

$t_dates = $store->getCountByDate($q['date']);
$t_cats = $store->getCategoryData($q['cat']);
$t_searchQuery = $q['search_word'];




header('Content-Type: text/html;charset=utf-8');
$PAGE_TITLE = L_TOPIC_LIST;
$PAGE_CLASS = 'nt-index';
include('../part/header.php');
?>
	<section class="nt-topics">
		<h2><?=_h(L_TOPIC_LIST)?></h2>
		<ul class="nt-topic-list">
<?php foreach($t_posts as $p): ?>
			<li<?php if ($p->isNewItem()) _eh(' class="new"')?>>
				<span class="date"><?=_h($p->getPublishedDate())?></span>
				<a href="<?=str_replace('<>', $p->getId(), $t_pUrl) ?>"><?=_h($p->getTitle())?></a>
				<span class="cat<?php if ($p->getCategory() === 'event') _eh(' ' . $p->getEventState()); ?>"><?=_h($p->getCategoryName())?></span>
			</li>
<?php endforeach ?>
		</ul>
<?php the_pagination($t_pgUrl, $t_size, $t_cur, PPP); ?>
	</section>
<?php the_filter($t_dates, $t_cats, $t_searchQuery); ?>
	</main><!-- site-main -->
	<footer class="site-footer">
	</footer>
</div><!-- site -->
</body>
</html>
