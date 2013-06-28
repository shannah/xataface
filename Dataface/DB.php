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
 * File: Dataface/DB.php
 * Author: Steve Hannah <shannah@sfu.ca>
 * Created: November 15, 2005
 * Description:
 * -------------
 * Performs database queries on the database.  This is better than using direct
 * mysql_query calls because it analyzes queries first to make sure that Blobs are
 * not loaded unnecessarily.  [To do] 
 */
import( 'Dataface/Application.php'); 
import('Dataface/Table.php');

class Dataface_DB {
	var $_db;
	var $_fieldIndex = array();
	var $_queryCache = array();
	var $_parser = null;
	var $_compiler = null;
	var $_cache = null;
	var $_cacheDirtyFlag = false;
	var $app;
	var $_matchCount;
	var $_matches;
	var $_insert_id;
	var $count=0;
	var $_fcache_base_path= null;
	var $db_hits = 0;
	var $cache_hits = 0;
	var $cache_fails = 0;
	
	var $blobs = array();  // Blobs.
	
	function Dataface_DB($db=null){
		if ( $db === null ){
			$db = DATAFACE_DB_HANDLE;
		}
		$this->_db = $db;
		$this->app =& Dataface_Application::getInstance();
		
		if ( @$this->app->_conf['cache_queries'] and !$this->_fcache_base_path ){
			if ( is_writable(DATAFACE_SITE_PATH.'/templates_c') ){
				$this->_fcache_base_path = DATAFACE_SITE_PATH.'/templates_c/query_results';
			} else {
				$this->_fcache_base_path = DATAFACE_PATH.'/Dataface/templates_c/query_results';
			}

			if ( !file_exists($this->_fcache_base_path) ){
							
				mkdir($this->_fcache_base_path, 0700);
				file_put_contents($this->_fcache_base_path.'/.htaccess', 'deny from all');
			}
		}
		
		
		
		
		
	}
	
	/**
	 * Loads cached queries from the file Dataface_DB.cache in the DATAFACE_CACHE
	 * directory - usually Dataface/templates_c/__cache .
	 * This file will be a php file with an array by the name of "cache" defined.
	 */
	function _loadCache(){
		if ( !isset($this->_cache) ){
			$filepath = DATAFACE_CACHE_PATH.'/Dataface_DB.cache';
			//echo "Checking cache... $filepath";
			
			if ( is_readable($filepath) and filemtime($filepath) > time()-1 ){
				//echo "Cache is readable";
				include DATAFACE_CACHE_PATH.'/Dataface_DB.cache';

			}
			if ( isset( $cache ) ){
				$this->_cache =& $cache;
			} else {
				$this->_cache = array();
			}
			register_shutdown_function(array(&$this, 'writeCache'));
			
		}
	}
	
	/**
	 * Adds a value to the cache.
	 */
	function cache($key, $value, $lang=null){
		if ( !isset($lang) ) $lang = $this->_app->_conf['lang'];
			// if the language isn't set, we use the default language from the database
		$this->_loadCache();
		$this->_cache[$lang][$key] = $value;
		$this->_cacheDirtyFlag = true;
	}
	
	
	
	/**
	 * Obtains reference to this class's SQL Parser.
	 */
	function &_getParser(){
		
		if ( !isset($this->_parser)){
			import('SQL/Parser.php');
			$this->_parser = new SQL_Parser(null, 'MySQL');
		}
		return $this->_parser;
	}
	
