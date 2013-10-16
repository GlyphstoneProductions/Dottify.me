<?php

/** This is essentially a collapse of the manager and dao levels of the architecture for now.
 * 
 * @author breanna
 *
 */
require_once "User.php";
require_once "Validation.php";
require_once "Zipcode.php";
require_once "UserCache.php";
require_once "UserSessionInfo.php";
class DottifyManager {
	public function listusers() {
		// $this->showSession() ;
		$sql = "select u.id, u.uuid, u.refid, u.ver, u.thisver, u.username, u.created, u.modified, u.refuser, u.refuserid, u.email, u.zipcode, ";
		$sql .= " u.countrycode, u.usertype, u.userstatus, u.userclass, u.mecon, u.userip, u.staylogged, c.latitude, c.longitude ";
		$sql .= "FROM user u left outer join usercache c on u.id = c.id WHERE u.ver = 0 ORDER BY u.created";
		try {
			$db = $this->getConnection ();
			$stmt = $db->query ( $sql );
			$users = $stmt->fetchAll ( PDO::FETCH_OBJ );
			$db = null;
			$visits = $this->getVisits ();
			$usersobj = new Users ();
			$usersobj->elements = $users;
			return $usersobj;
		} catch ( PDOException $e ) {
			$message = $e->getMessage ();
			return array (
					"Error" => array (
							"text" => $message 
					) 
			);
		}
	}
	
	public function getUserByUuid($uuid) {
		$sql = "select u.id, u.uuid, u.refid, u.ver, u.thisver, u.username, u.created, u.modified, u.refuser, u.refuserid, u.email, u.zipcode, ";
		$sql .= " u.countrycode, u.usertype, u.userstatus, u.userclass, u.mecon, u.userip, u.staylogged, c.latitude, c.longitude ";
		$sql .= "FROM user u left outer join usercache c on u.id = c.id WHERE u.uuid = :uuid AND u.ver = 0";
		
		// $sql = "SELECT * FROM user WHERE uuid=:uuid and ver = 0";
		try {
			$db = $this->getConnection ();
			$stmt = $db->prepare ( $sql );
			$stmt->bindParam ( "uuid", $uuid );
			$stmt->execute ();
			$user = $stmt->fetchObject ( "User" );
			//$visits = $this->getVisits ();
			// $user->visits = $visits;
			$influence = $this->getMyInfluence( $uuid, 1);
			$user->refcount = $influence["refcount"];
			$user->refrank = $influence["rank"] ;
			$user->numOpenQuestions = $this->getNumOpenQuestions( $uuid );
			$user->password = ""; // blank for security
			$db = null;
			return $user;
		} catch ( PDOException $e ) {
			$message = $e->getMessage ();
			return array (
					"Error" => array (
							"text" => $message 
					) 
			);
		}
	}
	
	public function getMyInfluence( $uuid, $depth ) {
		$influencers = $this->getInfluencers() ;
		$refcount = 0 ;
		$rank = null ;
		$n = 1;
		foreach( $influencers as $infl ) {
			if( $uuid == $infl->uuid ) {
				$refcount = $infl->refcount ;
				$rank = $n;
				break;
			}
			$n++ ;
		}
		$result = array( "refcount" => $refcount, "rank" => $rank) ;
		return $result ;
	}
	
	public function getNumOpenQuestions( $uuid ) {
		$opencount = 0 ;
		$survey = $this->getBaseSurveyByUuid($uuid);
		
		if( $survey ) {
			$fields = array( "age", "income", "race", "assignedgender", "primgenderid", "genderdesc", 
					"gid10", "gid20", "gid30", "gid40", "gid50", "gid60", "gid70", "gid80", "gid90", "gid100",
					"gid110", "gid120", "gid120", "gid140", "gid150", "gid160"
			 ) ;
			foreach( $fields as $field ) {
				if( !isset( $survey->$field )) $opencount++ ;
			}
		} else {
			$opencount = -1 ;
		}
		return $opencount ;
	}
	
	public function getInfluencers() {
		
		$sql = "SELECT u.uuid, u.id, u.created, u.username, u.zipcode, count(*) as 'refcount' " .
		    "from user u join user ru on ru.refuser = u.id and ru.ver = 0 " .
		    "where u.ver = 0 " .
		    "group by u.uuid	order by count(*) desc ";

		try {
			$db = $this->getConnection ();
			$stmt = $db->query ( $sql );
			$refs = $stmt->fetchAll ( PDO::FETCH_OBJ );
			$db = null;
			return $refs ;
		} catch ( PDOException $e ) {
			return null ;
		}
		
	}
	
