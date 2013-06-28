<?php
/**
 * A class to handle caching of output.  It takes into account whether database 
 * tables have been updated to decide whether a cached version is valid or not.
 */
class Dataface_OutputCache {

	var $useGzipCompression = true;
	var $tableName = '__output_cache';
	var $ignoredTables=array();
	var $observedTables=array();
	var $exemptActions=array();
	var $stripKeys=array('-l','-lang');
	var $app;
	var $threshold = 0.1;
	var $tableModificationTimes;
	var $lifeTime = 360000;
	var $randomize = 0;
	var $_cacheTableExists=null;
	var $lastModified=null;
	var $headers=array();
	var $userId = null;
	
	/**
	 * A list of the tables that have been used in the current request.
	 */
	var $usedTables=array();
	
	function Dataface_OutputCache($params=array()){
		if ( !extension_loaded('zlib') ){
			$this->useGzipCompression = false;
		} else if ( isset($params['useGzipCompression']) ){
			$this->useGzipCompression = $params['useGzipCompression'];
		}

		if ( isset($params['threshold']) ) $this->threshold = $params['threshold'];
		if ( isset($params['lifeTime']) ) $this->lifeTime = $params['lifeTime'];
		if ( isset($params['tableName']) ) $this->tableName = $params['tableName'];
		if ( isset($params['ignoredTables']) ) $this->ignoredTables = explode(',', $params['ignoredTables']);
		if ( isset($params['observedTables']) ) $this->observedTables = explode(',', $params['observedTables']);
		if ( isset($params['exemptActions']) ) $this->exemptActions = explode(',', $params['exemptActions']);
		if ( isset($params['stripKeys']) ) $this->stripKeys = explode(',', $params['stripKeys']);
		$this->app =& Dataface_Application::getInstance();
		
		if ( !$this->_cacheTableExists() ) $this->_createCacheTable();
	}
	
	function getUserId(){
		if ( !isset($this->userId) ){
			$del = $this->app->getDelegate();

			if ( $del and method_exists($del, 'getOutputCacheUserId') ){
				$this->userId = $del->getOutputCacheUserId();
			}
			if ( !isset($this->userId) ){
				
				if ( class_exists('Dataface_AuthenticationTool') ){
					$this->userId = Dataface_AuthenticationTool::getInstance()->getLoggedInUsername();
				}
				
			}
			if ( !isset($this->userId) ) $this->userId = '';
		}
		return $this->userId;
	
	}
	
	/**
	 * Builds the where clause for a select statement to get cached versions
	 * of pages from the database.
	 */
	function _buildPageSelect($params=array()){
		import('Dataface/AuthenticationTool.php');
		$query =& $this->app->getQuery();
		
		
		$PageID = $this->getPageID($params);
		$Language = ( isset($params['lang']) ? $params['lang'] : $this->app->_conf['lang']);
		$auth =& Dataface_AuthenticationTool::getInstance();
		//$UserID = ( isset($params['user']) ? $params['user'] : $auth->getLoggedInUsername());
		$UserID = $this->getUserId();
		$TimeStamp = ( isset($params['time']) ? $params['time'] : time()-$this->lifeTime);
		
		
		return "
			where `PageID` = '".addslashes($PageID)."'
			and `Language` = '".addslashes($Language)."'
			and `UserID` = '".addslashes($UserID)."'
			and `Expires` > NOW() 
			ORDER BY RAND()";
	}
	