	/**
	 * Obtains a reference to this object's SQL compiler.
	 */
	function &_getCompiler(){
		if ( !isset($this->_compiler) ){
			import('SQL/Compiler.php');
			$this->_compiler = SQL_Compiler::newInstance('mysql');
		}
		return $this->_compiler;
	}
	
	
	/**
	 * Takes a select SQL query and separates the query portion from the data
	 * in the query.  This will return an array where the first elemement
	 * is the generified SQL query sans any of the specified column values in the where clause
	 * and the remaining elements are the values that have been removed. 
	 *
	 * For example: Input = SELECT * FROM Players where FirstName = 'Bruce'
	 *              Output = array( 'SELECT * FROM Players where FirstName = '{!@#$%S1%$#@!}', 'Bruce' );
	 */
	function prepareQuery($query){
		//echo "Preparing query $query";
		$len = strlen($query);
		$escaped = false;
		$dblquoted = false;
		$sglquoted = false;
		$output_query = '';
		$buffer = '';
		$output_args = array();
		$count = 0;
		for ($i=0;$i<$len;$i++){
			$skip = false;
			switch ($query{$i}){
				case '\\': 	$escaped = !$escaped;	
							break;
				case '"' :	if ( !$escaped && !$sglquoted ){
								$dblquoted = !$dblquoted;
								if (!$dblquoted ){
									// double quotes are done, we can update the buffer.
									$count++;	// increment the counter for number of found strings
									$output_args[] = $buffer;
									$buffer = '';
									$output_query .= '"_'.$count.'_"';
								} 
								$skip = true;
							} 
							
							break;
				case '\'' : if (!$escaped && !$dblquoted) {
								$sglquoted = !$sglquoted;
								if ( !$sglquoted ){
									// double quotes are done, we can update the buffer.
									$count++;	// increment the counter for number of found strings
									$output_args[] = $buffer;
									$buffer = '';
									$output_query .= '\'_'.$count.'_\'';
								} 
								$skip = true;
								
							} 
							break;
				
				
			}
			
			if ( $query{$i} != '\\' ) $escaped = false;
			if ( $skip ) continue;
			if (  $dblquoted || $sglquoted) {
				$buffer .= $query{$i};
			}
			else $output_query .= $query{$i};
		}
		
		// Now to replace all numbers
		$this->_matchCount = 0;
		$this->_matches = array();
		$output_query = preg_replace_callback('/\b(-{0,1})([0-9]*\.{0,1}[0-9]+)\b/', array(&$this, '_replacePrepareDigits'), $output_query);
		$output_args = array($output_query, $output_args, $this->_matches);
		
		//print_r($output_args);
		//print_r($output_args);
		return $output_args;
			
	}
	
	function _replacePrepareDigits($matches){
		$this->_matches[] = $matches[1].$matches[2];
		return ++$this->_matchCount;
	}
	
	function _replaceCompileStrings($matches){
		return $matches[1].$this->_matches[intval($matches[2])-1].$matches[3];
	}
	
	function _replaceCompileDigits($matches){
		return $this->_matches[intval($matches[1])-1];
	}
	
	function _replaceBlobs($matches){
		$blob = $this->checkoutBlob($matches[1]);
		//if ( !is_uploaded_file($blob) ) throw new Exception(df_translate('scripts.Dataface.DB._replaceBlobs.BLOB_NOT_UPLOADED',"Attempt to load blob that is not uploaded. "), E_USER_ERROR);
		if ( PEAR::isError($blob) ) throw new Exception($blob->toString(), E_USER_ERROR);
		
		return mysql_real_escape_string(file_get_contents($blob));
	}
	

	
	function compilePreparedQuery($prepared_query){
		$numArgs = count($prepared_query[1]);
		$buffer = $prepared_query[0];
		$this->_matches = $prepared_query[2];
		$buffer = preg_replace_callback('/\b([0-9]+)\b/', array(&$this, '_replaceCompileDigits'), $buffer);
		
		$this->_matches = $prepared_query[1];
		$buffer = preg_replace_callback('/([\'"])_(\d+)_([\'"])/', array(&$this, '_replaceCompileStrings'), $buffer);
		
		$buffer = preg_replace_callback('/-=-=B(\d+)=-=-/', array(&$this, '_replaceBlobs'), $buffer);
		
		return $buffer;
	}
	
	
	
	/**
	 * Translates a select SQL query into a multilanguage query.  It will compute the
	 * appropriate joins to the translation tables and swap the original column for
	 * its translated column in the join table.
	 * @param $query The query.
	 * @param $lang The 2 digit language code for the translation we wish to obtain.
	 *
	 */
	function translate_query($query, $lang=null){
		//echo "Dirty flag: ".$this->_cacheDirtyFlag;
		if ( $lang === null ){
			// If no language is provided use the language in the conf.ini file
			$lang = $this->app->_conf['lang'];
		}
		$this->_loadCache();
		
		if ( strtolower(substr($query, 0, strlen('DELETE '))) == 'delete ' ){
			return $query;
		}
		
		$original_query = $query;
		$prepared_query = $this->prepareQuery($query);
		if ( isset( $this->_cache[$lang][$prepared_query[0]] )){
			// we have already translated this select query and cached it!
			// just load the query from the cache and fill in the appropriate
			// values:
			$prepared_query[0] = $this->_cache[$lang][$prepared_query[0]];
			return $this->compilePreparedQuery($prepared_query);
		}
		
		$query = $prepared_query[0];
		import('Dataface/QueryTranslator.php');
		$translator = new Dataface_QueryTranslator($lang);
		$output = $translator->translateQuery($prepared_query[0]);
		if (PEAR::isError($output) ){
			//echo $output->toString();
			throw new Exception(df_translate('scripts.Dataface.DB.translate_query.FAILED_TO_TRANSLATE', "Failed to translate query: $query.: ",array('query'=>$query)).$output->toString(), E_USER_ERROR);
		}
		
		$this->cache($prepared_query[0], $output, $lang);
		$prepared_query[0] = $output;
		return $this->compilePreparedQuery($prepared_query);				
	}
	
