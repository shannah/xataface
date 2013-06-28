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
require_once 'Cache/Lite.php';
require_once 'Dataface/Application.php';
class Dataface_PageCache extends Cache_Lite {

	var $tables;
	function Dataface_PageCache($tables=array()){
		$this->tables =& $tables;
		$app =& Dataface_Application::getInstance();
		$params = array(
			'cacheDir' => $app->_conf['cache_dir'].'/dataface_page_cache',
			'lifeTime' => 3600);
		
		
		if ( !file_exists($params['cacheDir']) ){
			mkdir($params['cacheDir'], true);
		}
		if ( !file_exists($params['cacheDir']) ){
			throw new Exception("Cannot create directory '".$params['cacheDir']."'", E_USER_ERROR);
		} else {
			//echo $params['cacheDir'];
		}
		$this->Cache_Lite($params);
		
		
	}
	
	function dbmtime(){
		$tables =& $this->tables;
		$lookup = array_flip($tables);
		$app =& Dataface_Application::getInstance();
		$res = mysql_query("SHOW TABLE STATUS", $app->_db);
		$latestMod = 0;
		while ( $row = mysql_fetch_array($res) ){
			if ( (sizeof($tables) === 0 || isset( $lookup[$row['Name']] ) ) && strtotime($row['Update_time']) > $latestMod ){
				$latestMod = strtotime($row['Update_time']);
			}
		}
		
		return $latestMod;
	
	}
	
	function get($id){
		$mtime = $this->dbmtime();
		$this->_setFileName($id,'default');
		if ( $mtime < $this->lastModified() ){
			return parent::get($id);
		} else {
			return false;
		}
	}
	
	
}

