<?php

/** This is essentially a collapse of the manager and dao levels of the architecture for now.
 * 
 * @author breanna
 *
 */

require_once "model/User.php";
require_once "model/UserCache.php";
require_once "model/Zipcode.php";
require_once "model/Incident.php";
require_once "model/Comment.php";

require_once "Validation.php";

require_once "UserSessionInfo.php";
require_once "dao/DaoManager.php" ;
require_once "MetadataManager.php" ;
require_once "Tuple.php" ;


use Dottify\dao\DaoManager ;
use Dottify\model\User ;
use Dottify\model\UserCache ;
use Dottify\model\Zipcode ;
use Dottify\model\Incident;
use Dottify\model\Comment;

class DottifyManager {
	public function listusers($offset, $limit, $preds, $order) {
		
		$offset = (is_null($offset))? 0 : $offset ;
		$limit = (is_null($limit))? 1000 : $limit ;
		
		// Fall back to basic list users behavior.  If preds is specified, caller is responsible for predicates.
		if( is_null($preds) || count($preds) == 0 ) {
			$preds = Tuple::parseTriTuple("ver|=|0") ;
		}
		
		$mgr = DaoManager::getInstance() ;
		$dao = $mgr->getDaoByName( "user") ;
		
		$result = $dao->query( $offset, $limit, $preds, $order ) ;
		
		return $result ;

	}
	
	public function getUserByUuid($uuid) {
		
		
		$offset = 0; 
		$limit = 1 ;
		$preds = Tuple::parseTriTuple("uuid|=|$uuid;ver|=|0") ;
		$order = null ;
		
		$mgr = DaoManager::getInstance() ;
		$dao = $mgr->getDaoByName( "user") ;
		
		$result = $dao->query( $offset, $limit, $preds, $order ) ;
		
		if( count($result->elements) > 0 ) {
			return $result->elements[0] ;
		} else { 
			return false ;
		}
	
		
		/*
		$mgr = DaoManager::getInstance() ;
		$dao = $mgr->getDaoByName( "user") ;
		
		$user = $dao->getByUuid( $uuid ) ;
		if( $user ) {
			//$user = $dao->get($user->id, 0);
		}
		return $user ;
		*/
	
	}
	
	/** 
	 * Stripped down get by id and ver for internal use
	 * @param unknown $id
	 * @param unknown $ver
	 * @return mixed|multitype:multitype:unknown
	 */
	public function getUserById($id, $ver) {
		
		$mgr = DaoManager::getInstance() ;
		$dao = $mgr->getDaoByName( "user") ;
		$user = $dao->get( $id, $ver ) ;
		return $user ;
		
	
	}
	
	public function getUserByRefid($refid) {
		$offset = 0;
		$limit = 1 ;
		$preds = Tuple::parseTriTuple("refid|=|$refid;ver|=|0") ;
		$order = null ;
		
		$mgr = DaoManager::getInstance() ;
		$dao = $mgr->getDaoByName( "user") ;
		
		$result = $dao->query( $offset, $limit, $preds, $order ) ;
			
		if( count($result->elements) > 0 ) {
			return $result->elements[0] ;
		} else {
			return false ;
		}
		
	
	}
	
	
	public function getUserByEmail($email ) {
		
		$offset = 0;
		$limit = 1 ;
		$preds = Tuple::parseTriTuple("email|=|$email;ver|=|0") ;
		$order = null ;
		
		$mgr = DaoManager::getInstance() ;
		$dao = $mgr->getDaoByName( "user") ;
		
		$result = $dao->query( $offset, $limit, $preds, $order ) ;
			
		if( count($result->elements) > 0 ) {
			return $result->elements[0] ;
		} else {
			return false ;
		}

	}
	
	public function loginUser($username, $password) {
		$cryptpass = md5($password) ;
		
		
		$offset = 0;
		$limit = 1 ;
		$preds = Tuple::parseTriTuple("username|=|$username;password|=|$cryptpass;ver|=|0") ;
		$order = null ;
		
		$mgr = DaoManager::getInstance() ;
		$dao = $mgr->getDaoByName( "user") ;
		
		$result = $dao->query( $offset, $limit, $preds, $order ) ;

		if( count($result->elements) > 0 ) {
			$user = $result->elements[0] ;
			$uuid = $user->uuid;
			return $uuid ;
		} else {
			return false ;
		}
		
	
	}
	
	/**
	 * Find out how many users the specified user has influced to join
	 * TODO: make it a multi-level recursive query.
	 * @param unknown $uuid
	 * @param unknown $depth
	 * @return multitype:Ambigous <NULL, number> number
	 */
	
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
	
	/**
	 * Get report of users who have referred other users (influencers)
	 * @return multitype:|NULL
	 */
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
	
	

