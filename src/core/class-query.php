<?php
namespace nt;
/**
 *
 * Query
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @version 2020-07-14
 *
 */


require_once( __DIR__ . '/class-logger.php' );
require_once( __DIR__ . '/class-indexer.php' );
require_once( __DIR__ . '/class-post.php' );
require_once( __DIR__ . '/class-taxonomy.php' );


class Query {

	private $_type   = null;
	private $_status = null;
	private $_search = null;
	private $_tax    = null;
	private $_date   = null;
	private $_meta   = null;

	public function __construct( array $args ) {
		if ( ! empty( $args['type'] ) ) {
			$this->_type = is_array( $args['type'] ) ? $args['type'] : [ $args['type'] ];
		}
		if ( ! empty( $args['status'] ) ) {
			$this->_status = is_array( $args['status'] ) ? $args['status'] : [ $args['status'] ];
		}
		if ( ! empty( $args['search'] ) ) {
			$this->_search = Indexer::segmentSearchQuery( $args['search'] );
		}
		if ( ! empty( $args['tax_query'] ) ) {
			$this->_initializeTaxQuery( $args['tax_query'] );
		}
		if ( ! empty( $args['date_query'] ) ) {
			$this->_initializeDateQuery( $args['date_query'] );
		}
		if ( ! empty( $args['meta_query'] ) ) {
			$this->_initializeDateQuery( $args['meta_query'] );
		}
	}

	private function _initializeTaxQuery( array $query ) {
		$qs = [];
		foreach ( $query as $idx => $ai ) {
			if ( ! is_numeric( $idx ) ) continue;
			if ( ! isset( $ai['taxonomy'] ) || empty( $ai['terms'] ) ) continue;
			$qs[ $ai['taxonomy'] ] = is_array( $ai['terms'] ) ? $ai['terms'] : [ $ai['terms'] ];
		}
		if ( ! empty( $qs ) ) {
			$tax = [];
			$tax['rel'] = empty( $query['relation'] ) ? 'AND' : $query['relation'];
			$tax['qs']  = $qs;
			$this->_tax = $tax;
		}
	}

	private function _initializeDateQuery( array $query ) {
		$qs = [];
		foreach ( $query as $idx => $ai ) {
			if ( ! is_numeric( $idx ) ) continue;
			$q = [];
			if ( empty( $ai['after'] ) && empty( $ai['before'] ) ) {
				$q['date'] = self::_normalizeDate( $ai, '' );
			} else {
				if ( ! empty( $ai['after'] ) ) {
					$q['after'] = self::_normalizeDate( $ai['after'], 0 );
				}
				if ( ! empty( $ai['before'] ) ) {
					$q['before'] = self::_normalizeDate( $ai['before'], 9 );
				}
			}
			$qs[] = $q;
		}
		if ( ! empty( $qs ) ) {
			$date = [];
			$date['rel'] = empty( $query['relation'] ) ? 'AND' : $query['relation'];
			$date['qs']  = $qs;
			$this->_date = $date;
		}
	}

	private function _initializeMetaQuery( array $query ) {
		$qs = [];
		foreach ( $query as $idx => $ai ) {
			if ( ! is_numeric( $idx ) ) continue;
			$q = [];
			if ( ! isset( $ai['key'] ) ) continue;
			$q['key'] = $ai['key'];
			$q['compare'] = isset( $ai['compare'] ) ? strtolower( $ai['compare'] ) : '=';
			$q['type'] = isset( $ai['type'] ) ? strtolower( $ai['type'] ) : 'string';
			$qs[] = $q;
		}
		if ( ! empty( $qs ) ) {
			$meta = [];
			$meta['rel'] = empty( $query['relation'] ) ? 'AND' : $query['relation'];
			$meta['qs']  = $qs;
			$this->_meta = $meta;
		}
	}

	public function match( array &$info, string $word_file_path ): bool {
		if ( $this->_type ) {
			if ( ! empty( $info['type'] ) && ! in_array( $info['type'], $this->_type, true ) ) return false;
			if ( empty( $info['type'] ) && ! in_array( 'post', $this->_type, true ) ) return false;
		}
		if ( $this->_status ) {
			if ( ! in_array( $info['status'], $this->_status, true ) ) return false;
		}
		if ( $this->_search ) {
			$info['_index_score'] = Indexer::calcIndexScore( $this->_search, $word_file_path );
			if ( $info['_index_score'] === 0.0 ) return false;
		}
		if ( $this->_tax ) {
			if ( ! $this->_matchTaxQuery( $info ) ) return false;
		}
		if ( $this->_date ) {
			if ( ! $this->_matchDateQuery( $info ) ) return false;
		}
		if ( $this->_meta ) {
			if ( ! $this->_matchMetaQuery( $info ) ) return false;
		}
		return true;
	}

