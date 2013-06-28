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
 
/**
 * Author: Steve Hannah <shannah@sfu.ca>
 * Created: March 23, 2006
 * A tool to manage application configuration. This can read and write configuration
 * files and transfer configuration information from database format to ini files and
 * vice versa.
 *
 */
 
require_once 'I18Nv2/I18Nv2.php';
 
class Dataface_ConfigTool {
	
	var $configTypes = array('actions','fields','relationships','valuelists','tables','lang','metadata');
	var $rawConfig = array();
	var $config = array();
	var $configLoaded = false;
	var $iniLoaded = array();
	var $configTableName = 'dataface__config';

	function Dataface_ConfigTool(){
		$this->apc_load();
		register_shutdown_function(array(&$this, 'apc_save'));
	}
	
	/**
	 * Array to lookup the name of an entity based on its ID.
	 */
	var $nameLookup = array('actions'=>array(), 'fields'=>array(), 'table'=>array(), 'relationships'=>array(), 'valuelists'=>array(),'lang'=>array());
	
	public static function &getInstance(){
		static $instance = 0;
		if (!$instance ){
			$instance = new Dataface_ConfigTool();
		}
		return $instance;
	}
	
	/**
	 * Loads configuration information of particular type for a particular table.
	 * @param $type The type of config information to load. (e.g., 'actions', 'fields', 'table', 'relationships')
	 * @param $table The name of the table for which to load configuration info.
	 * @return A 2-dimensional associative array modelling the configuration information that has been returned.
	 */
	function &loadConfig($type=null, $table=null){
		$out =& $this->loadConfigFromINI($type, $table);
		return $out;
	}
	
	
	/**
	 * Loads configuration information from an INI file.
	 * @param $type The type of configuration information to load: e.g., actions, relationships, valuelists, fields, etc..
	 * @param $tablename The name of the table for which to load the configuration information.
	 * @return Associative array of configuration options in the same form as they would be returned by parse_ini_file().
	 */
	 
