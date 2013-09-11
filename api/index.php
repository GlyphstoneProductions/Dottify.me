<?php

require_once 'Slim/Slim.php';
		
require_once "DottifyManager.php" ;
\Slim\Slim::registerAutoloader();


$app = new \Slim\Slim();
// -------------------------------------
// Routes
$app->get( '/user', 'listusers' ) ;
$app->get( '/user/zip/:zip', 'listusersinzip' ) ;
$app->get( '/user/:uuid', 'getuser' ) ;
$app->post( '/user', 'createuser' ) ;
$app->put( '/user', 'updateuser' ) ;
$app->delete( '/user/:uuid', 'deleteuser' ) ;

// --------------------------------------
$app->run();


function listusers() {
	$sql = "select * FROM user ORDER BY created";
	try {
		$db = getConnection();
		$stmt = $db->query($sql);
		$users = $stmt->fetchAll(PDO::FETCH_OBJ);
		$db = null;
		echo '{"users": ' . json_encode($users) . '}';
	} catch(PDOException $e) {
		echo '{"error":{"text":'. $e->getMessage() .'}}';
	}
}

function getuser($uuid) {
	$sql = "SELECT * FROM user WHERE uuid=:uuid";
	try {
		$db = getConnection();
		$stmt = $db->prepare($sql);
		$stmt->bindParam("uuid", $uuid);
		$stmt->execute();
		$user = $stmt->fetchObject();
		$db = null;
		echo json_encode($user);
	} catch(PDOException $e) {
		echo '{"error":{"text":'. $e->getMessage() .'}}';
	}
}

function createuser() {
	error_log('adduser\n', 3, '/var/tmp/php.log');
	$request = Slim::getInstance()->request();
	$user = json_decode($request->getBody());
	$sql = "INSERT INTO user (name, grapes, country, region, year, description) VALUES (:name, :grapes, :country, :region, :year, :description)";
	try {
		$db = getConnection();
		$stmt = $db->prepare($sql);
		$stmt->bindParam("name", $wine->name);
		$stmt->bindParam("grapes", $wine->grapes);
		$stmt->bindParam("country", $wine->country);
		$stmt->bindParam("region", $wine->region);
		$stmt->bindParam("year", $wine->year);
		$stmt->bindParam("description", $wine->description);
		$stmt->execute();
		$wine->id = $db->lastInsertId();
		$db = null;
		echo json_encode($wine);
	} catch(PDOException $e) {
		error_log($e->getMessage(), 3, '/var/tmp/php.log');
		echo '{"error":{"text":'. $e->getMessage() .'}}';
	}
}

function updateuser() {
	$request = Slim::getInstance()->request();
	$body = $request->getBody();
	$wine = json_decode($body);
	$sql = "UPDATE wine SET name=:name, grapes=:grapes, country=:country, region=:region, year=:year, description=:description WHERE id=:id";
	try {
		$db = getConnection();
		$stmt = $db->prepare($sql);
		$stmt->bindParam("name", $wine->name);
		$stmt->bindParam("grapes", $wine->grapes);
		$stmt->bindParam("country", $wine->country);
		$stmt->bindParam("region", $wine->region);
		$stmt->bindParam("year", $wine->year);
		$stmt->bindParam("description", $wine->description);
		$stmt->bindParam("id", $id);
		$stmt->execute();
		$db = null;
		echo json_encode($wine);
	} catch(PDOException $e) {
		echo '{"error":{"text":'. $e->getMessage() .'}}';
	}
}

function deleteuser($uuid) {
	$sql = "DELETE FROM user WHERE uuid=:uuid";
	try {
		$db = getConnection();
		$stmt = $db->prepare($sql);
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

function getConnection() {

	$dbhost="dottyfydb.ch4won8ycaxv.us-west-2.rds.amazonaws.com";
	$dbuser="sysadmin";
	$dbpass="Maxf1eld";
	$dbname="dott1";
	$dbh = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpass);
	$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	return $dbh;
}

