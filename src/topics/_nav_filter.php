<?php
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