	function &loadConfigFromINI($type=null, $tablename='__global__'){
		if ( !isset($tablename) ) $tablename = '__global__';
		$app =& Dataface_Application::getInstance();
		if ( $type == 'lang' ){
			if ( isset($this->config[$type][$app->_conf['lang']][$tablename]) ){
				return $this->config[$type][$app->_conf['lang']][$tablename];
			}
		} else {
			if ( isset( $this->config[$type][$tablename] ) ){
				return $this->config[$type][$tablename];
			}
		} 
		$app =& Dataface_Application::getInstance();
		$paths = array();
		$lpaths = array();
		if ( $type === 'lang' ){
			
			if ( $tablename !== '__global__' ){
				if ( !class_exists('Dataface_Table') ) import('Dataface/Table.php');
				$lpaths[] = Dataface_Table::getBasePath($tablename).'/tables/'.basename($tablename).'/lang/'.basename($app->_conf['lang']).'.ini';
				
			} else {
				$paths[] = DATAFACE_PATH.'/lang/'.basename($app->_conf['lang']).'.ini';
				$lpaths[] = DATAFACE_SITE_PATH.'/lang/'.basename($app->_conf['lang']).'.ini';
			}
		
		} else if ( $tablename !== '__global__' ){
			//$paths = array(DATAFACE_SITE_PATH.'/tables/'.$tablename.'/'.$type.'.ini');
			// Valuelists handle their own cascading because it involves loading
			// the valuelist each time... and there may be opportunities to 
			// share between tables
			if ( $type != 'valuelists' ) $paths[] = DATAFACE_PATH.'/'.basename($type).'.ini';
			if ( $type != 'valuelists' ) $lpaths[] = DATAFACE_SITE_PATH.'/'.basename($type).'.ini';
			$lpaths[] = Dataface_Table::getBasePath($tablename).'/tables/'.basename($tablename).'/'.basename($type).'.ini';
			
		} else {
			
			$paths[] = DATAFACE_PATH.'/'.basename($type).'.ini';
			$lpaths[] = DATAFACE_SITE_PATH.'/'.basename($type).'.ini';
		}
		
		// Add the ability to override settings in a module.
		// Added Feb. 28, 2007 by Steve Hannah for version 0.6.14
		if ( isset($app->_conf['_modules']) and count($app->_conf['_modules']) > 0 ){
			foreach ( $app->_conf['_modules'] as $classname=>$path ){
				$modpath = explode('_',$classname);
				array_shift($modpath);
				$modname = implode('_', $modpath);
				if ( $type == 'lang' ){
					$paths[] = DATAFACE_SITE_PATH.'/modules/'.basename($modname).'/lang/'.basename($app->_conf['lang']).'.ini';
					$paths[] = DATAFACE_PATH.'/modules/'.basename($modname).'/lang/'.basename($app->_conf['lang']).'.ini';
				} else {
					$paths[] = DATAFACE_SITE_PATH.'/modules/'.basename($modname).'/'.basename($type).'.ini';
					$paths[] = DATAFACE_PATH.'/modules/'.basename($modname).'/'.basename($type).'.ini';
				}
			}
		}
		
		// Add the ability to override settings in the database.
		// Added Feb. 27, 2007 by Steve Hannah for version 0.6.14
		if ( @$app->_conf['enable_db_config']  and $type != 'permissions'){
			if ( $type == 'lang' ){
				if ( isset($tablename) ){
					$lpaths[] = 'db:tables/'.basename($tablename).'/lang/'.basename($app->_conf['lang']);
				} else {
					$paths[] = 'db:lang/'.basename($app->_conf['lang']).'.ini';
				}
			} else {
				if ( isset($tablename) ){
					$paths[] = 'db:'.basename($type).'.ini';
					$lpaths[] = 'db:tables/'.basename($tablename).'/'.basename($type).'.ini';
				} else {
					$paths[] = 'db:'.basename($type).'.ini';
				}
			}
		}
		
		if ( !$tablename ){
			$tablename = '__global__';
			
		}

		$paths = array_merge($paths, $lpaths);
		//print_r($paths);
		//print_r($lpaths);
		if ( !isset( $this->config[$type][$tablename] ) ) $this->config[$type][$tablename] = array();
		//import('Config.php');

		foreach ( $paths as $path ){
			if ( !isset( $this->iniLoaded[$path] ) ){
				$this->iniLoaded[$path] = true;
				
				if ( is_readable($path) || strstr($path,'db:') == $path ){
					
					
					$config = $this->parse_ini_file($path, true);
				
					if ( isset( $config['charset'] ) and function_exists('iconv') ){
						I18Nv2::recursiveIconv($config, $config['charset'], 'UTF-8');
					}
					
					
					if ( isset($config['__extends__']) ){
						$config = array_merge_recursive_unique($this->loadConfigFromINI($type, $config['__extends__']), $config);
					}
					
					$this->rawConfig[$path] =& $config;
					
				} else {
					$config = array();
					$this->rawConfig[$path] =& $config;
				}
			} else {
				//echo "getting $path from raw config.";
				//echo "$path already loaded:".implode(',', array_keys($this->iniLoaded));
				$config =& $this->rawConfig[$path];
			}
					
					
			//echo "Conf for x".$path."x: ";
			if ( !$config ) $config = array();
			
			foreach ( array_keys($config) as $entry ){
				if ( $type == 'lang'){
					$this->config[$type][$app->_conf['lang']][$tablename][$entry] =& $config[$entry];
				} else {
					$sep = null;
					if ( strpos($entry, '>') !== false ){
						$sep = '>';
					}
					if ( strpos($entry, ' extends ') !== false ){
						$sep = ' extends ';
					}
					if ( $sep and is_array($config[$entry]) ){
						list($newentry,$entryParents) = explode($sep, $entry);
						$entryParents = array_map('trim',explode(',', $entryParents));
						$newentry = trim($newentry);
						$cout = array();
						foreach ($entryParents as $entryParent){
							if ( !isset($this->config[$type][$tablename][$entryParent]) ){
								throw new Exception("Illegal extends.  Parent not found: ".$entryParent." from rule: ".$entry." in ".$path);
							}
							$pconf =& $this->config[$type][$tablename][$entryParent];
							if ( !is_array($pconf) ){
								throw new Exception("Illegal extends.  Parent is not a section. It is a scalar: ".$entryParent." from rule: ".$entry." in ".$path);
								
							}
							foreach ($pconf as $pkey=>$pval){
								$cout[$pkey] = $pval;
							}
							unset($pconf);
							
						}
						$centry =& $config[$entry];
						foreach ($centry as $ckey=>$cval){
							$cout[$ckey] = $cval;
						}
						unset($centry);
						unset($this->config[$type][$tablename][$entry]);
						unset($this->config[$type][$tablename][$newentry]);
						$this->config[$type][$tablename][$newentry] =& $cout;
						unset($cout);
						
						
						//$this->config[$type][$tablename][trim($newentry)] = array_merge($this->config[$type][$tablename][trim($entryParent)],$config[$entry]);
					} else {
						$this->config[$type][$tablename][$entry] =& $config[$entry];
					}
					
				}
			}
			
			unset($config);
		}
		if ( $type == 'lang' ){
			return $this->config[$type][$app->_conf['lang']][$tablename];
		} else {
			return $this->config[$type][$tablename];
		}
		
	}
	
