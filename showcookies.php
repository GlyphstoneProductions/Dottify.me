<?php

var_dump( $_COOKIE );
echo  "\n" ;

if( isset($_COOKIE['DOTTIFYME_USER_UUID'])) {

	$uuid = $_COOKIE['DOTTIFYME_USER_UUID'] ;
	echo "user persistence cookie set: $uuid\n" ;
}