	public function findOrphanUser( $user ) {
		$zipcode = $user->zipcode ;
		
		$sql = "select uuid from user where zipcode = :zipcode and userstatus = 0 order by id limit 1" ;
		try {
			$db = $this->getConnection ();
			$stmt = $db->prepare ( $sql );
			$stmt->bindParam ( "zipcode", $zipcode );
			$stmt->execute ();
			$uuid = $stmt->fetchColumn() ;
			$db = null;
			
			if( $uuid ) {
				error_log ( "orphan found $uuid \n", 3, '/var/tmp/php.log' );
				$userout = $this->getUserByUuid( $uuid ) ;
				if( $userout ) {
					$userout->userstatus = 1 ;
					$userout->userclass = $user->userclass ;
					$userout->mecon = $user->mecon ;
					$userout = $this->updateUser( $userout, true ) ;	// norev
				}
				return $userout ;
			}
		} catch ( PDOException $e ) {
			$message = $e->getMessage ();
			error_log ( "error finding orphan $message \n", 3, '/var/tmp/php.log' );
		}
		return false ;
		
	}
	
	public function getUserByRefid($refid) {
		$sql = "SELECT * FROM user WHERE refid=:refid and ver = 0";
		try {
			$db = $this->getConnection ();
			$stmt = $db->prepare ( $sql );
			$stmt->bindParam ( "refid", $refid );
			$stmt->execute ();
			$user = $stmt->fetchObject ( "User" );
			$db = null;
			return $user;
		} catch ( PDOException $e ) {
			$message = $e->getMessage ();
			return array (
					"Error" => array (
							"text" => $message 
					) 
			);
		}
	}
	
	
	public function getUserByEmail($email ) {
		$sql = "SELECT uuid FROM user WHERE email=:email and ver = 0";
		try {
			$db = $this->getConnection ();
			$stmt = $db->prepare ( $sql );
			$stmt->bindParam ( "email", $email );
			$stmt->execute ();
			$uuid = $stmt->fetchColumn();
			$db = null;
			return $uuid;
		} catch ( PDOException $e ) {
			$message = $e->getMessage ();
			return array (
					"Error" => array (
							"text" => $message
					)
			);
		}
	}
	
	public function loginUser($username, $password) {
		$cryptpass = md5($password) ;
		$sql = "SELECT uuid FROM user WHERE username=:username and password = :cryptpass and ver = 0";
		try {
			$db = $this->getConnection ();
			$stmt = $db->prepare ( $sql );
			$stmt->bindParam ( "username", $username );
			$stmt->bindParam ( "cryptpass", $cryptpass );
			$stmt->execute ();
			$uuid = $stmt->fetchColumn();
			$db = null;
			return $uuid;
		} catch ( PDOException $e ) {
			$message = $e->getMessage ();
			return array (
					"Error" => array (
							"text" => $message
					)
			);
		}
	}
	/**
	 * Create a new user from scratch.
	 * 
	 * @param unknown $user        	
	 * @return unknown multitype:multitype:unknown
	 */
	public function createUser($user, $adopt) {
		$created = $date = date ( 'Y-m-d H:i:s' );
		error_log ( "adduser $created \n", 3, '/var/tmp/php.log' );
		
		if( $adopt ) {
			$adoptee = $this->findOrphanUser( $user) ;
			if( $adoptee ) {
				return $adoptee ;
			}
		}
	
		$userip = $_SERVER ['REMOTE_ADDR'];
		
		$uuid = uniqid ( "dottify", true ); // more entropy!
		$refid = md5 ( $uuid );
		// $created = $date = date('Y-m-d H:i:s');
		// add to object
		$user->uuid = $uuid;
		$user->refid = $refid;
		$user->created = $created;
		$user->modified = $created;
		$user->ver = 0; // the current entity is always ver 0 (with thisver = 1)
		$user->thisver = 1;
		$user->usertype = 0; // default user type
		$user->userstatus = 1; // status registered for now
	
		$cryptpass = null;
		$password = $this->getProp ( $user, "password", null );
		if (! is_null ( $password )) {
			$cryptpass = md5 ( $password );
		}
		
		$user->refuser = 0;
		$refuserid = $this->getProp ( $user, "refuserid", null );
		if (! empty ( $refuserid )) {
			$referringuser = $this->getUserByRefid ( $this->getProp ( $user, "refuserid", null ) );
			if (! is_null ( $referringuser )) {
				$user->refuser = intval ( $referringuser->id );
			}
		}
		
		$sql = "INSERT INTO user (uuid, refid, created, modified, ver, thisver, refuserid, refuser, zipcode, countrycode, mecon, username, password, email, userstatus, usertype, userclass, userip, staylogged) ";
		$sql .= "VALUES (:uuid, :refid, :created, :modified, 0, 1, :refuserid, :refuser, :zipcode, :countrycode, :mecon, :username, :password, :email, :userstatus, :usertype, :userclass, :userip, :staylogged)";
		try {
			$db = $this->getConnection ();
			$stmt = $db->prepare ( $sql );
			$stmt->bindParam ( "uuid", $user->uuid );
			$stmt->bindParam ( "refid", $user->refid ); // my refid
			$stmt->bindParam ( "created", $user->created );
			$stmt->bindParam ( "modified", $user->modified );
			// $stmt->bindParam("ver", $user->year);
			// $stmt->bindParam("thisver", $user->description);
			$stmt->bindParam ( "refuserid", $user->refuserid ); // hashed refid
			$stmt->bindParam ( "refuser", $user->refuser, PDO::PARAM_INT ); // integer refid
			$stmt->bindParam ( "zipcode", $this->getProp ( $user, "zipcode", null ) );
			$stmt->bindParam ( "countrycode", $this->getProp( $user, "countrycode", "US")) ;			
			$stmt->bindParam ( "mecon", $user->mecon ) ;
			$stmt->bindParam ( "username", $this->getProp ( $user, "username", null ) );
			$stmt->bindParam ( "password", $cryptpass );
			$stmt->bindParam ( "email", $this->getProp ( $user, "email", null ) );
			$stmt->bindParam ( "userstatus", $user->userstatus, PDO::PARAM_INT );
			$stmt->bindParam ( "usertype", $user->usertype, PDO::PARAM_INT );
			$stmt->bindParam ( "userclass", $user->userclass, PDO::PARAM_INT );
			$stmt->bindParam ( "userip", $userip );
			$stmt->bindParam ( "staylogged", $user->staylogged, PDO::PARAM_INT );
			$stmt->execute ();
			error_log ( 'useradded \n', 3, '/var/tmp/php.log' );
			$newid = intval ( $db->lastInsertId () );
			$user->id = $newid;
			error_log ( "new user id $newid\n", 3, '/var/tmp/php.log' );
			
			$db = null;
			$user->password = ""; // for security
			                       
			// look up the lat/long of the zip and compute a random offset as appropriate (+- .015 degrees lat and long ) Maybe less for latitude..
			$usercache = $this->createUserCache ( $user->id, $user->zipcode, $userip );
			$user->latitude = $usercache->latitude;
			$user->longitude = $usercache->longitude;
			
			return $user;
		} catch ( PDOException $e ) {
			$message = $e->getMessage ();
			error_log ( "Error creating user: $message... \n", 3, '/var/tmp/php.log' );
			return array (
					"Error" => array (
							"text" => $message 
					) 
			);
		}
	}
	