	function apc_save(){
		if ( function_exists('apc_store') and defined('DATAFACE_USE_CACHE') and DATAFACE_USE_CACHE ){
			$res = apc_store($this->apc_hash().'$config', $this->config);
			$res2 = apc_store($this->apc_hash().'$iniLoaded', $this->iniLoaded);
			
		}
	}
	
	function apc_load(){
		if ( function_exists('apc_fetch') and defined('DATAFACE_USE_CACHE') and DATAFACE_USE_CACHE ){
			$this->config = apc_fetch($this->apc_hash().'$config');
			$this->iniLoaded = apc_fetch($this->apc_hash().'$iniLoaded');
		}
	}
	
	function apc_hash(){
		$appname = basename(DATAFACE_SITE_PATH);
		return __FILE__.'-'.$appname;
	}
	
	
	
	/**
	 * Scours the tables directory to load all configuration information from the ini files.
	 */
	function loadAllConfigFromINI(){
		$tables_path = DATAFACE_SITE_PATH.'/tables';
		$dir = dir($tables_path);
		while ( false !== ( $entry = $dir->read() ) ){
			if ( $entry === '.' || $entry === '..' ) continue;
			$full_path = $tables_path.'/'.$entry;
			if ( is_dir($full_path) ){
				foreach ( $this->configTypes as $type ){
					$this->loadConfigFromINI($type, $entry);
				}
			}
		}
		foreach ($this->configTypes as $type){
			// load global properties.
			$this->loadConfigFromINI($type, null);
		}
		
	}
	
	function loadAllConfig(){
		$app =& Dataface_Application::getInstance();
		switch( strtolower($app->_conf['config_storage']) ){
			case 'db':
			case 'sql':
			case 'database':
				$this->loadConfigFromDB();
				break;
			case 'ini':
				$this->loadAllConfigFromINI();
				break;
				
		}
	
	}
	
	
	
	function parse_ini_file($path, $sections=false){
		static $config = 0;
		if ( !is_array($config) ){
			$config = array();
		}
		
		

		$app =& Dataface_Application::getInstance();
		//echo "Checking for $path";
		if ( strstr($path, 'db:') == $path ){
			$path = substr($path, 3);
			if ( !is_array($config) ){
				$config = array();
				if ( class_exists('Dataface_AuthenticationTool') ){
					$auth =& Dataface_AuthenticationTool::getInstance();
					$username = $auth->getLoggedInUsername();
				} else {
					$username = null;
				}
				
				
				$sql = $this->buildConfigQuery($path, $username, $app->_conf['lang']);
				$res = @mysql_query($sql, $app->db());
				if (!$res ){
					$this->createConfigTable();
					$res = mysql_query($sql, $app->db());
				}
				if ( !$res ){
					return $config;
				}
				while ( $row = mysql_fetch_assoc($res) ){
					if ( !$row['section'] ){
						$config[$row['file']][$row['key']] = $row['value'];
					} else {
						$config[$row['file']][$row['section']][$row['key']] = $row['value'];
					}
				}
				@mysql_free_result($res);
				
			
			}

			if ( !@$config[$path] ){

				return array();
			}
			
			return $config[$path];
			
		} else {
			if ( @$_GET['--refresh-apc'] or !(DATAFACE_EXTENSION_LOADED_APC && (filemtime($path) < apc_fetch($this->apc_hash().$path.'__mtime')) && ( $config[$path]=apc_fetch($this->apc_hash().$path) ) ) ){
				
				
				//$config[$path] =  parse_ini_file($path, $sections);
				$config[$path] = INIParser::parse_ini_file($path, $sections);
				if ( DATAFACE_EXTENSION_LOADED_APC ){
					apc_store($this->apc_hash().$path, $config[$path]);
					apc_store($this->apc_hash().$path.'__mtime', time());
				}
			} else {
				//
			}
			
			
			return $config[$path];
			
		}
			
	}
	
