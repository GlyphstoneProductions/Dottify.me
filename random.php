<?php

$rnd = getRandfloat( 300 ) ;
echo $rnd ;

function getRandFloat( $max ) {

	$irnd = mt_rand( 0, $max ) ;
	$rnd = (double)$irnd / 10000.00 ;
	return $rnd ;

}