	private function getProp($obj, $propname, $default) {
		error_log ( "get property $propname \n", 3, '/var/tmp/php.log' );
		if (property_exists ( $obj, $propname )) {
			return $obj->$propname;
		} else {
			error_log ( "prop $propname not found \n", 3, '/var/tmp/php.log' );
			return $default;
		}
	}
	
	/**
	 * modify user information
	 * Does not modify:
	 * uuid, refid, password, created,
	 * 
	 * @param unknown $user        	
	 * @return unknown multitype:multitype:unknown
	 */
	public function updateUser($user, $norev) {
		
		$now = $date = date ( 'Y-m-d H:i:s' );
		error_log ( "updateuser $now  norev=$norev\n", 3, '/var/tmp/php.log' );
		
		$userip = $_SERVER ['REMOTE_ADDR'];

		if (!$norev) {
			error_log ( "save user version \n", 3, '/var/tmp/php.log' );
			$this->saveUserVersion ( $user );
			$user->thisver = intval($user->thisver) + 1;
		}
		$cryptpass = null;
		$password = $this->getProp ( $user, "password", null );
		if (! is_null ( $password ) ) {
			$password = trim( $password ) ;
			if( strlen( $password ) > 0 ) {
				$cryptpass = md5 ( $password );
			}
		}
		
		// error_log("Update pwd: $cryptpass   username: ") ;
		$user->modified = $now;
		$user->userip = $userip;
		
		$sql = "UPDATE user	set modified = :modified, thisver = :thisver, zipcode = :zipcode, countrycode = :countrycode, mecon = :mecon, username = :username, email = :email, ";
		$sql .= " userclass = :userclass, userstatus = :userstatus, userip = :userip, staylogged = :staylogged ";
		if( $cryptpass != null ) {
			$sql .= ", password = :password ";
		}
		$sql .= "where uuid = :uuid and ver = 0 ";
		try {
			error_log ( "do update\n", 3, '/var/tmp/php.log' );
			$db = $this->getConnection ();
			$stmt = $db->prepare ( $sql );
			$stmt->bindParam ( "uuid", $user->uuid );
			$stmt->bindParam ( "modified", $user->modified );
			$stmt->bindParam ( "thisver", $user->thisver, PDO::PARAM_INT );
			$stmt->bindParam ( "zipcode", $user->zipcode );
			$stmt->bindParam ( "countrycode", $this->getProp( $user, "countrycode", "US")) ;
			$stmt->bindParam ( "mecon", $user->mecon ) ;			
			$stmt->bindParam ( "username", $this->getProp ( $user, "username", null ) );
			$stmt->bindParam ( "email", $this->getProp ( $user, "email", null ) );
			$stmt->bindParam ( "userclass", $user->userclass, PDO::PARAM_INT );
			$stmt->bindParam ( "userstatus", $user->userstatus, PDO::PARAM_INT);
			$stmt->bindParam ( "userip", $user->userip );
			$stmt->bindParam ( "staylogged", $user->staylogged, PDO::PARAM_INT );
			if( $cryptpass != null ) {
				$stmt->bindParam ( "password", $cryptpass );
			}
			// $stmt->bindParam ( "userstatus", $this->getProp( $user, "userstatus", PDO::PARAM_INT));		// criteria for setting user
			// $stmt->bindParam("usertype", $this->getProp( $user, "usertype"));
			$stmt->execute ();
			error_log ( "user updated \n", 3, '/var/tmp/php.log' );
			$db = null;
			$user->password = ""; // for security
			                       
			//TODO: update the usercache if the zipcode has changed.
			
			return $user;
		} catch ( PDOException $e ) {
			$message = $e->getMessage ();
			error_log ( "Error creating user: $message\n", 3, '/var/tmp/php.log' );
			return array (
					"Error" => array (
							"text" => $message 
					) 
			);
		}
	}
	
