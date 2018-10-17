<?php
namespace nt;
/**
 *
 * Functions
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @version 2018-10-17
 *
 */


// Functions Used in Initial Process -------------------------------------------


function reject_direct_access($path, $depth = 1) {
	$ifs = get_included_files();
	if (array_shift($ifs) === $path) {
		$to = $_SERVER['PHP_SELF'];
		for ($i = 0; $i < $depth; ++$i) {
			$to = dirname($to);
		}
		$host = (empty($_SERVER['HTTPS']) ? 'http://' : 'https://') . $_SERVER['HTTP_HOST'];
		$url = $host . rtrim($to, '/\\');
		header("Location: $url/");
		exit(1);
	}
}

function setLocaleSetting() {
	date_default_timezone_set('Asia/Tokyo');
	mb_language('Japanese');
	mb_internal_encoding('utf-8');
	mb_http_output('utf-8');
	mb_http_input('utf-8');
	mb_regex_encoding('utf-8');
}


// -----------------------------------------------------------------------------


function query_str($q, $keys) {
	$ret = '';
	foreach ($keys as $key) {
		if (!empty($q[$key])) $ret .= "&$key=" . _u($q[$key]);
	}
	return $ret;
}

function t_wrap($flag, $before, $cont, $after) {
	if ($flag) {
		echo $before . $cont . $after;
	} else {
		echo $cont;
	}
}


// Utility Functions -----------------------------------------------------------


function _h($str)  {
	return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

function _eh($str) {
	echo htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

function _u($str)  {
	return rawurlencode($str);
}

function _eu($str) {
	echo rawurlencode($str);
}
