<?php
namespace RealSelf\Model ;

interface IModel {
	
	/**
	 * The unqualified name of the model
	 */
	public function getModelName() ;
	/**
	 * The name of the table underlying the model
	 */
	public function getTableName() ;
	/**
	 * The fully qualified class name (with namespace)
	 */
	public function getClassName() ;
	/**
	 * Get the collection object (e.g. Questions vis Question.
	 */
	public function getCollection() ;
	/**
	 * Get an appropriate table alias for use in generating multi-table queries.
	 */
	public function getTableAlias() ;
	/**
	 * return the name of the column to order query results by default
	 */
	public function getDefaultOrderCol() ;
	/**
	 * return ASC or DESC indicating default query order direction (for above column)
	 */
	public function getDefaultOrderDir() ;
	/** return true if the table as rid on it (is a revisioned table)
	 *
	 */
	
	public function getRevisioned() ;
	/** Return true if the table id should be generated from the common pool (id_controller)
	 * 
	 */
	public function getCommonId() ;
	/** Get the id of the object
	 * 
	 */
	public function getId() ;
	/**
	 * Set the id of the object
	 * @param unknown $id
	 */
	public function setId($id) ;
	/** 
	 * If revisioned, get the revision (rid ) of the object
	 */
	public function getRevision() ;
	/**
	 * If revisioned, set the revision (rid) of the object
	 * @param unknown $rid
	 */
	public function setRevision($rid) ;
	
}