	/**
	 * save the current version of the user as ver = thisver
	 *
	 * @param unknown $user        	
	 */
	public function saveUserVersion($user) {
		$sql = "INSERT INTO user( id, uuid, refid, ver, thisver, username, created, modified, refuser, ";
		$sql .= "refuserid,  password, email, zipcode, countrycode, usertype, userstatus, userclass, mecon, userip, staylogged ) ";
		$sql .= "SELECT u.id, u.uuid, u.refid, u.thisver, u.thisver, u.username, u.created, u.modified, u.refuser, ";
		$sql .= "u.refuserid, u.password, u.email, u.zipcode, u.countrycode, u.usertype, u.userstatus, u.userclass, u.mecon, u.userip, u.staylogged from user u where u.uuid = :uuid and ver = 0";
		try {
			$db = $this->getConnection ();
			$stmt = $db->prepare ( $sql );
			$stmt->bindParam ( "uuid", $user->uuid );
			$stmt->execute ();
			error_log ( 'userversioned \n', 3, '/var/tmp/php.log' );
			return $user;
		} catch ( PDOException $e ) {
			$message = $e->getMessage ();
			error_log ( "Error creating user version: $message\n", 3, '/var/tmp/php.log' );
			return array (
					"Error" => array (
							"text" => $message 
					) 
			);
		}
	}
	
	public function createUserCache($userid, $zipcode, $userip) {
		error_log ( 'addusercache\n', 3, '/var/tmp/php.log' );
		
		$userCache = new UserCache ();
		$userCache->id = $userid;
		$userCache->lastip = $userip;
		$zipinfo = $this->getZipcodeInfo ( $zipcode );
		
		if (! is_null ( $zipinfo )) {
			$lat = $zipinfo->latitude;
			$long = $zipinfo->longitude;
			$latoffset = - 0.015 + $this->getRandFloat ( 300 ); // +/- 0.015 degrees should be about 1 mile radius?
			$longoffset = - 0.015 + $this->getRandFloat ( 300 );
			$userCache->latitude = $lat + $latoffset;
			$userCache->longitude = $long + $longoffset;
		}
		
		$sql = "INSERT INTO usercache (id, lastip, latitude, longitude) ";
		$sql .= "VALUES (:id, :lastip, :latitude, :longitude )";
		try {
			$db = $this->getConnection ();
			$stmt = $db->prepare ( $sql );
			$stmt->bindParam ( "id", $userCache->id, PDO::PARAM_INT );
			$stmt->bindParam ( "lastip", $userCache->lastip ); // my refid
			$stmt->bindParam ( "latitude", $userCache->latitude );
			$stmt->bindParam ( "longitude", $userCache->longitude );
			
			$stmt->execute ();
			
			$db = null;
			
			return $userCache;
		} catch ( PDOException $e ) {
			$message = $e->getMessage ();
			error_log ( "Error creating user: $message\n", 3, '/var/tmp/php.log' );
			return array (
					"Error" => array (
							"text" => $message 
					) 
			);
		}
	}
	
