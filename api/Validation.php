<?php


class ValidationList {
	
	public $element = array() ;
	public $allvalid = true ;
	
	public function add( $validation) {
		$this->element[$validation->attribute] = $validation ;
		if( !$validation->isvalid) {
			$this->allvalid = false ;
		}
	}
	
}


class Validation {
	
	public $attribute ;
	public $isvalid = false ;
	public $message ;
	
	public function __construct( $attribute, $valid, $message  ) {
		$this->attribute = $attribute ;
		$this->isvalid = $valid ;
		$this->message = $message ;
	}
	
}