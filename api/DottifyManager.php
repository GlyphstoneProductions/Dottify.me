<?php

/** This is essentially a collapse of the manager and dao levels of the architecture for now.
 * 
 * @author breanna
 *
 */

require_once "User.php" ;
require_once "Validation.php" ;
require_once "Zipcode.php" ;
require_once "UserCache.php" ;

class DottifyManager {
	
	public function listusers() {
		//$this->showSession() ;
		
		$sql = "select u.id, u.uuid, u.refid, u.ver, u.thisver, u.username, u.created, u.modified, u.refuser, u.refuserid, u.email, u.zipcode, " ;
		$sql .= " u.countrycode, u.usertype, u.userstatus, u.mecon, u.userip, c.latitude, c.longitude " ;
		$sql .= "FROM user u join usercache c on u.id = c.id WHERE u.ver = 0 ORDER BY u.created";
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

		$sql = "select u.id, u.uuid, u.refid, u.ver, u.thisver, u.username, u.created, u.modified, u.refuser, u.refuserid, u.email, u.zipcode, " ;
		$sql .= " u.countrycode, u.usertype, u.userstatus, u.mecon, u.userip, c.latitude, c.longitude " ;
		$sql .= "FROM user u join usercache c on u.id = c.id WHERE u.uuid = :uuid AND u.ver = 0";
		
		//$sql = "SELECT * FROM user WHERE uuid=:uuid and ver = 0";
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
		// lookup ref user and get their userid (integer)
		$userip = $_SERVER['REMOTE_ADDR'] ;

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
		$password = $this->getProp( $user, "password", null ) ;
		if( !is_null( $password )) {
			$cryptpass = md5( $password ) ;
		}
		
		$user->refuser = 0 ;
		$refuserid = $this->getProp($user, "refuserid", null ) ;
		if( !empty($refuserid) ) {
			$referringuser = $this->getUserByRefid( $this->getProp($user, "refuserid", null )) ;
			if( !is_null( $referringuser) ) {
				$user->refuser = intval( $referringuser->id) ;
			}
		}


		$sql = "INSERT INTO user (uuid, refid, created, modified, ver, thisver, refuserid, refuser, zipcode, username, password, email, userstatus, usertype, userip) " ;
	    $sql .= "VALUES (:uuid, :refid, :created, :modified, 0, 1, :refuserid, :refuser, :zipcode, :username, :password, :email, :userstatus, :usertype, :userip)";
		try {
			$db = $this->getConnection();
			$stmt = $db->prepare($sql);
			$stmt->bindParam("uuid", $user->uuid);
			$stmt->bindParam("refid", $user->refid);		// my refid
			$stmt->bindParam("created", $user->created);
			$stmt->bindParam("modified", $user->modified);
			//$stmt->bindParam("ver", $user->year);
			//$stmt->bindParam("thisver", $user->description);
			$stmt->bindParam("refuserid", $user->refuserid);			// hashed refid
			$stmt->bindParam("refuser", $user->refuser,  PDO::PARAM_INT) ;  // integer refid
			$stmt->bindParam("zipcode", $this->getProp( $user, "zipcode", null));
			$stmt->bindParam("username", $this->getProp($user, "username", null) );
			$stmt->bindParam("password", $cryptpass);
			$stmt->bindParam("email", $this->getProp($user, "email", null));
			$stmt->bindParam("userstatus", $user->userstatus);
			$stmt->bindParam("usertype", $user->usertype);
			$stmt->bindParam("userip", $userip) ;
			$stmt->execute();
			error_log('useradded \n', 3, '/var/tmp/php.log');
			$newid = intval( $db->lastInsertId() ) ;
			$user->id = $newid ;
			error_log("new user id $newid\n", 3, '/var/tmp/php.log');

			$db = null;
			$user->password = "" ;	// for security
						
			// look up the lat/long of the zip and compute a random offset as appropriate (+- .015 degrees lat and long ) Maybe less for latitude..
			$usercache = $this->createUserCache($user->id, $user->zipcode, $userip ) ;
			$user->latitude = $usercache->latitude ;
			$user->longitude = $usercache->longitude ;

			return $user ;
		} catch(PDOException $e) {
			$message = $e->getMessage() ;
			error_log("Error creating user: $message\n", 3, '/var/tmp/php.log');
			return array( "Error" => array( "text" => $message) ) ;
		}
	}
	
	private function getProp( $obj, $propname, $default ) {
		if( property_exists( $obj, $propname )) {
			return $obj->$propname ;
		} else {
			return $default ;
		}
	}
	