	function buildConfigQuery($path, $username, $lang, $where=null){
		$sql = "select * from `".$this->configTableName."` where (`lang` IS NULL OR `lang` = '".$lang."') and ( `username` IS NULL";
		if ( isset($username) ){
			$sql .= " OR `username`	= '".addslashes($username)."')";
		} else {
			$sql .= ')';
		}
		if ( isset($where) ) $sql .= ' and ('.$where.')';
				
				
		$sql .= ' ORDER BY `priority`';
		return $sql;
	}
	
	
	function createConfigTable(){
		import('Dataface/ConfigTool/createConfigTable.function.php');
		return Dataface_ConfigTool_createConfigTable();
	}
	
	function setConfigParam($file, $section, $key, $value, $username=null, $lang=null, $priority=5){
		import('Dataface/ConfigTool/setConfigParam.function.php');
		return Dataface_ConfigTool_setConfigParam($file, $section, $key, $value, $username, $lang, $priority);
	}
	
	function clearConfigParam($file, $section, $key, $value, $username=null, $lang=null){
		import('Dataface/ConfigTool/clearConfigParam.function.php');
		return Dataface_ConfigTool_setConfigParam($file, $section, $key, $value, $username, $lang);
	}

}



class INIParser {
	private $keys = array();
	private function replace_key($matches){
		$this->keys[] = trim($matches[1]);
		return 'keys'.(count($this->keys)-1).'=';
	}
	
	private function replace_section($matches){
		$this->keys[] = trim($matches[1]);
		return '[keys'.(count($this->keys)-1).']';
	}
	
	private function return_key($matches){
		$index = intval($matches[1]);
		if ( isset($this->keys[$index]) ){
			return $this->keys[$index].'=';
		} else {
			return $matches[0];
		}
	}
	
	
	private function refill_array($ini){
		foreach ( $ini as $key=>$val){
			if ( is_array($val) ){
				
				$val = $this->refill_array($val);
				
			} else {
				$val = preg_replace_callback('/^keys(\d+)=/m', array($this, 'return_key'), $val);
			}
			$index = intval(substr($key, 4));
			if ( isset($this->keys[$index]) ){
				unset($ini[$key]);
				$ini[$this->keys[$index]] = $val;
			} else {
				$ini[$key] = $val;
			}
		}
		return $ini;
	}
	
	
	private function parse($file, $sections=false){
		$this->keys = array();
		$contents = file_get_contents($file);
		$contents = preg_replace_callback('/^ *\[([^\]]+)\]/m', array($this, 'replace_section'), $contents);
		
		$contents = preg_replace_callback('/^([^\[=;"]+)(=)/m', array($this, 'replace_key'), $contents);
		//echo $contents;
		$ini = parse_ini_string($contents, $sections);
		return $this->refill_array($ini);
	}
	public static function parse_ini_file($file, $sections=false){
		if ( version_compare(PHP_VERSION, '5.3.0') >= 0 and version_compare(PHP_VERSION, '5.3.1') <= 0 ){
			$p = new INIParser();
			return $p->parse($file, $sections);
		} else {
			return parse_ini_file($file, $sections);
		}
	}

}
