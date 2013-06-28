<?php
class xatacard_layout_Schema {
	
	private $id;
	private $name;
	private $label;
	
	private $fields = array();
	private $subschemas = array();
	private $version = 0;
	
	private $properties = array();
	
	
	public static function createTable(){}
	public static function loadSchema($query){}
	public function save(){}
	public function revert(){}
	public function delete(){}
	
	
	protected function serialize(){}
	protected function unserialize(Dataface_Record $row){}
	
	
	public function getProperty($key){
		return @$this->properties[$key];
	}
	
	public function setProperty($key, $val){
		$this->properties[$key] = $val;
	}
	
	public function getProperties(){
		return $this->properties;
	}
	
	public function setProperties(array $props){
		foreach ($props as $k=>$v){
			$this->setProperty($k, $v);
		}
	}
	
	
	public function getName(){
		return $this->name;
	}
	
	public function getId(){
		return $this->id;
	}
	
	public function getLabel(){
		return $this->label;
	}
	
	
	public function getField($path){
		return $this->fields[$path];
	}
	
	public function getFields(){
		return $this->fields;
	}
	
	public function getSubschemas(){
		return $this->subschemas;
	}
	
	public function getSubschema($path){
		return $this->subschemas[$path];
	}
	
	public function getVersion(){
		return $this->version;
	}
	
	
}