	/** modify user information
	 * Does not modify:
	 * uuid, refid, password, created, 
	 * @param unknown $user
	 * @return unknown|multitype:multitype:unknown
	 */
	public function updateUser( $user, $norev ) {
		
		error_log('updateuser\n', 3, '/var/tmp/php.log');
		
		// validation:
		// valid zipcode,
		// unique unique email,
		// secure password
		
		// lookup ref user and get their userid (integer)
		$userip = $_SERVER['REMOTE_ADDR'] ;
	
		$now = $date = date('Y-m-d H:i:s');
		if( !$norev ) {
			$this->saveUserVersion( $user ) ;
			$user->thisver++ ;
		}
		
		$user->modified = $now ;
		$user->userip = $userip ;
		
		$sql = "UPDATE user	set modified = :modified, thisver = :thisver, zipcode = :zipcode, username = :username, email = :email, " ;
		$sql .= "userstatus = :userstatus, usertype = :usertype, userip = :userip " ;
		$sql .= "where uuid = :uuid and ver = 0 " ;
		try {
			$db = $this->getConnection();
			$stmt = $db->prepare($sql);
			$stmt->bindParam("uuid", $user->uuid);
			$stmt->bindParam("modified", $user->modified);
			$stmt->bindParam("thisver", $user->thisver, PDO::PARAM_INT) ;
			$stmt->bindParam("zipcode", $user->zipcode);
			$stmt->bindParam("username",  $this->getProp( $user, "username", null));
			$stmt->bindParam("email", $this->getProp( $user, "email"));
			//$stmt->bindParam("userstatus", $this->getProp( user, "userstatus"));
			//$stmt->bindParam("usertype", $this->getProp( $user, "usertype"));
			$stmt->bindParam("userip", $user->userip ) ;
			$stmt->execute();
			error_log('user updated \n', 3, '/var/tmp/php.log');
			$db = null;
			$user->password = "" ;	// for security
		
			// look up the lat/long of the zip and compute a random offset as appropriate (+- .015 degrees lat and long ) Maybe less for latitude..
			$usercache = $this->updateUserCache($user->id, $user->zipcode, $userip ) ;
			$user->latitude = $usercache->latitude ;
			$user->longitude = $usercache->longitude ;
		
			return $user ;
		} catch(PDOException $e) {
			$message = $e->getMessage() ;
			error_log("Error creating user: $message\n", 3, '/var/tmp/php.log');
			return array( "Error" => array( "text" => $message) ) ;
		}
		
	}
	
	/** save the current version of the user as ver = thisver
	 * 
	 * @param unknown $user
	 */
	public function saveUserVersion( $user ) {
		
		$sql = "INSERT INTO user( id, uuid, refid, ver, thisver, username, created, modified, refuser, " ;
		$sql .= "refuserid,  password, email, zipcode, countrycode, usertype, userstatus, mecon, userip ) " ;
		$sql .= "SELECT u.id, u.uuid, u.refid, u.thisver, u.thisver, u.username, u.created, u.modified, u.refuser, " ;
		$sql .= "u.refuserid, u.password, u.email, u.zipcode, u.countrycode, u.usertype, u.userstatus, u.mecon, u.userip ) from user u where u.uuid = :uuid" ;
		try {
			$db = $this->getConnection();
			$stmt = $db->prepare($sql);
			$stmt->bindParam("uuid", $user->uuid);
			$stmt->execute();
			error_log('userversioned \n', 3, '/var/tmp/php.log');
			return $user ;
		} catch(PDOException $e) {
			$message = $e->getMessage() ;
			error_log("Error creating user version: $message\n", 3, '/var/tmp/php.log');
			return array( "Error" => array( "text" => $message) ) ;
		}
	}
	
	public function createUserCache( $userid, $zipcode, $userip ) {
		error_log('addusercache\n', 3, '/var/tmp/php.log');
			
		$userCache = new UserCache() ;
		$userCache->id = $userid ;
		$userCache->lastip = $userip ;
		$zipinfo = $this->getZipcodeInfo( $zipcode ) ;
		
		if( !is_null($zipinfo )) {
			$lat = $zipinfo->latitude ;
			$long = $zipinfo->longitude ;
			$latoffset = -0.015 + $this->getRandFloat( 300 ) ;   // +/- 0.015  degrees should be about 1 mile radius?
			$longoffset = -0.015 + $this->getRandFloat( 300 ) ;
			$userCache->latitude = $lat + $latoffset ;
			$userCache->longitude = $long + $longoffset;
		}

		$sql = "INSERT INTO usercache (id, lastip, latitude, longitude) " ;
		$sql .= "VALUES (:id, :lastip, :latitude, :longitude )";
		try {
			$db = $this->getConnection();
			$stmt = $db->prepare($sql);
			$stmt->bindParam("id", $userCache->id, PDO::PARAM_INT );
			$stmt->bindParam("lastip", $userCache->lastip);		// my refid
			$stmt->bindParam("latitude", $userCache->latitude);
			$stmt->bindParam("longitude", $userCache->longitude);
		
			$stmt->execute();
		
			$db = null;
	
			return $userCache ;
		} catch(PDOException $e) {
			$message = $e->getMessage() ;
			error_log("Error creating user: $message\n", 3, '/var/tmp/php.log');
			return array( "Error" => array( "text" => $message) ) ;
		}
	}
	
