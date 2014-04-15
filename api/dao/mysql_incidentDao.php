<?php
namespace Dottify\dao;

require_once __DIR__ . "/../model/Incident.php" ;

use PDO ;
use Dottify\model\Incident ;

class mysql_incidentDao extends mysql_baseDao {
	
	// get a content item by id/version
	public function get( $id, $ver) {

		$obj = new Incident() ;
		$result = $this->doBasicGet( $obj, $id, $ver) ;
		return $result ;

	}
	

	// add an all new user
	public function create( $obj ) {
		
		error_log ( "add new incident\n", 3, '/var/tmp/php.log' );
		
		// Do the generic object creation, setting dates, saving into the cisint table and getting the id back.
		// TODO: get status from a constant singleton.
		$obj->status = 1 ;	// Incidents are created in a pending state
		$obj->ver = 0;		// initialize ver to 0
		
		$obj = $this->createObjInstance( $obj ) ;
		$obj = $this->doBasicCreate( $obj ) ;
		
		return $obj ;
			
	}
	
	public function update( $obj, $norev ) {
		
		if (!$norev) {
			error_log ( "save incident $obj->id version \n", 3, '/var/tmp/php.log' );
			$this->doBasicVersion ( $obj, true ) ;	// refetch and save version
		}
		
		// TODO: Combine these into the second?
		$obj = $this->updateObjInstance( $obj, !$norev ) ;
		$obj = $this->doBasicUpdate( $obj ) ;
		return $obj ;		
	}
	
	public function delete( $id ) {
		$obj = new Incident();
		$obj->id = $id ;
		$success = $this->doBasicDelete( $obj ) ;
	
		return $success ;
	}
	
	
	
	// get a list of content items by criteria
	public function query($offset, $limit, $preds, $order ) {
		$obj = new Incident() ;
		return $this->doBasicQuery( $obj, $offset, $limit, $preds, $order ) ;
	}
	
	// ==============================================================================================================
	
	// use default implementations
	/*
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
	*/

}


