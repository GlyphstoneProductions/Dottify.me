<?php
namespace Dottify\dao;

/**
 * Autoload daos
 * pass on everything else to allow other autoloaders to do their stuff
 * 
 */

spl_autoload_register(function ($class) {
	$parsepath = explode( '\\', $class ) ;
	$classdir = $parsepath[ count($parsepath) - 2 ] ;
	if( $classdir == "dao" ) {
		// only handle dao modules 
		$justclass = end( $parsepath ) ;
		error_log("DaoManager load class:'$justclass' from $class in $classdir \n", 3, '/var/tmp/php.log');
		include  $justclass . '.php';
		return true ;
	} 
	return false ;
});

use PDO ;

class DaoManager {
	
 	const INSTANCE_KEY = "DOTTIFY_DAO_MGR" ;
	private $dao_names = array() ;	// map of dao names to php implementation file name
	private $dao_ids = array() ;	// map of classids to php dao implementation file name
	private $daos = array() ;		// cache of dao instances by name	
	private $daosById = array() ;   // cache of dao instances by id
		
	// disable explicit creation of the DaoManager
	private function __construct() {
	
		$classes = $this->getClassInfo() ;
		// load map of all daos from cclass table
		foreach( $classes as $class ) {
			$this->dao_names[$class->name] = $class->daoname ;
			$this->dao_ids[$class->id] = $class->daoname; 
		}
		
	} 
	
	public static function getInstance() {
		
		$instance = apc_fetch(self::INSTANCE_KEY ) ;
		if( $instance == null ) {
			//echo "DaoManager: create new instance\n";//
			$instance = new DaoManager() ;
			apc_store( self::INSTANCE_KEY, $instance, 60 ) ;
		} else {
			//echo( 'from cache: ') ;
			//var_dump( $instance) ;
		}
		
		return $instance ;
	}
	
	
	/**
	 * Get a dao from the cache per the name of the classs
	 * or instantiate per the configuration 
	 * @param unknown $name
	 * @return unknown
	 */
	public function getDaoByName( $name ) {

		$dao = null ;
		if( !array_key_exists( $name, $this->daos)) {
		
		 	$daoName = $this->dao_names[$name] ;
		 	
		 	if( !empty( $daoName ) ) {
		 		try {
		 			$dao = new $daoName() ;
		 			$this->daos[$name] = $dao ;
		 		} catch( Exception $ex ) {
		 			error_log("error instantiating dao: {$ex->getMessage()}\n", 3, '/var/tmp/php.log');
		 		}
		 	} else {
		 		error_log("DAO Name $daoName not found. config file error?\n", 3, '/var/tmp/php.log');		 		
		 	}

		 } else {
		 	$dao = $this->daos[$name] ;
		 }
		 return $dao ;
	}
	
	public function getDaoByClassid( $classid ) {
		$dao = null ;
		if( !array_key_exists( $classid, $this->daosById)) {
		
			$daoName = $this->dao_ids[$classid] ;
		
			if( !empty( $daoName ) ) {
				try {
					$dao = new $daoName() ;
					$this->daosById[$classid] = $dao ;
				} catch( Exception $ex ) {
					error_log("error instantiating dao: {$ex->getMessage()}\n", 3, '/var/tmp/php.log');
				}
			} else {
				error_log("DAO for class $classid not found. config file error?\n", 3, '/var/tmp/php.log');
			}
		
		} else {
			$dao = $this->daosById[$classid] ;
		}
		return $dao ;
	}
	
	
	// flush all daos out of the buffer in case we need to reloade dynamically
	public function flush() {
		$this->daos = array() ;
	}
	
	public function ping() {
		return "Dottify Dao Manager" ;
	}

	protected function getClassInfo() {
		
		$sql = "select * from cclass ";
		try {
			$db = $this->getConnection ();
			
			$stmt = $db->query ( $sql );
			$classes = $stmt->fetchAll ( PDO::FETCH_OBJ );
			$db = null;
			return $classes;
		} catch ( PDOException $e ) {
			$message = $e->getMessage ();
			error_log( "Error getting classes for daoManager init", 3, '/var/tmp/php.log');	
		}
	
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
	
}


	?>