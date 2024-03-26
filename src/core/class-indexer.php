<?php
/**
 * Indexer
 *
 * @author Takuto Yanagida
 * @version 2024-03-26
 */

namespace nt;

require_once( __DIR__ . '/class-logger.php' );

/**
 * Indexer class for creating and calculating search indexes.
 */
class Indexer {

	/**
	 * Segments a search query into bigrams.
	 *
	 * @param string $searchQuery The search query to segment.
	 * @return string[] An array of bigrams.
	 */
	static public function segmentSearchQuery( string $searchQuery ): array {
		return self::_createBigram( $searchQuery );
	}

	/**
	 * Creates a search index from a text.
	 *
	 * @param string $text The text to create the search index from.
	 * @return string The created search index.
	 */
	static public function createSearchIndex( string $text ): string {
		$ws = self::_createBigram( $text );
		$sum = [];
		foreach ( $ws as $w ) {
			if ( isset( $sum[ $w ] ) ) $sum[ $w ] += 1;
			else $sum[ $w ] = 1;
		}
		arsort( $sum );
		$idx = [];
		$idx[] = count( $ws );
		foreach ( $sum as $key => $val ) {
			$idx[] = $key . "\t" . $val;
		}
		return implode( "\n", $idx );
	}

	/**
	 * Calculates the score of a search index.
	 *
	 * @param string[] $words  An array of words to calculate the score for.
	 * @param string   $bfPath The path to the bigram file.
	 * @return float The calculated score.
	 */
	static public function calcIndexScore( array $words, string $bfPath ): float {
		if ( ! is_readable( $bfPath ) ) {
			Logger::error( __METHOD__, 'The bigram file is not readable', $bfPath );
			return 0;
		}
		$fp = fopen( $bfPath, 'r' );
		if ( ! $fp ) {
			Logger::error( __METHOD__, 'Cannot open the bigram file', $bfPath );
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


	/**
	 * Creates bigrams from a text.
	 *
	 * @param string $text The text to create bigrams from.
	 * @return string[] An array of bigrams.
	 */
	static private function _createBigram( string $text ): array {
		$ret = [];

		$text = mb_convert_kana( $text, 'acHV' );
		$text = mb_strtolower( $text );

		$sts = mb_split( "[\s｢｣\(\)\[\]{}<>\"\'\`\\/~､,｡.?!:;･　「」『』（）［］｛｝〈〉《》【】〔〕〖〗〘〙〚〛＜＞“”‘’＼／～、，。．？！：；・]+", $text );
		if ( ! is_array( $sts ) ) {
			$sts = [ $text ];
		}
		$sts = array_map(
			function ( $e ) {
				return preg_replace( '/\A[\p{C}\p{Z}]++|[\p{C}\p{Z}]++\z/u', '', $e );
			},
			$sts
		);
		foreach ( $sts as $st ) {
			if ( empty( $st ) ) continue;
			self::_splitTerm( $st, $ret );
		}
		if ( empty( $ret ) && ! empty( $text ) ) $ret = [ $text ];
		return $ret;
	}

	/**
	 * Splits a term into bigrams.
	 *
	 * @param string   $term The term to split.
	 * @param string[] &$bis An array to store the bigrams.
	 */
	static private function _splitTerm( string $term, array &$bis ): void {
		$len = mb_strlen( $term, 'UTF-8' );
		$ng  = 2;

		for ( $i = 0; $i < $len - $ng + 1; $i += 1 ) {
			$bis[] = mb_substr( $term, $i, $ng, 'UTF-8' );
		}
	}

}