	/**
	 * rewrite the usercache if the zipcode has changed.
	 * 
	 * @param unknown $userid        	
	 * @param unknown $zipcode        	
	 * @param unknown $userip        	
	 * @return UserCache multitype:multitype:unknown
	 */
	public function updateUserCache($userid, $zipcode, $userip) {
		error_log ( 'updateusercache\n', 3, '/var/tmp/php.log' );
		
		$userCache = new UserCache ();
		$userCache->id = $userid;
		$userCache->lastip = $userip;
		$zipinfo = $this->getZipcodeInfo ( $zipcode );
		
		if (! is_null ( $zipinfo )) {
			$lat = $zipinfo->latitude;
			$long = $zipinfo->longitude;
			$latoffset = - 0.015 + $this->getRandFloat ( 300 ); // +/- 0.015 degrees should be about 1 mile radius?
			$longoffset = - 0.015 + $this->getRandFloat ( 200 );
			$userCache->latitude = $lat + $latoffset;
			$userCache->longitude = $long + $longoffset;
		}
		
		$sql = "UPDATE usercache ( lastip, latitude, longitude) ";
		$sql .= "VALUES ( :lastip, :latitude, :longitude ) where ip = :ip";
		try {
			$db = $this->getConnection ();
			$stmt = $db->prepare ( $sql );
			$stmt->bindParam ( "id", $userCache->id, PDO::PARAM_INT );
			$stmt->bindParam ( "lastip", $userCache->lastip ); // my refid
			$stmt->bindParam ( "latitude", $userCache->latitude );
			$stmt->bindParam ( "longitude", $userCache->longitude );
			
			$stmt->execute ();
			
			$db = null;
			
			return $userCache;
		} catch ( PDOException $e ) {
			$message = $e->getMessage ();
			error_log ( "Error creating user: $message\n", 3, '/var/tmp/php.log' );
			return array (
					"Error" => array (
							"text" => $message 
					) 
			);
		}
	}
	
	public function getRandFloat($max) {
		$irnd = mt_rand ( 0, $max );
		$rnd = ( double ) $irnd / 10000.00;
		return $rnd;
	}
	
	
	public function addBaseSurvey($survey) {
		$created = $date = date ( 'Y-m-d H:i:s' );
		$survey->created = $created ;
		error_log ( "addSurvey $created \n", 3, '/var/tmp/php.log' );
		
		$sql = 'INSERT INTO usersurvey1 (id, ver, created, age, income, race, race2, race3, assignedgender, primgenderid, genderdesc, gid10, gid20, gid30, gid40, gid50, ';
		$sql .= 'gid60, gid70, gid80, gid90, gid100, gid110, gid120, gid130, gid140, gid150, gid160	) ' ;
		$sql .=	'VALUES (:id, :ver, :created, :age, :income, :race, :race2, :race3, :assignedgender, :primgenderid, :genderdesc, :gid10, :gid20, :gid30, :gid40, :gid50, ' ;
		$sql .= ' :gid60, :gid70, :gid80, :gid90, :gid100, :gid110, :gid120, :gid130, :gid140, :gid150, :gid160 )';
		try {
			$db = $this->getConnection ();
			$stmt = $db->prepare ( $sql );
			$stmt->bindParam ( "id", $survey->id );
			$stmt->bindParam ( "ver", $survey->ver ); 
			$stmt->bindParam ( "created", $created );
			$stmt->bindParam ( "age", $survey->age, PDO::PARAM_INT );			
			$stmt->bindParam ( "income", $survey->income, PDO::PARAM_INT ); 
			$stmt->bindParam ( "race", $survey->race, PDO::PARAM_INT ); 
			$stmt->bindParam ( "race2", $survey->race2, PDO::PARAM_INT ); 
			$stmt->bindParam ( "race3", $survey->race3, PDO::PARAM_INT );
			$stmt->bindParam ( "assignedgender", $survey->assignedgender, PDO::PARAM_INT ); 
			$stmt->bindParam ( "primgenderid", $survey->primgenderid, PDO::PARAM_INT ); 
			$stmt->bindParam ( "genderdesc", $survey->genderdesc ); 
			$stmt->bindParam ( "gid10", $survey->gid10, PDO::PARAM_INT ); 
			$stmt->bindParam ( "gid20", $survey->gid20, PDO::PARAM_INT );
			$stmt->bindParam ( "gid30", $survey->gid30, PDO::PARAM_INT );
			$stmt->bindParam ( "gid40", $survey->gid40, PDO::PARAM_INT );
			$stmt->bindParam ( "gid50", $survey->gid50, PDO::PARAM_INT );
			$stmt->bindParam ( "gid60", $survey->gid60, PDO::PARAM_INT );
			$stmt->bindParam ( "gid70", $survey->gid70, PDO::PARAM_INT );
			$stmt->bindParam ( "gid80", $survey->gid80, PDO::PARAM_INT );
			$stmt->bindParam ( "gid90", $survey->gid90, PDO::PARAM_INT );
			$stmt->bindParam ( "gid100", $survey->gid100, PDO::PARAM_INT );
			$stmt->bindParam ( "gid110", $survey->gid110, PDO::PARAM_INT );
			$stmt->bindParam ( "gid120", $survey->gid120, PDO::PARAM_INT );
			$stmt->bindParam ( "gid130", $survey->gid130, PDO::PARAM_INT );
			$stmt->bindParam ( "gid140", $survey->gid140, PDO::PARAM_INT );
			$stmt->bindParam ( "gid150", $survey->gid150, PDO::PARAM_INT );
			$stmt->bindParam ( "gid160", $survey->gid160, PDO::PARAM_INT );
		
			$stmt->execute ();
			$db = null;
			return $survey ;
		} catch ( PDOException $e ) {
			$message = $e->getMessage ();
			error_log ( "Error adding survey: $message... \n", 3, '/var/tmp/php.log' );
			return array (
					"Error" => array (
							"text" => $message
					)
			);
		}
	}
	
