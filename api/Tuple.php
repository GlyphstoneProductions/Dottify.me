<?php

/**
 * Utility function to parse url arguments in the format "key1|value1;key2|value2..."
 * 
*/

class Tuple {

	
	/**
	 * Returns a traditional mapped associated array
	 * @param unknown $input
	 * @return multitype:Ambigous <>
	 */
	public static function parseTuple( $input ) {
		
		$tuples = array() ;
		if( !empty( $input ) ) {
			$tmplist = explode( ';', $input ) ;
			foreach( $tmplist as $item ) {
				$tuple = explode( '|', $item ) ;
				$key = $tuple[0] ;
				$val = null ;
				if( count( $tuple) == 2 ) $val = $tuple[1] ;
				$tuples[$key] = $val ;
			}
	
		}
		return $tuples ;
	}
	
	
	/**
	 * Returns a simple numbered array of elements each of which is an array of three elements
	 * @param unknown $input
	 * @return multitype:multitype:
	 */
	public static function parseTriTuple( $input ) {
		
		$tuples = array() ;
		if( !empty( $input ) ) {
			$tmplist = explode( ';', $input ) ;
			foreach( $tmplist as $item ) {
				$tuple = explode( '|', $item ) ;
				$tuples[] = $tuple ;	
			}
		
		}
		return $tuples ;
	}
	
}