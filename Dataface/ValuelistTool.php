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
class Dataface_ValuelistTool {

	
	var $_valuelists = array();
	private $_valuelistsConfig = null;
	
	function Dataface_ValuelistTool(){
	
		$this->_loadValuelistsIniFile();
			
	
	}
	
	function _valuelistsIniFilePath(){
		return DATAFACE_SITE_PATH.'/valuelists.ini';
	}
	
	function _hasValuelistsIniFile(){
		return file_exists($this->_valuelistsIniFilePath());
	}
	
	
	/**
	 * @brief A wrapper around ConfigTool::loadConfig().  Simply loads the 
	 * valuelists.ini file configuration for this table.  Doesn't try to 
	 * resolve the valuelists.
	 *
	 * @see _loadValuelist for code that resolves valuelists.
	 * @returns array Associative array of valuelist configuration.  Keys are the valuelist
	 *	names and the values are associative array of config options.
	 */
	private function &_loadValuelistsIniFile(){
		if ( !isset($this->_valuelistsConfig) ){
			import( 'Dataface/ConfigTool.php');
			$configTool =& Dataface_ConfigTool::getInstance();
			$this->_valuelistsConfig =& $configTool->loadConfig('valuelists');
		}
		return $this->_valuelistsConfig;
	}
	
	/**
	 * @brief Loads and resolves a valuelist from this table's valuelists.ini file.
	 * This does not try to load other valuelists that may be defined elsewhere
	 * in the application or in a delegate class.  Just the ones defined in this
	 * table's valuelists.ini file.
	 *
	 * @see _loadValuelistsIniFile() For the loading of the configuration.
	 * @see getValuelist() for a more generalized method to obtain any valuelist
	 * defined anywhere in the application.
	 *
	 * @param string $name The name of the valuelist to load.
	 * @returns void
	 */
	private function _loadValuelist($name){
		if ( !isset($this->_valuelists) ){
			$this->_valuelists = array();
		}
		
		if ( !isset($this->_valuelists[$name]) ){
			$conf =& $this->_loadValuelistsIniFile();
			if ( isset($conf[$name]) ){
				$vllist = $conf[$name];
				$vlname = $name;
				$valuelists =& $this->_valuelists;
				$valuelists[$vlname] = array();
				foreach ( $vllist as $key=>$value ){
					if ( $key == '__sql__' ) {
						// we perform the sql query specified to produce our valuelist.
						// the sql query should return two columns only.  If more are 
						// returned, only the first two will be used.   If one is returned
						// it will be used as both the key and value.
						$res = df_query($value, null, true, true);
						if ( is_array($res) ){
							//while ($row = mysql_fetch_row($res) ){
							foreach ($res as $row){
								$valuekey = $row[0];
								$valuevalue = count($row)>1 ? $row[1] : $row[0];
								$valuelists[$vlname][$valuekey] = $valuevalue;
								
								if ( count($row)>2 ){
									$valuelists[$vlname.'__meta'][$valuekey] = $row[2];
								}
							}
							//mysql_free_result($res);
						} else {
							throw new Exception("Valuelist query '".$value."' failed. ", E_USER_NOTICE);
						}
					
					} else {
						$valuelists[$vlname][$key] = $value;
					}
				}
			}
		}
		
	}
	
	
	function _loadValuelistsIniFile_old(){
		if ( !isset( $this->_valuelists ) ){
			$this->_valuelists = array();
		}
		$valuelists =& $this->_valuelists;
		
		if ( $this->_hasValuelistsIniFile() ){
			
			
			$conf = parse_ini_file( $this->_valuelistsIniFilePath(), true);
			
			foreach ( $conf as $vlname=>$vllist ){
				$valuelists[$vlname] = array();
				if ( is_array( $vllist ) ){
					foreach ( $vllist as $key=>$value ){
						
						if ( $key == '__sql__' ) {
							// we perform the sql query specified to produce our valuelist.
							// the sql query should return two columns only.  If more are 
							// returned, only the first two will be used.   If one is returned
							// it will be used as both the key and value.
							$res = df_query($value, null, true, true);
							if ( is_array($res) ){
								//while ($row = mysql_fetch_row($res) ){
								foreach ($res as $row){
									$valuekey = $row[0];
									$valuevalue = count($row)>1 ? $row[1] : $row[0];
									$valuelists[$vlname][$valuekey] = $valuevalue;
									
									if ( count($row)>2 ){
										$valuelists[$vlname.'__meta'][$valuekey] = $row[2];
									}
								}
							} else {
								throw new Exception('Valuelist sql query failed: '.$value.': '.mysql_error(), E_USER_NOTICE);
							}
						
						} else {
							$valuelists[$vlname][$key] = $value;
						}
					}
				}
				
				
			}
			
		} 
	}
	
	
	public static function &getInstance(){
		static $instance = 0;
		if ( $instance === 0 ){
			$instance = new Dataface_ValuelistTool();
		}
		return $instance;
	}
	
