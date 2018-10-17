<?php
namespace nt;
/**
 *
 * Template Tags
 *
 * @author Space-Time Inc.
 * @version 2018-10-17
 *
 */


function the_recent($ppp = 10, $cat = '', $new_day = 7, $omitFinishedEvent = false) {
	global $store;

	$ret = $store->getPosts(0, $ppp, ['cat' => $cat], $new_day, $omitFinishedEvent);
	$t_url = 'topic/view.php?id=';

	foreach ($ret['posts'] as $p) {
		$cls = ($p->getCategory() === 'event') ? (' ' . $p->getEventState()) : '';
?>
<li class="<?= _h($p->getStateClasses()) ?>">
	<a href="<?= _h($t_url.$p->getId()) ?>">
		<span class="nt-cat<?= _h($cls) ?>"><?= _ht($p->getCategoryName(), 'category') ?></span>
<?php if ($p->getCategory() === 'event'): ?>
		<span class="nt-event-term"><?= _ht('Event Date: ') ?><?= _h($p->getEventDateBgn()) ?><?= _ht(' to ') ?><?= _h($p->getEventDateEnd()) ?></span>
<?php endif ?>
		<div class="nt-title"><?= _h($p->getTitle(true)) ?></div>
		<div class="nt-excerpt"><?= $p->getExcerpt(60) ?></div>
		<div class="nt-date"><?= _ht('Updated: ') ?><?= _h($p->getPublishedDate()) ?></div>
	</a>
</li>
<?php
	}
}

function the_filter($dates = false, $cats = false, $searchQuery = false) {
	global $store, $q;
	if (!$dates)       $dates       = $store->getCountByDate($q['date']);
	if (!$cats)        $cats        = $store->getCategoryData($q['cat']);
	if (!$searchQuery) $searchQuery = $q['search_word'];
?>
<div class="nt-filter-bar">
	<nav class="nt-filter">
		<div>
			<form action="index.php" method="get">
				<label class="select" for="date">
					<select name="date">
						<option value=""><?= _ht('Month') ?></option>
<?php foreach ($dates as $d): ?>
						<option value="<?= _h($d['date']) ?>"<?php if ($d['cur']) _eh(' selected') ?>><?= _h($d['name']) ?><?= _ht(' (', 'post-count') . $d['count'] . _ht(')', 'post-count') ?></option>
<?php endforeach; ?>
					</select>
				</label>
				<label class="select" for="cat">
					<select name="cat">
						<option value=""><?= _ht('Category') ?></option>
<?php foreach ($cats as $c): ?>
						<option value="<?= _h($c['slug']) ?>"<?php if ($c['cur']) _eh(' selected') ?>><?= _ht($c['name'], 'category') ?></option>
<?php endforeach; ?>
					</select>
				</label>
				<input type="submit" value="<?= _ht('View') ?>">
			</form>
		</div>
		<div>
			<form action="index.php" method="get">
				<label class="search" for="search_word">
					<input type="text" name="search_word" id="search_word" value="<?= _h($searchQuery) ?>">
				</label>
				<input type="submit" value="<?= _ht('Search') ?>">
			</form>
		</div>
	</nav>
</div>
<?php
}

function the_pagination($pgUrl = false, $size = false, $cur = false, $ppp = false, $maxPg = 7) {
	global $store, $q;
	global $nt_posts, $nt_size, $nt_page;
	if (!$pgUrl) $pgUrl = 'index.php?page=<>' . query_str($q, ['date', 'cat', 'search_word']);
	if (!$size)  $size  = $nt_size;
	if (!$cur)   $cur   = $nt_page + 1;
	if (!$ppp)   $ppp   = NT_POSTS_PER_PAGE;

	if ($ppp >= $size) return;

	$pageSize = ceil($size / $ppp);
	$pgBgn = max($cur - intval(ceil(($maxPg - 1) / 2)), 1);
	$pgEnd = min($pgBgn + $maxPg - 1, $pageSize);
	if ($pgEnd - $pgBgn + 1 < $maxPg) {
		$pgBgn = max($pgEnd - $maxPg + 1, 1);
	}
	$t_pgs = [];
	for ($i = $pgBgn; $i <= $pgEnd; $i += 1) {
		$url = ($i == $cur) ? '' : str_replace('<>', $i, $pgUrl);
		$t_pgs[] = ['page' => $i, 'href' => $url];
	}
	$t_prev = ($cur > 1) ?         str_replace('<>', $cur - 1, $pgUrl) : false;
	$t_next = ($cur < $pageSize) ? str_replace('<>', $cur + 1, $pgUrl) : false;
	$t_pg1 = (1 < $pgBgn) ? ['page' => 1, 'href' => str_replace('<>', 1, $pgUrl)] : false;
	$t_el = (2 < $pgBgn);
	$t_eh = ($pgEnd < $pageSize);
?>
		<nav class="nt-pagination">
			<ul>
<?php if ($t_prev): ?>
				<li><a href="<?= $t_prev ?>"><?= _ht('Previous') ?></a></li>
<?php endif ?>
<?php if ($t_pg1): ?>
				<li><?php t_wrap($t_pg1['href'], '<a href="' . $t_pg1['href'] . '">', $t_pg1['page'], '</a>') ?></li>
<?php endif ?>
<?php if ($t_el): ?>
				<li class="nt-ellipsis">...</li>
<?php endif ?>
<?php foreach($t_pgs as $pg): ?>
				<li <?php if (!$pg['href']) echo(' class="nt-current"')?>><?php t_wrap($pg['href'], '<a href="' . $pg['href'] . '">', $pg['page'], '</a>') ?></li>
<?php endforeach ?>
<?php if ($t_eh): ?>
				<li class="nt-ellipsis">...</li>
<?php endif ?>
<?php if ($t_next): ?>
				<li><a href="<?= $t_next ?>"><?= _ht('Next') ?></a></li>
<?php endif ?>
			</ul>
		</nav>
<?php
}

function the_postlink() {
	global $store, $q, $nt_prev_post, $nt_next_post;
	$qurl = query_str($q, ['page', 'date', 'cat', 'search_word']);
	$iUrl = 'index.php' . (empty($qurl) ? '' : ('?' . substr($qurl, 1)));
	$pUrl = 'view.php?id=<>' . $qurl;

	$prev = $nt_prev_post;
	$next = $nt_next_post;

	$prevUrl = null;
	$nextUrl = null;

	if ($prev !== null) {
		$prevUrl = str_replace('<>', $prev->getId(), $pUrl);
		$prevTitle = $prev->getTitle();
	}
	if ($next !== null) {
		$nextUrl = str_replace('<>', $next->getId(), $pUrl);
		$nextTitle = $next->getTitle();
	}
?>
		<nav class="nt-postlink">
			<ul>
<?php if ($prev): ?>
				<li class="prev"><a  href="<?= $prevUrl ?>"><?= _h($prevTitle) ?></a></li>
<?php endif ?>
				<li class="list"><a href="<?= $iUrl ?>"><span><?= _ht('List') ?></span></a></li>
<?php if ($next): ?>
				<li class="next"><a  href="<?= $nextUrl ?>"><?= _h($nextTitle) ?></a></li>
<?php endif ?>
			</ul>
		</nav>
<?php
}

function get_permalink($base, $post) {
	global $q;
	$t_pUrl = $base . '?id=<>' . query_str($q, ['page', 'date', 'cat', 'search_word']);
	return str_replace('<>', $post->getId(), $t_pUrl);
}
