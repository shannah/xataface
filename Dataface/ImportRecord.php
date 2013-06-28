<?php

/**
 * A base class for an import record.  More complex import filters may
 * return arrays of ImportRecord objects instead of Dataface_Record
 * objects. This allows them to define exactly how the record is committed
 * to the database.
 * @author Steve Hannah <steve@weblite.ca>
 * @created March 18th, 2008
 */
class Dataface_ImportRecord {

	/**
	 * @var string
	 * Stores the path to the file that stores the subclass of this file.
	 * This value helps to reload the subclass before unserializing the data.
	 */
	var $classpath = null;
	
	/**
	 * @var array
	 * Stores the values of this import record.
	 */
	var $values = array();
	
	/**
	 * Creates a new record.  
	 * @param string $classpath.  The path to the file that is the subclass.
	 * @param array $data Associative array of data to populate this record.
	 * 
	 * @example Inside the constructor of the subclass, it should call this method:
	 * <code>
	 * class mySubclass extends Dataface_ImportRecord {
	 *		function mySubclass($data){
	 *			$this->Dataface_ImportRecord(__FILE__, $data);
	 *		}
	 * }
	 * </code>
	 */
	function Dataface_ImportRecord($classpath, $data){
		$this->classpath = $classpath;
		$this->load($data);
	}


	/**
	 * Exports the data from this record to an associative array
	 * so that it can be serialized.
	 */
	function toArray(){
		return array_merge(
			$this->getValues(), 
			array(
				'__CLASS__'=>get_class($this), 
				'__CLASSPATH__'=>$this->getClassPath()
			)
		);
	}
	
	/**
	 * Loads the record from an associative array.
	 */
	function load($data){
		if ( is_array($data) ){
			$this->values = $data;
		}
		unset($this->values['__CLASS__']);
		unset($this->values['__CLASSPATH__']);
	}

	function getValues(){
		return $this->values;
	}
	
	/**
	 * @returns Associative array of values in this import record.
	 */
	function setValue($key, $value){
		$this->values[$key] = $value;
	}
	
	
	/**
	 * Returns a value from this import record.
	 */
	function getValue($key){
		return $this->values[$key];
	}
	function val($key){ return $this->getValue($key);}
	
	
	/**
	 * Abstract method.  Commits this import data to the database.
	 */
	function commit(){
		return PEAR::raiseError("Method ".__FUNCTION__." is not implemented.  It should be implemented in a subclass.");
	}
	
	function getClassPath(){
		return $this->classpath;
	}
}
