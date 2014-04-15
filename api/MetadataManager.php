<?php
require_once "dao/DaoManager.php" ;

use Dottify\dao\DaoManager ;
use PDO ;

class MetadataManager {
	
	
	// link operation errors
	const ERR_DUPLICATE_LINK = 100 ;
	const ERR_INVALID_RELTYPE = 110 ;
	const ERR_INVALID_SOURCECLASS = 120 ;
	const ERR_INVALID_DESTCLASS = 130 ;
	const ERR_INVALID_SOURCECOUNT = 140 ;
	const ERR_INVALID_DESTCOUNT = 150 ;
	const ERR_INVALID_LINKOBJCLASS = 160 ;
	const ERR_LINKOBJ_MISSING = 170 ;
	
	const LINK_SOURCE = 0 ;
	const LINK_DEST = 1 ;
	
	const LINK_DIR_OUT = 0 ;
	const LINK_DIR_IN = 1 ;
	
	
	private function getObjList($classid, $objids ) {
		$mgr = DaoManager::getInstance() ;
		$dao = $mgr->getDaoByClassid( $classid ) ;
		
		// pass array and return list
		return $dao->get( $objids, 0 ) ;
	}
	
	public function getClassInfo() {
	
		$sql = "select * from cclass ";
		try {
			$db = $this->getConnection ();
				
			$stmt = $db->query ( $sql );
			$classes = $stmt->fetchAll ( PDO::FETCH_OBJ );
			$db = null;
			return $classes;
		} catch ( PDOException $e ) {
			$message = $e->getMessage ();
			error_log( "Error getting classes ", 3, '/var/tmp/php.log');
		}
	
	}
	
	
	public function getRelType( $id ) {
		
		$id = intval($id);
		$sql = "select * from creltype where id = :id" ;
				
		try {
			$db = $this->getConnection ();
			$stmt = $db->prepare ( $sql );
			$stmt->bindParam ( "id", $id, PDO::PARAM_INT );
			$stmt->execute ();
			$result = $stmt->fetchObject();
			
			$db = null;
			return $result;
		} catch ( PDOException $e ) {
			$message = $e->getMessage ();
			error_log( "Error getting reltypes $message", 3, '/var/tmp/php.log');
			return $message ;
		}
		
	}
	
	public function listRelTypes( $parent, $include, $norecurse ) {
	
		if( is_null( $include) ) {
			$include = true ;
		}
		
		$recurse = (isset($norecurse))? !$norecurse : true ;
		if( is_null($parent)) {
			return $this->getAllRelTypes() ;
		} else {
			$reltypes = array() ;
			
			if( $include ) {
				
				$parentrel = $this->getRelType($parent) ;
				
				if( !is_null( $parentrel )) {
					$reltypes[] = $parentrel;
				}
			}
			
			$childrels = $this->getChildRelTypes( $parent, $recurse);
			$reltypes = array_merge( $reltypes, $childrels);
			return $reltypes ;
		}
	
	}
	
	private function getAllRelTypes() {
		$sql = "select * from creltype" ;
		try {
			$db = $this->getConnection ();
		
			$stmt = $db->query ( $sql );
			//$stmt->bindParam ( "id", $id, PDO::PARAM_INT );
			$stmt->execute();
			$result = $stmt->fetchAll ( PDO::FETCH_OBJ );
			$db = null;
			return $result;
		} catch ( PDOException $e ) {
			$message = $e->getMessage ();
			error_log( "Error getting reltypes: $message ", 3, '/var/tmp/php.log');
		}	
	}
	
