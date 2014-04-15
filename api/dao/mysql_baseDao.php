<?php
namespace Dottify\dao;

use PDO ;

class mysql_baseDao {

	
	// Create new root object instance in cinst table
	protected function createObjInstance( $obj ) {
		
		$cclass = $obj->classid ;
		$now = $date = date ( 'Y-m-d H:i:s' );
		$obj->created = $now ;
		$obj->modified = $now ;
		$obj->thisver = 1 ;
				
		error_log ( "add new instance obj classid=$cclass\n", 3, '/var/tmp/php.log' );
			
		$sql = "INSERT INTO cinst ( thisver, created, modified, classid, status, userid ) ";
		$sql .= "VALUES (1, :created, :modified, :classid, :status, :userid )";
		try {
			$db = $this->getConnection ();
			$stmt = $db->prepare ( $sql );
			
			$stmt->bindParam ( "created", $now ); // my refid
			$stmt->bindParam ( "modified", $now );
			$stmt->bindParam ( "classid", intval($obj->classid), PDO::PARAM_INT );
			$stmt->bindParam ( "status", intval($obj->status), PDO::PARAM_INT );	// caller determines whether this goes straight to published
			$stmt->bindParam ( "userid", intval($this->getProp($obj, "userid", null)), PDO::PARAM_INT );				
			$stmt->execute ();
			$newid = intval ( $db->lastInsertId () );
			$obj->id = $newid;
			error_log ( "new entity id $newid\n", 3, '/var/tmp/php.log' );
			$db = null;
			return $obj ;
			
		} catch ( PDOException $e ) {
			$message = $e->getMessage ();
			error_log ( "Error creating obj instance: $message\n", 3, '/var/tmp/php.log' );
			return array (
					"Error" => array (
							"text" => $message
					)
			);
		}
	}
	
	// get generic object instance
	protected function getObjInstance( $id ) {

		$sql = "select id, thisver, created, modified, classid, status, userid FROM cinst where id = :id ";

		try {
			$db = $this->getConnection ();
			$stmt = $db->prepare ( $sql );
			$stmt->bindParam ( "id", $id , PDO::PARAM_INT);
			$stmt->execute ();
			$obj = $stmt->fetchObject ( );
			$db = null;
			return $obj;
		} catch ( PDOException $e ) {
			$message = $e->getMessage ();
			error_log ( "Error getting object $id err= $message \n", 3, '/var/tmp/php.log' );
			return array (
					"Error" => array (
							"text" => $message
					)
			);
		}
	}
	
	
	/**
	 * Update the date information and status on the object and optionally increment the version number.
	 * Do not rely on the object passed having the correct version number.
	 * @param unknown $obj
	 * @param unknown $incversion
	 */
	protected function updateObjInstance( $obj, $incversion ) {
		
		$now = $date = date ( 'Y-m-d H:i:s' );
		$id = intval($obj->id);
		$obj->modified = $now ;
			
		error_log ( "updating/ver instance obj ($id)\n", 3, '/var/tmp/php.log' );

		$query = "SELECT thisver from cinst where id = :id" ;
		$update = "UPDATE cinst SET modified = :modified, status = :status where id = :id" ;
		
		if( $incversion ) {
			$update = "UPDATE cinst SET modified = :modified, status = :status, thisver = thisver + 1 where id = :id " ;
		} 		
		
		try {
			$db = $this->getConnection ();
			$stmt = $db->prepare ( $update );
			$stmt->bindParam ( "id", $id, PDO::PARAM_INT );	
			$stmt->bindParam ( "modified", $now );
			$stmt->bindParam ( "status", $obj->status, PDO::PARAM_INT );
			$stmt->execute ();
			// there is a window of error in case of concurrent transactions.
			// this is considered low risk at this time
			// bda 1/10/14
			
			$stmt2 = $db->prepare( $query ) ;
			$stmt2->bindParam ( "id", $id, PDO::PARAM_INT );
			$stmt2->execute() ;
			$thisver = intval($stmt2->fetchColumn());
			$obj->thisver = $thisver ;
			$db = null;
			return $obj ;
				
		} catch ( PDOException $e ) {
			$message = $e->getMessage ();
			error_log ( "Error creating obj instance: $message\n", 3, '/var/tmp/php.log' );
			return array (
					"Error" => array (
							"text" => $message
					)
			);
		}
	}
	
		
	/**
	 * Delete the object instance reference record (by id)
	 * @param unknown $id
	 * @return boolean|multitype:multitype:unknown
	 */
	protected function deleteObjInstance( $id ) {
	
		$id = intval($obj->id);
			
		error_log ( "delete instance obj ($id)\n", 3, '/var/tmp/php.log' );
		
		$sql = "DELETE FROM cinst where id = :id" ;
			
		try {
			$db = $this->getConnection ();
			$stmt = $db->prepare ( $sql );
			$stmt->bindParam ( "id", $id, PDO::PARAM_INT );
			$stmt->execute();
			$db = null;
			return true ;
		
		} catch ( PDOException $e ) {
			$message = $e->getMessage ();
			error_log ( "Error deleting obj instance: $id $message\n", 3, '/var/tmp/php.log' );
			return array (
					"Error" => array (
							"text" => $message
					)
			);
		}
	}
	