	/**
	 * Queries the database with the given sql query.
	 * This currently passes the query straight through to
	 * mysql_query, but it will be modified in the future to
	 * automatically filter out blobs (because normally we don't want to 
	 * retrieve blob columns.
	 */
	function query($sql, $db=null, $lang=null, $as_array=false, $enumerated=false){
		
		$app =& Dataface_Application::getInstance();
		
		$refreshModTimes=false;
		if ( $as_array and ($isSelect = (strpos(strtolower(trim($sql)), 'select ') === 0)) ){
			if  ( ($results = $this->memcache_get($sql, $lang)) or is_array($results) ) {
				if ( @$this->app->_conf['cache_queries_log']){
					$fp = fopen('/tmp/querylog.log', 'a');
					fwrite($fp, "\n[".date('Y-m-d H:i:s')."] Cached: ".$sql);
					fclose($fp);
				}
				$this->cache_hits++;
				return $results;
			} else {
				if ( @$this->app->_conf['cache_queries_log']){
					$fp = fopen('/tmp/querylog.log', 'a');
					fwrite($fp, "\n[".date('Y-m-d H:i:s')."] Failed cached: ".$sql);
					fclose($fp);
				}
				$this->cache_fails++;
				$orig_sql = $sql; // save the original sql before it is translated
			}

		} else if ( @$app->_conf['cache_queries'] ){
			$refreshModTimes = true;
		}
		
		
		//$fp = fopen('/tmp/querylog.log', 'a');
		//fwrite($fp, "\n[".date('Y-m-d H:i:s')."] Uncached: ".$sql);
		//fclose($fp);
		$this->count++;
		
		if ( ( /*isset($lang) ||*/ $this->app->_conf['multilingual_content'])) {
			if ( @$app->_conf['debug_sql'] ){
				error_log("Before translation: ".$sql);
			}
			$sql = $this->translate_query($sql,$lang );
			if ( PEAR::isError($sql) ) return $sql;
			if ( @$app->_conf['debug_sql'] ){
				if ( is_array($sql) ){
					foreach ($sql as $sqli){
						error_log("After translation: ".$sqli);
					}
				} else {
					error_log("After translation: ".$sql);
				}
				
			}
			

		}
		if ( !isset($db) ){
			$db = $this->app->db();
		}
		$update_insert_id = true;
		if ( is_array($sql) ){
			$loopctr = 0;
			
			foreach ($sql as $q){
				if ( $loopctr++ > 0 and mysql_insert_id($db) ){
					$this->_insert_id = mysql_insert_id($db);
					$update_insert_id = false;
					$q = str_replace("'%%%%%__MYSQL_INSERT_ID__%%%%%'", mysql_insert_id($db), $q );
				}
				if ( defined('DATAFACE_DEBUG_DB') or @$app->_conf['debug_sql']) echo "Performing query: '$q' <br>";
				$res = mysql_query($q, $db);
				
			}
		} else {
			if ( defined('DATAFACE_DEBUG_DB') or @$app->_conf['debug_sql'] ) echo "Performing query: '$sql' <br>";
			$this->db_hits++;
			$res = mysql_query($sql, $db);
			
		}
		if ( $update_insert_id ) $this->_insert_id = mysql_insert_id($db);
		if ( $res and $refreshModTimes) Dataface_Table::getTableModificationTimes(true);
		if ( $as_array and $isSelect ){
			if ( !$res  ) {
				
				return $res;
			}
			// We want to return this as an array rather than a resource
			$out = array();
			while ( $row = ($enumerated ? mysql_fetch_row($res) : mysql_fetch_assoc($res)) ){
				$out[] = $row;
			}
			
			$this->memcache_set($orig_sql, $lang, $out);
			@mysql_free_result($res);
			
			return $out;
		
		}

		return $res;
	}
	
