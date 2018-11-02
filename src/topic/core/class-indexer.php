<?php
namespace nt;
/**
 *
 * Indexer
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @version 2018-11-02
 *
 */


require_once(__DIR__ . '/lib/class-tiny-segmenter.php');
require_once(__DIR__ . '/class-logger.php');


class Indexer {

	static function segmentSearchQuery($searchQuery) {
		$ts = new \TinySegmenter();
		$ws = $ts->segment($searchQuery);
		foreach ($ws as &$w) {
			$w = mb_convert_kana($w, 'acHV');
			$w = mb_strtolower($w);
		}
		return $ws;
	}

	static function updateSearchIndex($text, $fdPath, $mode) {
		$ts = new \TinySegmenter();
		$ws = $ts->segment($text);
		$sum = [];
		foreach ($ws as $w) {
			$nw = mb_convert_kana($w, 'acHV');
			$nw = mb_strtolower($nw);
			if (isset($sum[$nw])) $sum[$nw] += 1;
			else $sum[$nw] = 1;
		}
		$index = count($ws) . "\n";
		foreach ($sum as $key => $val){
			$index .= $key . "\t" . $val . "\n";
		}
		$suc = file_put_contents($fdPath, $index, LOCK_EX);
		if ($suc === false) {
			Logger::output('Error (Indexer::updateSearchIndex file_put_contents) [' . $fdPath . ']');
			return false;
		}
		chmod($fdPath, $mode);
		return true;
	}

	static function calcIndexScore($words, $fdPath) {
		$fp = @fopen($fdPath, 'r');
		if (!$fp) {
			Logger::output('Error (Indexer::calcIndexScore fopen) [' . $fdPath . ']');
			return 0;
		}
		$score = 0;
		$count = null;
		$matchCount = array_fill(0, count($words), 0);
		while (!feof($fp)) {
			$buf = fgets($fp);
			if ($buf === false) break;
			if ($count === null) {
				$count = intval($buf);
				continue;
			}
			$keyCount = explode("\t", $buf);
			$key = $keyCount[0];
			for ($i = 0; $i < count($words); $i += 1) {
				if (mb_strpos($key, $words[$i]) !== false) {
					$score += intval($keyCount[1]) / $count;
					$matchCount[$i] = 1;
				}
			}
		}
		fclose($fp);
		$ms = 0;
		foreach ($matchCount as $mc) $ms += $mc;
		if ($ms !== count($matchCount)) return 0;
		return $score;
	}

}
