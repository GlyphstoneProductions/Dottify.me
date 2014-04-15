<?php
namespace Dottify\dao;

require_once __DIR__ . "/../model/User.php" ;
require_once __DIR__ . "/../model/Zipcode.php" ;
require_once __DIR__ . "/../model/UserCache.php" ;

use PDO ;
use Dottify\model\User ;
use Dottify\model\Zipcode ;
use Dottify\model\UserCache ;

class mysql_userDao extends mysql_baseDao {
	
	// get a content item by id/version
	public function get( $id, $ver) {
		
		if (is_null( $ver )) $ver = 0 ;
		
		$sql = "select ci.id, u.ver, ci.thisver, ci.classid, cc.name as 'classname', ci.status, cs.name as 'statusname', cs.ispublished as 'ispublished', u.uuid, u.refid, u.username, u.password, ci.created, u.modified, u.refuser, u.refuserid, u.email, u.zipcode, ";
		$sql .= " u.countrycode, u.usertype, u.userstatus, u.userclass, u.mecon, u.userip, u.staylogged, u.notes, c.latitude, c.longitude, c.usersetloc  ";
		$sql .= "FROM cinst ci join user u on ci.id = u.id left outer join cclass cc on ci.classid = cc.id join cstatus cs on ci.status = cs.id left outer join usercache c on u.id = c.id WHERE ci.id = :id AND u.ver = :ver";

		try {
			$db = $this->getConnection ();
					
			$stmt = $db->prepare ( $sql );
			$stmt->bindParam ( "id", $id , PDO::PARAM_INT);
			$stmt->bindParam ( "ver", $ver , PDO::PARAM_INT);
			$stmt->execute ();
			$user = $stmt->fetchObject( "Dottify\model\User" );
			$db = null;
			return $user;
		} catch ( PDOException $e ) {

			$message = $e->getMessage ();
			echo "Error! $message\n" ;
			return array (
					"Error" => array (
							"text" => $message
					)
			);
		}
	}
	
	// get just the user table information
	// to facilitate lookups
	public function getByRefId( $refid ) {

		$sql = "select u.* FROM user u WHERE u.refid = :refid AND u.ver = 0 ";
		
		try {
			$db = $this->getConnection ();
				
			$stmt = $db->prepare ( $sql );
			$stmt->bindParam ( "refid", $refid , PDO::PARAM_INT);
			$stmt->execute ();
			$user = $stmt->fetchObject( "Dottify\model\User" );
			$db = null;
			return $user;
		} catch ( PDOException $e ) {
		
			$message = $e->getMessage ();
			echo "Error! $message\n" ;
			return array (
					"Error" => array (
							"text" => $message
					)
			);
		}
	}
	
	// get just the user table information
	// to facilitate lookups
	public function getByUuid( $uuid ) {
	
		$sql = "select u.* FROM user u WHERE u.uuid = :uuid AND u.ver = 0 ";
	
		try {
			$db = $this->getConnection ();
	
			$stmt = $db->prepare ( $sql );
			$stmt->bindParam ( "uuid", $uuid );
			$stmt->execute ();
			$user = $stmt->fetchObject( "Dottify\model\User" );
			$db = null;
			return $user;
		} catch ( PDOException $e ) {
	
			$message = $e->getMessage ();
			echo "Error! $message\n" ;
			return array (
					"Error" => array (
							"text" => $message
					)
			);
		}
	}
	
