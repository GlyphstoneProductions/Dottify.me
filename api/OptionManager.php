<?php
/**
 * Utility function to simplify use of options passed in query strings and consumed in the Manager and DAO layers
 */

class OptionManager {
	
	private $options = array() ;
	
	public static function parse( $input ) {
		$opts = new OptionManager() ;
		if( !empty( $input ) ) {
			$opts->load($input ) ;
		}
		return $opts ;
	}
	
	public function count() {
		return count($this->options);
	}
	
	public function isEmpty() {
		if( $this->count() == 0 ) {
			return true;
		}
		return false ;
	}
	
	public function load( $optstring ) {
		$opts = explode( ';', $optstring) ;
		foreach( $opts as $opt ) {
			$kv = explode( '|', $opt ) ;
			$key = $kv[0] ;
			$val = (count($kv) == 2 )? $kv[1] : null ;
			$this->options[$key] = $val ;
		}
	}
	
	public function all() {
		return $this->options ;
	}
	
	public function get( $key, $default = null) {
		$skey = trim($key) ;
		if( array_key_exists( $skey, $this->options ) ) {
			return trim($this->options[$skey]) ;
		}
		return $default ;
	}
	
	public function getInt( $key, $default = 0) {
		$val = $this->get( $key, $default ) ;
		if( !empty( $val ) ) {
			return intval( $val ) ;
		}
	}
	
	public function equals( $key, $comp, $default = null ) {
		$val = $this->get($key, $default) ;
		if( $val === null ) return false ;
		if( $val == $comp ) return true ;
		return false ;
	}
	
	public function equalsNc( $key, $comp, $default = null ) {
		$val = $this->get($key, $default) ;
		if( $val === null ) return false ;
		if( strcasecmp($val, $comp ) == 0 ) return true ;
		return false ;
	}
	
	public function exists( $key ) {
		return array_key_exists( $key, $this->options ) ;
	}
	
	public function isTrue( $key ) {
		$val = $this->get($key, null) ;
		if( empty($val)) return false ;
		
		if( strcasecmp($val, "true" ) == 0 || strcasecmp($val, "t") == 0 || strcasecmp($val, "1") == 0 ) {
			return true ;
		} else if ( strcasecmp( $val, "false") == 0  || strcasecmp("f") == 0 || strcasecmp($val, "0") == 0 ) {
			return false ;
		}
		
		return ($val === TRUE ) ;
	}
	
	
}