	public function getBaseSurvey( $id, $ver ) {
		
		$sql = "SELECT * FROM usersurvey1 WHERE id = :id and ver = :ver ";
		try {
			$db = $this->getConnection ();
			$stmt = $db->prepare ( $sql );
			$stmt->bindParam ( "id", $id, PDO::PARAM_INT );
			$stmt->bindParam ( "ver", $ver, PDO::PARAM_INT );
			$stmt->execute ();
			$obj = $stmt->fetchObject();
			$db = null;
			return $obj;
		} catch ( PDOException $e ) {
			$message = $e->getMessage ();
			return array (
					"Error" => array (
							"text" => $message
					)
			);
		}
		
	}
	
	public function getBaseSurveybyUuid( $uuid ) {
	    
		$sql = "SELECT s.* FROM usersurvey1 s join user u on s.id = u.id and s.ver = u.thisver WHERE u.uuid = :uuid and u.ver = 0 ";
		try {
			$db = $this->getConnection ();
			$stmt = $db->prepare ( $sql );
			$stmt->bindParam ( "uuid", $uuid );
			$stmt->execute ();
			$obj = $stmt->fetchObject();
			$db = null;
			return $obj;
		} catch ( PDOException $e ) {
			$message = $e->getMessage ();
			return array (
					"Error" => array (
							"text" => $message
					)
			);
		}
	
	}
	
	public function validateUser($user, $attributes, $mode) {


		$val = new ValidationList ();
		// valid zipcode,
		if( !$this->zipcodeExists( $user->zipcode )) {
			$val->add ( new Validation ( "zipcode", false, "zipcode not found. Only 5 digits allowed." ) );
			$val->allvalid = false ;
		}
		
		if( !isset( $user->userclass)) {
			$val->add ( new Validation ( "userclass", false, "You must choose a user class." ) );
			$val->allvalid = false ;			
		}
		
		if( $mode != "revalidate" ) {
			if( isset($user->password)) {
				$pwerr = $this->strongEnoughPassword($user->password) ;
				if( isset( $pwerr) ) {
					$val->add( $pwerr ) ;
					$val->allvalid = false ;
				}
			}
			if( isset( $user->username )) {
				// unique userid, unique email,
				if( $this->usernameExists( $user->username, $user->id )) {
					$val->add ( new Validation ( "username", false, "The username is already in use." ) );
					$val->allvalid = false ;
				}
			}
			if( isset( $user->email )) {
				if( $this->emailExists( $user->email, $user->id )) {
					$val->add ( new Validation ( "email", false, "The email is already in use." ) );
					$val->allvalid = false ;
				}
			}
		}

		return $val;
	}
	
	public function reValidateUser($uuid, $attributes) {
		$user = $this->getUserByUuid ( $uuid );
		if (! empty ( $user )) {
			$user->password = null ;
			$mode = "revalidate" ;
			return $this->validateUser( $user, $attributes, $mode );
		} else {
			return "User Not Found";
		}
	}
	
	private function zipcodeExists( $zipcode ) {
		$zipinfo = $this->getZipcodeInfo( $zipcode ) ;
		// var_dump($zipinfo) ;
		return ( $zipinfo )? true : false ;
	}
	
	public function strongEnoughPassword( $password ) {
		if( !isset( $password) || strlen($password) == 0 ) return null ;
		
		$validation = null ;
		if( strlen($password) < 7 ) {
			$validation = new Validation( "password", false, "Password need to be 7 or more characters long" ) ;
		} else if( !preg_match( '/^\S*(?=\S{7,})(?=\S*[a-z])(?=\S*[A-Z])(?=\S*[\d])\S*$/', $password )) {
			$validation = new Validation( "password", false, "Password is too weak. It must contain upper and lower case characters and at least 1 digit" ) ;
		}

		return $validation ;
	}
	
	private function usernameExists( $username, $id ) {
		$sql = "SELECT 1 FROM user WHERE username=:username and id <> :id";
		try {
			$db = $this->getConnection ();
			$stmt = $db->prepare ( $sql );
			$stmt->bindParam ( "username", $username );
			$stmt->bindParam ( "id", $id, PDO::PARAM_INT) ;
			$stmt->execute ();
			$res = intval($stmt->fetchColumn());
			$db = null;
			return $res;
		} catch ( PDOException $e ) {
			$message = $e->getMessage ();
			return array (
					"Error" => array (
							"text" => $message
					)
			);
		}
	}
	