	// ===========================================================================
	// Generic CRUD+ implementations
	
	
	protected function doBasicGet( $obj, $id, $ver ) {
		
		if (is_null( $ver )) $ver = 0 ;
		
		$className = $obj->getClassName() ;
		$tableName = $obj->getTableName() ;
		$tableAlias = $obj->getTableAlias()  ;
		$isVersioned = $obj->getIsVersioned() ;
		
		$selection = $this->getSelection( $obj, $tableAlias ) ;
		$from = $this->getFrom($tableName, $tableAlias) ;
		
		if( is_array($id)) {
			$qmtokens = str_repeat("?,", count($id) - 1) . "?" ;
			$where = " WHERE ci.id in ($qmtokens) " ;
			if( $isVersioned ) {
				$where = $where . " AND $tableAlias.ver = 0 " ;
			}
		} else {
			$where = " WHERE ci.id = :id AND $tableAlias.ver = :ver " ;
			if( !$isVersioned ) {
				$where = " WHERE ci.id = :id " ;
			}
		}
				
		$query = "SELECT " . $selection . $from . $where  ;
				
		try {
			$db = $this->getConnection ();
				
			$stmt = $db->prepare ( $query );
			if( is_array($id)) {
				$stmt->execute($id);
				$obj = $stmt->fetchAll(PDO::FETCH_CLASS, $className);
			} else {
				$stmt->bindParam ( "id", $id , PDO::PARAM_INT);
				if( $isVersioned ) {
					$stmt->bindParam ( "ver", $ver , PDO::PARAM_INT);
				}
				$stmt->execute ();
				$obj = $stmt->fetchObject( $className );
			}

			$db = null;
			return $obj;
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
	

	
	// generates and executes an insert for the basic object table -- assuming that the 
	// cinst table instance has been created
	protected function doBasicCreate( $obj ) {
		
	
		$sql = $this->genInsertSql( $obj ) ;
	    
		try {
			$db = $this->getConnection();
			$stmt = $db->prepare($sql);
				
			$autokeycol = $this->bindColParams( $stmt, $obj, 0 ) ; // insert mode
		
			$stmt->execute();
		
			$db = null;
			//$obj->sql = $sql ;
					
			return $obj ;
		
		} catch(PDOException $e) {
			// TODO: use Slim logger
			error_log($e->getMessage(), 3, '/var/tmp/php.log');
			return '{"error":{"text":'. $e->getMessage() .'}}';
		}
		
		return $obj ;
		
	}
	

	protected function doBasicUpdate( $obj ) {
		
		$sql = $this->genUpdateSql( $obj ) ;
		 
		try {
			$db = $this->getConnection();
			$stmt = $db->prepare($sql);
		
			//echo $sql ;
			
			$autokeycol = $this->bindColParams( $stmt, $obj, 1 ) ; // update mode
		
			echo $sql . "\n" ;
			
			$stmt->execute();
		
			$db = null;
			$obj->sql = $sql ;
				
			return $obj ;
		
		} catch(PDOException $e) {
			// TODO: use Slim logger
			error_log($e->getMessage(), 3, '/var/tmp/php.log');
			return '{"error":{"text":'. $e->getMessage() .'}}';
		}
		
		return $obj ;
		
	}
	
	/**
	 * Save a copy of the current object version in preparation for an update of the head version.
	 * The method may be passed a working copy of the current version or may be passed a modified version
	 * from which the id should be got and nothign else.
	 * 
	 * @param unknown $obj
	 */
	protected function doBasicVersion( $obj, $refetch ) {
		
		$now = $date = date ( 'Y-m-d H:i:s' );
		if( $refetch) {
			$obj = $this->get( $obj->id, 0 ) ;
		}
		
		$obj->ver = $obj->thisver ;
		// $obj->modified = $now ;  // modified should show time this version was last substantially modified (not versioned)
		
		$this->doBasicCreate( $obj ) ;
		
	}
	
	/** 
	 * The object contains the id and the metadata, but nothing else guaranteed
	 * This will delete all versions of the specified object.
	 * @param unknown $obj
	 * @return unknown|string
	 */
	protected function doBasicDelete( $obj ) {
		
		$tableName = $obj->getTableName() ;
		
		$sql = "DELETE FROM $tableName WHERE id = :id" ;
		 
		try {
			$db = $this->getConnection();
			$stmt = $db->prepare($sql);
			$stmt->bindParam ( "id", $obj->id, PDO::PARAM_INT );
			$stmt->execute();
			$db = null;
				
			return true ;
		
		} catch(PDOException $e) {
			// TODO: use Slim logger
			error_log($e->getMessage(), 3, '/var/tmp/php.log');
			return false ;
		}

	}
	
	// ==============================================================
	
	private function genInsertSql( $obj ) {
		$tableName = $obj->getTableName() ;
		$metadata = $obj->getMetadata() ;
		$sql = "INSERT INTO $tableName (" ;

		$colmeta = $metadata->columns ;
	
		$first = true ;
		foreach ($colmeta as $propname => $meta ) {
			if( $this->canInsert( $meta )) {
				if( $first === true ) {
					$first = false ;
				} else {
					$sql .= ', ' ;
				}
				$sql .= $this->insertCol( $propname, $meta ) ;
			}
		}
	
		$sql .= ") VALUES (";
		$first = true ;
		foreach ($colmeta as $propname => $meta ) {
			if( $this->canInsert( $meta )) {
				if( $first === true ) {
					$first = false ;
				} else {
					$sql .= ', ' ;
				}
				$sql .= $this->insertColVal( $propname, $meta ) ;
			}
		}
	
		$sql .= ")" ;
	
		return $sql ;
	}
	
	
	private function genUpdateSql( $obj ) {
		$tableName = $obj->getTableName() ;
		$metadata = $obj->getMetadata() ;
		$isVersioned = $obj->getIsVersioned() ;
		$sql = "UPDATE $tableName SET " ;
		$colmeta = $metadata->columns ;
		$first = true ;
		
		foreach ($colmeta as $propname => $meta ) {

			// echo "gencol $propname " ;
			if( $this->canUpdate( $meta )) {
				
				if( $first === true ) {
					$first = false ;
				} else {
					$sql .= ", " ;
				}
				$sql .= $this->updateColVal( $propname, $meta ) ;
			} else {
				
			}
				
		}

		$sql .= " \n" ;
		if( $isVersioned ) {
			$sql .=" WHERE id = :id AND ver = 0" ;
		} else {
			$sql .=" WHERE id = :id " ;			
		}
	
		return $sql ;
	}
	
	/**
	 * Returns boolean indicating whether the column is insertable.
	 * @param unknown $meta
	 * @return boolean
	 */
	private function canInsert( $meta ) {
		$readonly = ( property_exists($meta, "readonly"))?  $meta->readonly : false ;
		$cinst = ( property_exists($meta, "cinst"))?  $meta->cinst : "" ;
		$cinstonly = ( strtoupper( $cinst) == "ONLY" )? true : false ;

		if( $readonly || $cinstonly ) return false ;
		
		return true ;
	}
	
	
	private function insertCol( $propname, $meta ) {
		$colname = $propname ;
		if( !empty($meta->colname)) {
			$colname = $meta->colname ;
		}
	
		return $colname ;
	
	}
	
	private function insertColVal( $propname, $meta ) {
	
		// look for an insert override for the value
		if( !empty($meta->insert) ) {
			$insval = strtoupper($meta->insert) ;
	
			switch( $insval ) {
				case "NOW" :
				case "NOW()" :
					return "now()";
					break ;
			}
		}
	
		return ":$propname" ;
	
	}
	
	private function updateColVal( $propname, $meta ) {

		return "$propname = :$propname" ;
	}
	
	
	private function canUpdate( $meta ) {
		$readonly = ( property_exists($meta, "readonly"))?  $meta->readonly : false ;
		$cinst = ( property_exists($meta, "cinst"))?  $meta->cinst : "" ;
		$cinstonly = ( strtoupper( $cinst) == "ONLY" )? true : false ;
		$canupdate = ( property_exists($meta, "update"))?  $meta->update : true ;
		
		//echo( "canupdate: $canupdate ro: $readonly cio:$cinstonly\n");
		
		if( !$canupdate || $readonly || $cinstonly ) return false ;
		
		return true ;
	}
	
	private function canSelect( $colname, $meta ) {
		$readonly = ( property_exists($meta, "readonly"))?  $meta->readonly : false ;
		$cinst = ( property_exists($meta, "cinst"))?  $meta->cinst : "" ;
		$cinstonly = ( strtoupper( $cinst) == "ONLY" )? true : false ;
		
		if( $colname == "id" || $readonly || $cinstonly ) return false ;
		
		return true ;		
	}
	
	private function bindColParams( &$stmt, $obj, $mode ) {
		$autokeycol = null ;
	
		$metadata = $obj->getMetadata() ;
		$colmeta = $metadata->columns ;
		foreach ($colmeta as $propname => $meta ) {

			$bind = true ;
			if( $mode == 0 ) {
				// insert
				$bind = $this->canInsert( $meta );
			} else if ( $mode == 1 ) {
				// update
				$bind = $this->canUpdate( $meta ) || $propname === "id" ;
								
			}
				
			if( $bind ) {

				$val = $this->getProp( $obj, $propname, null);
				// $val = $obj->$propname ;
				$param_dtype = $this->getPDODataType( $meta );
				
				if( $param_dtype == PDO::PARAM_INT) {
					$val = intval( $val ) ;
				}
							
				$stmt->bindValue( $propname, $val, $param_dtype ) ;
			}
				
		}

		return $autokeycol ;
	
	}
	
		// convert abstract metadata type to PDO data type as necessary
		// DateTime are passed as STRING,
		// Only Integers need to be explicitly cast.
	private function getPDODataType( $meta ) {
		$pdotype = PDO::PARAM_STR ;
	
		if( !empty($meta->datatype)) {
			$metatype = $meta->datatype ;
			switch( $metatype ) {
				case "INT" :
					$pdotype = PDO::PARAM_INT ;
					break ;
			}
		}
		return $pdotype ;
	}
	
	
	// ===============================================================
	
	
	// override these functions in the dao implementation to create extended selection 
	protected function getSelection( $obj, $tableAlias ) {
		
		$select = " ci.id, ci.thisver, ci.classid, ci.created, ccl.name as 'classname', ci.status, cst.name as 'statusname', cst.ispublished as 'ispublished', ci.userid ";
		
		$metadata = $obj->getMetadata() ;
		$colmeta = $metadata->columns ;
	
		foreach ($colmeta as $colname => $meta ) {
			if( $this->canSelect( $colname, $meta )) {
				$select .= ", $tableAlias.$colname";
			}
		}
		
		return $select ;
	}
	
	protected function getFrom( $tableName, $tableAlias ) {
		return " FROM cinst ci LEFT OUTER JOIN cclass ccl on ci.classid = ccl.id LEFT OUTER JOIN cstatus cst on ci.status = cst.id JOIN $tableName $tableAlias ON ci.id = $tableAlias.id" ;
	}
	
	
	
	public function doBasicQuery($obj, $offset, $limit, $predicates, $orderby ) {
			
		$className = $obj->getClassName() ;
		$tableName = $obj->getTableName() ;
		$tableAlias = $obj->getTableAlias()  ;
		$defOrderCol = $obj->getDefaultOrderCol() ;
		$defOrderDir = $obj->getDefaultOrderDir() ;
		
		$selection = $this->getSelection( $obj, $tableAlias ) ;
		$from = $this->getFrom($tableName, $tableAlias) ;
	
		$where = $this->predicates_to_where( $predicates, $tableAlias ) ;
		$orderbyclause = $this->gen_orderby( $orderby, array($defOrderCol => $defOrderDir), $tableAlias ) ;
		$post = $this->gen_limit($offset, $limit) ;
		
		$query = "SELECT " . $selection . $from . $where . $orderbyclause . $post ;
			
		// return $query ;
		
		try {
			$db = $this->getConnection();
			$stmt = $db->prepare($query);
	
			$stmt->execute();
	
			$data = $stmt->fetchAll(PDO::FETCH_CLASS, $className);
			$rowcnt = $stmt->rowCount() ;
			$db = null;
	
			$result = $obj->getCollection() ;
			$result->elements = $data ;
			$result->rowcount = $rowcnt ;
			//$result->q = $query ;
	
			return $result ;
	
		} catch(PDOException $e) {
			return json_decode( '{"error":{"text":'. $e->getMessage() . '}}' );
		}
	}
	
	// --- begin query helpers ---
	
	/**
	 * creates where predicates
	 * Current limitations:
	 * Note only handles ANDing of simple predicates
	 * Also only handles predicates involving columns of Guide, not any joined table.
	 * Alert: not currently safe from SQL injection in where clauses.
	 * @param unknown $predicates
	 * @return string
	 */
	protected function predicates_to_where( $predicates, $tableAlias ) {
		if( empty( $predicates) ) return "" ;
	
		$where = "" ;
		$first = true ;
		foreach( $predicates as $pred ) {
			$col = $pred[0] ;
			$op = $pred[1] ;
			$val = ( sizeof($pred) > 2)? $pred[2] : "" ; 
			$bool = ( sizeof($pred) > 3)? $pred[3] : "AND" ;
				
			$suffix = "";
			$op = strtolower($op) ;
			
			if( $op == "like" ) {
				// force to be case insensitive
				// TODO: externalize and parameterize
				$suffix = " COLLATE utf8_general_ci " ;
			}
			
			if( $first ) {
				$first = false ;
				$where = " WHERE " ;
			} else {
				$where = $where . " $bool " ;
			}
	
			// $where = $where . " $tableAlias.$col $op '$val' $suffix" ;
			switch( $op ) {
				case "isnull" :
					$where = $where . " $col IS NULL " ;
					break ;
				case "isnotnull" :
				case "notnull" :
					$where = $where . " $col IS NOT NULL " ;
					break ;
				default:
					$where = $where . " $col $op '$val' $suffix" ;
					break ;

			}

		}
		return $where ;
	
	}
	
	protected function gen_orderby( $orderby, $default, $tableAlias ) {
		$clause = "" ;
		if( empty( $orderby ) ) {
			$orderby = $default ;
		}
	
		$first = true ;
		foreach( $orderby as $col => $ascdesc) {
			if( $first ) {
				$first = false ;
				$clause = " ORDER BY" ;
			} else {
				$clause = $clause . ', ' ;
			}
			//$clause .= " $tableAlias.$col $ascdesc " ;
			$clause .= " $col $ascdesc " ;
	
		}
		return $clause ;
	
	}
	
	protected function gen_limit( $offset, $limit ) {
		if( empty( $offset ) && empty( $limit ) ) {
			return "" ;
		}
	
		$out = " LIMIT " ;
		if( !is_null( $offset ) ) {
			$out = $out . " $offset " ;
			if( !empty( $limit ) ) {
				$out = $out . ", $limit" ;
			}
		} else {
			if( !empty( $limit ) ) {
				$out = $out . " $limit" ;
			}
		}
		return $out ;
	}
	
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
		//error_log ( "get property $propname \n", 3, '/var/tmp/php.log' );
		if (property_exists ( $obj, $propname )) {
			return $obj->$propname;
		} else {
			//error_log ( "prop $propname not found \n", 3, '/var/tmp/php.log' );
			return $default;
		}
	}
	
}