	private function _matchTaxQuery( array $info ): bool {
		$qs = $this->_tax['qs'];
		if ( $this->_tax['rel'] === 'AND' ) {
			foreach ( $qs as $tax => $ts ) {
				if ( ! self::_matchTax( $tax, $ts, $info ) ) return false;
			}
		} else if ( $this->_tax['rel'] === 'OR' ) {
			$ok = false;
			foreach ( $qs as $tax => $ts ) {
				if ( self::_matchTax( $tax, $ts, $info ) ) {
					$ok = true;
					break;
				}
			}
			if ( ! $ok ) return false;
		}
		return true;
	}

	private function _matchDateQuery( array $info ): bool {
		$pd = $info['date'];
		$qs = $this->_date['qs'];
		if ( $this->_date['rel'] === 'AND' ) {
			foreach ( $qs as $q ) {
				if ( ! self::_matchDate( $q, $pd ) ) return false;
			}
		} else if ( $this->_date['rel'] === 'OR' ) {
			$ok = false;
			foreach ( $qs as $q ) {
				if ( self::_matchDate( $q, $pd ) ) {
					$ok = true;
					break;
				}
			}
			if ( ! $ok ) return false;
		}
		return true;
	}

	private function _matchMetaQuery( array $info ): bool {
		$qs = $this->_meta['qs'];
		$ms = isset( $info['meta'] ) ? $info['meta'] : [];
		if ( $this->_meta['rel'] === 'AND' ) {
			foreach ( $qs as $q ) {
				if ( ! self::_matchMeta( $q, $ms ) ) return false;
			}
		} else if ( $this->_meta['rel'] === 'OR' ) {
			$ok = false;
			foreach ( $qs as $q ) {
				if ( self::_matchMeta( $q, $ms ) ) {
					$ok = true;
					break;
				}
			}
			if ( ! $ok ) return false;
		}
		return true;
	}

	static private function _matchTax( string $tax, array $ts, array $info ): bool {
		if ( ! isset( $info['taxonomy'][ $tax ] ) ) return false;
		return self::_isIntersect( $ts, $info['taxonomy'][ $tax ] );
	}

	static private function _matchDate( array $q, string $pd ): bool {
		if ( ! empty( $q['date'] ) ) {
			if ( strpos( $pd, $q['date'] ) !== 0 ) return false;
		}
		$pd_int = intval( $pd );
		if ( ! empty( $q['after'] ) ) {
			if ( intval( $q['after'] ) > $pd_int ) return false;
		}
		if ( ! empty( $q['before'] ) ) {
			if ( intval( $q['before'] ) < $pd_int ) return false;
		}
		return true;
	}

	static private function _matchMeta( array $q, array $ms ): bool {
		$key  = $q['key'];
		$comp = $q['compare'];
		$type = $q['type'];

		if ( $comp === 'exist' )     return isset( $ms[ $key ] );
		if ( $comp === 'not exist' ) return ! isset( $ms[ $key ] );

		if ( empty( $ms[ $key ] ) ) return false;
		$v  = $ms[ $key ];
		$qv = $q['val'];

		if ( $type === 'date' ) {
			$v  = substr( $v,  0, 8 );
			$qv = substr( $qv, 0, 8 );
		} else if ( $type === 'time' ) {
			$v  = substr( $v,  8, 14 );
			$qv = substr( $qv, 8, 14 );
		} else if ( $type === 'datetime' ) {
			// Do nothing
		}
		$v  = intval( $v );
		$qv = intval( $qv );

		switch ( $comp ) {
			case '=':  return $v === $qv;
			case '!=': return $v !== $qv;
			case '<':  return $v  <  $qv;
			case '>':  return $v  >  $qv;
			case '<=': return $v  <= $qv;
			case '>=': return $v  >= $qv;
		}
		return false;
	}

	static private function _isIntersect( array $as, array $bs ): bool {
		foreach ( $as as $a ) {
			if ( in_array( $a, $bs, true ) ) return true;
		}
		return false;
	}

	static private function _normalizeDate( array $dq, string $padding ): string {
		$y = empty( $dq['year'] )  ? '' : $dq['year'];
		$m = empty( $dq['month'] ) ? '' : $dq['month'];
		$d = empty( $dq['day'] )   ? '' : $dq['day'];

		if ( empty( $y ) && empty( $m ) && empty( $d ) ) {
			$y = substr( $dq['date'], 0, 4 );
			$m = substr( $dq['date'], 4, 2 );
			$d = substr( $dq['date'], 6, 2 );
		}
		if ( ! empty( $m ) && empty( $y ) ) $y = date('Y');
		if ( ! empty( $d ) && empty( $m ) ) $m = date('m');

		$date = "$y$m$d";
		return empty( $padding ) ? $date : str_pad( $date, 8, $padding );
	}

}