	/**
	 * Create a new user from scratch.
	 * we are deprecating the adoption logic now.
	 * @param unknown $user        	
	 * @return unknown multitype:multitype:unknown
	 */
	public function createUser( $obj, $adopt) {
			
		// TODO: verify no duplicate name or email
		
		$user = new User() ;
		$user->load($obj) ;
		$userip = $_SERVER ['REMOTE_ADDR'];
		$user->userip = $userip ;
		
		$mgr = DaoManager::getInstance() ;
		$dao = $mgr->getDaoByName( "user") ;
		
		$result = $dao->create( $user ) ;
		return $result ;
		
	}

	
	/**
	 * modify user information
	 * Does not modify:
	 * uuid, refid, password, created,
	 * 
	 * @param unknown $user        	
	 * @return unknown multitype:multitype:unknown
	 */
	public function updateUser($obj, $norev) {
		
		// TODO: VERIFY NO duplicate user name or email
		
		$now = $date = date ( 'Y-m-d H:i:s' );
		error_log ( "updateuser $now  norev=$norev\n", 3, '/var/tmp/php.log' );
		
		$user = new User() ;
		$user->load($obj) ;
		$userip = $_SERVER ['REMOTE_ADDR'];
		$user->userip = $userip ;
		
		$mgr = DaoManager::getInstance() ;
		$dao = $mgr->getDaoByName( "user") ;
		
		$result = $dao->update( $user, $norev ) ;
		return $result ;
		

	}
	