	private function getChildRelTypes( $parentid, $recurse ) {
		
		// TODO: cache results
		
		$sql = "select * from creltype where parentid = $parentid and id != $parentid" ;
		try {
			$db = $this->getConnection ();
		
			$stmt = $db->prepare( $sql );
			$stmt->bindParam ( "parentid", $parentid, PDO::PARAM_INT );
			$stmt->execute();
			$result = $stmt->fetchAll ( PDO::FETCH_OBJ );
			$db = null;
			
			if( $recurse) {
				$children = array();
				foreach( $result as $rel ) {
					$subres = $this->getChildRelTypes( $rel->id, $recurse) ;
					$children = array_merge( $children, $subres);
				}
				$result = array_merge($result, $children);
			}
			
			return $result;
		} catch ( PDOException $e ) {
			$message = $e->getMessage ();
			error_log( "Error getting reltypes: $message", 3, '/var/tmp/php.log');
		}
		
	}
	
	public function listAllLinks( $offset, $limit, $reltype ) {
		
		$offset = (isset( $offset))? intval($offset) : 0 ;
		$limit = (isset( $limit))? intval( $limit) : 1000;
		$reltype = (isset($reltype))? intval( $reltype) : null ;

		$sql = "select r.id, r.reltypeid, rt.name as 'reltypename', r.fromid, r.fromver, r.toid, r.tover, r.relobjid, r.relobjver, created, userid, status, weight, vocid from crel r join creltype rt on r.reltypeid = rt.id" ;
		
		if( isset($reltype)) {
			$sql = $sql . " WHERE r.reltypeid = :reltypeid";
		} 
		$sql = $sql . " LIMIT $offset, $limit " ;

		try {
			$db = $this->getConnection ();
		
			$stmt = $db->prepare( $sql );
			if( isset($reltype)) {
				$stmt->bindParam ( "reltypeid", $reltype, PDO::PARAM_INT );
			}

			$stmt->execute();
			$result = $stmt->fetchAll ( PDO::FETCH_OBJ );
			$db = null;
			return $result;
		} catch ( PDOException $e ) {
			$message = $e->getMessage ();
			error_log( "Error getting reltypes: $message ", 3, '/var/tmp/php.log');
		}
		
	}
	
	public function listLinks( $offset, $limit, $origin, $direction, $reltype ) {
	
		$offset = (isset( $offset))? intval($offset) : 0 ;
		$limit = (isset( $limit))? intval( $limit) : 1000;
		$origin = intval($origin) ;
		$direction = (isset($direction))? intval($direction) : self::LINK_DIR_OUT;
		$reltype = (isset($reltype))? intval( $reltype) : null ;
		// TODO: explode reltype into all child relationship types
		// TODO: handle reciprocal relationships
	
		$sql = "SELECT r.id, r.reltypeid, rt.name as 'reltypename', r.fromid, r.fromver, o1.classid as 'fromclass', r.toid, r.tover, o2.classid as 'toclass', r.relobjid, r.relobjver, " ;
		$sql = $sql . " r.created, r.userid, r.status, r.weight, r.vocid FROM crel r JOIN creltype rt ON r.reltypeid = rt.id 	LEFT OUTER JOIN cinst o1 ON r.fromid = o1.id LEFT OUTER JOIN cinst o2 ON r.toid = o2.id" ;
		
		//TODO: allow selection of versioned relationships
		if( $direction == self::LINK_DIR_OUT ) {
			$sql = $sql . " WHERE r.fromid = :origin" ;
		} else {
			// inbound rels
			$sql = $sql . " WHERE r.toid = :origin" ;
		}
		
		if( isset( $reltype)) {
			$sql = $sql . " AND r.reltypeid = :reltypeid " ;
		}
		
		$sql = $sql . " LIMIT $offset, $limit " ;
			
		try {
			$db = $this->getConnection ();
	
			$stmt = $db->prepare( $sql );
			$stmt->bindParam( "origin", $origin, PDO::PARAM_INT) ;
			
			if( isset($reltype)) {
				$stmt->bindParam ( "reltypeid", $reltype, PDO::PARAM_INT );
			}
	
			$stmt->execute();
			$result = $stmt->fetchAll ( PDO::FETCH_OBJ );
			$db = null;
			return $result;
		} catch ( PDOException $e ) {
			$message = $e->getMessage ();
			
			echo $message ;
			error_log( "Error getting reltypes: $message ", 3, '/var/tmp/php.log');
		}
	
	}
	