	function insert_id(){
		return $this->_insert_id;
	}
	
	
	public static function &getInstance(){
		static $instance = null;
		if ( $instance === null ){
			//echo "In get instance";
			$instance = new Dataface_DB();
		}
		
		return $instance;
	}
	
	
	/**
	 * Writes the cache of SQL queries to a PHP file. This method is registered in
	 * DB constructor to automatically run on shutdown.
	 */
	function writeCache(){
		//echo "in write cache...";
		//print_r($this);
		if ( $this->_cacheDirtyFlag ){
			//echo "Dirty flag";
			// The cache has been updated so we have to write it.
			ob_start();
			echo '<?php
			$cache = array();
			';
			foreach ($this->_cache as $lang=>$values){
				foreach ($values as $key=>$value){
					if ( is_array($value) ){
						foreach ($value as $innerValue){
							echo '$cache[\''.$lang.'\'][\''.str_replace("'", "\\'", $key).'\'][] = \''.str_replace("'", "\\'", $innerValue).'\';
							';
						}
					} else {
						echo '$cache[\''.$lang.'\'][\''.str_replace("'", "\\'", $key).'\'] = \''.str_replace("'", "\\'", $value).'\';
						';
					}
				}
			}

			echo '
			?>';
			$contents = ob_get_contents();
			ob_end_clean();
			if ( !file_exists(DATAFACE_CACHE_PATH) ) @mkdir(DATAFACE_CACHE_PATH);
			$fh = @fopen(DATAFACE_CACHE_PATH.'/Dataface_DB.cache', 'w');
			if ( !$fh or !fwrite($fh, $contents) ){
				error_log("Failed to write DB cache", E_USER_ERROR);
			}
			@fclose($fh);
		}
	}
	
	
	function registerBlob($blobData){
		static $id=1;
		$this->blobs[$id++] = $blobData;
		return $id-1;
		
		
	}
	
	function checkoutBlob($blobID){
		if ( !isset( $this->blobs[$blobID]) ) return PEAR::raiseError(df_translate('scripts.Dataface.DB.checkoutBlob.BLOB_DOESNT_EXIST', "Blob with ID $blobID doesn't exist. ",array('blobID'=>$blobID)), DATAFACE_E_ERROR);
		
		$blob = $this->blobs[$blobID];
		unset($this->blobs[$blobID]);
		return $blob;
	}
	
	function startTransaction(){ return mysql_query('begin', df_db() ); }
	function commitTransaction(){ return mysql_query('commit', df_db() ); }
	function rollbackTransaction(){ return mysql_query('rollback', df_db() ); }
	
	function memcache_get($sql, $lang=null){
	
		$app =& Dataface_Application::getInstance();
		
		$memcache =& $app->memcache;

		if ( !@$app->_conf['cache_queries'] ) {
			
			return null;
		}
		
		$key = $this->memcache_get_key($sql, $lang);
		
		
		
		$tables = $this->getTableDependencies($sql, $lang);
		if ( PEAR::isError($tables) ) return null;
			// This is a list of the tables that would cause the cache to be invalidated.
		
		$modification_times = Dataface_Table::getTableModificationTimes();
		$mtime = 0;
		foreach ( $tables as $table){
			if ( isset($modification_times[$table]) ) $mtime = max($mtime, $modification_times[$table]);
			else {
				$t =& Dataface_Table::loadTable($table);
				if ( @$t->_atts['__source_tables__'] ){
					$ts = explode(',', $t->_atts['__source_tables__']);
					foreach ($ts as $tst){
						if ( isset($modification_times[trim($tst)]) ){
							$mtime = max($mtime, $modification_times[trim($tst)]);
						} else {
							$mtime = time();
							break;
						}
					}
				} else {
					//echo "$table no modified date";
					$mtime = time();
					break;
				}
				unset($t);
			}
		}
		
		
		
		// Now we will get the cached value if it is newer than $mtime
		$cache_mtime = 0;
		if ( $memcache ) $cache_mtime = $this->memcache_mtime($key);
		else $cache_mtime = $this->fcache_mtime($key);
		//echo "Cache time for ".$this->fcache_path($key)." is $cache_mtime";
		//echo "[$sql : $cache_mtime : $mtime]";
		if ( $cache_mtime > $mtime ){
			if ( $memcache ) {

				if ( $result = $memcache->get($key) ) {
					return unserialize($result);
				}
			}
			else if (($result = $this->fcache_get($key)) !== null ){

				return unserialize($result);
			}
		}
		
		
		
		return null;
	}
	