	/** 
	 * rewrite the usercache if the zipcode has changed.
	 * @param unknown $userid
	 * @param unknown $zipcode
	 * @param unknown $userip
	 * @return UserCache|multitype:multitype:unknown
	 */
	public function updateUserCache( $userid, $zipcode, $userip ) {
		error_log('updateusercache\n', 3, '/var/tmp/php.log');
			
		$userCache = new UserCache() ;
		$userCache->id = $userid ;
		$userCache->lastip = $userip ;
		$zipinfo = $this->getZipcodeInfo( $zipcode ) ;
		
		if( !is_null($zipinfo )) {
			$lat = $zipinfo->latitude ;
			$long = $zipinfo->longitude ;
			$latoffset = -0.015 + $this->getRandFloat( 300 ) ;   // +/- 0.015  degrees should be about 1 mile radius?
			$longoffset = -0.015 + $this->getRandFloat( 300 ) ;
			$userCache->latitude = $lat + $latoffset ;
			$userCache->longitude = $long + $longoffset;
		}

		$sql = "UPDATE usercache ( lastip, latitude, longitude) " ;
		$sql .= "VALUES ( :lastip, :latitude, :longitude ) where ip = :ip";
		try {
			$db = $this->getConnection();
			$stmt = $db->prepare($sql);
			$stmt->bindParam("id", $userCache->id, PDO::PARAM_INT );
			$stmt->bindParam("lastip", $userCache->lastip);		// my refid
			$stmt->bindParam("latitude", $userCache->latitude);
			$stmt->bindParam("longitude", $userCache->longitude);
		
			$stmt->execute();
		
			$db = null;
	
			return $userCache ;
		} catch(PDOException $e) {
			$message = $e->getMessage() ;
			error_log("Error creating user: $message\n", 3, '/var/tmp/php.log');
			return array( "Error" => array( "text" => $message) ) ;
		}
	}
	
	public function getRandFloat( $max ) {
		
		$irnd = mt_rand( 0, $max ) ;
		$rnd = (double)$irnd / 10000.00 ;
		return $rnd ;
		
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
	
	public function getZipcodeInfo( $zipcode ) {
		$sql = "SELECT * FROM zipinfo WHERE zipcode=:zipcode ";
		try {
			$db = $this->getConnection();
			$stmt = $db->prepare($sql);
			$stmt->bindParam("zipcode", $zipcode);
			$stmt->execute();
			$obj = $stmt->fetchObject("Zipcode") ;
			$db = null;
			return $obj ;
		} catch(PDOException $e) {
			$message = $e->getMessage() ;
			return array( "Error" => array( "text" => $message) ) ;
		}
		
	}
	
	// list zipcodes from refernce database
	public function listZipcodes( $offset, $limit ) {

		$offset = ( is_null( $offset ) )? 0 : intval($offset) ;
		$limit = ( is_null ( $limit))? 100 : intval($limit) ;
		//echo "offset: $offset  limit : $limit\n" ;
		
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
	
	public function listNTDSUsers( $state, $offset, $limit ) {

		$offset = ( is_null( $offset ) )? 0 : intval($offset) ;
		$limit = ( is_null ( $limit))? 100 : intval($limit) ;
		
		$sql = "select n.idcode, n.q10 as zipcode, z.latitude, z.longitude, z.state, n.q2 as assignedgender, n.currentgender, n.complexgender, n.tggnc, n.visualconformity, " ;
		$sql .= "n.medicaltransition, n.surgicaltransition,	n.sofinal, n.age, n.agecat, n.agefulltime, n.areout, n.workforce, n.unemployment, n.income, " ;
		$sql .= "n.q11a as 'white', n.q11b as 'black', n.q11c as 'amind', n.q11d as 'latino', n.q11e as 'api', " ;
		$sql .= "n.q11f as 'mideast', n.q11g as 'multirace' " ;
		$sql .= "from ntdsdata n join zipinfo z on n.q10 = z.zipcode " ;
		if( !is_null( $state ) ) {
			$sql .= " where z.state = :state " ;
		}
		$sql .= "order by z.state limit :offset, :limit " ;
		
		try {
			$db = $this->getConnection();
			$stmt = $db->prepare($sql);
			if( !is_null( $state ) ) {
			   $stmt->bindParam( "state", $state ) ;
			}
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