	public function listLinkObjs( $offset, $limit, $origin, $direction, $reltype, $withlink ) {
		$withlink = (isset( $withlink))? $withlink : false ;
		$links = $this->listLinks( $offset, $limit, $origin, $direction, $reltype) ;
		
		$result = array() ;
		if( isset($links) && count($links) > 0 ) {
			$linkMap = array() ;	// map of links by target object id
			$classMap = array() ;	// map of classes found in the target list with arrays of ids associated.
			$this->mapLinkKeys($linkMap, $classMap, $links, $direction ) ;
			// get objects per class group
			foreach( $classMap as $classid => $idlist ) {
				$objlist = $this->getObjList( $classid, $idlist ) ;
				
			}
			// return objects or insert into link as per (withlink)
			if( $withlink ) {
				foreach( $objlist as $obj ) {
					$link = $linkMap[$obj->id] ;
					$link->obj = $obj ;
					$result[] = $link ;
				}
			} else {
				$result = $objlist ;
			}

		}
		
		return $result ;
	}
	
	private function mapLinkKeys( &$linkmap, &$classMap, $links, $direction ) {
		
		foreach( $links as $link ) {
			if( $direction == self::LINK_DIR_OUT) {
				$key = $link->toid ;
				$classid = $link->toclass ;
			} else {
				$key = $link->fromid ;
				$classid = $link->fromclass ;
			}
			$linkmap[$key] = $link ;
			
			if( array_key_exists( $classid, $classMap )) {
			
				$classIdList = $classMap[$classid] ;
				$classIdList[] = $key ;
				$classMap[$classid] = $classIdList;
				
			} else {
			
				$classIdList = array() ;
				$classIdList[] = $key ;
				$classMap[$classid] = $classIdList;
				
			}

		}
		
	}
	
	public function getLink( $id ) {
		
		$sql = "select r.id, r.reltypeid, rt.name as 'reltypename', r.fromid, r.fromver, r.toid, r.tover, r.relobjid, r.relobjver, created, userid, status, weight, vocid from crel r join creltype rt on r.reltypeid = rt.id where r.id = :id" ;

		try {
			$db = $this->getConnection ();
		
			$stmt = $db->prepare( $sql );
			$stmt->bindParam ( "id", $id, PDO::PARAM_INT );
			
			$stmt->execute();
			$result = $stmt->fetchObject( );
			$db = null;
			return $result;
		} catch ( PDOException $e ) {
			$message = $e->getMessage ();
			error_log( "Error getting reltypes: $message ", 3, '/var/tmp/php.log');
		}
		
	}
	
	public function createLink( $link ) {
		
		$link->fromver = $this->getProp( $link, "fromver", 0) ;
		$link->tover = $this->getProp( $link, "tover", 0);
		
		$valid = $this->validateLink( $link ) ;
		if( $valid->success ) {
			return $this->addLink($link);
		} else {
			return $valid ;
		}
	}
	
