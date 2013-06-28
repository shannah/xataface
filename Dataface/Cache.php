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
class Dataface_Cache {
	/**
	 * The string prefix that should be prepended to all keys for using APC cache.
	 */
	var $prefix;
	
	/**
	 * The cache directory that is used (if APC is not used)
	 */
	var $cachedir;
	
	/**
	 * Array of references to variables that should be stored when updateCache()
	 * is called.
	 */
	var $monitored = array();
	
	function Dataface_Cache($cachedir=null, $prefix=null){
		if ( $cachedir === null ) $cachedir = '/tmp';
		$this->cachedir = $cachedir;
		if ( $prefix === null ) $prefix = DATAFACE_SITE_PATH;
		$this->prefix = $prefix;
		
	
	}
	
	function apc_get($key){
		return apc_fetch($this->prefix.$key);
	}
	
	function apc_set($key, &$value){
		return apc_store($this->prefix.$key, $value);
	}
	
	function get($key){
		return $this->apc_get($key);
	}
	
	function set($key, &$value){
		return $this->apc_set($key, $value);
	}
	
	public static function &getInstance(){
		static $instance = null;
		if ( $instance === null ){
			$instance = new Dataface_Cache();
		}
		return $instance;
	}

}

