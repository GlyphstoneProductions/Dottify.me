<?php
namespace Dottify\model;

require_once "BaseModel.php" ;

use \Dottify\model\BaseModel ;

class Comments  {

	public $elements = array() ;	// array of entities selected

	public function count() {
		return count( $this->element ) ;
	}
	

}

class Comment extends BaseModel {
	
	public function __construct() {
		$this->classid = 3 ;
	}

	public function getModelName( ) {
		return "Comment" ;
	}
	
	public function getClassName() {
		return "Dottify\model\Comment" ;
	}
	
	// table class name
	public function getClassId() {
		return $this->classid ;
	}
	
	public function getTableName() {
		return "comment" ;
	}
	
	public function getTableAlias() {
		return "cm" ;
	}
	
	public function getDefaultOrderCol() {
		return "id" ;
	}
	
	public function getDefaultOrderDir() {
		return "DESC" ;
	}
	
	public function getIsVersioned() {
		return false ;
	}
	
	public function getIsMappable() {
		return false ;
	}
	
	/* return a new instance of the collection object 
	 * 
	 */
	public function getCollection() {
		return new Comments() ;
	}
	
}

