<?php
namespace nt;
/**
 *
 * Indexer
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @version 2020-07-26
 *
 */


require_once( __DIR__ . '/class-logger.php' );


class Indexer {

	static function segmentSearchQuery( string $searchQuery ): array {
		return self::_create_bigram( $searchQuery );
	}

	static function updateSearchIndex( string $text, string $fdPath, int $mode ): bool {
		$ws = self::_create_bigram( $text );
		$sum = [];
		foreach ( $ws as $w ) {
			if ( isset( $sum[ $w ] ) ) $sum[ $w ] += 1;
			else $sum[ $w ] = 1;
		}
		arsort( $sum );
		$index = count( $ws ) . "\n";
		foreach ( $sum as $key => $val ) {
			$index .= $key . "\t" . $val . "\n";
		}
		$suc = file_put_contents( $fdPath, $index, LOCK_EX );
		if ( $suc === false ) {
			Logger::output( "Error (Indexer::updateSearchIndex file_put_contents) [$fdPath]" );
			return false;
		}
		chmod( $fdPath, $mode );
		return true;
	}

	static function calcIndexScore( array $words, string $fdPath ): float {
		$fp = @fopen( $fdPath, 'r' );
		if ( ! $fp ) {
			Logger::output( "Error (Indexer::calcIndexScore fopen) [$fdPath]" );
			return 0;
		}
		$score = 0;
		$count = null;
		$matchCount = array_fill( 0, count( $words ), 0 );

		while ( ! feof( $fp ) ) {
			$buf = fgets( $fp );
			if ( $buf === false ) break;
			if ( $count === null ) {
				$count = intval( $buf );
				continue;
			}
			$keyCount = explode( "\t", $buf );
			$key = $keyCount[0];
			for ( $i = 0; $i < count( $words ); $i += 1 ) {
				if ( mb_strpos( $key, $words[ $i ] ) !== false ) {
					$score += intval( $keyCount[1] ) / $count;
					$matchCount[ $i ] = 1;
				}
			}
		}
		fclose( $fp );
		$ms = 0;
		foreach ( $matchCount as $mc ) $ms += $mc;
		if ( $ms !== count( $matchCount ) ) return 0;
		return $score;
	}


	// -------------------------------------------------------------------------


	static private function _create_bigram( string $text ): array {
		$ret = [];

		$text = mb_convert_kana( $text, 'acHV' );
		$text = mb_strtolower( $text );

		$sts = mb_split( "[\s｢｣\(\)\[\]{}<>\"\'\`\\/~､,｡.?!:;･　「」『』（）［］｛｝〈〉《》【】〔〕〖〗〘〙〚〛＜＞“”‘’＼／～、，。．？！：；・]+", $text );
		$sts = array_map( function ( $e ) { return preg_replace( '/\A[\p{C}\p{Z}]++|[\p{C}\p{Z}]++\z/u', '', $e ); }, $sts );

		foreach ( $sts as $st ) {
			if ( empty( $st ) ) continue;
			self::_split_term( $st, $ret );
		}
		return $ret;
	}

	static private function _split_term( string $term, array &$bis ): void {
		$chs = preg_split( "//u", $term, -1, PREG_SPLIT_NO_EMPTY );

		$temp = '';
		foreach ( $chs as $i => $ch ) {
			if ( $temp !== '' ) {
				$bis[] = $temp;
				$temp = '';
			}
			if ( isset( $chs[ $i + 1 ] ) ) {
				$bis[] = $ch . $chs[ $i + 1 ];
			}
		}
		if ( $temp !== '' ) $bis[] = $temp;
    }

}
