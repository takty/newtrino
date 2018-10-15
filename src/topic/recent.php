<?php
/*
 * recent.php
 * 2017-02-22
 *
 */

require_once(dirname(__FILE__) . '/_init.php');

function the_recent($ppp = 10, $cat = '', $new_day = 7, $omitFinishedEvent = false) {
	global $store;

	$ret = $store->getPosts(0, $ppp, ['cat' => $cat], $new_day, $omitFinishedEvent);
	$t_url = 'topics/view.php?id=';

	foreach($ret['posts'] as $p) {
?>
<li class="<?=_h($p->getStateClasses())?>">
	<a href="<?=_h($t_url.$p->getId())?>">
		<span class="nt-cat<?php if ($p->getCategory() === 'event') _eh(' ' . $p->getEventState()); ?>"><?=_h($p->getCategoryName())?></span>
<?php if ($p->getCategory() === 'event'): ?>
		<span class="nt-event-term"><?=_h(L_EVENT_DATE)?><?=_h($p->getEventDateBgn())?><?=_h(L_EVENT_DATE_TO)?><?=_h($p->getEventDateEnd())?></span>
<?php endif ?>
		<div class="nt-title"><?=_h($p->getTitle(true))?></div>
		<div class="nt-excerpt"><?=$p->getExcerpt(60)?></div>
		<div class="nt-date"><?=_h(L_PUBLISHED_DATE_BEFORE)?><?=_h($p->getPublishedDate())?><?=_h(L_PUBLISHED_DATE_AFTER)?></div>
	</a>
</li>
<?php
	}
}
