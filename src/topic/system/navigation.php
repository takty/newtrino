<?php
/*
 * Navigations
 * 
 * @author Space-Time Inc.
 * @version 2018-10-15
 *
 */


function the_filter($dates, $cats, $searchQuery) {
?>
<div class="nt-filter-bar">
	<nav class="nt-filter">
		<div>
			<form action="index.php" method="get">
				<label class="select" for="date">
					<select name="date">
						<option value=""><?=_h(L_MONTH)?></option>
<?php foreach($dates as $d): ?>
						<option value="<?=_h($d['date'])?>"<?php if ($d['cur']) _eh(' selected')?>><?=_h($d['name'])?><?=_h(L_TOPIC_COUNT_BEFORE.$d['count'].L_TOPIC_COUNT_AFTER)?></option>
<?php endforeach; ?>
					</select>
				</label>
				<label class="select" for="cat">
					<select name="cat">
						<option value=""><?=_h(L_CATEGORY)?></option>
<?php foreach($cats as $c): ?>
						<option value="<?=_h($c['slug'])?>"<?php if ($c['cur']) _eh(' selected')?>><?=_h($c['name'])?></option>
<?php endforeach; ?>
					</select>
				</label>
				<input type="submit" value="<?=_h(L_VIEW)?>">
			</form>
		</div>
		<div>
			<form action="index.php" method="get">
				<label class="search" for="search_word">
					<input type="text" name="search_word" id="search_word" value="<?=_h($searchQuery)?>">
				</label>
				<input type="submit" value="<?=_h(L_SEARCH)?>">
			</form>
		</div>
	</nav>
</div>
<?php
}

const PG_MAX = 7;

function the_pagination($pgUrl, $size, $cur, $ppp, $maxPg = PG_MAX) {
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
				<li><a href="<?=$t_prev?>"><?=_h(L_PREVIOUS)?></a></li>
<?php endif ?>
<?php if ($t_pg1): ?>
				<li><?php t_wrap($t_pg1['href'], '<a href="' . $t_pg1['href'] . '">', $t_pg1['page'], '</a>') ?></li>
<?php endif ?>
<?php if ($t_el): ?>
				<li class="nt-ellipsis">...</li>
<?php endif ?>
<?php foreach($t_pgs as $pg): ?>
				<li<?php if (!$pg['href']) echo(' class="nt-current"')?>><?php t_wrap($pg['href'], '<a href="' . $pg['href'] . '">', $pg['page'], '</a>') ?></li>
<?php endforeach ?>
<?php if ($t_eh): ?>
				<li class="nt-ellipsis">...</li>
<?php endif ?>
<?php if ($t_next): ?>
				<li><a href="<?=$t_next?>"><?=_h(L_NEXT)?></a></li>
<?php endif ?>
			</ul>
		</nav>
<?php
}

function the_postlink($iUrl, $pUrl, $prev, $next) {
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
				<li class="prev"><a  href="<?=$prevUrl?>"><?=_h($prevTitle)?></a></li>
<?php endif ?>
				<li class="list"><a href="<?=$iUrl?>"><span><?=_h(L_LIST)?></span></a></li>
<?php if ($next): ?>
				<li class="next"><a  href="<?=$nextUrl?>"><?=_h($nextTitle)?></a></li>
<?php endif ?>
			</ul>
		</nav>
<?php
}
