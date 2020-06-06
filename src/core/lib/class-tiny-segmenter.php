<?php
/**
 *
 * PHP Version of TinySegmenter
 *
 * The Original is:
 * TinySegmenter 0.1 -- Super compact Japanese tokenizer in Javascript
 * (c) 2008 Taku Kudo <taku@chasen.org>
 * TinySegmenter is freely distributable under the terms of a new BSD licence.
 * For details, see http://chasen.org/~taku/software/TinySegmenter/LICENCE.txt
 *
 */


class TinySegmenter {

	private $_patterns = [
		'[一二三四五六七八九十百千万億兆]' => 'M',
		'[一-龠々〆ヵヶ]'                  => 'H',
		'[ぁ-ん]'                          => 'I',
		'[ァ-ヴーｱ-ﾝﾞｰ]'                   => 'K',
		'[a-zA-Zａ-ｚＡ-Ｚ]'               => 'A',
		'[0-9０-９]'                       => 'N'
	];

	static private function stringToArray($str, $enc = 'UTF-8') {
		$a = [];
		$len = mb_strlen($str, $enc);
		for($i = 0; $i < $len; $i += 1) {
			$a[] = mb_substr($str, $i, 1, $enc);
		}
		return $a;
	}

	private function _ctype($str) {
		foreach ($this->_patterns as $p => $t) {
			if (mb_ereg_match($p, $str)) return $t;
		}
		return 'O';
	}

	static private function _ts($v) {
		return ($v) ? $v : 0;
	}

