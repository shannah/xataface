<?php
class xatacard_layout_Record {
	
	private $datasource = null;
	private $schema = null;
	private $values = array();
	
	private $version = 0;
	private $id;
	
	/**
	 * Array to keep track of any changed values.  This maps path to old value
	 *
	 * @type array(string=>mixed)
	 */
	private $changed = array();
	
	
	
	public function setValue($path, $value){
		$field = $this->schema->getField($path);
		if ( !$field ){
			throw new Exception(sprintf(
				"No such field '%s' in schema '%s' attempting to set value.",
				$path,
				$this->schema->getLabel()
			));
		}
		
		$oldValue = $this->getValue($path);
		if ( $oldValue != $value ){
			$this->values[$path] = $value;
			if ( !$field->isReadOnly() ){
				$this->changed[$path] = $oldValue;
			}
		}
		
		return $this;
		
		
	}
	public function getValue($path){
		return @$this->values[$path];
	}
	
	
	public function s($path, $value){ return $this->setValue($path, $value);}
	public function g($path){ return $this->getValue($path);}
	
	
	public function isChanged($path=null){
		if ( isset($path) ){
			return isset($this->changed[$path]);
		} else {
			return (count($this->changed)>0);
		}
	}
	
	
	/**
	 * Returns an associative array of changed fields with their current values.
	 * This will only include fields that have changed so if no fields have changed
	 * then this will return an empty array.
	 *
	 * @param array(string) $paths Optional array of field paths to limit the search
	 * 	to.  This will result in only a subset of these paths being returned in the
	 *	changed set.
	 *
	 * @returns array(path=>value) Associative array of key value pairs with the current
	 *	values of fields that have changed only.
	 */
	public function getChanged($paths=null){
		if ( !isset($paths) ){
			$paths = array_keys($this->changed);
		}
		
		$out = array();
		foreach ($paths as $p){
			if ( !isset($this->changed[$p]) ) continue;
			$out[$p] = $this->getValue($p);
		}
		return $out;
	}
	
	public function clearSnapshot($paths = null){
		if ( !isset($paths) {
			$paths = array_keys($this->changed);
		}
		foreach ($paths as $p){
		
		
			unset($this->changed[$p]);
		}
		return $this;
	}
	
	
	/**
	 * Returns a snapshot of the record to show the old values of all
	 * records that have been changed.  This will return an associative
	 * array mapping field paths to their corresponding snapshot values.
	 * It will only contain keys for fields that have changed.  Hence if no
	 * fields have changed then this will return an empty array.
	 *
	 * @param array(string) $paths An optional array of fields to limit
	 * 	the search to.  This will be an array of field paths.  This will
	 *  cause only a subset of these paths to be returned in the snapshot.
	 *
	 * @returns array(path=>value)
	 */
	public function getSnapshot($paths=null){
		if ( !isset($paths) ){
			$paths = array_keys($this->changed);
		}
		
		$out = array();
		foreach ($paths as $p){
			
			if ( isset($this->changed[$p]) ){
				$out[$p] = $this->changed[$p];
			}
		}
		return $out;
	}
	
	
	public function save(){
		$this->datasource->save($this);
		return $this;
	}
	
	
	public function delete(){
		$this->datasource->delete($this);
		return $this;
	
	}
	
	
	
}