	function &getValuelist($name){
		if ( !is_a($this, 'Dataface_ValuelistTool') ){
			$vlt =& Dataface_ValuelistTool::getInstance();
		} else {
			$vlt =& $this;
		}
		$vlt->_loadValuelist($name);
		if ( isset($vlt->_valuelists[$name] ) ){
			
			return $vlt->_valuelists[$name];
		}
		
		throw new Exception("Request for valuelist '$name' that does not exist in Dataface_ValuelistTool::getValuelist().", E_USER_ERROR);
	}
	
	
	function hasValuelist($name){
		if ( !is_a($this, 'Dataface_ValuelistTool') ){
			$vlt =& Dataface_ValuelistTool::getInstance();
		} else {
			$vlt =& $this;
		}
		return isset( $vlt->_valuelistsConfig[$name]);
	}
	
	/**
	 * Obtains reference to valuelists associative array.  Note that this
	 * may not include all valuelists defined.  Only those valuelists that have already been loaded.
	 *
	 */
	function &valuelists(){
		if ( !is_a($this, 'Dataface_ValuelistTool') ){
			$vlt =& Dataface_ValuelistTool::getInstance();
		} else {
			$vlt =& $this;
		}
		$out =& $vlt->_valuelists;
		
	}
	
	/**
	 * Adds a value to a valuelist.  This only works for valuelists
	 * that are pulled from the database.
	 * @param Dataface_Table The table to add the valuelist to.
	 * @param string $valuelistName The name of the valuelist.
	 * @param string $value The value to add.
	 * @param string $key The key to add.
	 * @param boolean $checkPerms If true, this will first check permissions
	 *		  before adding the value.
	 * @returns mixed May return a permission denied error if there is insufficient
	 *			permissions.
	 */
	function addValueToValuelist(&$table, $valuelistName,  $value, $key=null, $checkPerms=false){

		import( 'Dataface/ConfigTool.php');
		$configTool =& Dataface_ConfigTool::getInstance();
		$conf = $configTool->loadConfig('valuelists', $table->tablename);
		
		$relname = $valuelistName.'__valuelist';
		//$conf = array($relname=>$conf);
		$table->addRelationship( $relname, $conf[$valuelistName]);
		$rel =& $table->getRelationship($relname);
		$fields =& $rel->fields();
		if ( count($fields) > 1 ) {
			$valfield = $fields[1];
			$keyfield = $fields[0];
		}
		else {
			$valfield = $fields[0];
			$keyfield = $fields[0];
		}
		
		$record = new Dataface_Record($table->tablename);
		$rrecord = new Dataface_RelatedRecord($record, $relname);
		if ( $checkPerms and !$rrecord->checkPermission('edit', array('field'=>$valfield)) ){
			return Dataface_Error::permissionDenied();
		}
		$rrecord->setValue($valfield, $value);
		if (isset($key) and isset($keyfield) ){
			if ( $checkPerms and !$rrecord->checkPermission('edit', array('field'=>$keyfield)) ){
				return Dataface_Error::permissionDenied();
			}
			$rrecord->setValue($keyfield, $key);
		}
		import('Dataface/IO.php');
		$io = new Dataface_IO($table->tablename);
		$res = $io->addRelatedRecord($rrecord);
		if ( PEAR::isError($res) ) return $res;
		return array('key'=>$rrecord->val($keyfield), 'value'=>$rrecord->val($valfield));
	
	}
	
	/**
	 * @brief Returns the valuelist as a relationship.  This is handy for
	 * adding values to it and searching it.
	 *
	 * @param Dataface_Table &$table The table where the valuelist is defined.
	 * @param string $valuelistName The name of the valuelist.
	 * @return Dataface_Relationship A wrapper relationship for the valuelist.
	 * @return PEAR_Error If there is a problem generating the relationship.
	 */
	function &asRelationship(&$table, $valuelistName){
		import( 'Dataface/ConfigTool.php');
		$configTool =& Dataface_ConfigTool::getInstance();
		$conf = $configTool->loadConfig('valuelists', $table->tablename);
		if ( !@$conf[$valuelistName]['__sql__'] ){
			$out = null;
			return $out;
		}
		
		$relname = $valuelistName.'__valuelist';
		//$conf = array($relname=>$conf);
		$table->addRelationship( $relname, $conf[$valuelistName]);
		$rel =& $table->getRelationship($relname);
		$rel->_schema['action']['visible']=0;
		return $rel;
	
	}
	
	
	

}