	private $_BIAS = 2252;
	private $_BC1 = ['IH' => -2384, 'II' => 3270, 'IK' => -100, 'NH' => 2173, 'OH' => -1008, 'OO' => 1343];
	private $_BC2 = ['AA' => -12150, 'HH' => -4329, 'HI' => -978, 'IH' => 1459, 'II' => -6625, 'IO' => 4584, 'KI' => 1578, 'KK' => -11956, 'KM' => -2208, 'NH' => 1157, 'NN' => -9390, 'OH' => 1131, 'OI' => 320, 'OO' => -11227];
	private $_BC3 = ['HH' => 906, 'IO' => 614, 'KK' => 170];
	private $_BP1 = ['BB' => 302, 'OB' => -231, 'OO' => 146, 'UB' => 282];
	private $_BP2 = ['OB' => 1327, 'UB' => 49, 'UU' => -146];
	private $_BQ1 = ['BII' => -100, 'OII' => 252];
	private $_BQ2 = ['BHH' => 434, 'BHI' => 455, 'BIH' => -1446, 'OHH' => -802, 'OKK' => -1977, 'UII' => -661];
	private $_BQ3 = ['BHH' => -148, 'BHI' => 139, 'BIH' => 58, 'OHH' => 1008, 'UOH' => -1190];
	private $_BQ4 = ['BAA' => -1264, 'BHH' => -2820, 'BII' => -3700, 'BOO' => -353, 'OHI' => -1024, 'OIH' => -893, 'UHH' => -712];
	private $_BW1 = ['、と' => 49, 'いる' => 462, 'うし' => -2206, 'から' => 1819, 'こと' => 2372, 'した' => 315, 'して' => 1779, 'しょ' => 1428, 'そこ' => 1096, 'そし' => -1226, 'たち' => 1283, 'った' => 2355, 'つい' => -384, 'てい' => 1550, 'てき' => 250, 'てく' => -346, 'てし' => -784, 'でも' => 589, 'どこ' => 1229, 'ない' => 6901, 'なっ' => 505, 'にし' => 1693, 'ませ' => 1564, 'まる' => -3912, 'よう' => 2051, 'よっ' => -317, 'をし' => 457, '同時' => -3330, '本当' => -3421];
	private $_BW2 = ['——' => -12400, '──' => -6300, 'いう' => -4120, 'いた' => 955, 'いは' => -3214, 'から' => -3546, 'こと' => -8616, 'この' => -4459, 'させ' => 759, 'され' => 9917, 'しい' => -642, 'した' => 2787, 'その' => -5916, 'たち' => -3820, 'った' => 1591, 'っと' => -4079, 'てい' => 7284, 'てく' => 98, 'ては' => -2186, 'ても' => -567, 'であ' => 1657, 'でい' => 1374, 'でき' => -48, 'でし' => -2471, 'です' => -3900, 'とい' => 4250, 'とこ' => -865, 'ない' => -1798, 'にお' => -1010, 'にし' => 2847, 'にな' => 99, 'によ' => -4340, 'に対' => -9967, 'に関' => -6166, 'のか' => 146, 'のに' => -1710, 'まし' => -508, 'まで' => -3022, 'まれ' => 2249, 'もの' => -13605, 'れた' => 399, 'れば' => 3115, 'ろう' => 3694, 'われ' => 398, 'を通' => -7766, 'んだ' => 730, 'んな' => -4906, 'ネロ' => 1184, '時に' => -480];
	private $_BW3 = ['ある' => 1331, 'いい' => 3101, 'いっ' => 1643, 'いる' => 3480, 'う。' => 705, 'うと' => 3187, 'うに' => -298, 'かっ' => -2286, 'から' => 5058, 'がら' => -2272, 'きた' => 249, 'こと' => 8301, 'この' => 1826, 'ころ' => -1355, 'しい' => -1677, 'して' => 153, 'しま' => 355, 'す。' => -5317, 'する' => 3975, 'その' => 1365, 'た。' => 6490, 'たい' => -2823, 'たの' => 252, 'った' => -2120, 'てい' => 6251, 'てく' => 99, 'ての' => -403, 'です' => 952, 'とい' => 671, 'とも' => -402, 'ない' => 3314, 'に、' => -1220, 'には' => 1946, 'のも' => -929, 'の子' => -1619, 'まし' => 1875, 'ます' => 4331, 'まで' => 1733, 'もの' => 3095, 'られ' => 5894, 'れた' => 49, 'れて' => 936, 'れば' => -1671, 'われ' => -1412, 'th' => 148];
	private $_TC1 = ['HII' => 395, 'IHI' => 205, 'III' => 665, 'KKK' => 635, 'OII' => -318];
	private $_TC2 = ['AAA' => -159, 'HHH' => -293, 'IHI' => -710, 'IIH' => -161, 'III' => -2058, 'KKI' => 1771, 'OII' => -3714];
	private $_TC3 = ['HHI' => -62, 'HIH' => -201, 'HII' => -1059, 'IHH' => 1154, 'IHI' => -1153, 'IIH' => -2697, 'IIK' => -395, 'IKK' => 1530];
	private $_TC4 = ['AAA' => 3209, 'HHI' => 1144, 'HIH' => 558, 'HII' => -340, 'HIO' => -102, 'IIH' => -525, 'III' => 1057, 'IIO' => 56, 'IOO' => 311, 'KKK' => 1907];
	private $_TQ1 = ['BHII' => -57, 'BIII' => 607, 'OHII' => 838];
	private $_TQ2 = ['BIIH' => -621, 'BOHI' => -105, 'OKHH' => -48];
	private $_TQ3 = ['BHIH' => 63, 'BHII' => -160, 'BIHI' => 245, 'OAAA' => 1641, 'OHHI' => 842, 'OHII' => 1610, 'OIII' => -55, 'OIKK' => -333, 'OKKK' => 626, 'OOHI' => -338, 'OOII' => -979];
	private $_TQ4 = ['BHHH' => -1955, 'BHIH' => 723, 'BIII' => -1987, 'OHIH' => -6132, 'OHII' => 73, 'OIHI' => -2427, 'OIII' => -1146];
	private $_TW1 = ['につい' => -1161, 'によっ' => -48];
	private $_TW2 = ['しょう' => 1174, 'そして' => -1058, 'として' => -1014, 'まるで' => -48, 'よって' => -610, 'ラグイ' => 49];
	private $_TW3 = ['として' => -861, 'につい' => -449, 'にとっ' => -1982, 'ので、' => -1324];
	private $_TW4 = ['いうこ' => 1351, 'した。' => -1282, 'してい' => 1032, 'ている' => 569, 'という' => 355, 'ました' => 4971, 'ません' => 3006, 'ようと' => -2566, 'ように' => 831];
	private $_UC1 = ['A' => 102, 'I' => -85, 'O' => -686];
	private $_UC2 = ['H' => 3493, 'I' => -156, 'K' => 897, 'M' => 2732, 'O' => -816];
	private $_UC3 = ['I' => 837, 'M' => -4795, 'O' => 10912];
	private $_UC4 = ['H' => 664, 'I' => 328, 'M' => -1563, 'O' => 7853];
	private $_UC5 = ['H' => 1360, 'I' => -789];
	private $_UC6 = ['A' => 340, 'H' => -155, 'I' => 445, 'O' => 862];
	private $_UP1 = ['B' => 92, 'O' => 403, 'U' => -451];
	private $_UP2 = ['O' => -211];
	private $_UP3 = ['O' => 540];
	private $_UQ1 = ['BK' => 390, 'OH' => -96, 'OI' => 157, 'OK' => -107, 'UI' => -535];
	private $_UQ2 = ['OH' => -1085, 'OK' => 594];
	private $_UQ3 = ['BH' => -2469, 'BI' => 3942, 'BK' => -57, 'BO' => 561];
	private $_UW1 = ['、' => -277, 'が' => -686, 'こ' => 585, 'し' => -105, 'っ' => 105, 'と' => -191, 'に' => -1346, 'の' => -55, 'は' => -1508, 'も' => -415, 'や' => -98, 'よ' => 948, 'る' => 578, 'を' => -647, 'ー' => 162, 'B1' => 181, 'i' => 49];
	private $_UW2 = ['、' => -558, '。' => -316, '」' => 1067, 'い' => 100, 'う' => -137, 'お' => -98, 'か' => 1811, 'が' => -1128, 'く' => -638, 'こ' => 2216, 'さ' => 400, 'し' => 1372, 'す' => -260, 'そ' => -595, 'た' => 209, 'だ' => 1161, 'っ' => 507, 'て' => -763, 'と' => -1617, 'な' => 177, 'に' => -1957, 'の' => 53, 'は' => -1657, 'ま' => 157, 'も' => -852, 'よ' => 787, 'り' => -421, 'る' => -1385, 'れ' => 362, 'を' => -2556, 'ん' => 2137, '不' => -1603, '大' => -3151, '小' => -1297, '見' => -2653];
	private $_UW3 = ['、' => 6254, 'あ' => -4503, 'う' => 2797, 'え' => 1407, 'お' => -5353, 'か' => -1596, 'が' => 3324, 'く' => 1164, 'こ' => -4431, 'ご' => -1948, 'さ' => -1320, 'し' => -556, 'せ' => 3286, 'そ' => -5733, 'た' => 63, 'ち' => -309, 'っ' => -96, 'つ' => -768, 'て' => 6469, 'で' => 563, 'と' => 1292, 'ど' => -1158, 'な' => -3544, 'に' => 1789, 'の' => 3496, 'は' => 5820, 'ほ' => -3055, 'ま' => -5490, 'み' => -101, 'も' => 1444, 'よ' => -1197, 'り' => 841, 'る' => 5418, 'れ' => 2871, 'わ' => -1525, 'を' => 7656, 'グ' => 408, 'ニ' => -246, 'ネ' => 3481, 'ル' => 99, 'ン' => 794, '二' => 4131, '人' => 1803, '何' => 4115, '大' => 98, '当' => -3288, '彼' => 637, '数' => 1064, '的' => 7837, '私' => 4337, '立' => -618, '第' => 98, '見' => 98, 'd' => 1243, 'e' => 702, 'i' => -2894, 's' => 793, 'y' => 299];
	private $_UW4 = ['、' => 1653, 'あ' => 6035, 'い' => -3377, 'う' => -1003, 'え' => -4600, 'お' => 818, 'か' => 428, 'が' => 6472, 'き' => -4125, 'く' => -3903, 'け' => -4373, 'こ' => 1197, 'さ' => 947, 'し' => -740, 'じ' => -780, 'そ' => 4140, 'た' => 5005, 'だ' => 5176, 'ち' => -3275, 'っ' => -6387, 'つ' => -2120, 'て' => 3190, 'で' => 7549, 'と' => 3978, 'な' => 5348, 'に' => 6219, 'の' => 8590, 'は' => 9538, 'ば' => 180, 'び' => -938, 'へ' => 3780, 'ほ' => 1481, 'ま' => 2351, 'め' => -4288, 'も' => 1870, 'ゃ' => -1888, 'や' => 1878, 'ょ' => -2697, 'よ' => 2577, 'ら' => -4078, 'り' => -8857, 'る' => -11569, 'れ' => -1577, 'ろ' => -3354, 'わ' => -75, 'を' => 17597, 'ん' => -257, 'ソ' => 1737, 'ッ' => -835, 'ド' => 1016, 'ニ' => -1362, 'ラ' => 148, 'ル' => -145, 'ン' => -1043, 'ー' => -5308, '人' => 2294, '子' => -408, '対' => -1833, '的' => 2740, '者' => 249, 'e' => -2623, 'n' => -703, 'r' => -516];
	private $_UW5 = ['、' => -407, '。' => 764, 'あ' => 689, 'い' => 160, 'か' => 768, 'が' => -331, 'き' => 1863, 'さ' => -1524, 'し' => -186, 'た' => 158, 'ち' => 1347, 'つ' => 266, 'て' => -465, 'で' => -302, 'と' => 1191, 'な' => -598, 'に' => -1416, 'も' => -424, 'ゃ' => 1741, 'り' => -49, 'る' => 1002, 'れ' => 963, 'を' => -609, 'ン' => 1702, 'ー' => 857, '度' => -611, '的' => -3183, 'a' => 102, 'h' => 1134, 'o' => 1386, 't' => -618];
	private $_UW6 = ['、' => -561, 'あ' => -100, 'い' => 49, 'か' => 108, 'た' => -50, 'だ' => 51, 'っ' => 148, 'て' => -2204, 'と' => -832, 'の' => -368, 'は' => -456, 'も' => -102, 'れ' => -51];