	/**
	 * Obtains the cached version of a page.
	 * @param $params Associative array of parameters:
	 *		@param id The Page ID of the page we wish to obtain.  This is stored as
	 *					a VARCHAR(64) so it can be any string not longer than
	 *					64 characters.
	 *		@param lang The language of the page (e.g. en, fr, zh, etc...).
	 *					This will default to the currently logged in user
	 * 		@param user The user id of the user accessing the page.  This will
	 *					default to the currently logged in user.
	 *	@returns The cached content (possibly gzip compressed).  Or null if there
	 *			 was no valid cached version.
	 */		
	function getPage($params=array()){
		
		$app =& Dataface_Application::getInstance();
		$query =& $app->getQuery();
		if ( @$app->_conf['nocache'] or in_array($query['-action'], $this->exemptActions) ) return null;
		
		if ( $this->gzipSupported() and $this->useGzipCompression ){
			$DataColumn = 'Data_gz';
		} else {
			$DataColumn = 'Data';
		}
		
		$res = mysql_query("select `".addslashes($DataColumn)."`, UNIX_TIMESTAMP(`LastModified`) as `TimeStamp`, `Dependencies`, `Headers` from `".addslashes($this->tableName)."` 
			".$this->_buildPageSelect($params)." LIMIT 1", $this->app->db());
			
		if ( !$res ){
			 throw new Exception(mysql_error($this->app->db()), E_USER_ERROR);
		}
		
		if ( mysql_num_rows($res) == 0 ) return null;
		//echo "here";
		list($data, $lastModified, $dependencies, $headers) = mysql_fetch_row($res);
		$this->lastModified=$lastModified;
		$tables = explode(',',$dependencies);
		if ( $headers ) $this->headers = unserialize($headers);
		if ( count($tables) == 0 ) $tables = null;
		if ( $this->isModified($lastModified, $tables) ){
			return null;
		}
		if ( is_resource($res) ) mysql_free_result($res);
		return $data;	
	}
	
	/**
	 * Returns the number of versions of a page there are still current.
	 * @param $params Associative array of parameters:
	 *		@param id The Page ID of the page we wish to obtain.  This is stored as
	 *					a VARCHAR(64) so it can be any string not longer than
	 *					64 characters.
	 *		@param lang The language of the page (e.g. en, fr, zh, etc...).
	 *					This will default to the currently logged in user
	 * 		@param user The user id of the user accessing the page.  This will
	 *					default to the currently logged in user.
	 * @returns Integer number of current versions.
	 */
	function numCurrentVersions($params=array()){
		$res = mysql_query("
			SELECT COUNT(*) FROM `".addslashes($this->tableName)."` ".
			$this->_buildPageSelect($params), $this->app->db());
		if ( !$res ){
			throw new Exception(mysql_error($this->app->db()), E_USER_ERROR);
		}
		list($num) = mysql_fetch_row($res);
		mysql_free_result($res);
		return $num;
			
	}
	
	/**
	 * Starts buffering output.  This will first check for a cached version
	 * of the current page, however, and output that to the browser.  If that
	 * fails, then this will turn on output buffering and register the OutputCache::ob_flush()
	 * method (in this class) to can called when the script finishes - or when ob_flush()
	 * is called.
	 *
	 * @param $params Associative array of parameters:
	 *		@param id 	The Page id to store this data as.  Optional.  If omitted
	 *					Then an md5 hash of the current REQUEST_URI is used as
	 *					the ID.
	 *				  	It is stored as a VARCHAR(64) so it can be any string not
	 *					longer than 64 characters.
	 *		@param data The content of the page to be cached.   Required.
	 *		@param lang	The language of the content. Optional.  Will default to 
	 *					the currently selected language ($app->_conf['lang']).
	 *		@param user The username of the user that this page is cached for.
	 *					Defaults to currently logged in user.
	 *		@param expires	The unix timestamp when this page will expire. Optional.
	 *						Defaults to NOW + $this->lifeTime (usually 3600 seconds).
	 *		@param tables	An array or comma-delimited list of table names that
	 *						This page depends on.  If these tables have been updated
	 *						after the cache is created then the cache is invalidated.
	 *		@randomize		An optional integer number of versions of this page
	 *						that should stay on random rotation.
	 */
	function ob_start($params=array()){
		
		$app =& Dataface_Application::getInstance();
		$query =& $app->getQuery();
		if ( @$app->_conf['nocache'] or in_array($query['-action'], $this->exemptActions) ){
			return true;
		}
		
		if ( floatval($this->threshold) * floatval(100) > rand(0,100) ){
			register_shutdown_function(array(&$this, 'cleanCache'));
		}
	
		if ( isset($params['randomize']) and $params['randomize'] > 1 ){
			$this->randomize = $params['randomize'];
			$numVersions = $this->numCurrentVersions($params);
			if ( $numVersions < $params['randomize'] ){
				// We don't have enough versions yet to do a proper randomization.
				if ( floatval(100)*floatval($numVersions)/floatval($params['randomize']) > rand(0,100) ){
					// We will use the cached version
					$useCache = true;
				} else {
					$useCache = false;
				}
			} else {
				$useCache = true;
			}
		} else {
			$useCache = true;
		}
		
		if ( $useCache ){
			//echo "Trying to use cached version";
			$output = $this->getPage($params);
		} else {
			//echo "Not using cached version";
			$output = null;
		}
		
		if ( isset($output) ){
			//echo "Using cached version";
			//$last_modified_time = filemtime($file);
			$etag = md5($output);
			
			//echo "Session enabled: ".$this->app->sessionEnabled();exit;
			if (!$this->app->sessionEnabled() and  @$_SERVER['REQUEST_URI'] and strpos($_SERVER['REQUEST_URI'], '?') === false ){
				session_cache_limiter('public');
				$expires = 60*60*24;
				header('Cache-Control: public, max-age='.$expires.', s-maxage='.$expires);
				header('Connection: close');
				header("Last-Modified: ".gmdate("D, d M Y H:i:s", $this->lastModified)." GMT");
				header('Pragma: public');
				header('Content-Length: '.strlen($output));
				//header('Expires: '.gmdate('D, d M Y H:i:s', time()+$expires) . ' GMT');
		
			} else {
				header("Last-Modified: ".gmdate("D, d M Y H:i:s", $this->lastModified)." GMT");
				header("Etag: $etag");
				if (@strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) == $this->lastModified ||
					@trim($_SERVER['HTTP_IF_NONE_MATCH']) == $etag) {
					header("HTTP/1.1 304 Not Modified");
					exit;
				}
			
			}
			
			
			// Send the necessary headers
			if ( function_exists('headers_list')){
				$hlist = headers_list();
				$harr = array();
				foreach ($hlist as $h){
					if ( preg_match( '/^(?:Content-Type|Content-Language|Content-Location|Content-Disposition|P3P):/i', $h ) ) {
						list($hname,$hval) = array_map('trim',explode(':',$h));
						$harr[$hname] = $hval;
					}
				}
				
				foreach ( $this->headers as $h){
					list($hname,$hval) = array_map('trim',explode(':',$h));
					if ( !isset($harr[$hname]) ){
						header($hname.': '.$hval);
					}
				}
			}	
				
			
			if ( $this->gzipSupported() and $this->useGzipCompression ){
				header("Content-Encoding: gzip");
				echo $output;
			} else {
				echo $output;
			}
			exit;
		}
		
		ob_start(array(&$this, 'ob_flush'));
		ob_implicit_flush(0);
		return true;
	}
	
