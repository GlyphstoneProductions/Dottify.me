<?php
namespace Dottify\model;

require_once "BaseModel.php" ;

use \Dottify\model\BaseModel ;

class Incidents  {

	public $elements = array() ;	// array of entities selected

	public function count() {
		return count( $this->element ) ;
	}
	

}

class Incident extends BaseModel {
	
	public function __construct() {
		$this->classid = 2 ;
	}

	public function getModelName( ) {
		return "Incident" ;
	}
	
	public function getClassName() {
		return "Dottify\model\Incident" ;
	}
	
	// table class name
	public function getClassId() {
		return $this->classid ;
	}
	
	public function getTableName() {
		return "incident" ;
	}
	
	public function getTableAlias() {
		return "ic" ;
	}
	
	public function getDefaultOrderCol() {
		return "id" ;
	}
	
	public function getDefaultOrderDir() {
		return "DESC" ;
	}
	
	public function getIsVersioned() {
		return true ;
	}
	
	public function getIsMappable() {
		return true ;
	}
	

	public function getCollection() {
		return new Incidents() ;
	}


}