	private function emailExists( $email, $id) {
		$sql = "SELECT 1 FROM user WHERE email=:email and id <> :id";
		try {
			$db = $this->getConnection ();
			$stmt = $db->prepare ( $sql );
			$stmt->bindParam ( "email", $email );
			$stmt->bindParam ( "id", $id, PDO::PARAM_INT) ;
			$stmt->execute ();
			$res = intval($stmt->fetchColumn());
			$db = null;
			return $res;
		} catch ( PDOException $e ) {
			$message = $e->getMessage ();
			return array (
					"Error" => array (
							"text" => $message
					)
			);
		}
	}
	
	public function getZipcodeInfo($zipcode) {
		$sql = "SELECT * FROM zipinfo WHERE zipcode=:zipcode ";
		try {
			$db = $this->getConnection ();
			$stmt = $db->prepare ( $sql );
			$stmt->bindParam ( "zipcode", $zipcode );
			$stmt->execute ();
			$obj = $stmt->fetchObject ( "Zipcode" );
			$db = null;
			return $obj;
		} catch ( PDOException $e ) {
			$message = $e->getMessage ();
			return array (
					"Error" => array (
							"text" => $message 
					) 
			);
		}
	}
	
	// list zipcodes from refernce database
	public function listZipcodes($offset, $limit) {
		$offset = (is_null ( $offset )) ? 0 : intval ( $offset );
		$limit = (is_null ( $limit )) ? 100 : intval ( $limit );
		// echo "offset: $offset limit : $limit\n" ;
		
		$sql = "select zipcode, country, latitude, longitude, state, population";
		$sql .= " FROM zipinfo where population > 0 ORDER BY state LIMIT  :offset, :limit";
		// $sql .= " FROM zipinfo where population > 0 ORDER BY state limit 0, 20";
		try {
			$db = $this->getConnection ();
			$stmt = $db->prepare ( $sql );
			$stmt->bindParam ( "offset", $offset, PDO::PARAM_INT );
			$stmt->bindParam ( "limit", $limit, PDO::PARAM_INT );
			$stmt->execute ();
			$objs = $stmt->fetchAll ( PDO::FETCH_OBJ );
			$db = null;
			return $objs;
		} catch ( PDOException $e ) {
			$message = $e->getMessage ();
			return array (
					"Error" => array (
							"text" => $message 
					) 
			);
		}
	}
	
	public function listCountries() {
		// $this->showSession() ;
		$sql = "select * from countries order by displorder, shortname";
		try {
			$db = $this->getConnection ();
			$stmt = $db->query ( $sql );
			$countries = $stmt->fetchAll ( PDO::FETCH_OBJ );
			$db = null;
			return $countries;
		} catch ( PDOException $e ) {
			$message = $e->getMessage ();
			return array (
					"Error" => array (
							"text" => $message
					)
			);
		}
	}
	public function listNTDSUsers($state, $offset, $limit) {
		$offset = (is_null ( $offset )) ? 0 : intval ( $offset );
		$limit = (is_null ( $limit )) ? 100 : intval ( $limit );
		
		$sql = "select n.idcode, n.q10 as zipcode, z.latitude, z.longitude, z.state, n.q2 as assignedgender, n.currentgender, n.complexgender, n.tggnc, n.visualconformity, ";
		$sql .= "n.medicaltransition, n.surgicaltransition,	n.sofinal, n.age, n.agecat, n.agefulltime, n.areout, n.workforce, n.unemployment, n.income, ";
		$sql .= "n.q11a as 'white', n.q11b as 'black', n.q11c as 'amind', n.q11d as 'latino', n.q11e as 'api', ";
		$sql .= "n.q11f as 'mideast', n.q11g as 'multirace' ";
		$sql .= "from ntdsdata n join zipinfo z on n.q10 = z.zipcode ";
		if (! is_null ( $state )) {
			$sql .= " where z.state = :state ";
		}
		$sql .= "order by z.state limit :offset, :limit ";
		
		try {
			$db = $this->getConnection ();
			$stmt = $db->prepare ( $sql );
			if (! is_null ( $state )) {
				$stmt->bindParam ( "state", $state );
			}
			$stmt->bindParam ( "offset", $offset, PDO::PARAM_INT );
			$stmt->bindParam ( "limit", $limit, PDO::PARAM_INT );
			$stmt->execute ();
			$objs = $stmt->fetchAll ( PDO::FETCH_OBJ );
			$db = null;
			return $objs;
		} catch ( PDOException $e ) {
			$message = $e->getMessage ();
			return array (
					"Error" => array (
							"text" => $message 
					) 
			);
		}
	}
	public function getUserSessionInfo($uuid) {
		if ($uuid === '*') {
			$uuid = null;
		}
		$now = date ( 'Y-m-d H:i:s' );
		$sessionUuid = $this->getSessionVar ( "uuid", null );
		// echo "session uuid: [$sessionUuid] \n" ;
		$info = new UserSessionInfo ();
		$info->passedUuid = $uuid;
		$info->sessionUuid = $sessionUuid;
		$info->thisVisitStart = $now;
		$info->lastVisit = $this->getSessionVar ( "lastvisit", null );
		$info->isloggedIn = $this->getSessionVar ( "islogged", false );
		$info->lastIp = $this->getSessionVar ( "lastip", "" );
		$info->thisIp = $_SERVER ['REMOTE_ADDR'];
		$info->sessionState = "unknown" ;
		if (empty( $info->sessionUuid )) {
			// not in a session
			$info->sessionState = "nosession" ;
			$info->sessionUuid = $uuid;
			if( empty($info->sessionUuid) ) {
				$info->sessionState = "nopassed" ;
				// no uuid was passed
				$info->cookies = $_COOKIE ;
				if( isset($_COOKIE['DOTTIFYME_USER_UUID'])) {
					$info->sessionState = "staylogged" ;
					$info->sessionUuid = $_COOKIE['DOTTIFYME_USER_UUID'] ;
					$info->cookieUuid = $info->sessionUuid;
				}
			}
		} else {
			$info->sessionState = "insession" ;
			// in a session
			if (!empty ( $uuid )) {
				// but an id is passed, override.
				$info->sessionUuid = $uuid;
			} 
		}
		

		$info->user = $this->getUserByUuid ( $info->sessionUuid );

		if( isset($info->user->id) ) {
			
			$info->createdms = strtotime( $info->user->created ) ;
			$this->setSessionVar ( 'uuid', $info->sessionUuid );
			$this->setSessionVar ( 'lastvisit', $now );
			$this->setSessionVar ( 'lastip', $info->thisIp );
			
			//if( $info->user->staylogged ) {
			// does not work as it needs a page-reload/redirect so it can be sent with headers
			//	setcookie( "DOTTIFYME_USER_UUID", $info->sessionUuid, time() + 60*60*24*365, '/' ) ;
			//	$info->cookieset = true;
			//}
			//$info->cookies = $_COOKIE ;
		}

		return $info;
	}
	
