<?php
class JSON {

	public static function notice($msg){
		return self::_json(array('notice'=>$msg, 'success'=>0, 'msg'=>$msg));
	}
	
	public static function warning($msg){
		return self::_json(array('warning'=>$msg, 'success'=>0, 'msg'=>$msg));
	}
	
	public static function error($msg){
		return self::_json(array('error'=>$msg, 'success'=>0, 'msg'=>$msg));
	}
	
	public static function _json($arr){
		import('Services/JSON.php');
		$json = new Services_JSON();
		return $json->encode($arr);
		/*
		if ( is_array($arr) ){
			$out = array();
			foreach ( $arr as $key=>$val){
				$out[] = "'".addslashes($key)."': ".JSON::_json($val);
			}
			return "{".implode(', ', $out)."}";
		} else if ( is_int($arr) || is_float($arr) ){
			return $arr;
		} else if ( is_bool($arr) ){
			return ( $arr?'1':'0');
		} else {
			return "'".addslashes($arr)."'";
		}
		*/
	}
}
