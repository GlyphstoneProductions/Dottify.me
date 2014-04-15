<?php
namespace RealSelf\services\kraken\dao ;
/**
 * Base implementation of DAOs to provide generalized utility methods
 */

require_once "BaseMySqlDao.php" ;
require_once __DIR__ . "/../../config/Pimp.php" ;

use RealSelf\services\config\Pimp ;
use PDO ;

abstract class BaseContentMySqlDao extends BaseMySqlDao {
	
	// handles gets from all tables that follow the standard pattern for revisioned keyed tables: (id, rid, status...)
	// Also handles cases where table does not have rid (uses metadata inspection)
	public function doBasicRevisionedGet( $obj, $id, $rid, $status ) {
		
		$bindstatus = false ;
		$bindrid = false ;
		$tableName = $obj->getTableName() ;
		$className = $obj->getClassName() ;
		
		$metadata = $obj->getMetadata() ;
		
		if( $this->colExists( $metadata, "rid" )) {
			
			// use is_null and not empty() as empty interprets 0 as empty
			if( is_null($rid) ) {
				// get the highest revision
				$sql = "SELECT * FROM $tableName WHERE id=:id " ;
				if( !is_null($status)) {
					$sql .= "AND status = :status" ;
					$bindstatus = true ;
				}
				$sql .= " ORDER BY rid DESC LIMIT 1";
			
			} else {
				// rid is specified, get specified id/rid regardless of status
				$sql = "SELECT * FROM $tableName WHERE id = :id and rid = :rid" ;
				$bindrid = true ;
			}		
		} else {
			$sql = "SELECT * FROM $tableName WHERE id = :id " ;			
		}
		
		try {
			$db = $this->getConnection();
			$stmt = $db->prepare($sql);
			$stmt->bindParam("id", $id);
			if( $bindrid ) { $stmt->bindParam("rid", $rid); }
			if( $bindstatus ) { $stmt->bindParam("status", $status); }
			$stmt->execute();
			$guide = $stmt->fetchObject( $className );
			$db = null;
		
			return $guide;
		} catch(PDOException $e) {
			return json_decode( '{"error":{"text":'. $e->getMessage() .'}}' );
				
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
	protected function nextCommonIdRevision( $id, $rid ) {
	
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
	protected function setPreviousStatuses($obj)
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
	protected function colExists( $metadata, $colname ) {
		$colmetas = $metadata->columns ;
		return array_key_exists( $colname, $colmetas ) ;
	}
	
	
}