	/*
	 * Destroy all session information and clear cookie 
	 * so that the next user does not have access to this users's information
	 */
	public function logout() {
		$_SESSION = array();
		
		if (ini_get("session.use_cookies")) {
			$params = session_get_cookie_params();
			setcookie(session_name(), '', time() - 42000,
			$params["path"], $params["domain"],
			$params["secure"], $params["httponly"]
			);
		}
		
		session_destroy();
		return true ;
	}
	
	public function emailUserLink( $email ) {
		
		$uuid = $this->getUserByEmail( $email ) ;
		if( $uuid) {
			$url = "http://dottify.me/?uuid=$uuid";
			$message = "This is a response to a request to send you your private link.\n" ;
			$message .= "Your private link is: $url.\n" ;
			$message .= "Please keep it confidential save this link or bookmark it.\n" ;
			$message .= "If you add a username and password, you can log in to your account\nin the usual way." ;
			$headers = 'From: admin@dottifyme.org' . "\r\n" .
   						 'Reply-To: admin@dottifyme.org' . "\r\n" .
   						 'X-Mailer: PHP/' . phpversion() ;
			
			$subject = "Your personal Dottify.me link" ;
			$ok = mail( $email, $subject, $message, $headers ) ;
			
			$result = array( "uuid" => $uuid, "link" => $url, "email" => $email, "success" => $ok  );
			return $result ;
		} else {
			$result = array( "link" => "", "email" => $email, "success" => 0 , "message" => "user not found" );
			return $result ;
		}
	}
	
	protected function getSessionVar($varname, $defval) {
		if (isset ( $_SESSION [$varname] )) {
			return $_SESSION [$varname];
		} else {
			return $defval;
		}
	}
	
	protected function setSessionVar($varname, $value) {
		$_SESSION [$varname] = $value;
	}
	
	protected function getVisits() {
		if (isset ( $_SESSION ['count'] )) {
			$_SESSION ['count'] ++;
		} else {
			$_SESSION ['count'] = 1;
		}
		$visits = $_SESSION ['count'];
		return $visits;
	}
	protected function showSession() {
		$visits = $this->getVisits ();
		echo "visits: $visits\n";
	}
	

	protected function getConnection() {
		$dbinfo = getenv ( "DOTTIFY_DB" );
		// echo "dbinfo: $dbinfo\n" ;
		$connect = explode ( ':', $dbinfo );
		$dbhost = $connect [0];
		$dbuser = $connect [1];
		$dbpass = $connect [2];
		$dbname = $connect [3];
		
		$dbh = new PDO ( "mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpass );
		$dbh->setAttribute ( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
		return $dbh;
	}
}