	// ----------------------------------------------
	private function validateLink( $link ) {
		$valid = new stdClass() ;
		$valid->success = true ;
		
		$reltype = $this->getRelType( $link->reltypeid) ;
		
		if( !$reltype) {
			$valid->success = false ;
			$valid->code = self::ERR_INVALID_RELTYPE;
			$valid->message = "Invalid relationship type" ;	
			return $valid;		
		}
		
		// check for duplicate links
		if( $this->duplicateLinkTest($link)) {
			$valid->success = false ;
			$valid->code = self::ERR_DUPLICATE_LINK;
			$valid->message = "Link already exists" ;
			return $valid ;
		} 
			
			
		if( !$this->targetClassLinkTest( $reltype, $link, self::LINK_SOURCE ) ) {
			$valid->success = false ;
			$valid->code = self::ERR_INVALID_SOURCECLASS ;
			$valid->message = "Invalid source class" ;
			return $valid;
		}
		
		if( !$this->targetClassLinkTest( $reltype, $link, self::LINK_DEST ) ) {
			$valid->success = false ;
			$valid->code = self::ERR_INVALID_DESTCLASS ;
			$valid->message = "Invalid destination class" ;
			return $valid;
		}
		
		// check for source counts
		if( !$this->linkCountTest( $reltype, $link, self::LINK_SOURCE, 1 ) ) {
			// destination object is linked to to many source objects
			$valid->success = false ;
			$valid->code = self::ERR_INVALID_SOURCECOUNT ;
			$valid->message = "To many source objects" ;
			return $valid;
		}	

		if( !$this->linkCountTest( $reltype, $link, self::LINK_DEST, 1 ) ) {
			// source object is linked to too many destination objects
			$valid->success = false ;
			$valid->code = self::ERR_INVALID_DESTCOUNT ;
			$valid->message = "To many destination objects" ;
			return $valid;
		}
		
		// check for destination counts
		
		return $valid ;
	}
	
	private function duplicateLinkTest( $link ) {
		
		// TODO: if reltype is reciprocal, verify that there is not a inverse relationship registered too. 
		
		$sql = "SELECT 1 FROM crel WHERE fromid = :fromid AND fromver = :fromver AND toid = :toid AND tover = :tover AND reltypeid = :reltypeid LIMIT 1" ;
	
		try {
			$db = $this->getConnection();
			$stmt = $db->prepare($sql);
			$stmt->bindParam ( "reltypeid", intval($link->reltypeid), PDO::PARAM_INT);
			$stmt->bindParam ( "fromid", intval($link->fromid), PDO::PARAM_INT );
			$stmt->bindParam ( "fromver", intval($link->fromver), PDO::PARAM_INT  );
			$stmt->bindParam ( "toid", intval($link->toid), PDO::PARAM_INT  );
			$stmt->bindParam ( "tover", intval($link->tover), PDO::PARAM_INT  );
			$stmt->execute() ;
			$res = $stmt->fetchColumn() ;
			$db = null;
	
			$pass = ( $res === "1")? true : false ;

			return $pass ;
		} catch(PDOException $e) {
			// TODO: log
			return true ;
		}
	}
	
	// check that record link is of correct class type
	private function targetClassLinkTest( $reltype, $link, $target ) {
		
		// TODO: if we add sub-classes should expand sub-class types. for now class 'hierarchy' is flat.
		
		$sql = "SELECT 1 FROM cinst o WHERE o.id = :id AND o.classid = :classid" ; 
		try {
			$db = $this->getConnection();
			$stmt = $db->prepare($sql);
			if( $target == self::LINK_SOURCE ) {
				$stmt->bindParam ( "id", intval($link->fromid), PDO::PARAM_INT);
				$stmt->bindParam ( "classid", intval($reltype->fromclass), PDO::PARAM_INT );
			} else {
				$stmt->bindParam ( "id", intval($link->toid), PDO::PARAM_INT);
				$stmt->bindParam ( "classid", intval($reltype->toclass), PDO::PARAM_INT );
			}
			
			$stmt->execute() ;
			$res = $stmt->fetchColumn() ;
			$db = null;
	
			$pass = ( $res === "1")? true : false ;

			return $pass ;
		} catch(PDOException $e) {
			// TODO: log
			return true ;
		}
		
		
	}
	
