<?php
/**
 * A logger that can be used to log notices, error messages, and more.
 * Helpful for debugging.
 */
class Dataface_Logger {
	private $items = array();
	private $format='html';
	
	public function setFormat($format){
		$this->format = $format;
	}
	public function log($str, $level=0){
		$this->items[] = array('message'=>$str, 'level'=>$level);
	}
	public function clear(){
		$this->items = array();
	}
	
	public function items($level=0){
		$out = array();
		foreach ($this->items as $i){
			if ( $i['level'] >= $level ) $out[] = $i;
		}
		return $out;
	}
	
	public function dump($level=0, $separator = "\n"){
		$messages = array();
		foreach ($this->items($level) as $i){
			$messages[] = $i['message'];
		}
		if ( $this->format == 'html' ){
			$messages = array_map('df_escape', $messages);
		}
		
		echo implode($separator, $messages);
	}
	
}
