<?php
class xatacard_layout_Field {
	
	
	private $path;
	private $schema;
	private $readOnly = false;;
	
	
	public function __construct(xatacard_layout_Schema $schema, $path){
		$this->path = $path;
		$this->schema = $schema;
	}
	
	public function setSchema(xatacard_layout_Schema $schema){
		$this->schema = $schema;
		return $this;
	}
	
	public function getSchema(){
		return $this->schema;
	}
	
	public function setPath($path){
		$this->path = $path;
		return $this;
	}
	
	public function getPath(){
		return $this->path;
	}
	
	public function __destruct(){
		unset($this->schema);
	}
	
	public function isReadOnly(){
		return $this->readOnly;
	}
	
	public function setReadOnly($readOnly){
		$this->readOnly = $readOnly;
		return $this;
	}
}