	// check on the count to be sure adding one will not exceed
	private function linkCountTest( $reltype, $link, $target, $delta ) {
	
		// TODO: if we add sub-classes should expand sub-class types. for now class 'hierarchy' is flat.
		$maxcount = -1 ;
		$objid = 0;
		$objver = 0 ;
		$reltypeid = $reltype->id ;
		if($target == self::LINK_SOURCE ) {
			$maxcount = intval($reltype->fromcount) ;
			$sql = "SELECT count(*) FROM crel WHERE toid = :objid AND tover = :objver and reltypeid = :reltypeid" ;
			$objid = intval($link->toid) ;
			$objver = intval($link->tover) ;
		} else {
			$maxcount = intval( $reltype->tocount ) ;
			$sql = "SELECT count(*) FROM crel WHERE fromid = :objid AND fromver = :objver and reltypeid = :reltypeid" ;
			$objid = intval($link->fromid) ;
			$objver = intval($link->fromver) ;
		}
		
		if( $maxcount == -1 ) return true;
	
		try {
			$db = $this->getConnection();
			$stmt = $db->prepare($sql);
			$stmt->bindParam( "objid", $objid, PDO::PARAM_INT);
			$stmt->bindParam( "objver", $objver, PDO::PARAM_INT );
			$stmt->bindParam( "reltypeid", $reltypeid, PDO::PARAM_INT);
			$stmt->execute() ;
			$count = intval($stmt->fetchColumn()) ;
			$db = null;
	
			$pass = ( $count + $delta > $maxcount)? false : true ;
	
			return $pass ;
		} catch(PDOException $e) {
			
			$message = $e->getMessage ();
			echo( $message ) ;
			error_log ( "Error creating user: $message\n", 3, '/var/tmp/php.log' );
			return false ;
		}
	
	
	}
	// ---------------------------------------------
	
	private function addLink( $link ) {
	
		$now = $date = date ( 'Y-m-d H:i:s' );
		$link->created = $now ;
		$weight = $this->getProp( $link, "weight", 0);
		$status = 0 ; // published
	
		$sql = "INSERT INTO crel ( reltypeid, fromid, fromver, toid, tover, relobjid, relobjver, created, userid, status, weight, vocid ) ";
		$sql .= "VALUES ( :reltypeid, :fromid, :fromver, :toid, :tover, :relobjid, :relobjver, :created, :userid, :status, :weight, :vocid)";
		try {
			$db = $this->getConnection ();
			$stmt = $db->prepare ( $sql );
			
			$stmt->bindParam ( "reltypeid", intval($link->reltypeid), PDO::PARAM_INT); 
			$stmt->bindParam ( "fromid", intval($link->fromid), PDO::PARAM_INT );
			$stmt->bindParam ( "fromver", intval($link->fromver), PDO::PARAM_INT  );
			$stmt->bindParam ( "toid", intval($link->toid), PDO::PARAM_INT  );
			$stmt->bindParam ( "tover", intval($link->tover), PDO::PARAM_INT  );
			$stmt->bindParam ( "relobjid", $link->relobjid, PDO::PARAM_INT  );
			$stmt->bindParam ( "relobjver", $link->relobjver, PDO::PARAM_INT  );
			$stmt->bindParam ( "created", $link->created );
			$stmt->bindParam ( "userid", $link->userid, PDO::PARAM_INT  );
			$stmt->bindParam ( "status", intval($status), PDO::PARAM_INT  );
			$stmt->bindParam ( "weight", intval($weight), PDO::PARAM_INT  );
			$stmt->bindParam ( "vocid", $link->vocid, PDO::PARAM_INT  );
			$stmt->execute ();
			$newid = $db->lastInsertId();
			$link->id = intval($newid);
			$db = null;
			return $link;
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
	
	
	// =================================================================================
	
	protected function getConnection() {
		$dbinfo = getenv ( "DOTTIFY_DB" );
	
		$connect = explode ( ':', $dbinfo );
		$dbhost = $connect [0];
		$dbuser = $connect [1];
		$dbpass = $connect [2];
		$dbname = $connect [3];
		 
		$dbh = new PDO( "mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpass );
	
		$dbh->setAttribute ( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
		return $dbh;
	}
	
	protected function getProp($obj, $propname, $default) {
		
		if (property_exists ( $obj, $propname )) {
			return $obj->$propname;
		} else {
			return $default;
		}
	}
}