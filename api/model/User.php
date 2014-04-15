<?php
namespace Dottify\model;

require_once "BaseModel.php" ;

use \Dottify\model\BaseModel ;

class Users  {

	public $elements = array() ;	// array of entities selected

	public function count() {
		return count( $this->element ) ;
	}
	

}

class User extends BaseModel {
	
	public function __construct() {
		$this->classid = 1 ;
	}

	public function getModelName( ) {
		return "User" ;
	}
	
	public function getClassName() {
		return "Dottify\model\User" ;
	}
	
	// table class name
	public function getClassId() {
		return $this->classid ;
	}
	
	public function getTableName() {
		return "user" ;
	}
	
	public function getTableAlias() {
		return "us" ;
	}
	
	public function getDefaultOrderCol() {
		return "created" ;
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
	
	/* return a new instance of the collection object 
	 * 
	 */
	public function getCollection() {
		return new Users() ;
	}
	

}
?>