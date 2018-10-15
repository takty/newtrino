<?php
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