	// add an all new user
	public function create( $user ) {
		
		error_log ( "add new user\n", 3, '/var/tmp/php.log' );
		
		// Do the generic object creation, setting dates, saving into the cisint table and getting the id back.

		// TODO: get status from a constant singleton.
		$user->status = 0 ;	// user are published right off the bat
				
		$user = $this->createObjInstance( $user ) ;
		
		// create user specific row
		
		$uuid = uniqid ( "dottify", true ); // more entropy!
		$refid = md5 ( $uuid );
		
		$user->uuid = $uuid;
		$user->refid = $refid;
		$user->ver = 0; // the current entity is always ver 0 (with thisver = 1)
		$user->usertype = 0; // default user type
		$user->userstatus = 1; // status registered for now
		
		$cryptpass = null;
		$password = $this->getProp ( $user, "password", null );
		if (! is_null ( $password )) {
			$cryptpass = md5 ( $password );
		}
		$user->password = $cryptpass ;
		$user->refuser = 0;
		
		$refuserid = $this->getProp ( $user, "refuserid", null );
		if (! empty ( $refuserid )) {
			$referringuser = $this->getByRefId ( $refuserid );
			if (! is_null ( $referringuser )) {
				$user->refuser = intval ( $referringuser->id );
			}
		}
	
		$user = $this->doBasicCreate( $user ) ;
		
		// for now fill in usercache table with lat/long
		// TODO: Merge Usercache into user table for simplicity
		
		$usercache = $this->createUserCache ( $user->id, $user->countrycode, $user->zipcode, $user->userip );
		$user->latitude = $usercache->latitude;
		$user->longitude = $usercache->longitude;
		$user->locupdate = true ;	// flag front end that location has been set/updated

		return $user ;
			
	}
	

	
	public function update( $user, $norev ) {
		
		// for reference comparisons
		$olduser = $this->get( $user->id, 0) ;
	
		if (!$norev) {
			error_log ( "save user version \n", 3, '/var/tmp/php.log' );
			$this->doBasicVersion ( $olduser, false ) ;	// do not refetch, we are passing the current saved version.
		}
		
		$cryptpass = $olduser->password ;
		$password = $this->getProp ( $user, "password", null );
		if ( !is_null( $password ) ) {
			$password = trim( $password ) ;
			if( strlen( $password ) > 0 ) {
				$cryptpass = md5 ( $password );
			}
		} 
		
		$user->password = $cryptpass ;
		
		
		// TODO: Combine these into the second?
		$user = $this->updateObjInstance( $user, !$norev ) ;
		$user = $this->doBasicUpdate( $user ) ;
		$user->password = ""; // for security
		
		if( $user->countrycode != $olduser->countrycode || $user->zipcode != $olduser->zipcode ) {
			
			// if the country or zip has changed update.
			// Even if user has customized their lat/long it will be reset.
			$usercache = $this->updateUserCache( $user->id, $user->countrycode, $user->zipcode ) ;
			$user->latitude = $usercache->latitude;
			$user->longitude = $usercache->longitude;
			$user->usersetloc = 1 ;	// flag front end that location has been set/updated
		
		} else {
			// fetch cache (lat/long) for the record
			$usercache = $this->getUserCache( $user->id ) ;
			$user->latitude = $usercache->latitude;
			$user->longitude = $usercache->longitude;
			$user->usersetloc = $usercache->usersetloc ;	// flag front end that location has been set/updated
		}
		
		return $user ;
		
	
		
	}
	
	public function delete( $id ) {
		$obj = new User();
		$obj->id = $id ;
		$success = $this->doBasicDelete( $obj ) ;
		if( $success ) {
			$this->deleteUserCache( $id ) ;
		}
		return $success ;
	}
	
	
	
	// get a list of content items by criteria
	public function query($offset, $limit, $preds, $order ) {
		$obj = new User() ;
		
		return $this->doBasicQuery( $obj, $offset, $limit, $preds, $order ) ;
		
	}
	
	// ==============================================================================================================
	
	protected function getSelection( $tableAlias ) {
		//return ' * ' ;
		$select = " ci.id, us.ver, ci.thisver, ci.classid, ccl.name as 'classname', ci.status, cst.name as 'statusname', cst.ispublished as 'ispublished', us.uuid, us.refid, us.username, ci.created, us.modified, us.refuser, us.refuserid, us.email, us.zipcode, ";
		$select .= " us.countrycode, us.usertype, us.userstatus, us.userclass, us.mecon, us.userip, us.staylogged, us.notes, c.latitude, c.longitude, c.usersetloc  ";
		return $select ;
	}
	
	protected function getFrom( $tableName, $tableAlias ) {
		//return "FROM $tableName $tableAlias" ;
		return "FROM cinst ci join user us on ci.id = us.id join cclass ccl on ci.classid = ccl.id join cstatus cst on ci.status = cst.id left outer join usercache c on us.id = c.id " ;
	}
	
