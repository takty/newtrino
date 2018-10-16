<?php
namespace nt;

if (!function_exists('_h'))  {function _h($str)  {return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');}}
if (!function_exists('_eh')) {function _eh($str) {echo htmlspecialchars($str, ENT_QUOTES, 'UTF-8');}}
if (!function_exists('_u'))  {function _u($str)  {return rawurlencode($str);}}
if (!function_exists('_eu')) {function _eu($str) {echo rawurlencode($str);}}

function t_wrap($flag, $before, $cont, $after) {
	if ($flag) echo $before . $cont . $after;
	else echo $cont;
}

function query_str($q, $keys) {
	$ret = '';
	foreach ($keys as $key) {
		if (!empty($q[$key])) $ret .= "&$key=" . _u($q[$key]);
	}
	return $ret;
}