	public function segment($input) {
 		$input = trim($input);
		if (empty($input)) {
			return [];
		}
		$result = [];
		$seg = ['B3', 'B2', 'B1'];
		$ctype = ['O', 'O', 'O'];
		$o = self::stringToArray($input);
		for ($i = 0; $i < count($o); $i += 1) {
			$seg[] = $o[$i];
			$ctype[] = $this->_ctype($o[$i]);
		}

		$seg[] = 'E1';
		$seg[] = 'E2';
		$seg[] = 'E3';
		$ctype[] = 'O';
		$ctype[] = 'O';
		$ctype[] = 'O';
		$word = $seg[3];
		$p1 = 'U';
		$p2 = 'U';
		$p3 = 'U';

		for ($i = 4; $i < count($seg) - 3; $i += 1) {
			$score = $this->_BIAS;

			$w1 = $seg[$i - 3];
			$w2 = $seg[$i - 2];
			$w3 = $seg[$i - 1];
			$w4 = $seg[$i];
			$w5 = $seg[$i + 1];
			$w6 = $seg[$i + 2];

			$c1 = $ctype[$i - 3];
			$c2 = $ctype[$i - 2];
			$c3 = $ctype[$i - 1];
			$c4 = $ctype[$i];
			$c5 = $ctype[$i + 1];
			$c6 = $ctype[$i + 2];

			$score += self::_ts(@$this->_UP1[$p1]);
			$score += self::_ts(@$this->_UP2[$p2]);
			$score += self::_ts(@$this->_UP3[$p3]);
			$score += self::_ts(@$this->_BP1[$p1 . $p2]);
			$score += self::_ts(@$this->_BP2[$p2 . $p3]);
			$score += self::_ts(@$this->_UW1[$w1]);
			$score += self::_ts(@$this->_UW2[$w2]);
			$score += self::_ts(@$this->_UW3[$w3]);
			$score += self::_ts(@$this->_UW4[$w4]);
			$score += self::_ts(@$this->_UW5[$w5]);
			$score += self::_ts(@$this->_UW6[$w6]);
			$score += self::_ts(@$this->_BW1[$w2 . $w3]);
			$score += self::_ts(@$this->_BW2[$w3 . $w4]);
			$score += self::_ts(@$this->_BW3[$w4 . $w5]);
			$score += self::_ts(@$this->_TW1[$w1 . $w2 . $w3]);
			$score += self::_ts(@$this->_TW2[$w2 . $w3 . $w4]);
			$score += self::_ts(@$this->_TW3[$w3 . $w4 . $w5]);
			$score += self::_ts(@$this->_TW4[$w4 . $w5 . $w6]);

			$score += self::_ts(@$this->_UC1[$c1]);
			$score += self::_ts(@$this->_UC2[$c2]);
			$score += self::_ts(@$this->_UC3[$c3]);
			$score += self::_ts(@$this->_UC4[$c4]);
			$score += self::_ts(@$this->_UC5[$c5]);
			$score += self::_ts(@$this->_UC6[$c6]);
			$score += self::_ts(@$this->_BC1[$c2 . $c3]);
			$score += self::_ts(@$this->_BC2[$c3 . $c4]);
			$score += self::_ts(@$this->_BC3[$c4 . $c5]);
			$score += self::_ts(@$this->_TC1[$c1 . $c2 . $c3]);
			$score += self::_ts(@$this->_TC2[$c2 . $c3 . $c4]);
			$score += self::_ts(@$this->_TC3[$c3 . $c4 . $c5]);
			$score += self::_ts(@$this->_TC4[$c4 . $c5 . $c6]);

			$score += self::_ts(@$this->_UQ1[$p1 . $c1]);
			$score += self::_ts(@$this->_UQ2[$p2 . $c2]);
			$score += self::_ts(@$this->_UQ1[$p3 . $c3]);
			$score += self::_ts(@$this->_BQ1[$p2 . $c2 . $c3]);
			$score += self::_ts(@$this->_BQ2[$p2 . $c3 . $c4]);
			$score += self::_ts(@$this->_BQ3[$p3 . $c2 . $c3]);
			$score += self::_ts(@$this->_BQ4[$p3 . $c3 . $c4]);
			$score += self::_ts(@$this->_TQ1[$p2 . $c1 . $c2 . $c3]);
			$score += self::_ts(@$this->_TQ2[$p2 . $c2 . $c3 . $c4]);
			$score += self::_ts(@$this->_TQ3[$p3 . $c1 . $c2 . $c3]);
			$score += self::_ts(@$this->_TQ4[$p3 . $c2 . $c3 . $c4]);

			$p = 'O';
			if ($score > 0) {
				$result[] = $word;
				$word = '';
				$p = 'B';
			}
			$p1 = $p2;
			$p2 = $p3;
			$p3 = $p;
			$word .= $seg[$i];
		}
		$result[] = $word;
		return $result;
	}
}