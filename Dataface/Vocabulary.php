<?php
/*-------------------------------------------------------------------------------
 * Xataface Web Application Framework
 * Copyright (C) 2005-2008 Web Lite Solutions Corp (shannah@sfu.ca)
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *-------------------------------------------------------------------------------
 */
/*******************************************************************************
 * File: 		Dataface/Vocabulary.php
 * Author: 	Steve Hannah <shannah@sfu.ca>
 * Created: 	Sept. 2, 2005
 * Description:
 * 	Encapsulates vocabularies that can be used in select lists and auto complete widgets
 * 	to limit the input to a field.
 ******************************************************************************/
 
class Dataface_Vocabulary {

	var $_options = array();
	var $_name;
	function Dataface_Vocabulary($name,$options){
		$this->_name = $name;
		$this->_options = $options;
	}
	
	
	/**
	 * Static method to get a named vocabulary.
	 */
	public static function &getVocabulary($name){
		$vocabularies =& Dataface_Vocabulary::getVocabularies();
		
		if ( !isset( $vocabularies[$name] ) ){
			$vocabularies[$name] = new Dataface_Vocabulary($name, array());
		}
		
		
		return $vocabularies[$name];
	}
	
	
	public static function &getVocabularies(){
		if ( !isset( $vocabularies ) ){
			static $vocabularies = array();
		}
		return $vocabularies;
	}
	
	function register($name, $vocab){
		$vocabs =& Dataface_Vocabulary::getVocabularies();
		
		if ( is_array($vocab) ){
			$vocab = new Dataface_Vocabulary($name, $vocab);
		}
		
		$vocabs[$name] =& $vocab;
	}
	
	
	
	function &options(){
		return $this->_options;
	}
	
	function setOptions($options){
		$this->_options = $options;
	}
	
	function addOption($key,$value=''){
		if ( !$value ) {
			$value = $key;
		}
		
		$this->_options[$key] = $value;
	}
	
	function removeOptions($key){
		unset($this->_options[$key]);
	}
}
		
