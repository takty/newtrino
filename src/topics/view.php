<?php
/*
 * view.php
 * 2017-02-22
 *
 */

require_once('_init.php');
$t_ps = $store->getPostWithNextAndPrevious($q['id'], ['cat' => $q['cat'], 'date' => $q['date'], 'search_word' => $q['search_word']]);
$url = SERVER_HOST_URL . rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
if (!$t_ps) {
	header("Location: $url/");
	exit(1);
}
$t_p = $t_ps[1];

$qurl = query_str($q, ['page', 'date', 'cat', 'search_word']);
$t_iUrl = 'index.php' . (empty($qurl) ? '' : ('?' . substr($qurl, 1)));
$t_pUrl = 'view.php?id=<>' . $qurl;
$t_link = $url . '/view.php?id=' . $t_p->getId();

$t_dates = $store->getCountByDate($q['date']);
$t_cats = $store->getCategoryData($q['cat']);
$t_searchQuery = $q['search_word'];




header('Content-Type: text/html;charset=utf-8');
$PAGE_TITLE = $t_p->getTitle();
$PAGE_CLASS = 'nt-view';
include('../part/header.php');
?>
	<section class="nt-topic-post">
		<h2><?=_h($t_p->getCategoryName()) ?></h2>
		<header>
			<div class="header-main">
				<h3><?=_h($t_p->getTitle()) ?></h3>
			</div>
<?php if ($t_p->getCategory() === 'event'): ?>
			<div class="event-term <?=_h($t_p->getEventState()); ?>"><?=_h(L_EVENT_DATE)?><?=_h($t_p->getEventDateBgn()) ?><?=_h(L_EVENT_DATE_TO)?><?=_h($t_p->getEventDateEnd()) ?></div>
<?php endif ?>
		</header>
		<section class="nt-post-content"><?=$t_p->getContent()?></section>
		<footer>
			<div class="date"><?=_h(L_PUBLISHED_DATE_BEFORE)?><?=_h($t_p->getPublishedDate()) ?><?=_h(L_PUBLISHED_DATE_AFTER)?></div>
		</footer>
<?php include('_nav_postlink.php'); the_postlink($t_iUrl, $t_pUrl, $t_ps[0], $t_ps[2]); ?>
	</section>
<?php include('_nav_filter.php'); the_filter($t_dates, $t_cats, $t_searchQuery); ?>
<?php include('../part/footer.php'); ?>
