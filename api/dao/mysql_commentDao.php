<?php
namespace Dottify\dao;

require_once __DIR__ . "/../model/Comment.php" ;

use PDO ;
use Dottify\model\Comment ;

class mysql_commentDao extends mysql_baseDao {
	
	// get a content item by id/version
	public function get( $id, $ver) {

		$obj = new Comment() ;
		$result = $this->doBasicGet( $obj, $id, $ver) ;
		return $result ;

	}
	

	// add an all new user
	public function create( $obj ) {
		
		error_log ( "add new comment\n", 3, '/var/tmp/php.log' );
		
		// Do the generic object creation, setting dates, saving into the cisint table and getting the id back.
		// TODO: get status from a constant singleton.
		$obj->status = 0 ;	// Comments are created published
				
		$obj = $this->createObjInstance( $obj ) ;
		$obj = $this->doBasicCreate( $obj ) ;
		// TODO:
		// if reid is provided, link to other content
		
		return $obj ;
			
	}
	
	public function update( $obj, $norev ) {
		
		/* not versioned!
		if (!$norev) {
			error_log ( "save comment $obj->id version \n", 3, '/var/tmp/php.log' );
			$this->doBasicVersion ( $obj, true ) ;	// refetch and save version
		} */
		
		// TODO: Combine these into the second?
		$obj = $this->updateObjInstance( $obj, !$norev ) ;
		$obj = $this->doBasicUpdate( $obj ) ;
		return $obj ;		
	}
	
	public function delete( $id ) {
		$obj = new Comment();
		$obj->id = $id ;
		$success = $this->doBasicDelete( $obj ) ;
	
		return $success ;
	}
	
	
	
	// get a list of content items by criteria
	public function query($offset, $limit, $preds, $order ) {
		$obj = new Comment() ;
		return $this->doBasicQuery( $obj, $offset, $limit, $preds, $order ) ;
	}

}



