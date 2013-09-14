<?php

/** This is essentially a collapse of the manager and dao levels of the architecture for now.
 * 
 * @author breanna
 *
 */

require_once "User.php" ;
require_once "Validation.php" ;
require_once "Zipcode.php" ;

class DottifyManager {
	
	public function listusers() {
		//$this->showSession() ;
		
		$sql = "select id, uuid, refid, ver, thisver, username, created, modified, refuser, refuserid, email, zipcode, countrycode, usertype, userstatus, mecon " ;
		$sql .= "FROM user WHERE ver = 0 ORDER BY created";
		try {
			$db = $this->getConnection();
			$stmt = $db->query($sql);
			$users = $stmt->fetchAll(PDO::FETCH_OBJ);
			$db = null;
			$visits = $this->getVisits();
			$usersobj = new Users() ;
			$usersobj->elements = $users ;
			return $usersobj ;
		} catch(PDOException $e) {
			$message = $e->getMessage() ;
			return array( "Error" => array( "text" => $message) ) ;
		}
	}
	
	public function getUserByUuid( $uuid ) {
		$sql = "SELECT * FROM user WHERE uuid=:uuid and ver = 0";
		try {
			$db = $this->getConnection();
			$stmt = $db->prepare($sql);
			$stmt->bindParam("uuid", $uuid);
			$stmt->execute();
			$user = $stmt->fetchObject("User") ;
			$visits = $this->getVisits() ;
			$user->visits = $visits ;
			$user->password = "" ;	// blank for security
			$db = null;
			return $user ;
		} catch(PDOException $e) {
			$message = $e->getMessage() ;
			return array( "Error" => array( "text" => $message) ) ;
		}
	}
	
	public function getUserByRefid( $refid ) {
		$sql = "SELECT * FROM user WHERE refid=:refid and ver = 0";
		try {
			$db = $this->getConnection();
			$stmt = $db->prepare($sql);
			$stmt->bindParam("refid", $refid);
			$stmt->execute();
			$user = $stmt->fetchObject("User") ;
			$db = null;
			return $user ;
		} catch(PDOException $e) {
			$message = $e->getMessage() ;
			return array( "Error" => array( "text" => $message) ) ;
		}
	}
	
	/**
	 * Create a new user from scratch.
	 * @param unknown $user
	 * @return unknown|multitype:multitype:unknown
	 */
	public function createUser( $user ) {
		error_log('adduser\n', 3, '/var/tmp/php.log');
		
		// validation:
		// valid zipcode, 
		// unique userid, unique email, 
		// secure password
		// valid referring user

		$uuid = uniqid( "dottify", true ) ; // more entropy!
		$refid = md5( $uuid) ;
		$created = $date = date('Y-m-d H:i:s');
		// add to object
		$user->uuid = $uuid ;
		$user->refid = $refid ;
		$user->created = $created ;
		$user->modified = $created ;
		$user->ver = 0 ;	// the current entity is always ver 0 (with thisver = 1)
		$user->thisver = 1 ;
		$user->usertype = 0 ; // default user type
		$user->userstatus = 0 ; // status active for now
		$cryptpass = null ;
		if( !is_null( $user->password)) {
			$cryptpass = md5( $user->password ) ;
		}
		// TODO:
		// log the visit and the ip address
		// lookup ref user and get their userid (integer)
		// look up the lat/long of the zip and compute a random offset as appropriate
		
		$sql = "INSERT INTO user (uuid, refid, created, modified, ver, thisver, refuserid, zipcode, username, password, email, userstatus, usertype) " ;
	    $sql .= "VALUES (:uuid, :refid, :created, :modified, 0, 1, :refuserid, :zipcode, :username, :password, :email, :userstatus, :usertype)";
		try {
			$db = $this->getConnection();
			$stmt = $db->prepare($sql);
			$stmt->bindParam("uuid", $user->uuid);
			$stmt->bindParam("refid", $user->refid);
			$stmt->bindParam("created", $user->created);
			$stmt->bindParam("modified", $user->modified);
			//$stmt->bindParam("ver", $user->year);
			//$stmt->bindParam("thisver", $user->description);
			$stmt->bindParam("refuserid", $user->refuserid);
			$stmt->bindParam("zipcode", $user->zipcode);
			$stmt->bindParam("username", $user->username);
			$stmt->bindParam("password", $cryptpass);
			$stmt->bindParam("email", $user->email);
			$stmt->bindParam("userstatus", $user->userstatus);
			$stmt->bindParam("usertype", $user->usertype);
			$stmt->execute();

			$db = null;
			$user->password = "" ;	// for security

			return $user ;
		} catch(PDOException $e) {
			$message = $e->getMessage() ;
			error_log("Error creating user: $message\n", 3, '/var/tmp/php.log');
			return array( "Error" => array( "text" => $message) ) ;
		}
	}
	
	public function validateUser( $user, $attributes, $mode ) {
		
		if( $mode === "create" ) {
			// verity unique userid and unique email if specified
			
		}
		$val = new ValidationList() ;
		$val->add( new Validation("zipcode", false, "not a valid zipcode")) ;
		$val->add( new Validation("password", false, "Password is not strong enough")) ;
		
		return $val ;
	}
	
	public function reValidateUser( $uuid, $attributes ) {
		$user = $this->getUserByUuid( $uuid ) ;
		if( !empty( $user)) {
			return $this->validateUser( $user, $attributes ) ;
		} else {
			return "User Not Found" ;
		}
	
	}
	
	// list zipcodes from refernce database
	public function listZipcodes( $offset, $limit ) {

		$offset = ( is_null( $offset ) )? 0 : $offset ;
		$limit = ( is_null ( $limit))? 100 : $limit ;
		echo "offset: $offset  limit : $limit\n" ;
		
		$sql = "select zipcode, country, latitude, longitude, state, population" ;
		$sql .= " FROM zipinfo where population > 0 ORDER BY state LIMIT  :offset, :limit";
		//$sql .= " FROM zipinfo where population > 0 ORDER BY state limit 0, 20";
		try {
			$db = $this->getConnection();
			$stmt = $db->prepare($sql);
			$stmt->bindParam("offset", $offset, PDO::PARAM_INT);
			$stmt->bindParam("limit", $limit, PDO::PARAM_INT);
			$stmt->execute();
			$objs = $stmt->fetchAll(PDO::FETCH_OBJ);
			$db = null;
			return $objs ;
		} catch(PDOException $e) {
			$message = $e->getMessage() ;
			return array( "Error" => array( "text" => $message) ) ;
		}

	}
	
	protected function getVisits() {

		if( isset( $_SESSION['count'])) {
			$_SESSION['count']++ ;
		} else {
			$_SESSION['count'] = 1;
		}
		$visits = $_SESSION['count'] ;
		return $visits ;
	}
	
	protected function showSession() {
		$visits = $this->getVisits() ;
		echo "visits: $visits\n" ;
	}
	
	protected function getConnection() {
	
		$dbhost="dottyfydb.ch4won8ycaxv.us-west-2.rds.amazonaws.com";
		$dbuser="sysadmin";
		$dbpass="Maxf1eld";
		$dbname="dott1";
		$dbh = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpass);
		$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		return $dbh;
	}
}