	public function createUserCache($userid, $countrycode, $zipcode, $userip) {
		error_log ( 'addusercache\n', 3, '/var/tmp/php.log' );
	
		$userCache = new UserCache ();
		$userCache->id = $userid;
		$userCache->lastip = $userip;
	
		$userCache = $this->calcUserLatLong( $userCache, $countrycode, $zipcode ) ;
	
		$sql = "INSERT INTO usercache (id, lastip, latitude, longitude, usersetloc) ";
		$sql .= "VALUES (:id, :lastip, :latitude, :longitude, 0 )";
		try {
			$db = $this->getConnection ();
			$stmt = $db->prepare ( $sql );
			$stmt->bindParam ( "id", intval($userCache->id), PDO::PARAM_INT );
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
	
	public function deleteUserCache($userid) {
		error_log ( 'deleteusercache\n', 3, '/var/tmp/php.log' );
	
		$sql = "DELETE FROM usercache where id = :id " ; 
		try {
			$db = $this->getConnection ();
			$stmt = $db->prepare ( $sql );
			$stmt->bindParam ( "id", intval($userCache->id), PDO::PARAM_INT );
			$stmt->execute ();
			$db = null;
			return true ;
		} catch ( PDOException $e ) {
			$message = $e->getMessage ();
			error_log ( "Error deleting user: $userid : $message\n", 3, '/var/tmp/php.log' );
			return false ;
		}
	}
	
	public function calcUserLatLong( $usercache, $countrycode, $zipcode ) {
	
		// default in case where location not found in the database.
		// Center of the US.
		$latitude = 39.828 ;
		$longitude = -98.579 ;
		$latoffset = - 0.015 + $this->getRandFloat ( 600 );  
		$longoffset = - 0.015 + $this->getRandFloat ( 600 );
		
		if( $countrycode == 'US' || $countrycode == 'CA') {
	
			$zipinfo = $this->getZipcodeInfo ( $countrycode, $zipcode );
			if ( $zipinfo ) {
				$latitude = $zipinfo->latitude;
				$longitude = $zipinfo->longitude;
				$latoffset = - 0.015 + $this->getRandFloat ( 300 ); // +/- 0.015 degrees should be about 1 mile radius?
				$longoffset = - 0.015 + $this->getRandFloat ( 200 );
			}
		} else {
			$country = $this->getCountry( $countrycode ) ;
			if( $country ) {
				$latitude = $country->latitude;
				$longitude = $country->longitude;
				$latoffset = - 0.015 + $this->getRandFloat ( 600 );  // larger spread for countries
				$longoffset = - 0.015 + $this->getRandFloat ( 600 );
			}
		}
	
		$usercache->latitude = $latitude + $latoffset;
		$usercache->longitude = $longitude + $longoffset;
	
		return $usercache ;
	
	}
	
	/**
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
			$obj = $stmt->fetchObject ( "Dottify\model\Zipcode" );
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
	
	/**
	 * rewrite the usercache if the zipcode has changed.
	 *
	 * @param unknown $userid
	 * @param unknown $zipcode
	 * @param unknown $userip
	 * @return UserCache multitype:multitype:unknown
	 */
	public function updateUserCache($userid, $countrycode, $zipcode) {
		error_log ( 'updateusercache\n', 3, '/var/tmp/php.log' );
	
		$usercache = new UserCache() ;
		$usercache = $this->calcUserLatLong( $usercache, $countrycode, $zipcode ) ;
	
		$this->updateUserLatLong( $userid, $usercache->latitude, $usercache->longitude, 0 );
		return $usercache ;
	}
	
	
	public function getUserCache( $id ) {
	
		$sql = "SELECT * FROM usercache WHERE id = :id";
		try {
			$db = $this->getConnection ();
			$stmt = $db->prepare ( $sql );
			$stmt->bindParam ( "id", intval($id), PDO::PARAM_INT );
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
	
	public function updateUserPosition( $user ) {
		$this->updateUserLatLong( $user->id, $user->latitude, $user->longitude, 1 ) ;
		return $user ;
	}
	
	public function updateUserLatLong( $userid, $latitude, $longitude, $usersetloc ) {
	
		$sql = "UPDATE usercache SET latitude = :latitude, longitude = :longitude, usersetloc = :usersetloc where id = :id ";
		try {
			$db = $this->getConnection ();
			$stmt = $db->prepare ( $sql );
			$stmt->bindParam ( "id", intval($userid), PDO::PARAM_INT );
			$stmt->bindParam ( "latitude", $latitude );
			$stmt->bindParam ( "longitude", $longitude );
			$stmt->bindParam ( "usersetloc", $usersetloc, PDO::PARAM_INT);
	
			$stmt->execute ();
			$db = null;
	
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
}