	function ob_flush($data){
		if ( !$data ) return false;
		$params = array('randomize'=>$this->randomize, 'data'=>$data, 'tables'=>$this->app->tableNamesUsed);
                if ( !@Dataface_Application::getInstance()->_conf['nocache'] ){
                    $res = $this->cachePage($params);

                    $etag = md5($data);
                    if ( !$this->app->sessionEnabled() and @$_SERVER['REQUEST_URI'] and strpos($_SERVER['REQUEST_URI'], '?') === false ){
                            //echo "here";exit;
                            $expires = 60*60*24;
                            session_cache_limiter('public');
                            header('Cache-Control: public, max-age='.$expires.', s-maxage='.$expires);
                            header('Connection: close');
                            header("Last-Modified: ".gmdate("D, d M Y H:i:s", time())." GMT");
                            header('Pragma: public');
                            header('Content-Length: '.strlen($data));
                    } else {
                            header("Last-Modified: ".gmdate("D, d M Y H:i:s", time())." GMT");
                            header("Etag: $etag");
                            if (@strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) == time() ||
                                    @trim($_SERVER['HTTP_IF_NONE_MATCH']) == $etag) {
                                    header("HTTP/1.1 304 Not Modified");
                                    exit;
                            }
                    }
                }
		
		
		return $data;
		
	}
	
	function getPageID($params=array()){
		$page_url = $_SERVER['REQUEST_URI'];
		foreach ($this->stripKeys as $key){
			$page_url = preg_replace('/&?'.preg_quote($key, '/').'=[^&]*/','', $page_url);
		}
		//mail('steve@weblite.ca', 'Page URL', $page_url);
		$PageID = ( isset($params['id']) ? $params['id'] : md5($page_url));
		return $PageID;
	}
	
	/**
	 * Saves a page into the database.  This will save both the raw text and
	 * a gzipped version (if the zlib extension is present).
	 *
	 * @param $params An associative array of parameters:
	 *		@param id 	The Page id to store this data as.  This is a required field
	 *				  	It is stored as a VARCHAR(64) so it can be any string not
	 *					longer than 64 characters.
	 *		@param data The content of the page to be cached.   Required.
	 *		@param lang	The language of the content. Optional.  Will default to 
	 *					the currently selected language ($app->_conf['lang']).
	 *		@param user The username of the user that this page is cached for.
	 *					Defaults to currently logged in user.
	 *		@param expires	The unix timestamp when this page will expire. Optional.
	 *						Defaults to NOW + $this->lifeTime (usually 3600 seconds).
	 *		@param tables	An array or comma-delimited list of table names that
	 *						This page depends on.  If these tables have been updated
	 *						after the cache is created then the cache is invalidated.
	 *		@randomize		An optional integer number of versions of this page
	 *						that should stay on random rotation.
	 */
	function cachePage($params=array()){
		$PageID = $this->getPageID($params);
		
		if ( !isset($params['data']) ) throw new Exception('Missing parameter "data"', E_USER_ERROR);
		$Data = $params['data'];
		$Language = ( isset($params['lang']) ? $params['lang'] : $this->app->_conf['lang']);
		//if ( class_exists('Dataface_AuthenticationTool') ){$auth =& Dataface_AuthenticationTool::getInstance();
		//	$UserID = ( isset($params['user']) ? $params['user'] : $auth->getLoggedInUsername());
		//} else {
		//	$UserID = null;
		//}
		$UserID = $this->getUserId();
		
		$Expires = (isset($params['expires']) ? $params['expires'] : time() + $this->lifeTime);
		$tables = (isset($params['tables']) ? $params['tables'] : '');
		$Dependencies = (is_array($tables) ? implode(',',$tables) : $tables);
		
		if ( $this->useGzipCompression && extension_loaded('zlib') ){
			// If we are using GZIP compression then we will use zlib library
			// functions (gzcompress) to compress the data also for storage
			// in the database.
			// Apparently we have to play with the headers and footers of the 
			// gzip file for it to work properly with the web browsers.
			// see http://ca.php.net/gzcompress user comments.
			
			$size = strlen($Data);
			$crc = crc32($Data);
			/*
			$Data_gz = "\x1f\x8b\x08\x00\x00\x00\x00\x00".
						substr(gzcompress($Data,9),0, $size-4).
						$this->_gzipGetFourChars($crc).
						$this->_gzipGetFourChars($size);
			*/
			/* Fix for IE compatibility .. seems to work for mozilla too. */
			$Data_gz = "\x1f\x8b\x08\x00\x00\x00\x00\x00".
						substr(gzcompress($Data,9),0, $size);
			
		}
		
		
		if ( isset($params['randomize']) and $params['randomize'] ){
			// We are keeping multiple versions of this page so that we can 
			// show them on a random rotation.  This is to simulate dynamicism
			// while still caching pages.
			
			// Basically the following query will delete existing cached versions
			// of this page except for the most recent X versions - where X
			// is the number specified in the $randomize parameter.  The 
			// $randomize parameter is the number of versions of this page
			// that should be used on random rotation.
			$res = mysql_query("
				DELETE FROM `".addslashes($this->tableName)."`
				WHERE 
					`PageID`='".addslashes($PageID)."' AND
					`Language`='".addslashes($Language)."' AND
					`UserID`='".addslashes($UserID)."' AND
					`GenID` NOT IN (
						SELECT `GenID` FROM `".addslashes($this->tableName)."`
						WHERE 
							`PageID`='".addslashes($PageID)."' AND
							`Language`='".addslashes($Language)."' AND
							`UserID`='".addslashes($UserID)."'
						ORDER BY
							`LastModified` desc
						LIMIT ".(intval($params['randomize']) - 1)."
				)", $this->app->db() );
			
			if ( !$res ){
				throw new Exception(mysql_error($this->app->db()), E_USER_ERROR);
			}
		} else {
			// We are not randomizing.  We delete any existing pages.
			/*
			$res = mysql_query("
				DELETE low_priority FROM `".addslashes($this->tableName)."`
				WHERE
					`PageID`='".addslashes($PageID)."' AND
					`Language`='".addslashes($Language)."' AND
					`UserID`='".addslashes($UserID)."'", $this->app->db());
			if ( !$res ){
				throw new Exception(mysql_error($this->app->db()), E_USER_ERROR);
			}
			*/
		}
		
		// Get the headers so we can reproduce them properly.
		if ( function_exists('headers_list') ){
			//$headers = serialize(headers_list());
			$headers = headers_list();
			$hout = array();
			foreach ( $headers as $h){
				if ( preg_match( '/^(?:Content-Type|Content-Language|Content-Location|Content-Disposition|P3P):/i', $h ) ) {
					$hout[] = $h;
				}
			}
			$headers = $hout;
		} else {
			$headers = array();
		}
		
		
		// Now we can insert the cached page.
		$sql = "
			replace INTO `".addslashes($this->tableName)."`
			(`PageID`,`Language`,`UserID`,`Dependencies`,`Expires`,`Data`,`Data_gz`, `Headers`)
			VALUES
			('".addslashes($PageID)."',
			 '".addslashes($Language)."',
			 '".addslashes($UserID)."',
			 '".addslashes($Dependencies)."',
			 FROM_UNIXTIME('".addslashes($Expires)."'),
			 '".addslashes($Data)."',
			 '".addslashes($Data_gz)."',
			 '".addslashes(serialize($headers))."'
			)";
			//file_put_contents('/tmp/dump.sql',$sql);
		$res = mysql_query($sql, $this->app->db());
		
		if ( !$res ){
			throw new Exception(mysql_error($this->app->db()), E_USER_ERROR);
		}
	
		if ( @$this->app->_conf['_output_cache']['cachedir'] ){
			$filename =  DATAFACE_SITE_PATH.'/'.$this->app->_conf['_output_cache']['cachedir'];
			$dir = $PageID{0};
			$filename = $filename.'/'.$dir;
			if ( !file_exists($filename)){
				mkdir($filename, 0777);
				
			}
			
			$filename .= '/'.$PageID.'-'.md5($Language.'-'.$UserID);
			if ( file_exists($filename) ){
				@unlink($filename);
			} 
			//echo "Opening $filename";
			$fh = fopen($filename, 'w');
			if ( $fh ){ 
				fwrite($fh, $Data);
				fclose($fh);
			}
			
			$fh = fopen($filename.'.gz', 'w');
			if ( $fh ){
				fwrite($fh, $Data_gz);
				fclose($fh);
			}
			
		}
		
		
		
		
		
	
	}
	
	/**
	 * A utility script to get 4 characters from the size or CRC of a gzip file.
	 *  Borrowed from php.net (http://ca.php.net/gzcompress).
	 */
	function _gzipGetFourChars($Val){
		$out = '';
		for ($i = 0; $i < 4; $i ++) { 
		   $out .= chr($Val % 256); 
		   $Val = floor($Val / 256); 
	   } 
	   return $out;
	
	}
	
	
	/**
	 * Creates the table to store the cached pages.
	 */
	function _createCacheTable(){
		$res = mysql_query("create table IF NOT EXISTS `".addslashes($this->tableName)."`(
			`GenID` INT(11) auto_increment,
			`PageID` VARCHAR(64),
			`Language` CHAR(2),
			`UserID` VARCHAR(32),
			`Dependencies` TEXT,
			`LastModified` TIMESTAMP,
			`Expires` DateTime,
			`Data` LONGTEXT,
			`Data_gz` LONGBLOB,
			`Headers` TEXT,
			PRIMARY KEY (`GenID`),
			INDEX `LookupIndex` (`Language`,`UserID`,`PageID`)
			)", $this->app->db());
		if ( !$res ){
			return PEAR::raiseError('Could not create cache table: '.mysql_error($this->app->db()));
		}	
		
	}
	
	/**
	 * Checks if the database table to store the cached pages already exists.
	 * if it doesn't we shall have to create it.
	 * @returns boolean
	 */
	function _cacheTableExists(){
		if ( isset($this->_cacheTableExists) ) return $this->_cacheTableExists;
		$res = mysql_query("SHOW TABLES LIKE '".addslashes($this->tableName)."'", $this->app->db());
		if ( !$res ){
			throw new Exception(mysql_error($this->app->db()), E_USER_ERROR);
		}
		return (mysql_num_rows($res) > 0);
	}
	
	/**
	 * Deletes all expired pages from the cache.
	 */
	function cleanCache(){
		$res = mysql_query("delete low_priority from `".addslashes($this->tableName)."` where `Expires` < NOW()", $this->app->db());
	}
	
	/**
	 * Indicates whether the user's browser supports gzip compression.  This is 
	 * important because, if it does, then we will be using the GZIP compressed
	 * versions of cached pages to save bandwidth and reduce latency.
	 */
	function gzipSupported(){
		return stristr(@$_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip');
	}
	
	/**
	 * Returns an associative array of table names and their associated 
	 * update times as unix timestamps.
	 * eg: [Tablename] -> [Unix Timestamp]
	 */
	function &getTableModificationTimes(){	
		$mod_times =&  Dataface_Table::getTableModificationTimes();
		$this->tableModificationTimes =& $mod_times;
		return $mod_times;
	}
	
	/**
	 * Checks to see if any of the specified tables have been modified since 
	 * a given time.
	 * Note that the tables stored in $this->observedTables will automatically
	 * be added to $tables for this check.  $this->observedTables is an array
	 * of tablenames that must always be observed.
	 *
	 * @param $time The unix timestamp that we are checking against.
	 * @param $tables An array (or comma-delimited string) of table names
	 * @returns True if the tables have been modified since $time.  False otherwise.
	 */
	function isModified($time, $tables=null){
		$this->getTableModificationTimes();
		if ( !isset($tables) ) $tables = array_keys($this->tableModificationTimes);
		if ( !is_array($tables) ){
			$tables = explode(',', $tables);
		}
		$tables = array_merge($this->observedTables, $tables);
		foreach ($tables as $table ){
			if ( isset( $this->ignoredTables[$table] ) ) continue;
			if ( !isset($this->tableModificationTimes[$table]) ) continue;
			if ( $this->tableModificationTimes[$table] > $time ) return true;
		}
		return false;
	}

}
