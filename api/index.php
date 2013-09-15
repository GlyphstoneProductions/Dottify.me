<?php

require_once 'Slim/Slim.php';
		
require_once "DottifyManager.php" ;

use Slim\Slim ;

\Slim\Slim::registerAutoloader();

session_start() ;
$app = new \Slim\Slim();
// -------------------------------------
// Routes
$app->get( '/user', 'listusers' ) ;
$app->get( '/user/zip/:zip', 'listusersinzip' ) ;
$app->post( '/user/validate', 'validateuser' ) ;
$app->get( '/user/validate/:uuid', 'revalidateuser' ) ;
$app->get( '/user/:uuid', 'getuser' ) ;
$app->post( '/user', 'createuser' ) ;
$app->put( '/user', 'updateuser' ) ;
$app->delete( '/user/:uuid', 'deleteuser' ) ;
$app->get( '/zipcode', 'listzipcodes') ;
$app->get( '/zipcode/:zip', 'getzipinfo') ;
$app->get( '/ntdsuser', 'listNTDSUsers') ;

// --------------------------------------
$app->run();

// dispatch the calls to the Manager to separate concerns
//
function listusers() {
	$start = microtime() ;
	$mgr = new DottifyManager() ;
	$result = $mgr->listusers() ;
	send( $result, $start ) ;

}

function getuser($uuid) {
	$start = microtime() ;
	$mgr = new DottifyManager() ;
	send( $mgr->getUserByUuid($uuid) , $start) ;
}

function createuser() {
	$start = microtime() ;
	$request = Slim::getInstance()->request();
	$user = json_decode($request->getBody());
	$mgr = new DottifyManager() ;
	$userout = $mgr->createUser( $user ) ;
	send( $userout, $start) ;
}

function validateuser() {
	
	$start = microtime() ;
	$request = Slim::getInstance()->request();
	$user = json_decode($request->getBody());
	$mode = $reqest->params("mode") ;
	$attributes = $request->params( "attr" ) ;
	if( !empty( $attributes)) {
		$attributes = explode( ',', $attributes) ;
	}
	
	$mgr = new DottifyManager() ;
	$validout = $mgr->validateUser( $user, $attributes, $mode ) ;
	send( $validout, $start) ;
	
}

function revalidateuser( $uuid ) {

	$start = microtime() ;
	$request = Slim::getInstance()->request();
	$attributes = $request->params( "attr" ) ;
	if( !empty( $attributes)) {
		$attributes = explode( ',', $attributes) ;
	}
	
	$mgr = new DottifyManager() ;
	$validout = $mgr->reValidateUser( $uuid, $attributes ) ;
	send( $validout, $start) ;
}

function updateuser() {
	$start = microtime() ;
	$request = Slim::getInstance()->request();
	$user = json_decode($request->getBody());
	$mgr = new DottifyManager() ;
	$userout = $mgr->updateUser( $user ) ;
	send( $userout, $start) ;
}

function deleteuser($uuid) {
	$sql = "DELETE FROM user WHERE uuid=:uuid";
	try {
		$db = getConnection();
		$stmt = $db->prepare($sql);echo
		$stmt->bindParam("uuid", $uuid);
		$stmt->execute();
		$db = null;
	} catch(PDOException $e) {
		echo '{"error":{"text":'. $e->getMessage() .'}}';
	}
}

function listusersinzip($zipcode) {
	$sql = "SELECT * FROM user WHERE zip = :zip ORDER BY created";
	try {
		$db = getConnection();
		$stmt = $db->prepare($sql);
		$stmt->bindParam("zipcode", $zipcode);
		$stmt->execute();
		$users = $stmt->fetchAll(PDO::FETCH_OBJ);
		$db = null;
		echo '{"users": ' . json_encode($users) . '}';
	} catch(PDOException $e) {
		echo '{"error":{"text":'. $e->getMessage() .'}}';
	}
}

function listzipcodes( ) {
	$start = microtime() ;
	$request = Slim::getInstance()->request();
	$offset = $request->get( "offset" ) ;
	$limit = $request->get( "limit" ) ;

	$mgr = new DottifyManager() ;
	$out = $mgr->listZipcodes( $offset, $limit ) ;
	send( $out, $start) ;
	

}

function getzipinfo($zipcode) {
	$start = microtime() ;

	$mgr = new DottifyManager() ;
	$out = $mgr->getZipcodeInfo( $zipcode ) ;
	send( $out, $start) ;
}

function listNTDSUsers() {
	$start = microtime() ;
	$request = Slim::getInstance()->request();
	$offset = $request->get( "offset" ) ;
	$limit = $request->get( "limit" ) ;
	$state = $request->get( "state" ) ;
	
	$mgr = new DottifyManager() ;
	$out = $mgr->listNTDSUsers( $state, $offset, $limit ) ;
	send( $out, $start) ;	
	
	
}

// --------------------------------------------------------------
function send( $result, $start ) {
	$elapsed = microtime() - $start ;
	// $result->elapsed = $elapsed ;
	header( "Content-type: application/json") ;
	echo json_encode($result) ;
	
}

function getConnection() {

	$dbhost="dottyfydb.ch4won8ycaxv.us-west-2.rds.amazonaws.com";
	$dbuser="sysadmin";
	$dbpass="Maxf1eld";
	$dbname="dott1";
	$dbh = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpass);
	$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	return $dbh;
}

