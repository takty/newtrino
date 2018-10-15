<?php
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
