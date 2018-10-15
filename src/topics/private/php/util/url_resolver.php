<?php
/*
 * URL Resolver
 * 2017-01-16
 *
 */

function resolve_url($target, $base) {
	$comp = parse_url($base);
	$dir = preg_replace('!/[^/]*$!', '/', $comp["path"]);
	$uri = '';

	switch (true) {
	case preg_match("/^http/", $target):
		$uri =  $target;
		break;
	case preg_match("/^\/\/.+/", $target):
		$uri =  $comp["scheme"].":".$target;
		break;
	case preg_match("/^\/[^\/].+/", $target):
		$uri =  $comp["scheme"]."://".$comp["host"].$target;
		break;
	case preg_match("/^\.\/(.+)/", $target,$maches):
		$uri =  $comp["scheme"]."://".$comp["host"].$dir.$maches[1];
		break;
	case preg_match("/^([^\.\/]+)(.*)/", $target,$maches):
		$uri =  $comp["scheme"]."://".$comp["host"].$dir.$maches[1].$maches[2];
		break;
	case preg_match("/^\.\.\/.+/", $target):
		preg_match_all("!\.\./!", $target, $matches);
		$nest =  count($matches[0]);

		$dir = preg_replace('!/[^/]*$!', '/', $comp["path"])."\n";
		$dir_array = explode("/",$dir);
		array_shift($dir_array);
		array_pop($dir_array);
		$dir_count = count($dir_array);
		$count = $dir_count - $nest;
		$pathto = "";
		$i = 0;
		while ($i < $count) {
			$pathto .= "/" . $dir_array[$i];
			$i++;
		}
		$file = str_replace("../", "", $target);
		$uri =  $comp["scheme"] . "://" . $comp["host"] . $pathto . "/" . $file;
		break;
	}
	return $uri;
}
?>
