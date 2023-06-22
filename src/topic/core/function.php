<?php
namespace nt;
/**
 *
 * Functions
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @version 2023-06-22
 *
 */


// Functions Used in Initial Process -------------------------------------------


function reject_direct_access($urlHost, $path, $depth = 1) {
	$ifs = get_included_files();
	if (array_shift($ifs) === $path) {
		$to = $_SERVER['SCRIPT_NAME'];
		for ($i = 0; $i < $depth; ++$i) {
			$to = dirname($to);
		}
		$url = $urlHost . rtrim($to, '/\\');
		header("Location: $url/");
		exit(1);
	}
}

function set_locale_setting() {
	date_default_timezone_set('Asia/Tokyo');
	mb_internal_encoding('utf-8');
	mb_http_output('utf-8');
	mb_regex_encoding('utf-8');
}

function load_config($dirData) {
	$conf = [];
	$path = $dirData . 'config.json';
	if (file_exists($path)) {
		$json = file_get_contents($path);
		$conf = json_decode($json, true);
	}
	// Default Config
	$conf += [
		'language' => 'en',
		'language_private' => 'en',
		'posts_per_page' => 10,
		'newly_arrived_day' => 3
	];
	return $conf;
}

function load_resource($dirData, $lang) {
	$path = $dirData . 'text.' . $lang . '.json';
	if (file_exists($path)) {
		$json = file_get_contents($path);
		return json_decode($json, true);
	}
	return [];
}

function prepare_query($opt = []) {
	$q = empty($_POST) ? $_GET : $_POST;
	$q += $opt;
	return $q;
}


// Utility Functions -----------------------------------------------------------


function query_str($q, $keys) {
	$ret = '';
	foreach ($keys as $key) {
		if (!empty($q[$key])) $ret .= "&$key=" . _u($q[$key]);
	}
	return $ret;
}

function wrap($flag, $before, $cont, $after) {
	if ($flag) {
		echo $before . $cont . $after;
	} else {
		echo $cont;
	}
}

function resolve_url($target, $base) {
	$comp = parse_url($base);
	$dir = preg_replace('!/[^/]*$!', '/', $comp['path']);

	switch (true) {
	case preg_match('/^http/', $target):
		return $target;
	case preg_match('/^\/\/.+/', $target):
		return $comp['scheme'] . ':' . $target;
	case preg_match('/^\/[^\/].+/', $target):
		return $comp['scheme'] . '://' . $comp['host'] . $target;
	case preg_match('/^\.\/(.+)/', $target, $maches):
		return $comp['scheme'] . '://' . $comp['host'] . $dir . $maches[1];
	case preg_match('/^([^\.\/]+)(.*)/', $target, $maches):
		return $comp['scheme'] . '://' . $comp['host'] . $dir . $maches[1] . $maches[2];
	case preg_match('/^\.\.\/.+/', $target):
		preg_match_all('!\.\./!', $target, $matches);
		$nest = count($matches[0]);

		$dir = preg_replace('!/[^/]*$!', '/', $comp['path']) . '\n';
		$dir_array = explode('/', $dir);
		array_shift($dir_array);
		array_pop($dir_array);
		$dir_count = count($dir_array);
		$count = $dir_count - $nest;
		$pathto = '';
		$i = 0;
		while ($i < $count) {
			$pathto .= '/' . $dir_array[$i];
			$i++;
		}
		$file = str_replace('../', '', $target);
		return $comp['scheme'] . '://' . $comp['host'] . $pathto . '/' . $file;
	}
	return $target;
}


// Output Functions ------------------------------------------------------------


function _h($str) {
	return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

function _eh($str) {
	echo htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

function _u($str) {
	return rawurlencode($str);
}

function _eu($str) {
	echo rawurlencode($str);
}

function _ht($str, $context = 'default') {
	return htmlspecialchars(translate($str, $context), ENT_QUOTES, 'UTF-8');
}

function _eht($str, $context = 'default') {
	echo htmlspecialchars(translate($str, $context), ENT_QUOTES, 'UTF-8');
}

function _ut($str, $context = 'default') {
	return rawurlencode(translate($str, $context));
}

function _eut($str, $context = 'default') {
	echo rawurlencode(translate($str, $context));
}

function translate($str, $context = 'default') {
	if (defined('NT_PRIVATE') && $context === 'default') {
		$context = 'private';
	}
	global $nt_res;
	if (isset($nt_res[$context])) {
		if (isset($nt_res[$context][$str])) {
			return $nt_res[$context][$str];
		}
	}
	if ($context !== 'default') {
		if (isset($nt_res['default'])) {
			if (isset($nt_res['default'][$str])) {
				return $nt_res['default'][$str];
			}
		}
	}
	return $str;
}