	public function deleteUser( $uuid ) {
		

		$now = $date = date ( 'Y-m-d H:i:s' );
		error_log ( "delete user $uuid at $now \n", 3, '/var/tmp/php.log' );
	
		$mgr = DaoManager::getInstance() ;
		$dao = $mgr->getDaoByName( "user") ;
		
		$user = $dao->getByUuid( $uuid ) ;
		if( $user ) {
			$dao->delete( $user->id ) ;
			return true ;
		}
		return false ;
	
		
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
		if( $user->countrycode == 'US' || $user->countrycode == 'CA' ) {
			if( !$this->zipcodeExists( $user->countrycode, $user->zipcode )) {
				$val->add ( new Validation ( "zipcode", false, "zipcode not found." ) );
				$val->allvalid = false ;
			}
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
	
	private function zipcodeExists( $countrycode, $zipcode ) {
		$zipinfo = $this->getZipcodeInfo( $countrycode, $zipcode ) ;
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
	
	
	
	/**
	 * * TODO: MOVE TO Geography utilty DAO.
	 * get postal code (actually) by country (currently US and CA)
	 * @param unknown $countrycode
	 * @param unknown $zipcode
	 * @return mixed|multitype:multitype:unknown
	 */
	public function getZipcodeInfo($countrycode, $zipcode) {
		$sql = "SELECT * FROM zipinfo WHERE country = :country and zipcode=:zipcode ";
		try {
			$db = $this->getConnection ();
			$stmt = $db->prepare ( $sql );
			$stmt->bindParam ( "country", $countrycode );
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
		
		$sql = "select zipcode, country, countryloc, latitude, longitude, state, population";
		$sql .= " FROM zipinfo where population > 0 ORDER BY country, state LIMIT  :offset, :limit";
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
	
	public function getCountry( $isoid) {
		$sql = "SELECT * FROM countries WHERE isoid =:isoid ";
		try {
			$db = $this->getConnection ();
			$stmt = $db->prepare ( $sql );
			$stmt->bindParam ( "isoid", $isoid );
			$stmt->execute ();
			$obj = $stmt->fetchObject ( );
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
	
	// =============================================================================
	// Stats methods
	
	public function getTagList($raw ) {
		$sql = "select s.genderdesc from usersurvey1 s join user u on s.id = u.id and s.ver = u.thisver where u.ver = 0" ;

		$desclist = array() ;
		try {
			$db = $this->getConnection ();
			$stmt = $db->prepare ( $sql );
			$stmt->execute ();
			$desclist = $stmt->fetchAll( PDO::FETCH_COLUMN );
			$db = null;
			if( $raw ) return $desclist ;
			
			return $this->processTagList( $desclist ) ;
		} catch ( PDOException $e ) {
			$message = $e->getMessage ();
			return array (
					"Error" => array (
							"text" => $message
					)
			);
		}
	
	}
	
	private function processTagList( $desclist ) {
		$stoplist = array( 'a', 'and', 'as', 'am', 'an', 'be', 'both', 'crucial','detail', 'etc', 'experience', 'is', 'happens',  'i', 
				'like', 'likes', 'only', 'on', 'of',  'when', 'to', 'that',  'that','the', 'with', 'who', "i'm", 'interior', 'exterior', 'expresses', 'late',
				'simple','men', 'identity', 'working','aligning'
				) ;

		$phrases = array( 
			"trans" => array( "trans-man", "trans-woman", "trans-girl", "trans-dyke"),
			"transgender" => array( "transgender-man", "transgender-woman", "transgender-female" ),
			"transsexual" => array( "transsexual-woman", "transsexual-man"),
			"girlie" => array( "girlie-man"),
			"two" => array( "two-spirit"), 
			"pan" => array( "pan-sexual"),
			"gender" => array( "gender-queer", "gender-outlaw"),
			"woman" => array( "woman-of-transgender-experience", "woman-of-transsexual-experience", "woman-who-happens-to-be-trans")
		) ;
		
		$tagcounts = array() ;
		
		foreach( $desclist as $desc ) {
			$desc = strtolower( $desc ) ;
			// eliminate anything but alpha and selected charactgers
			$desc = preg_replace( '/[^a-z\-\'\*]/', ' ', $desc) ;
			// compress excess whitespace ;
			$desc = preg_replace('/\s\s+/', ' ', $desc);
			$desc = preg_replace('/[\r\n]/', ' ', $desc );
			$tags = explode( ' ', $desc) ;

			for( $n = 0; $n < count($tags); $n++) {
			    $tag = $tags[$n] ;
				
				if( $tag !== "" && !in_array( $tag, $stoplist)) {
					$tag = $this->mergeTagPhrase( $tag, $tags, $phrases, $n );
				    if( array_key_exists( $tag, $tagcounts ) ) {
				    	$tagcounts[$tag] += 1 ;
				    } else {
				    	$tagcounts[$tag] = 1 ;
				    }
				}
			}
						
		}
		return $tagcounts ;
	}
	
	private function mergeTagPhrase( $tag, $tags, $phrases, &$n ) {
		if( array_key_exists( $tag, $phrases)) {
			$candidates = $phrases[$tag] ;
			foreach( $candidates as $candidate ) {
				if( $this->phraseMatch( $candidate, $tags, $n)) {
					return $candidate ;
				}
			}
		}
		return $tag ;
	}
	
	private function phraseMatch( $phrase, $tags, &$n ) {
		$phrasetags = explode( '-', $phrase) ;
		if( count( $tags) >= $n + count( $phrasetags)) {
			for( $m = 0; $m < count( $phrasetags); $m++ ) {
				$pt = $phrasetags[$m] ;
				if( $pt !== $tags[$n + $m]) {
					return false ;
				}
			}
			$n += count( $phrasetags ) - 1 ;
			return true ;
		}
		return false ;
	}
	
	
	public function getUsersPerZip( $country ) {
		
		if( $country ) {
			$sql = "select zi.zipcode, zi.latitude, zi.longitude, zi.country, count(*) as 'count' from user u join zipinfo zi on u.zipcode = zi.zipcode " ;
			$sql .= " where u.ver = 0 and u.countrycode = :countrycode group by u.zipcode order by count(*) desc" ;
		} else {
			$sql = "select zi.zipcode, zi.latitude, zi.longitude, zi.country, count(*) as 'count'  from user u join zipinfo zi on u.zipcode = zi.zipcode " ;
			$sql .= " where u.ver = 0 group by u.zipcode order by count(*) desc" ;
		}
		
		$desclist = array() ;
		try {
			$db = $this->getConnection ();
			$stmt = $db->prepare ( $sql );
			if( $country){
				$stmt->bindParam ( "countrycode", $country );
			}
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
	
	public function daoTest() {
		
		$mgr = DaoManager::getInstance() ;
		$dao = $mgr->getDaoByName( "user") ;
		$user = $dao->get( 745, 0 ) ;
		//var_dump($user);

		return $user;
	}
	
	public function queryTest($offset, $limit, $preds, $order) {

		$offset = (is_null($offset))? 0 : $offset ;
		$limit = (is_null($limit))? 1000 : $limit ;
				
		$mgr = DaoManager::getInstance() ;
		$dao = $mgr->getDaoByName( "user") ;
		
		$result = $dao->query( $offset, $limit, $preds, $order ) ;
	
		return $result;
	}
	
	public function getModelMetadata( $modelName ) {
		
		$modelName = strtolower( $modelName ) ;
		$model = null ;
		switch( $modelName) {
			case "user" :
				$model = new User() ;
				break ;
				
		}
		
		$response = "Model: $modelName does not exist" ;
		if( !is_null($model) ) {
			$response = $model->getMetadata() ;
		} 
		
		return $response ;
	}
	
	// -------------------------------------------------------------
	// Incident
	
	public function getIncident( $id, $ver ) {
		$mgr = DaoManager::getInstance() ;
		$dao = $mgr->getDaoByName( "incident") ;
		
		$result = $dao->get( $id, $ver ) ;
		return $result ;
	}
	
	public function listIncidents( $offset, $limit, $preds, $order ) {
		$offset = (is_null($offset))? 0 : $offset ;
		$limit = (is_null($limit))? 1000 : $limit ;
		
		// Fall back to basic behavior.  If preds is specified, caller is responsible for predicates.
		if( is_null($preds) || count($preds) == 0 ) {
			$preds = Tuple::parseTriTuple("ver|=|0") ;
		}
		
		$mgr = DaoManager::getInstance() ;
		$dao = $mgr->getDaoByName( "incident") ;
		
		$result = $dao->query( $offset, $limit, $preds, $order ) ;
		return $result ;
		
	}
	
	public function createIncident( $obj) {
			
		$incident = new Incident() ;
		$incident->load($obj) ;
		$userip = $_SERVER ['REMOTE_ADDR'];
		$incident->userip = $userip ;
	
		$mgr = DaoManager::getInstance() ;
		$dao = $mgr->getDaoByName( "incident") ;
	
		$result = $dao->create( $incident ) ;
		return $result ;
	
	}
	
	
	public function updateIncident( $obj, $norev) {
			
		$incident = new Incident() ;
		$incident->load($obj) ;
		$userip = $_SERVER ['REMOTE_ADDR'];
		$incident->userip = $userip ;
	
		$mgr = DaoManager::getInstance() ;
		$dao = $mgr->getDaoByName( "incident") ;
	
		$result = $dao->update( $incident, $norev ) ;
		return $result ;
	
	}
	
	public function deleteIncident( $id ) {
			
		$mgr = DaoManager::getInstance() ;
		$dao = $mgr->getDaoByName( "incident") ;
	
		$result = $dao->delete( $id ) ;
		return $result ;
	
	}
	
	
	// -----------------------------------------------
	// Comment
	
	public function getComment( $id, $ver ) {
		$mgr = DaoManager::getInstance() ;
		$dao = $mgr->getDaoByName( "comment") ;
	
		$result = $dao->get( $id, $ver ) ;
		return $result ;
	}
	
	public function listComments( $offset, $limit, $preds, $order ) {
		$offset = (is_null($offset))? 0 : $offset ;
		$limit = (is_null($limit))? 1000 : $limit ;
	
		$mgr = DaoManager::getInstance() ;
		$dao = $mgr->getDaoByName( "comment") ;
	
		$result = $dao->query( $offset, $limit, $preds, $order ) ;
		return $result ;
	
	}
	
	public function createComment( $obj) {
			
		$comment = new Comment() ;
		$comment->load($obj) ;
	
		$mgr = DaoManager::getInstance() ;
		$dao = $mgr->getDaoByName( "comment") ;
	
		$result = $dao->create( $comment ) ;
		return $result ;
	
	}
	
	
	public function updateComment( $obj, $norev) {
			
		$comment = new Comment() ;
		$comment->load($obj) ;
		
		$mgr = DaoManager::getInstance() ;
		$dao = $mgr->getDaoByName( "comment") ;
	
		$result = $dao->update( $comment, $norev ) ;
		return $result ;
	
	}
	
	public function deleteComment( $id ) {
			
		$mgr = DaoManager::getInstance() ;
		$dao = $mgr->getDaoByName( "comment") ;
	
		$result = $dao->delete( $id ) ;
		return $result ;
	
	}
	
	public function getRelType( $id ) {
		$mgr = new MetadataManager() ;

		$result = $mgr->getRelType( $id ) ;
		return $result ;
	}
	
	public function listRelTypes( $parent, $include, $norecurse ) {
		
		$mgr = new MetadataManager() ;
		$result = $mgr->listRelTypes( $parent, $include, $norecurse) ;
		return $result ;
	
	}
	
	public function listLinks( $offset, $limit, $origin, $direction, $reltype ) {
		
		$mgr = new MetadataManager() ;
		if( is_null($origin)) {
			return $mgr->listAllLinks( $offset, $limit, $reltype ) ;
		} else {
			return $mgr->listLinks( $offset, $limit, $origin, $direction, $reltype ) ;
		}
	}
	
	public function listLinkObjs( $offset, $limit, $origin, $direction, $reltype, $withlink ) {
	
		$mgr = new MetadataManager() ;
		if( is_null($origin)) {
			return "origin required\n";
		} else {
			return $mgr->listLinkObjs( $offset, $limit, $origin, $direction, $reltype, $withlink ) ;
		}
	}
	
	public function getLink( $id ) {
		$mgr = new MetadataManager() ;
		return $mgr->getLink($id);
	}
	
	public function createLink( $data ) {
		$mgr = new MetadataManager() ;
		return $mgr->createLink( $data ) ;
	}
	
	/*
	 * 
	 * updateStats
	 
	 comments by user
	 
	 comments for subject
	 childComments
	 */
	
	
	// =====================================================================================================================
	
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