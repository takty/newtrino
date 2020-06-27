<?php
namespace nt;
/**
 *
 * Query
 *
 * @author Takuto Yanagida @ Space-Time Inc.
 * @version 2020-06-27
 *
 */


require_once(__DIR__ . '/class-logger.php');
require_once(__DIR__ . '/class-indexer.php');
require_once(__DIR__ . '/class-post.php');
require_once(__DIR__ . '/class-taxonomy.php');


class Query {

	private $_status = null;
	private $_search = null;
	private $_tax    = null;
	private $_date   = null;
	private $_meta   = null;

	public function __construct( $args ) {
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

	private function _initializeTaxQuery( $query ) {
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

	private function _initializeDateQuery( $query ) {
		$qs = [];
		foreach ( $query as $idx => $ai ) {
			if ( ! is_numeric( $idx ) ) continue;
			$q = [];
			if ( empty( $ai['after'] ) && empty( $ai['before'] ) ) {
				$q['date'] = self::_normalizeDate( $ai );
			} else {
				if ( isset( $ai['after'] ) ) {
					$q['after'] = self::_normalizeDate( $ai['after'] );
				}
				if ( isset( $ai['before'] ) ) {
					$q['before'] = self::_normalizeDate( $ai['before'] );
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

	private function _initializeMetaQuery( $query ) {
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

	public function match( &$meta, $word_file_path ) {
		if ( $this->_status ) {
			if ( ! in_array( $meta['status'], $this->_status, true ) ) return false;
		}
		if ( $this->_search ) {
			$meta['_index_score'] = Indexer::calcIndexScore( $this->_search, $word_file_path );
			if ( $meta['_index_score'] === 0 ) return false;
		}
		if ( $this->_tax ) {
			if ( ! $this->_matchTaxQuery( $meta ) ) return false;
		}
		if ( $this->_date ) {
			if ( ! $this->_matchDateQuery( $meta ) ) return false;
		}
		if ( $this->_meta ) {
			if ( ! $this->_matchMetaQuery( $meta ) ) return false;
		}
		return true;
	}

	private function _matchTaxQuery( $meta ) {
		$qs = $this->_tax['qs'];
		if ( $this->_tax['rel'] === 'AND' ) {
			foreach ( $qs as $tax => $ts ) {
				if ( ! self::_matchTax( $tax, $ts, $meta ) ) return false;
			}
		} else if ( $this->_tax['rel'] === 'OR' ) {
			$ok = false;
			foreach ( $qs as $tax => $ts ) {
				if ( self::_matchTax( $tax, $ts, $meta ) ) {
					$ok = true;
					break;
				}
			}
			if ( ! $ok ) return false;
		}
		return true;
	}

	private function _matchDateQuery( $meta ) {
		$pd = preg_split( '/[- ]/', $meta['published_date'] );
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

	private function _matchMetaQuery( $meta ) {
		$qs = $this->_meta['qs'];
		$ms = isset( $meta['meta'] ) ? $meta['meta'] : [];
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

	static private function _matchTax( $tax, $ts, $meta ) {
		if ( ! isset( $meta['taxonomy'][ $tax ] ) ) return false;
		return self::_isIntersect( $ts, $meta['taxonomy'][ $tax ] );
	}

	static private function _matchDate( $q, $pd ) {
		if ( ! empty( $q['date'] ) ) {
			$d = $q['date'];
			if ( $d[0] !== $pd[0] ) return false;
			if ( ! empty( $d[1] ) && $d[1] !== $pd[1] ) return false;
			if ( ! empty( $d[2] ) && $d[2] !== $pd[2] ) return false;
		}
		if ( ! empty( $q['after'] ) ) {
			$da = $q['after'];
			if ( $da[0] > $pd[0] ) return false;
			if ( $da[0] === $pd[0] && ! empty( $da[1] ) ) {
				if ( $da[1] > $pd[1] ) return false;
				if ( $da[1] === $pd[1] && ! empty( $da[2] ) ) {
					if ( $da[2] > $pd[2] ) return false;
				}
			}
		}
		if ( ! empty( $q['before'] ) ) {
			$db = $q['before'];
			if ( $db[0] < $pd[0] ) return false;
			if ( $db[0] === $pd[0] && ! empty( $db[1] ) ) {
				if ( $db[1] < $pd[1] ) return false;
				if ( $db[1] === $pd[1] && ! empty( $db[2] ) ) {
					if ( $db[2] < $pd[2] ) return false;
				}
			}
		}
		return true;
	}

	static private function _matchMeta( $q, $ms ) {
		$key  = $q['key'];
		$comp = $q['compare'];
		$type = $q['type'];

		if ( $comp === 'exist' ) return isset( $ms[ $key ] );
		if ( $comp === 'not exist' ) return ! isset( $ms[ $key ] );

		if ( empty( $ms[ $key ] ) ) return false;
		$v = $ms[ $key ];
		$val = $q['val'];

		if ( $type === 'date' )     return self::_compareDate( $type, $v, $val );
		if ( $type === 'time' )     return self::_compareTime( $type, $v, $val );
		if ( $type === 'datetime' ) return self::_compareDateTime( $type, $v, $val );

		if ( $type === 'numeric' ) {
			$v = intval( $v );
			$val = intval( $val );
		}

		switch ( $comp ) {
			case '=':  return $v === $val;
			case '!=': return $v !== $val;
			case '<':  return $v < $val;
			case '>':  return $v > $val;
			case '<=': return $v <= $val;
			case '>=': return $v >= $val;
		}
		return false;
	}

	static private function _compareDate( $type, $d1, $d2 ) {
		switch ( $type ) {
			case '=':  return $d1 === $d2;
			case '!=': return $d1 !== $d2;
		}
		$date1 = intval( preg_replace( '/(\d{4})-(\d{2})-(\d{2})/', '$1$2$3', $d1 ) );
		$date2 = intval( preg_replace( '/(\d{4})-(\d{2})-(\d{2})/', '$1$2$3', $d2 ) );

		switch ( $type ) {
			case '<':  return $date1 <  $date2;
			case '>':  return $date1 >  $date2;
			case '<=': return $date1 <= $date2;
			case '>=': return $date1 >= $date2;
		}
		return false;
	}

	static private function _compareTime( $type, $d1, $d2 ) {
		switch ( $type ) {
			case '=':  return $d1 === $d2;
			case '!=': return $d1 !== $d2;
		}
		$time1 = intval( preg_replace( '/(\d{2}):(\d{2}):(\d{2})/', '$1$2$3', $d1 ) );
		$time2 = intval( preg_replace( '/(\d{2}):(\d{2}):(\d{2})/', '$1$2$3', $d2 ) );

		switch ( $type ) {
			case '<':  return $time1 <  $time2;
			case '>':  return $time1 >  $time2;
			case '<=': return $time1 <= $time2;
			case '>=': return $time1 >= $time2;
		}
		return false;
	}

	static private function _compareDateTime( $type, $d1, $d2 ) {
		switch ( $type ) {
			case '=':  return $d1 === $d2;
			case '!=': return $d1 !== $d2;
		}
		$date1 = intval( preg_replace( '/(\d{4})-(\d{2})-(\d{2})/', '$1$2$3', $d1 ) );
		$time1 = intval( preg_replace( '/(\d{2}):(\d{2}):(\d{2})/', '$1$2$3', $d1 ) );
		$date2 = intval( preg_replace( '/(\d{4})-(\d{2})-(\d{2})/', '$1$2$3', $d2 ) );
		$time2 = intval( preg_replace( '/(\d{2}):(\d{2}):(\d{2})/', '$1$2$3', $d2 ) );

		switch ( $type ) {
			case '<':  return ( $date1 < $date2 ) || ( $date1 === $date2 && $time1 <  $time2 );
			case '>':  return ( $date1 > $date2 ) || ( $date1 === $date2 && $time1 >  $time2 );
			case '<=': return ( $date1 < $date2 ) || ( $date1 === $date2 && $time1 <= $time2 );
			case '>=': return ( $date1 > $date2 ) || ( $date1 === $date2 && $time1 >= $time2 );
		}
		return false;
	}

	static private function _isIntersect( $as, $bs ) {
		foreach ( $as as $a ) {
			if ( in_array( $a, $bs, true ) ) return true;
		}
		return false;
	}

	static private function _normalizeDate( $dq ) {
		$n_y = date('Y');
		$n_m = date('m');

		$y = empty( $dq['year'] )  ? '' : $dq['year'];
		$m = empty( $dq['month'] ) ? '' : $dq['month'];
		$d = empty( $dq['day'] )   ? '' : $dq['day'];
		if ( empty( $y ) && empty( $m ) && empty( $d ) ) {
			$y = substr( $dq['date'], 0, 4 );
			$m = substr( $dq['date'], 4, 2 );
			$d = substr( $dq['date'], 6, 2 );
		}
		if ( ! empty( $m ) && empty( $y ) ) $y = $n_y;
		if ( ! empty( $d ) && empty( $m ) ) $m = $n_m;
		return [ $y, $m, $d ];
	}

}