	/**
	 * Returns the modification time of the memcache entry for a particular key.
	 */
	function memcache_mtime($key, $set=false){
		
		$key .= '&-action=mtime';
		$key = md5($key);
		if ( DATAFACE_EXTENSION_LOADED_APC and !$set ){
			return apc_fetch($key);
		} else if ( DATAFACE_EXTENSION_LOADED_APC and $set ){
			apc_store($key, time());
		} else if ( $set ){
			
			$_SESSION[$key] = time();
		} else if ( !$set and isset($_SESSION[$key])){
			
			return $_SESSION[$key];
		}
		return 0;
		
		
	}
	
	function fcache_mtime($key){
		if ( file_exists($this->fcache_path($key)) ){
			//echo "Checking mtime for $key : ".$this->fcache_path($key);
			return filemtime($this->fcache_path($key));
		} else {
			//echo "File does not exist : ".$this->fcache_path($key);
			return 0;
		}
	}
	
	function fcache_get($key){
		
		if ( file_exists($this->fcache_path($key)) ){
			return file_get_contents($this->fcache_path($key));
		}
		return null;
	}
	
	function fcache_set($key, $value){
		
		file_put_contents($this->fcache_path($key), serialize($value));
	}
	
	function fcache_path($key){
		return $this->_fcache_base_path.'/'.md5($key);
	}
	
	function memcache_get_key($sql, $lang){
		
		$app =& Dataface_Application::getInstance();
		
		$auth =& Dataface_AuthenticationTool::getInstance();
		
		
		$dbname = $app->_conf['_database']['name'];
		
		if ( !isset($lang) ) $lang = $app->_conf['lang'];
		
		$key = urlencode($dbname).'?-query='.urlencode($sql).'&-lang='.urlencode($lang);
		
		return md5($key);
	}
	
	function memcache_set($sql, $lang, $value){

		$app =& Dataface_Application::getInstance();
		$memcache =& $app->memcache;
		if ( !$memcache and !@$app->_conf['cache_queries'] ) return null;
		
		$key = $this->memcache_get_key($sql, $lang);
		if ( $memcache ){
			$memcache->set($key, serialize($value), false, 0);
			$this->memcache_mtime($key, true);
		} else if ( @$app->_conf['cache_queries'] ){
			//echo "Setting $sql $key ".$this->fcache_path($key);
			$this->fcache_set($key, $value);
		}
		
		
	}
	
	function getTableDependencies($sql, $lang=null){
		$app =& Dataface_Application::getInstance();
		$key = $this->memcache_get_key($sql, $lang);
		$key .= '&-action=deps';
		$key = md5($key);
		if ( DATAFACE_EXTENSION_LOADED_APC && !isset($_GET['--clear-cache']) ){
			$deps = apc_fetch($key);
			if ( is_array($deps) ) return $deps;
		} else if ( isset($_SESSION[$key]) && !isset($_GET['--clear-cache']) ){
			$deps = $_SESSION[$key];
			if ( is_array($deps) ) return $deps;
		}
		// We actually need to calculate the dependencies, so we will
		// parse the SQL query.
		import('SQL/Parser.php');
		$parser = new SQL_Parser( null, 'MySQL');
		$data =& $parser->parse($sql);
		if ( PEAR::isError($data) ){
			return $data;
		}
		$tables = array_unique($data['all_tables']);
		
		if ( @$app->_conf['cache_queries_log'] ){
			$fp = fopen('/tmp/querylog.log', 'a');
			fwrite($fp, "\n[".date('Y-m-d H:i:s')."] Dependencies: ".implode(',', $tables)." ".$sql);
			fclose($fp);
		}
		//import('SQL/Parser/wrapper.php');
		
		
		//$wrapper = new SQL_Parser_wrapper($data);
		//$tables = $wrapper->getTableNames();
		
		foreach ($tables as $tid=>$table){
			if ( preg_match('/^dataface__view_(.*)_[a-z0-9]{32}$/', $table, $matches) ){
				$tables[$tid] = $table = $matches[1];
			}
			$tobj =& Dataface_Table::loadTable($table,null,true);
			if ( is_a($tobj, 'Dataface_Table') and isset($tobj->_atts['__dependencies__']) ){
				$deps = array_map('trim', explode(',', $tobj->_atts['__dependencies__']));
				$tables = array_merge($tables, $deps);
				
			}
		}	
		
		if ( isset($app->_conf['__dependencies__']) ){
			$deps = array_map('trim',explode(',', $app->_conf['__dependencies']));
			$tables = array_merge($tables, $deps);
		}
		
		$deps = array_unique($tables);
		
		if ( DATAFACE_EXTENSION_LOADED_APC ){
			apc_store($key, $deps);
		} else {
			$_SESSION[$key] = $deps;
		}
		
		
		return $deps;
	}
	

}
