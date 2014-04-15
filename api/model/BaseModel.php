<?php
namespace Dottify\model;

/*
use RealSelf\Model\IModel ;
use RealSelf\Model\ModelMetadata ;
use ReflectionClass ;
use ReflectionProperty ;
use Exception ;
use RealSelf\Kraken\Tuple ;
use RealSelf\Kraken\OptionManager ;
use RealSelf\Kraken\Dao\DaoManager ;
*/

abstract class BaseModel {
	
	private $metadata = array() ;
	
	// override get the primary id for the record
	public function getId() {
		return null ;
	}
	
	public function setId($id ) {
		
	}
	

	public function getDefaultOrderCol() {
		return "modified" ;
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
	
	/**
	 * Load the array data into properties of this object
	 * @param unknown $data
	 */
	public function load($data ) {
		
		if( !isset($data)) return ;
		
		foreach( $data as $prop => $val ) {
			$this->$prop = $val ;
		}
		
	}
	
	public function getMetadata() {

		if( empty( $this->metadata) ) {

			$filepath = $this->getMetadataFilePath() ;
			
			$rawconfig = file_get_contents( $filepath, true ) ; 
			
			if( $rawconfig != null ) {
				$this->metadata = json_decode($rawconfig, false ) ;
				
				if( $this->metadata === null ) {
					echo "Invalid Metadata json: $filepath\n" ;
				}
			}
		}

		return $this->metadata ;
	
	}
	
	private function getMetadataFilePath() {
		$modelName = $this->getModelName() ;
		$filepath = __DIR__ . "/metadata/" . $modelName . ".json" ;
		return $filepath ;
	}
	
	/*
	private function getThisDao() {
		$thisDaoName = $this->getModelName() . "-dao" ;
		$thisDao = DaoManager::getInstance()->getDao( $thisDaoName ) ;
		return $thisDao ;
	}
	*/
	
 
    protected function getProperty( $obj, $property, $default ) {
    	return (property_exists( $obj, $property )? $obj->$property : $default ) ;
    }
	
	
}