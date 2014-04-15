<?php
namespace RealSelf\services\kraken\dao ;
/**
 * Base implementation of DAOs to provide generalized utility methods
 */

require_once __DIR__ . "/../../config/Pimp.php" ;
require_once __DIR__ . "/../OptionManager.php" ;

use RealSelf\services\config\Pimp ;
use RealSelf\services\kraken\OptionManager ;
use PDO ;
use PDOException ;

abstract class BaseMySqlDao {
	public $logger ;
	
	public abstract function newModelObj() ;
	
	public function getConnection() {
	
		$pimp = Pimp::getInstance() ;
		$conf = $pimp->getConfigFromFile("serviceconfig", "serviceconfig.conf" ) ;
		
		$dbhost = $conf['db-server'] ;
		$dbuser = $conf['db-user'] ;
		$dbpass= $conf['db-pwd'] ;
		$dbname = $conf['db-database'] ;
		// echo "GetConnection: host: $dbhost  user: $dbuser  pwd: $dbpass  dbname: $dbname\n";
		try {
			$dbh = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpass);
			
		} catch (PDOException $e) {
			echo 'Connection failed: ' . $e->getMessage();
		}
		$dbh->exec("SET NAMES utf8");
		$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		return $dbh;
	}
	
	public function setLogger( $logger ) {
		$this->logger = $logger ;
	}
	
	public function getLogger( ) {
		return $this->logger ;
	}
	

	public function uniqueTest( $colname, $value, $caseSensitive ) {
		
		$obj = $this->newModelObj() ;
		$tableName = $obj->getTableName() ;

		if( $caseSensitive ) {
			$sql = "SELECT 1 FROM $tableName WHERE $colname = '$value'" ;
		} else {
			$value = strtolower($value) ;
			$sql = "SELECT 1 FROM $tableName WHERE LOWER($colname) = '$value'" ;			
		}
	
		try {
			$db = $this->getConnection();
			$stmt = $db->query($sql);
			$res = $stmt->fetchColumn() ;
			$db = null;
			$pass = ( $res === "1")? false : true ;
			return $pass ;
		} catch(PDOException $e) {
			// TODO: log
			return false ;
		}
	
	}
	
	/**
	 * Basic get without rid
	 * Assuming key is named "id"
	 * @param unknown $obj
	 * @param unknown $id
	 * @param unknown $status
	 * @return mixed
	 */
	public function doBasicGet( $obj, $id, $status ) {
		
		$bindstatus = false ;
		$tableName = $obj->getTableName() ;
		$className = $obj->getClassName() ;
			
		$metadata = $obj->getMetadata() ;
		$sql = "SELECT * FROM $tableName WHERE id = :id " ;	 ;
		if( $this->colExists( $metadata, "status" ) && !is_null($status)) {
			$sql .= "AND status = :status";
		}	
		
		try {
			$db = $this->getConnection();
			$stmt = $db->prepare($sql);
			$stmt->bindParam("id", $id);
			if( $bindstatus ) { $stmt->bindParam("status", $status); }
			$stmt->execute();
			$objout = $stmt->fetchObject( $className );
			$db = null;
		
			return $objout;
		} catch(PDOException $e) {
			return json_decode( '{"error":{"text":'. $e->getMessage() .'}}' );
				
		}
	}
	
	public function doGenericObjGet( $obj, $sql, $params ) {

		$className = $obj->getClassName() ;
		try {
			$db = $this->getConnection();
			
			$stmt = $db->prepare($sql);
			foreach( $params as $paramkey => $paramval ) {
				$stmt->bindParam( $paramkey, $paramval);
			}
			$stmt->execute();
			$objout = $stmt->fetchObject( $className );
			$db = null;
		
			return $objout;
		} catch(PDOException $e) {
			return json_decode( '{"error":{"text":'. $e->getMessage() .'}}' );
		}
		
	}
	
	/**
	 * May be used to create a new record or a revision of an existing record
	 * If the id is set and revision 
	 * 
	 * @param unknown $data
	 * @param unknown $obj
	 * @param unknown $revision - if a revision of the record already exists, increment the revision (assuming the table supports it)
	 * @return unknown|string
	 */
	public function doBasicCreate( $obj, $newRevision ) {
			
		$id = $obj->getId() ;
		$revision = $obj->getRevision() ;
		
		// Creating a new record (not revising an existing)
		if( $obj->getCommonId() && empty($id)) {
			$obj->setId( $this->nextCommonId($obj)) ;
			$obj->setRevision(0) ;
		}

		$saveNewRev = false ;
		$metadata = $obj->getMetadata() ;
		if( $this->colExists( $metadata, "rid")) {
			if( !empty($id) && $obj->getRevisioned() && $newRevision ) {
				$newrev = $obj->getRevision() + 1 ;
				$obj->setRevision( $newrev ) ;
				$saveNewRev = true ;
			
			}			
		}
		
		$sql = $this->genInsertSql( $obj ) ;
		
		try {
			$db = $this->getConnection();
			$stmt = $db->prepare($sql);
			
			$autokeycol = $this->bindColParams( $stmt, $obj ) ;
				
			$stmt->execute();
			$rowsaffected = $stmt->rowCount() ;
			$newid = $db->lastInsertId();
			$db = null;
			
			$this->setKey( $newid, $obj, $autokeycol ) ;
			$obj->rowsaffected = $rowsaffected ;
			$obj->sql = $sql ;
			
			if( $saveNewRev ) {
				$this->nextCommonIdRevision( $id, $newrev ) ;
				$this->setPreviousStatuses($obj) ;
			}

			return $obj ;
				
		} catch(PDOException $e) {
			// TODO: use Slim logger
			error_log($e->getMessage(), 3, '/var/tmp/php.log');
			return '{"error":{"text":'. $e->getMessage() .'}}';
		}
		
	}

	/**
	 * Do a basic update operation
	 * By definition this is not a revision "update" which is a copy/insert/rev increment etc.
	 * @param unknown $data
	 * @param unknown $obj
	 * @return unknown|string
	 */
	public function doBasicUpdate( $obj ) {
	
		$sql = $this->genUpdateSql( $obj ) ;
		$now = date("Y-m-d H:i:s") ;
		error_log("update question:[$now] $sql\n", 3, '/var/tmp/php.log');
		try {
			
			$db = $this->getConnection();
			$stmt = $db->prepare($sql);
				
			$autokeycol = $this->bindColParams( $stmt, $obj ) ;
	
			$stmt->execute();
			$rowsaffected = $stmt->rowCount() ;
			$db = null;

			$obj->rowsaffected = $rowsaffected ;
						
			$obj->sql = $sql ;
			return $obj ;
	
		} catch(PDOException $e) {
			// TODO: use Slim logger
			error_log($e->getMessage(), 3, '/var/tmp/php.log');
			return '{"error":{"text":'. $e->getMessage() .'}}';
		}
	
	}
	
	/**
	 * Do a basic delete for a content object.
	 * Delete all versions of the object
	 * and delete the handle record from id_controller
	 * @param unknown $obj
	 */
	public function doBasicDelete( $obj ) {
		
		$tableName = $obj->getTableName() ;
		$modelName = $obj->getModelName() ;
		$commonId = $obj->getCommonId() ;
		$id = $obj->getId() ;
		$delSql = "DELETE FROM $tableName WHERE id = :id" ;
		$cleanSql = "DELETE FROM id_controller WHERE id = :id AND LOWER(controller) = LOWER(:model)" ;
		$db = null ;
		try {
			$db = $this->getConnection();
			$stmt = $db->prepare($delSql);
			$stmt->bindValue( "id", $id, PDO::PARAM_INT ) ;
			$stmt->execute();
			$rowsaffected = $stmt->rowCount() ;
			// if uses common id table and delete succeeded, try to remove the corresponding record form id_controller
			if( $rowsaffected > 0  && $commonId) {
				$stmt = $db->prepare($cleanSql);
				$stmt->bindValue( "id", $id, PDO::PARAM_INT ) ;
				$stmt->bindValue( "model", $modelName ) ;
				$stmt->execute();
			} else {
				error_log("Error deleting $modelName id=$id ", 3, '/var/tmp/php.log');
				// return '{"error":{"text":'. $e->getMessage() .'}}';		
				return false ;		
			}
			$db = null;
			return true ;
		
		} catch(PDOException $e) {
			$db = null;
			// TODO: use Slim logger
			error_log($e->getMessage(), 3, '/var/tmp/php.log');
			return json_decode('{"error":{"text":'. $e->getMessage() .'}}') ;
		}

		
	}
	
	
	// ----------------------------------------------------

	
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
		$sql = "UPDATE $tableName SET \n" ;
		$colmeta = $metadata->columns ;
		$first = true ;
		$idcols = array() ;
		foreach ($colmeta as $propname => $meta ) {

			if( !empty( $meta->key )) {
				$keyval = $meta->key ;
				$idcols[$propname] = $keyval ;
			} else {
				if( $this->canUpdate( $meta )) {
					if( $first === true ) {
						$first = false ;
					} else {
						$sql .= ", " ;
					}
					$sql .= $this->updateColVal( $propname, $meta ) ;
				}
			}
	
		}

		$sql .= " \n" ;
		$sql .=" WHERE " ;
		$first = true ;
		foreach( $idcols as $idcol => $val ) {
			if( $first ) {
				$first = false ;
			} else {
				$sql .= " AND " ;
			}
			$sql .= "$idcol = :$idcol" ;
		}
	
		return $sql ;
	}
	

	// look for the column with insert=>AUTOKEY and set the id to that field for return
	private function setKey( $id, &$data, $keycolname ) {
		if( !empty( $keycolname ) ) {
			$data->$keycolname = $id ;
		}
	}
	
	/**
	 *  generate column insert content
	 *  by default the property name (key) is the column name
	 *  but can override column name explicitly in metadata
	 *  Also allows special tokens to represent functions or constants to replace the
	 */
	
	private function canInsert( $meta ) {
		if( !empty($meta->insert) ) {
			$insval = strtoupper($meta->insert) ;
		
			switch( $insval ) {
				case "NOINSERT" :	// do not insert
				case "AUTOKEY" : 	// automatically generated key
					return false ;
			}
		}
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
		if( !empty( $meta->update)) {
			$updval = strtoupper($meta->update) ;
			
			switch( $updval ) {
				case "NOW" :
				case "NOW()" :
					return "$propname = now()";
					break ;
			}			
		}
		return "$propname = :$propname" ;
	}
	

	private function canUpdate( $meta ) {
		if( !empty( $meta->update)) {
			$updval = strtoupper($meta->update) ;
			
			switch( $updval ) {
				case "SKIP" :
				case "FALSE" :
					return false ;
			}			
		}
		return true ;
	}
	
	private function bindColParams( &$stmt, $obj ) {
		$autokeycol = null ;
		
		$metadata = $obj->getMetadata() ;
		$colmeta = $metadata->columns ;
		foreach ($colmeta as $propname => $meta ) {
			// if there is an "insert" directive in the metadata
			// we do not have to bind the value as it is set to a function or constant or is not insertable (like an ID)
			if( empty($meta->insert) ) {

				$val = $obj->$propname ;
				$param_dtype = $this->getPDODataType( $meta );
				if( $param_dtype == PDO::PARAM_INT) {
					$val = intval( $val ) ;
				}
				$stmt->bindValue( $propname, $val, $param_dtype ) ;
				
			} else {
				if( $meta->insert == "AUTOKEY" ) {
					$autokeycol = $propname ;
				}
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
	
	public function doBasicQuery($obj, $offset, $limit, $predicates, $orderby, $joins, $options) {
		// options
		if( empty($options) ) {
			$options = new OptionManager() ;
		}
		$dtformat = $options->get("dtformat" ) ;
		$totalcount = $options->isTrue("totcnt" ) ;
		
		$selection = ' * ' ;
		
		$className = $obj->getClassName() ;
		$tableName = $obj->getTableName() ;
		$tableAlias = $obj->getTableAlias()  ;
		$defOrderCol = $obj->getDefaultOrderCol() ;
		$defOrderDir = $obj->getDefaultOrderDir() ;

		$pre = "" ;
		// TODO -- generate from model/metadata
		$from = " from $tableName $tableAlias" ;
		$where = $this->predicates_to_where( $predicates, $tableAlias ) ;
		$pre = $this->gen_pre( $where, $totalcount ) ;
		$orderbyclause = $this->gen_orderby( $orderby, array($defOrderCol => $defOrderDir), $tableAlias ) ;
		$post = $this->gen_limit($offset, $limit) ;
		
		/*
		 foreach( $joins as $entity => $jointype ) {
		if( $entity == "user" ) {
		$joinstr = "" ;
		if( strcasecmp($jointype, "outer") == 0 ) $joinstr = "LEFT OUTER" ;
		$from = $from . " $joinstr JOIN user u on g.user_id = u.id " ;
		if( $options->equalsNc('dtformat', 'timestamp' ) ) {
		$selection = $selection . " , u.nickname as 'user_nickname', u.email as 'user_email', UNIX_TIMESTAMP(u.created) as 'user_created'" ;
		} else {
		$selection = $selection . " , u.nickname as 'user_nickname', u.email as 'user_email', u.created as 'user_created'" ;
		}
		}
		}
		*/
		
		$query = "SELECT " . $pre . $selection . $from . $where . $orderbyclause . $post ;
		
		try {
			$db = $this->getConnection();
			$stmt = $db->prepare($query);
		
			$stmt->execute();
		
			$data = $stmt->fetchAll(PDO::FETCH_CLASS, $className);
			$rowcnt = $stmt->rowCount() ;
			$totalrows = null ;
			if( $totalcount ) {
				$totalrows = $db->query('SELECT FOUND_ROWS();')->fetch(PDO::FETCH_COLUMN);
			}
			$db = null;
		
			$result = $obj->getCollection() ;
			$result->elements = $data ;
			$result->rowcount = $rowcnt ;
			$result->totalrows = $totalrows ;
			$result->q = $query ;
				
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
			$val = $pred[2] ;
			$bool = ( sizeof($pred) > 3)? $pred[3] : "AND" ;
			
			$suffix = "";
			if( strtolower($op) == "like" ) {
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
				
			$where = $where . " $tableAlias.$col $op '$val' $suffix" ;
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
			$clause .= " $tableAlias.$col $ascdesc " ;
				
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
	
	// generate preamble for query
	// in this case a directive to get all record count irrespective of limit clauses.
	
	protected function gen_pre( $where, $totalcnt) {
	
		if( !$totalcnt == 1 ) {
			return "" ;
		}
	
		$pre = "" ;
		if( !$this->isNullOrEmpty( $where ) ) {
			$pre = " SQL_CALC_FOUND_ROWS " ;
		}
		return $pre ;
	}
	
	// --------- End query helpers -----
	
	
	/**
	 * Generate a new id from the id_controller table
	 * @param unknown $obj
	 */
	protected function nextCommonId( $obj ) {
		
		$model = $obj->getModelName() ;
		
		$sql = "INSERT INTO id_controller( controller) values ( :controller ) " ;

		try {
			$db = $this->getConnection();
			$stmt = $db->prepare($sql);
			$stmt->bindValue( 'controller', $model ) ;
			$stmt->execute();
			$newid = $db->lastInsertId();
			$db = null;
			return $newid ;
		
		} catch(PDOException $e) {
			// TODO: use Slim logger
			error_log($e->getMessage(), 3, '/var/tmp/php.log');
			return '{"error":{"text":'. $e->getMessage() .'}}';
		}	
	}
	
	/**
	 * Generate a new id from the id_controller table
	 * @param unknown $obj
	 */
	private function nextCommonIdRevision( $id, $rid ) {
	
		$sql = "UPDATE id_controller SET current_rid = :rid WHERE id = :id " ;
	
		try {
			$db = $this->getConnection();
			$stmt = $db->prepare($sql);
			$stmt->bindValue( 'id', $id, PDO::PARAM_INT ) ;
			$stmt->bindValue( 'rid', $rid, PDO::PARAM_INT ) ;
			$stmt->execute();
			$db = null;
			return $rid ;
	
		} catch(PDOException $e) {
			// TODO: use Slim logger
			error_log($e->getMessage(), 3, '/var/tmp/php.log');
			return '{"error":{"text":'. $e->getMessage() .'}}';
		}
	}
	
	/**
	 * Update previous versions and set their status to "1" (inactive)
	 */
	private function setPreviousStatuses($obj)
	{
		if ($obj->status > 1) {
			
			try {
				$tableName = $obj->getTableName() ;
				$id = $obj->getId() ;
				$rid = $obj->getRevision() ;
				$sql = "UPDATE $tableName SET status = 1 where id = :id AND rid != :rid" ;
				$db = $this->getConnection();
				$stmt = $db->prepare($sql);
				$stmt->bindValue( 'id', $id, PDO::PARAM_INT ) ;
				$stmt->bindValue( 'rid', $rid, PDO::PARAM_INT ) ;
				$stmt->execute();
				$db = null;
			
			} catch(PDOException $e) {
				// TODO: use Slim logger
				error_log($e->getMessage(), 3, '/var/tmp/php.log');
				return '{"error":{"text":'. $e->getMessage() .'}}';
			}
			
	
		}
	}
	
	// return boolean indicating if the column exits in the metadata set
	private function colExists( $metadata, $colname ) {
		$colmetas = $metadata->columns ;
		return array_key_exists( $colname, $colmetas ) ;
	}
	
	
}
