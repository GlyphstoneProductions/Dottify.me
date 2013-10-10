<?php
/**
 * Simple transient page to set the uuid remember me cookie for the acccount.
 */

$redirect = $_GET['redir'] ;
$uuid = $_GET['uuid'] ;

// echo "Redir: $redirect  uuid: $uuid\n" ;

$time = 0 ;
$args = "" ;

if( strlen($uuid) > 0 ) {
	$time = time() + 60*60*24*365 ;
	$args = "?uuid=$uuid";
}

setcookie( "DOTTIFYME_USER_UUID", $uuid, $time, '/' ) ;

header("Location: $redirect$args");