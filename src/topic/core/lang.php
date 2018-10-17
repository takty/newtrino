<?php
define('LANG', 'ja');

if (LANG === 'ja') {
	// define('L_TOPIC_LIST', '記事一覧');
	define('L_MONTH', '月');
	define('L_CATEGORY', 'カテゴリー');
	define('L_VIEW', '表示');
	define('L_SEARCH', '検索');
	define('L_PREVIOUS', '前へ');
	define('L_NEXT', '次へ');
	define('L_LIST', '一覧');

	define('L_TOPIC_COUNT_BEFORE', '（');
	define('L_TOPIC_COUNT_AFTER', '件）');

	define('L_PUBLISHED_DATE_BEFORE', '更新: ');
	define('L_EVENT_DATE', '開催日: ');
	define('L_EVENT_DATE_TO', ' ～ ');
}
if (LANG === 'en') {
	define('L_TOPIC_LIST', 'Topic List');
	define('L_MONTH', 'Month');
	define('L_CATEGORY', 'Category');
	define('L_VIEW', 'View');
	define('L_SEARCH', 'Search');
	define('L_PREVIOUS', 'Previous');
	define('L_NEXT', 'Next');
	define('L_LIST', 'List');

	define('L_TOPIC_COUNT_BEFORE', ' (');
	define('L_TOPIC_COUNT_AFTER', ')');

	define('L_PUBLISHED_DATE_BEFORE', 'Updated: ');
	define('L_EVENT_DATE', 'Event Date: ');
	define('L_EVENT_DATE_TO', ' - ');
}
