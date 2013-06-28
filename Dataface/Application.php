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
 
 
if ( !function_exists('sys_get_temp_dir') )
{
 // Based on http://www.phpit.net/
 // article/creating-zip-tar-archives-dynamically-php/2/
 function sys_get_temp_dir()
 {
   // Try to get from environment variable
   if ( !empty($_ENV['TMP']) )
   {
     return realpath( $_ENV['TMP'] );
   }
   else if ( !empty($_ENV['TMPDIR']) )
   {
     return realpath( $_ENV['TMPDIR'] );
   }
   else if ( !empty($_ENV['TEMP']) )
   {
     return realpath( $_ENV['TEMP'] );
   }

   // Detect by creating a temporary file
   else
   {
     // Try to use system's temporary directory
     // as random name shouldn't exist
     $temp_file = tempnam( md5(uniqid(rand(), TRUE)), '' );
     if ( $temp_file )
     {
       $temp_dir = realpath( dirname($temp_file) );
       unlink( $temp_file );
       return $temp_dir;
     }
     else
     {
       return FALSE;
     }
   }
 }
}

require_once dirname(__FILE__)."/../config.inc.php";
import('Dataface/PermissionsTool.php');
import('Dataface/LanguageTool.php');
define('DATAFACE_STRICT_PERMISSIONS', 100);
	// the minimum security level that is deemed as strict permissions.  
	// strict permissions mean that permissions must be explicitly granted to a 
	// table, record, or action or they will not be accessible

/**
 * @brief The main Application object that handles requests and response. 
 * 
 * This is the one object that is presumed to always exist in a Dataface request.
 *
 *
 * @par Example 1
 *
 * Usage in the index.php file or entry point of an application.  In this context, the 
 * application is merely loaded and called upon to display the application.
 *
 * @code
 * <?php
 * require_once 'xataface/public-api.php';
 * df_init(__FILE__, '/dataface')->display();
 * 
 * @endcode
 *
 * In the above example we're exploiting the fact that df_init() returns the
 * application object so we can call Dataface_Application::display() using method 
 * chaining.
 *
 * @par Obtaining the Dataface_Application Object
 *
 * Always use the Dataface_Application::getInstance() to obtain a reference to
 * the Dataface_Application object.  Never use the constructor directly.
 *
 * @code
 * @app = Dataface_Application::getInstance();
 * @endcode
 *
 * @par Getting the Current Query
 *
 * The most common use of the application object is to get a reference to the current
 * request parameters.  Use Dataface_Application::getQuery() to get this as an associative
 * array.
 * 
 * @code
 * $app = Dataface_Application::getInstance();
 * $query =& $app->getQuery();
 * if ( $query['-action'] == 'foo' ){
 *     // Do some foo stuff
 * }
 * @endcode
 *
 * @par Getting the Current Record
 * 
 * Each HTTP request should resolve to context which includes a current record.  The 
 * current record is decided by <a href="http://xataface.com/wiki/URL_Conventions">Xataface URL Conventions</a>.  
 * It will either be decided by the -recordid parameter, or the current filter combined with 
 * the -cursor parameter (although there are some other possiblities.
 *
 * @code
 * $app = Dataface_Application::getInstance();
 * $record = $app->getRecord();
 * echo "The current record is ".$record->getTitle();
 * @endcode
 *
 * @see Dataface_Record
 *
 *
 * @author Steve Hannah (shannah@sfu.ca)
 * @since 0.6
 */
class Dataface_Application {

	const EX_FAILED_TO_CREATE_SESSION_DIR = 5500;
	
	
	/**
	 * @brief An object that implements a method named 'redirect' that is
	 *  supposed to handle requests to redirect to a new page.  This
	 *  gives an opportunity to suppress redirects or handle them differently 
	 *  inline.  This is helpful if you're loading Xataface from a cron script
	 *  or another application and need to prevent redirects.
	 *
	 * @section example Example
	 *
	 * @code
	 * $app = Dataface_Application::getInstance();
	 * class MyHandler {
	 *     function redirect($url){
	 *         echo "A request to redirect to $url.";
	 *     }
	 * }
	 * $app->redirectHandler = new MyHandler();
	 * $app->display();
	 * @endcode
	 *
	 * Note that if your handler does not either throw an exception or exit execution, then
	 * Xataface will throw a Dataface_Application_RedirectException which can be caught 
	 * higher up the call stack.
	 *
	 */
	public $redirectHandler = null;
	
	private $pageTitle = null;

	/**
	 * @private
	 */
	var $sessionCookieKey;

	
	/**
	 * @private
	 */
	var $autoSession = false;

	/**
	 * @private
	 */
	var $_url_filters = array();
	/**
	 * An associative array of the table names that should be included in the tables menu.
	 * @private
	 */
	var $_tables = array();
	
	/**
	 * An associative array of all of the tables in the database.
	 * @private
	 */
	var $tableIndex = array();
	
	/**
	 * The base url of the site.
	 * @private
	 */
	var $_baseUrl;
	
	/**
	 * @private
	 */
	var $_currentTable;
	
	
	/**
	 * @private
	 */
	var $memcache;
	
	/**
	 * Database resource handle.
	 * @private
	 */
	var $_db;
	/**
	 * @brief Associative array of request variables.  These modified from $_REQUEST
	 *		to fill in missing values and change some values depending on application
	 * 		preferences.  Access this variable only via Dataface_Application::getQuery()
	 * @private
	 */
	var $_query;
	
	/**
	 * @var array The raw request variables straight from $_REQUEST. The only difference
	 * 	between this array and $_REQUEST is that the $_REQUEST[-__keys__] array
	 *  will be used to override key variables for the current table.
	 * 
	 */
	var $rawQuery;
	
	/**
	 * @private
	 */
	var $queryTool = null;
	
	/**
	 * @private
	 */
	var $currentRecord = null;
	
	/**
	 * @private
	 */
	var $recordContext = null;
	
	/**
	 * @private
	 */
	var $_customPages;
	
	/**
	 * An array of locations that have been visited.  The keys
	 * are md5 encodings of the values so that the location can be passed
	 * as a GET parameter as an MD5 string.
	 * @private
	 */
	var $locations = null;
	
	/**
	 * Registered listeners for various events in the application.
	 * of the form [Event_name] -> array([callback1], [callback2], ...)
	 *			   [Event_name2] -> array([callback1], ...)
	 * @private
	 *
	 */
	var $eventListeners = array();
	
	/**
	 * @brief User preferences matrix.
	 */
	var $prefs = array(
		'show_result_stats'=>1, // The result statistics (e.g. found x of y records in table z)
		'show_jump_menu'=>1,	// The drop-down menu that allows you to "jump" to any record in the found set.
		'show_result_controller'=>1,	// Next, previous, page number .. links...
		'show_table_tabs'=>1,			// Details, List, Find, etc...
		'show_actions_menu'=>1,			// New record, Show all, delete, etc...
		'show_logo'=>1,					// Show logo at top right of app
		'show_tables_menu'=>1,			// The tabs to select a table.
		'show_search'=>1,				// Show search field in upper right.
		'show_record_actions'=>1,		// Show actions related to particular record
		'show_recent_records_menu'=>1,	// Menu to jump to recently visited record (deprecated)
		'show_bread_crumbs' => 1,		// Bread crumbs at top of page to show where you are
		'show_record_tabs' => 1,		// View, Edit, Translate, History, etc...
		'show_record_tree' => 1,		// Tree to navigate the relationships of this record.
		'list_view_scroll_horizontal'=>1, // Whether to scroll list horizontal if it exceeds page width
		'list_view_scroll_vertical'=>1	// Whether to scroll list vertical if it exceeds page height.
	
	);
	
	/**
	 * @brief Keeps track of the table names used in the current request. -- just so 
	 * we know the breadth of the request.
	 * @private
	 */
	var $tableNamesUsed = array();
	
	/**
	 * @private
	 */
	var $main_content_only = false;
		// IF true then output only includes main content - not the 
		// surrounding frame.
	
	/**
	 * Reference to the delegate object for this application.  The delegate class is an optional
	 * class that can be placed in the conf/ApplicationDelegate.php file with the class name
	 * "conf_ApplicationDelegate";
	 * @private
	 */
	var $delegate = -1;
	
	
	
	/**
	 * @private
	 */
	var $errors=array();
	
	/**
	 * @private
	 */
	var $messages = array();
	
	/**
	 * @private
	 */
	var $debugLog = array();
	
	/**
	 * @private
	 */
	var $authenticationTool = null;
	
	/**
	 * An array of text that is to be inserted into the head of the template.
	 * This allows a more efficient method for adding a custom javascript or
	 * stylesheet.
	 * @private
	 */
	var $headContent=array();
	
	/**
	 * The mysql version info.
	 * @private
	 *
	 */
	var $mysqlVersion = null;
	
	
	
	// @{
	/**
	 * @name Languages
	 * 
	 * Methods for dealing with multiple languages.
	 */
	
	
	/**
	 * Associative array to map locales to a language code.
	 * This is necessary because Xataface only uses 2-digits language
	 * codes, some nonstandard.  So these language codes
	 * must map to real locales.
	 *
	 * E.g. Xataface uses zt to mean traditional chinese, but zh
	 * for simplified chinese.  This array will map the 
	 * zh_CN locale to the zh code and the zh_TW, and zh_HK codes to 
	 * zt.
	 *
	 * These values can be supplemented or overridden using the '_locales'
	 * section of the conf.ini file.
	 * @private
	 */
	 var $_locales = array(
		'zh_CN'=>'zh',
		'zh_TW'=>'zt',
		'zh_HK'=>'zt',
		'en_US'=>'en'
	);
	
	/**
	 * Associative array to map non-standard language codes to their 
	 * corresponding language.
	 *
	 * These values can be supplemented or overridden using the '_language_codes'
	 * section of the conf.ini file.
	 * @private
	 *
	 */
	var $_languages = array(
		'zt'=>'zh'
	);
	
	/**
	 * @brief Returns the language associated with a given language code.
	 * @param string $langCode  The 2-digit xataface language code. May be non-standard.
	 * @return string The 2-digit ISO-639 language code corresponding to the Xataface
	 *	language code.
	 *
	 * @since 1.0
	 *
	 * @par Example
	 *
	 * Given a @b languages section in your conf.ini file that resembles:
	 * @par Inputs:
	 * @code
	 * echo $app->getLanguage('zt');
	 * echo "\n". $app->getLanguage('zh');
	 * @endcode
	 *
	 * @par Outputs
	 * @code
	 * zh
	 * zh
	 * @endcode
	 *
	 * @since 1.2
	 *
	 */
	function getLanguage($langCode){
		if ( isset($this->_languages[$langCode]) ){
			return $this->_languages[$langCode];
		} else {
			return $langCode;
		}
	}
	
	/**
	 * @brief Returns the Xataface language code for a particular locale.
	 * E.g. zh_CN would return zh, while zh_TW would return zt.
	 *
	 * @param string $locale A local in the form <lang>_<COUNTRY>.  E.g. en_US or zh_CN
	 * @return string The 2-digit xataface language code that handles the locale.
	 *
	 * @par Inputs:
	 * @code
	 *	echo $app->getLanguageCode('zh_TW');
	 * @endcode
	 * 
	 * @par Outputs:
	 * @code
	 *	zh
	 * @endcode
	 * @since 1.2
	 */
	function getLanguageCode($locale){
		if ( isset($this->_locales[$locale]) ) return $this->_locales[$locale];
		else {
			list($langCode) = explode('_', $locale);
			return $langCode;
		}
	}
	
	/**
	 * @brief Returns an array of all available languages in the application.
	 * This is derived from all languages listed in the [languages] section
	 * of the conf.ini file.
	 * 
	 * @return array Array of 2-digit xataface language codes.
	 * @since 1.0
	 *
	 * @par Example conf.ini:
	 * @code
	 * [languages]
	 *	en=English
	 *  fr=French
	 * @endcode
	 * 
	 * @par Example Inputs:
	 * @code
	 * $app->getAvailableLanguages();
	 * @endcode
	 *
	 * @par Example Outputs:
	 * @code
	 * array(
	 *	en => English
	 *  fr => French
	 * )
	 * @endcode
	 *
	 */
	function getAvailableLanguages(){
		$langs = array_keys($this->_conf['languages']);
		if ( @$this->_conf['default_language'] ) $langs[] = $this->_conf['default_language'];
		else $langs[] = 'en';
		$out = array();
		foreach ($langs as $lang){
			$out[$this->getLanguage($lang)] = true;
		}
		return array_keys($out);
		
	}
	

	
	
	// @}
	// END LANGUAGES
	
	
	// @{
	/**
	 * @name Configuration & Initialization
	 * 
	 * Methods and Data Structures for Storing and Accessing Configuration.
	 */
	
	
	/**
	 * @brief Returns MySQL connection resource.
	 */
	function db(){ return $this->_db;}
	
	/**
	 * @brief A configuration array to store configuration information.
	 */
	var $_conf;
	
	/**
	 * @brief Constructor.  Do not use this.  getInstance() instead.
	 */
	function Dataface_Application($conf = null){
		if ( !isset($this->sessionCookieKey) ){
		    $this->sessionCookieKey = md5(DATAFACE_SITE_URL.'#'.__FILE__);
		}
		$this->_baseUrl  = $_SERVER['PHP_SELF'];
		if ( !is_array($conf) ) $conf = array();
		if ( is_readable(DATAFACE_SITE_PATH.'/conf.ini') ){
			$conf = array_merge(parse_ini_file(DATAFACE_SITE_PATH.'/conf.ini', true), $conf);
			if ( @$conf['__include__'] ){
				$includes = array_map('trim',explode(',', $conf['__include__']));
				foreach ($includes as $i){
					if ( is_readable($i) ){
						$conf = array_merge($conf, parse_ini_file($i, true));
					}
				}
			}
		}
		
		
		
		if ( !isset( $conf['_tables'] ) ){
			throw new Exception('Error loading config file.  No tables specified.', E_USER_ERROR);

		}

		
		
		if ( isset( $conf['db'] ) and is_resource($conf['db']) ){
			$this->_db = $conf['db'];
		} else {
			if ( !isset( $conf['_database'] ) ){
				throw new Exception('Error loading config file. No database specified.', E_USER_ERROR);

			}
			$dbinfo =& $conf['_database'];
			if ( !is_array( $dbinfo ) || !isset($dbinfo['host']) || !isset( $dbinfo['user'] ) || !isset( $dbinfo['password'] ) || !isset( $dbinfo['name'] ) ){
				throw new Exception('Error loading config file.  The database information was not entered correctly.<br>
					 Please enter the database information int its own section of the config file as follows:<br>
					 <pre>
					 [_database]
					 host = localhost
					 user = foo
					 password = bar
					 name = database_name
					 </pre>', E_USER_ERROR);

			}
			if ( @$dbinfo['persistent'] ){
				$this->_db = mysql_pconnect( $dbinfo['host'], $dbinfo['user'], $dbinfo['password'] );
			} else {
				$this->_db = mysql_connect( $dbinfo['host'], $dbinfo['user'], $dbinfo['password'] );
			}
			if ( !$this->_db ){
				throw new Exception('Error connecting to the database: '.mysql_error());

			}
			$this->mysqlVersion = mysql_get_server_info($this->_db);
			mysql_select_db( $dbinfo['name'] ) or die("Could not select DB: ".mysql_error($this->_db));
		}
		if ( !defined( 'DATAFACE_DB_HANDLE') ) define('DATAFACE_DB_HANDLE', $this->_db);
		
		
		if ( !is_array( $conf['_tables'] ) ){
			throw new Exception("<pre>
				Error reading table information from the config file.  Please enter the table information in its own section
				of the ini file as follows:
				[_tables]
				table1 = Table 1 Label
				table2 = Table 2 Label
				</pre>");

		}
		
		$this->_tables = $conf['_tables'];
		
		
		
		if ( count($this->_tables) <= 10 ){
			$this->prefs['horizontal_tables_menu'] = 1;
		}
		
		// We will register a _cleanup method to run after code execution is complete.
		register_shutdown_function(array(&$this, '_cleanup'));

		// Set up memcache if it is installed.
		if ( DATAFACE_EXTENSION_LOADED_MEMCACHE ){
			if ( isset($conf['_memcache']) ){
				if ( !isset($conf['_memcache']['host']) ){
					$conf['_memcache']['host'] = 'localhost';
				}
				if ( !isset($conf['_memcache']['port']) ){
					$conf['_memcache']['port'] = 11211;
				}
				$this->memcache = new Memcache;
				$this->memcache->connect($conf['_memcache']['host'], $conf['_memcache']['port']) or die ("Could not connect to memcache on port 11211");
				
			}
		}
		
		//
		// -------- Set up the CONF array ------------------------
		$this->_conf = $conf;
		
		if ( !isset($this->_conf['_disallowed_tables']) ){
			$this->_conf['_disallowed_tables'] = array();
		}
		
		$this->_conf['_disallowed_tables']['history'] = '/__history$/';
		$this->_conf['_disallowed_tables']['cache'] = '__output_cache';
		$this->_conf['_disallowed_tables']['dataface'] = '/^dataface__/';
                $this->_conf['_disallowed_tables']['xataface'] = '/^xataface__/';
		if ( !@$this->_conf['_modules'] or !is_array($this->_conf['_modules']) ){
			$this->_conf['_modules'] = array();
		}
		
		// Include XataJax module always.
		$mods = array('modules_XataJax'=>'modules/XataJax/XataJax.php');
		foreach ($this->_conf['_modules'] as $k=>$v){
			$mods[$k] = $v;
		}
		$this->_conf['_modules'] = $mods;
		
		
		if ( isset($this->_conf['_modules'])  and count($this->_conf['_modules'])>0 ){
			import('Dataface/ModuleTool.php');
		}

		if ( isset($this->_conf['languages']) ){
			$this->_conf['language_labels'] = $this->_conf['languages'];
			foreach ( array_keys($this->_conf['language_labels']) as $lang_code){
				$this->_conf['languages'][$lang_code] = $lang_code;
			}
		}
		
		if ( @$this->_conf['support_transactions'] ){
			// We will support transactions
			@mysql_query('SET AUTOCOMMIT=0', $this->_db);
			@mysql_query('START TRANSACTION', $this->_db);
		
		}
		if ( !isset($this->_conf['default_ie']) ) $this->_conf['default_ie'] = 'UTF-8';
		if ( !isset($this->_conf['default_oe']) ) $this->_conf['default_oe'] = 'UTF-8';
		if ( isset( $this->_conf['multilingual_content']) || isset($this->_conf['languages']) ){
			$this->_conf['oe'] = 'UTF-8';
			$this->_conf['ie'] = 'UTF-8';
			
			if (function_exists('mb_substr') ){
				// The mbstring extension is loaded
				ini_set('mbstring.internal_encoding', 'UTF-8');
				//ini_set('mbstring.encoding_translation', 'On');
				ini_set('mbstring.func_overload', 7);
				
			}
			
			if ( !isset($this->_conf['languages']) ){
				$this->_conf['languages'] = array('en'=>'English');
			}
			if ( !isset($this->_conf['default_language']) ){
				if ( count($this->_conf['languages']) > 0 )
					$this->_conf['default_language'] = reset($this->_conf['languages']);
					
				else 
					$this->_conf['default_language'] = 'en';
					
			}
			
		} else {
			$this->_conf['oe'] = $this->_conf['default_oe'];
			$this->_conf['ie'] = $this->_conf['default_ie'];
		}
		
                define('XF_OUTPUT_ENCODING', $this->_conf['oe']);
                
		if ( $this->_conf['oe'] == 'UTF-8' ){
			$res = mysql_query('set character_set_results = \'utf8\'', $this->_db);
			mysql_query("SET NAMES utf8", $this->_db);
		}
		if ( $this->_conf['ie'] == 'UTF-8' ){
			$res = mysql_query('set character_set_client = \'utf8\'', $this->_db);
			
		}
		
		
		if ( isset($this->_conf['use_cache']) and $this->_conf['use_cache'] and !defined('DATAFACE_USE_CACHE') ){
			define('DATAFACE_USE_CACHE', true);
		}
		
		if ( isset($this->_conf['debug']) and $this->_conf['debug'] and !defined('DATAFACE_DEBUG') ){
			define('DATAFACE_DEBUG', true);
		} else if ( !defined('DATAFACE_DEBUG') ){
			define('DATAFACE_DEBUG',false);
		}
		
		if ( !@$this->_conf['config_storage'] ) $this->_conf['config_storage'] = DATAFACE_DEFAULT_CONFIG_STORAGE;
			// Set the storage type for config information.  It can either be stored in ini files or
			// in the database.  Database will give better performance, but INI files may be simpler
			// to manage for simple applications.
		
		if ( !isset($this->_conf['garbage_collector_threshold']) ){
			/**
			 * The garbage collector threshold is the number of seconds that "garbage" can
			 * exist for before it is deleted.  Examples of "garbage" include import tables
			 * (ie: temporary tables created as an intermediate point to importing data).
			 */
			$this->_conf['garbage_collector_threshold'] = 10*60;
		}
		
		if ( !isset($this->_conf['multilingual_content']) ) $this->_conf['multilingual_content'] = false;
			// whether or not the application will use multilingual content.
			// multilingual content enables translated versions of content to be stored in
			// tables using naming conventions.
			// Default to false because this takes a performance hit (sql queries take roughly twice
			// as long because they have to be parsed first.
		
		if ( !isset($this->_conf['cookie_prefix']) ) $this->_conf['cookie_prefix'] = 'dataface__';
		
		if ( !isset($this->_conf['security_level']) ){
			// Default security is strict if security is not specified.  This change is effectivce
			// for Dataface 0.6 .. 0.5.3 and earlier had a loose permissions model by default that 
			// could be tightened using delegate classes.
			$this->_conf['security_level'] = 0; //DATAFACE_STRICT_PERMISSIONS;
		}
		
		
		if ( !isset($this->_conf['default_action']) ){
			// The default action defines the action that should be set if no
			// other action is specified.
			$this->_conf['default_action'] = 'list';
		}
		
		if ( !isset($this->_conf['default_browse_action']) ){
			$this->_conf['default_browse_action'] = 'view';
		}
		
		
		if ( !isset($this->_conf['default_mode'] ) ) $this->_conf['default_mode'] = 'list';
		
		if ( !isset($this->_conf['default_limit']) ){
			$this->_conf['default_limit'] = 30;
		}
		
		if ( !isset($this->_conf['default_table'] ) ){
			// The default table is the table that is used if no other table is specified.
			foreach ($this->_tables as $key=>$value){
				$this->_conf['default_table'] = $key;
				
				break;
			}
		}
		
		if ( !isset($this->_conf['auto_load_results']) ) $this->_conf['auto_load_results'] = false;
		
		if ( !isset( $this->_conf['cache_dir'] ) ){
			if ( ini_get('upload_tmp_dir') ) $this->_conf['cache_dir'] = ini_get('upload_tmp_dir');
			else $this->_conf['cache_dir'] = '/tmp';
		}
		
		if ( !isset( $this->_conf['default_table_role'] ) ){
			
			if ( $this->_conf['security_level'] >= DATAFACE_STRICT_PERMISSIONS ){
				$this->_conf['default_table_role'] = 'NO ACCESS';
			} else {
				$this->_conf['default_table_role'] = 'ADMIN';
			}
			
		}
		
		if ( !isset( $this->_conf['default_field_role'] ) ){
			if ( $this->_conf['security_level'] >= DATAFACE_STRICT_PERMISSIONS ){
				$this->_conf['default_field_role'] = 'NO ACCESS';
			} else {
				$this->_conf['default_field_role'] = 'ADMIN';
				
			}
		}
		
		if ( !isset( $this->_conf['default_relationship_role'] ) ){
			if ( $this->_conf['security_level'] >= DATAFACE_STRICT_PERMISSIONS ){
				$this->_conf['default_relationship_role'] = 'READ ONLY';
			} else {
				$this->_conf['default_relationship_role'] = 'ADMIN';
				
			}
		}
		
		if ( !isset( $this->_conf['languages'] ) ) $this->_conf['languages'] = array('en');
		else if ( !is_array($this->_conf['languages']) ) $this->_conf['languages'] = array($this->_conf['languages']);
		
		if ( isset($this->_conf['_language_codes']) ){
			$this->_languages = array_merge($this->_languages, $this->_conf['_language_codes']);
		}
		if ( isset($this->_conf['_locales']) ){
			$this->_locales = array_merge($this->_locales, $this->_conf['_locales']);
		}
		
		// Set the language.
		// Language is stored in a cookie.  It can be changed by passing the -lang GET var with the value
		// of a language.  e.g. fr, en, cn
		if ( !isset( $this->_conf['default_language'] ) ) $this->_conf['default_language'] = 'en';
		$prefix = $this->_conf['cookie_prefix'];
		//print_r($_COOKIE);
		if ( isset($_REQUEST['--lang']) ){
			$_REQUEST['--lang'] = basename($_REQUEST['--lang']);
			$this->_conf['lang'] = $_REQUEST['--lang'];
		} else if ( isset( $_REQUEST['-lang'] ) ){
			$_REQUEST['-lang'] = basename($_REQUEST['-lang']);
			$this->_conf['lang'] = $_REQUEST['-lang'];
			if ( @$_COOKIE[$prefix.'lang'] !== $_REQUEST['-lang'] ){
				setcookie($prefix.'lang', $_REQUEST['-lang'], null, '/');
			}
		} else if (isset( $_COOKIE[$prefix.'lang']) ){
			$this->_conf['lang'] = $_COOKIE[$prefix.'lang'];
		} else {
			import('I18Nv2/I18Nv2.php');
			$negotiator = I18Nv2::createNegotiator($this->_conf['default_language'], 'UTF-8');
			$this->_conf['lang'] = $this->getLanguageCode(
				$negotiator->getLocaleMatch(
					$this->getAvailableLanguages()
				)
			);
			setcookie($prefix.'lang', $this->_conf['lang'], null, '/');
		}
		
		$this->_conf['lang'] = basename($this->_conf['lang']);
		
                
                if ( isset($_REQUEST['-template']) ){
                    $_REQUEST['-template'] = basename($_REQUEST['-template']);
                }
                if ( isset($_GET['-template']) ){
                    $_GET['-template'] = basename($_GET['-template']);
                }
                if ( isset($_POST['-template']) ){
                    $_POST['-template'] = basename($_POST['-template']);
                }
		
		// Set the mode (edit or view)
		if ( isset($_REQUEST['-usage_mode'] )){
			$this->_conf['usage_mode'] = $_REQUEST['-usage_mode'];
			if (@$_COOKIE[$prefix.'usage_mode'] !== $_REQUEST['-usage_mode']){
				setcookie($prefix.'usage_mode', $_REQUEST['-usage_mode'], null, '/');
			}
		} else if ( isset( $_COOKIE[$prefix.'usage_mode'] ) ){
			$this->_conf['usage_mode'] = $_COOKIE[$prefix.'usage_mode'];
		} else if ( !isset($this->_conf['usage_mode']) ){
			$this->_conf['usage_mode'] = 'view';
		}
		
		define('DATAFACE_USAGE_MODE', $this->_conf['usage_mode']);
		
		if ( @$this->_conf['enable_workflow'] ){
			import('Dataface/WorkflowTool.php');
		}
		
		
		
		
		// ------- Set up the current query ---------------------------------
		
		if ( isset($_REQUEST['__keys__']) and is_array($_REQUEST['__keys__']) ){
			$query = $_REQUEST['__keys__'];
			foreach ( array_keys($_REQUEST) as $key ){
				if ( $key{0} == '-' and !in_array($key, array('-search','-cursor','-skip','-limit'))){
					$query[$key] = $_REQUEST[$key];
				}
			}
		} else {
			$query = array_merge($_GET, $_POST);
		}
		if ( @$query['-action'] ){
			$query['-action'] = trim($query['-action']);
			if ( !preg_match('/^[a-zA-Z0-9_]+$/', $query['-action']) ){
				throw new Exception("Illegal action name.");
			}
			$query['-action'] = basename($query['-action']);
		}
		if ( @$query['-table'] ){
			$query['-table'] = trim($query['-table']);
			if ( !preg_match('/^[a-zA-Z0-9_]+$/', $query['-table']) ){
				throw new Exception("Illegal table name.");
			}
			$query['-table'] = basename($query['-table']);
		}
		if ( @$query['-lang'] ){
			$query['-lang'] = trim($query['-lang']);
			if ( !preg_match('/^[a-zA-Z0-9]{2}$/', $query['-lang']) ){
				throw new Exception("Illegal language code: ".$query['-lang']);
			}
			$query['-lang'] = basename($query['-lang']);
		}
		
		if ( @$query['--lang'] ){
			$query['--lang'] = trim($query['--lang']);
			if ( !preg_match('/^[a-zA-Z0-9]{2}$/', $query['--lang']) ){
				throw new Exception("Illegal language code: ".$query['--lang']);
			}
			$query['--lang'] = basename($query['--lang']);
		}
		
		if ( @$query['-theme'] ){
			$query['-theme'] = trim($query['-theme']);
			if ( !preg_match('/^[a-zA-Z0-9_]+$/', $query['-theme']) ){
				throw new Exception("Illegal theme name.");
			}
			$query['-theme'] = basename($query['-theme']);
		}
		
		if ( @$query['-cursor']){
			$query['-cursor'] = intval($query['-cursor']);
		}
		if ( @$query['-limit'] ){
			$query['-limit'] = intval($query['-limit']);
		}
		if ( @$query['-skip'] ){
			$query['-skip'] = intval($query['-skip']);
		}
		if ( @$query['-related-limit'] ){
			$query['-related-limit'] = intval($query['-related-limit']);
		}
		if ( @$query['-relationship'] ){
			if ( !preg_match('/^[a-zA-Z0-9_]+$/', $query['-relationship']) ){
				throw new Exception("Illegal relationship name.");
			}
		}
		
		
		
		
		$this->rawQuery = $query;
		
		if ( !isset( $query['-table'] ) ) $query['-table'] = $this->_conf['default_table'];
		$this->_currentTable = $query['-table'];
		
		
		if ( !@$query['-action'] ) {
			$query['-action'] = $this->_conf['default_action'];
			$this->_conf['using_default_action'] = true;
		}
		
		$query['--original_action'] = $query['-action'];
		if ( $query['-action'] == 'browse') {
			if ( isset($query['-relationship']) ){
				$query['-action'] = 'related_records_list';
			} else if ( isset($query['-new']) and $query['-new']) {
				$query['-action'] = 'new';
			} else {
				$query['-action'] = $this->_conf['default_browse_action']; // for backwards compatibility to 0.5.x
			}
		} else if ( $query['-action'] == 'find_list' ){
			$query['-action'] = 'list';
		}
		if ( !isset( $query['-cursor'] ) ) $query['-cursor'] = 0;
		if ( !isset( $query['-skip'] ) ) $query['-skip'] = 0;
		if ( !isset( $query['-limit'] ) ) $query['-limit'] = $this->_conf['default_limit'];
		
		if ( !isset( $query['-mode'] ) ) $query['-mode'] = $this->_conf['default_mode'];
		$this->_query =& $query;
		
		
		if ( isset( $query['--msg'] ) ) {
			$query['--msg'] = preg_replace('#<[^>]*>#','', $query['--msg']);
			if ( preg_match('/^@@$/', $query['--msg']) ){
				
				if ( @$_SESSION['--msg'] ){
					$this->addMessage(@$_SESSION['--msg']);
					unset($_SESSION['--msg']);
				}
			} else {
				
				$this->addMessage($query['--msg']);
			}
		}
		
		
		
		
		if ( isset($query['--error']) and trim($query['--error']) ){
			$query['--error'] = preg_replace('#<[^>]*>#','', $query['--error']);
			$this->addError(PEAR::raiseError($query['--error']));
		}
		
		// Now allow custom setting of theme
		if ( isset($query['-theme']) ){
			if ( !isset($this->_conf['_themes']) ) $this->_conf['_themes'] = array();
			$this->_conf['_themes'][basename($query['-theme'])] = 'themes/'.basename($query['-theme']);
		}
		
		// Check to see if we should set a custom default preview length
		if ( isset($query['--default-preview-length']) ){
			$len = intval($query['--default-preview-length']);
			if ( $len > 0 && !defined('XATAFACE_DEFAULT_PREVIEW_LENGTH') ){
				define('XATAFACE_DEFAULT_PREVIEW_LENGTH', $len);
			}
		}
		
		

	}
	
	
	/**
	 * @brief Returns reference to the singleton instance of this class.
	 *
	 * @param array $conf Optional configuration associative array that matches the 
	 * 	stucture of the conf.ini file.  This parameter will only be considered the
	 *  first time this method is called in the request. 
	 *
	 * @return Dataface_Application
	 * @since 0.6
	 *
	 * @par Example
	 * @code
	 * $app = Dataface_Application::getInstance();
	 * $app->display();
	 * @endcode
	 *
	 *
	 */
	public static function &getInstance($conf=null){
		static $instance = array();
		//static $blobRequestCount = 0;
		if ( !isset( $instance[0] ) ){
			$instance[0] = new Dataface_Application($conf);
			if ( !defined('DATAFACE_APPLICATION_LOADED') ){
				define('DATAFACE_APPLICATION_LOADED', true);
			}
		}
		
		return $instance[0];
	}
	
	
	
	/**
	 * @brief Returns the config array as loaded from the conf.ini file, except that 
	 * it opens up the opportunity for the delegate class to load values into
	 * the config using its own conf() method.
	 * 
	 * This is useful if an application wants to store config information in
	 * the database and still make it available to the application.
	 *
	 * @returns array
	 *
	 */
	function &conf(){	
		static $loaded = false;
		if ( !$loaded ){
			$loaded = true;
			$del = $this->getDelegate();
			if ( isset($del) and method_exists($del,'conf') ){
				$conf = $del->conf();
				if ( !is_array($conf) ) throw new Exception("The Application Delegate class defined a method 'conf' that must return an array, but returns something else.", E_USER_ERROR);
				foreach ( $conf as $key=>$val){
					if ( isset($this->_conf[$key]) ){
						if ( is_array($this->_conf[$key]) and is_array($val) ){
							$this->_conf[$key] = array_merge($this->_conf[$key], $val);
						} else {
							$this->_conf[$key] = $val;
						}
					} else {
						$this->_conf[$key] = $val;
					}
				}
				
			}
			
		}
		return $this->_conf;
		
	}
	
	
	/**
	 * @brief Get the mysql major version number of MySQL.
	 * returns int
	 */
	function getMySQLMajorVersion(){
		if ( !isset($this->mysqlVersion) ){
			$this->mysqlVersion = mysql_get_server_info($this->_db);
		}
		list($mv) = explode('.',$this->mysqlVersion);
		return intval($mv);
	}
	
	function getPageTitle(){
		if ( isset($this->pageTitle) ){
			return $this->pageTitle;
		} else {
			$title = $this->getSiteTitle();
			$query =& $this->getQuery();
			if ( ($record = $this->getRecord()) && $query['-mode'] == 'browse'  ){
                return $record->getTitle().' - '.$title;
            } else {
                $tableLabel = Dataface_Table::loadTable($query['-table'])->getLabel();
                return $tableLabel.' - '.$title;
                
            }
		}
	}
	
	function setPageTitle($title){
		$this->pageTitle = $title;
	}
	
	
	/**
	 * @brief Gets the site title.
	 *
	 * @returns string The site title.
	 *
	 * If $app->_conf['title'] is set then this will just return that.  If not 
	 * it will determine an appropriate title based on the current record and
	 * the current table.
	 */
	function getSiteTitle(){
		$query =& $this->getQuery();
		$title = 'Dataface Application';
		 
		if ( isset($this->_conf['title']) ) {
			try {
				$title = $this->parseString($this->_conf['title']);
			} catch (Exception $ex){
				$title = $this->_conf['title'];
			}
		}
		return $title;
		
	
	}
	
	
	// @}
	// END CONFIGURATION
	
	
	// @{
	/**
	 * @name Request Context
	 * 
	 * Methods and Structures for getting information about the current request context.
	 *
	 */
	
	/**
	 * @brief Returns a reference to the current query object.  This is very similar to the $_GET
	 * and $_REQUEST globals except this array has been filled in with missing values.
	 *
	 * @return array Reference to current query object.
	 *
	 * @par Example
	 * @code
	 * $query =& $app->getQuery();
	 * if ( $query['-table'] == 'dashboard' ){
	 *		// Always set the action for the dashboard to 'dashboard_action'
	 *		$query['-action'] = 'dashboard_action';
	 * }
	 * @endcode
	 */
	function &getQuery(){
		return $this->_query;
	}
	
	/**
	 * @brief Returns a query parameter.  
	 *
	 * @param string $key The query parameter to obtain.  This should omit the leading '-'
	 * in the parameter name.   E.g. Instead of '-action' this will be 'action'.
	 *
	 * @return mixed The query parameter or null if not present.
	 *
	 * @par Example
	 * Input:
	 * @code
	 * echo $app->getQueryParam('table');
	 * $query = $app->getQuery();
	 * echo $app['-table'];
	 * @endcode
	 *
	 * Output:
	 * @code
	 * my_table
	 * my_table
	 * @endcode
	 *
	 */
	function &getQueryParam($key){
		if ( isset( $this->_query['-'.$key] ) ){
			return $this->_query['-'.$key];
		} else {
			$null = null;
			return $null;
		}
	}
	
	/**
	 * @brief Loads the current result set.
	 * @returns Dataface_QueryTool
	 */
	function &getResultSet(){
		if ( $this->queryTool === null ){
			import('Dataface/QueryTool.php');
			$this->queryTool = Dataface_QueryTool::loadResult($this->_query['-table'], $this->db(), $this->_query);
		}
		return $this->queryTool;
	
	}
	
	/**
	 * @brief Gets the current record based on the current query.
	 *
	 * @returns Dataface_Record
	 *
	 * @par How Is the Record Selected?
	 * -# It checks for the __keys__ parameter and uses these keys as a filter.
	 * -# It then checks for the -__keys__ parameter and uses these keys as a filter.
	 * -# It then checks for the --__keys__ parameter and uses these keys as a filter.
	 * -# It then checks for the --recordid parameter and returns the specified record.
	 * -# It then checks for the -recordid parameter and returns the specified record.
	 * -# It then loads the current result set and returns the record specified by the 
	 *		-cursor parameter (which is default 0).
	 *
	 * @par The --no-query Parameter
	 * @attention This method may be affected by the --no-query parameter which tells Xataface
	 * that no query should be performed automatically during this request.  This
	 * parameter is meant for performance reasons so save load on the database
	 * at the developer's request.  If --no-query is specified, then this method will
	 * simply return null.
	 *
	 * @par Example
	 * Given the following query: @code
	 *	array(
	 *		user_id => 10
	 *  )
	 * @code
	 * We have @code
	 * $rec = $app->getRecord();  // Record with user_id 10.
	 * @endcode
	 *
	 * Given the query: @code
	 * array(
	 *     user_id => 10,
	 *     -recordid => 'users?user_id=11'
	 * )
	 * @endcode
	 * We have @code
	 * $rec = $app->getRecord() // Record with user_id 11
	 * @endcode
	 *
	 * 
	 * 
	 */
	function &getRecord(){
		$null = null;
		if ( $this->currentRecord === null ){
			$query =& $this->getQuery();
			if ( @$query['--no-query'] ){
				$null = null;
				return $null;
			}
			$q=array();
			if ( isset($_REQUEST['__keys__']) and is_array($_REQUEST['__keys__']) ){
				foreach ($_REQUEST['__keys__'] as $key=>$val) $q[$key] = '='.$val;
				$this->currentRecord = df_get_record($query['-table'], $q);
			} else if ( isset($_REQUEST['-__keys__']) and is_array($_REQUEST['-__keys__']) ){
				foreach ($_REQUEST['-__keys__'] as $key=>$val) $q[$key] = '='.$val;
				$this->currentRecord = df_get_record($query['-table'], $q);
			} else if ( isset($_REQUEST['--__keys__']) and is_array($_REQUEST['--__keys__']) ){
				foreach ($_REQUEST['--__keys__'] as $key=>$val) $q[$key] = '='.$val;
				$this->currentRecord = df_get_record($query['-table'], $q);
			} else if ( isset($_REQUEST['--recordid']) ){
				$this->currentRecord = df_get_record_by_id($_REQUEST['--recordid']);
			} else if ( isset($_REQUEST['-recordid']) ){
				$this->currentRecord = df_get_record_by_id($_REQUEST['-recordid']);
			} else {
				$rs = $this->getResultSet();
				$this->currentRecord = $rs->loadCurrent();
			}
			if ( $this->currentRecord === null ) $this->currentRecord = -1;
		}
		if ( $this->currentRecord === -1 || !$this->currentRecord ) return $null;
		return $this->currentRecord;
	}
	
	/**
	 * @brief Returns the related record that forms a context for the specified
	 * record id.  A context is provided so that we can tell if a record
	 * is being viewed through the lense of a related record.  This can affect
	 * things like the permissions, bread-crumbs, and other navigation items.
	 * It allows us to tell where we are and where we came from.
	 *
	 * @param string $id The record ID to check for context.
	 * @returns Dataface_RelatedRecord A related record that wraps the record in question.
	 * @since 2.0
	 */
	function getRecordContext($id=null){
		if ( !isset($this->recordContext) ){
			$this->recordContext = array();
			$query = $this->getQuery();
			if ( @$query['-portal-context'] ){
				$rrec = df_get_record_by_id($query['-portal-context']);
				if ( PEAR::isError($rrec) ){
					$rrec = null;
				}
				if ( is_a($rrec, 'Dataface_RelatedRecord') ){
					$destRecords = $rrec->toRecords();
					foreach ($destRecords as $destRec){
						$this->recordContext[$destRec->getId()] = $rrec;
					}
				}
				
			}
		}
		if ( !isset($id) ){
			foreach ($this->recordContext as $rrec) return $rrec;
		} else {
			return @$this->recordContext[$id];
		}
	}
	
	/**
	 * @brief Adds a related record to the current context.  This provides
	 * a lense through which to view the destination records of this related
	 * record so that their permissions are evaluated as if they are part
	 * of the relationship.
	 *
	 * @param Dataface_RelatedRecord $rec The related record to add for context.
	 * @returns void
	 * @since 2.0
	 * @see getRecordContext()
	 * @see clearRecordContext()
	 */
	function addRecordContext(Dataface_RelatedRecord $rec){
		$this->getRecordContext();
		$destRecords = $rec->toRecords();
		foreach ($destRecords as $destRec){
			$this->recordContext[$destRec->getId()] = $rec;
		}
		Dataface_PermissionsTool::addContextMask($rec);
	}
	
	
	/**
	 * @brief Clears the current record context.  The record context is a set
	 * of related records that are meant to be used as a lense through which 
	 * to view any destination records of any related record in the set.
	 *
	 * @since 2.0
	 * @returns void
	 * @see getRecordContext()
	 * @see addRecordContext()
	 */
	function clearRecordContext(){
		$this->recordContext = array();
		$contextMasks =& Dataface_PermissionsTool::getInstance()->getContextMasks();
		foreach ($contextMasks as $k=>$v){
			unset($contextMasks[$k]);
		}
	}
	
	/**
	 * @brief Checks is the current record has been loaded yet.  
	 *
	 * @returns boolean
	 *
	 * @see getRecord()
	 */
	function recordLoaded(){
		return ( $this->currentRecord !== null);
	}
	 
	 
	/**
	 * @brief Gets the settings array for the current action as specified
	 * by the -action parameter of the current query.
	 *
	 * @return array Action parameters (or null if action doesn't exist).
	 */
	function &getAction(){
		import('Dataface/ActionTool.php');
		$actionTool = Dataface_ActionTool::getInstance();
		return $actionTool->getAction(array('name'=>$this->_query['-action']));
	}
	
	/**
	 * @brief Gets the name of the action that should be used as a search target from the given
	 * 	action context.  If $action is omitted, then the current action (specified by the -action
	 *	query parameter) will be used as the current context.
	 *
	 * <p>This method is used by the find form and the search form to figure out which action should
	 * be used to show the search results when performing a find.   Before this method, searches 
	 * would always go to the list view, but as the list of modules grow, there are many other
	 * actions that might be appropriate for showing search results.  Most notably, if you are 
	 * viewing an action that "lists" a found set and you perform a search, you would expect
	 * to remain in the action/view from which you initiated the search.  Previously
	 * they would have been kicked back to list view, which may not be desirable.</p>
	 *
	 * <h3>How Search Target Is Determined</h3>
	 *
	 * <ol>
	 *	<li>If the DelegateClass::getSearchTarget() method is implemented, its result will be 
	 *		used.
	 *	</li>
	 *	<li>If the ApplicationDelegateClass::getSearchTarget() method is implemented, its result
	 *		will be used.
	 *	</li>
	 *	<li>
	 *		If the current action's @c  search_target directive is set, then that value will be used.
	 *	</li>
	 *	<li>If the @c default_search_target directive of the @e conf.ini file is set, then
	 * 		its value will be used.
	 *	</li>
	 *	<li>@c list will be used if nothing else was specified.</li>
	 * </ol>
	 *
	 * @param array $action The action definition associative array, or null.  If null is provided,
	 *	then the current action (as specified by @c $query['-action'] will be used).
	 * @returns string The name of an action to send for search results.
	 *
	 * @since 2.0
	 *
	 */
	function getSearchTarget(array $action=null){
		if ( !isset($action) ){
			$action = $this->getAction();
			
			if ( !isset($action) or !is_array($action)){
				if ( @$this->_conf['default_search_target'] ) return $this->_conf['default_search_target'];
				else return 'list';
			} else {
				return $this->getSearchTarget($action);
			}
		} else {
		
			$table = Dataface_Table::loadTable($this->_query['-table']);
			$tableDel = $table->getDelegate();
			$method = 'getSearchTarget';
			if ( isset($tableDel) and method_exists($tableDel, $method) ){
				return $tableDel->$method($action);
			}
			
			$appDel = $this->getDelegate();
			if ( isset($appDel) and method_exists($appDel, $method) ){
				return $appDel->$method($action);
			}
			
			
			if ( @$action['search_target'] ){
				return $action['search_target'];
			} else {
				if ( @$this->_conf['default_search_target'] ) return $this->_conf['default_search_target'];
				else return 'list';
			}
		
		}
	
	}
	 
	// @}
	// END Request Context
	
	// @{
	/**
	 * @name Session Handling
	 *
	 * Methods and data structures for dealing with Session Handling and authentication.
	 *
	 */
	 
	 
	/**
	 * @brief Sets a message to be displayed as an info/alert the next time a 
	 * page is rendered.  This is handy if your action is performing some 
	 * funcitons and then redirecting to a new page on complete - and you
	 * want the message to be displayed on the other page.
	 *
	 * @param string $str The message that should be displayed.
	 * @returns void
	 *
	 * @par Example
	 * @code
	 * $app->saveMessage("The record was saved successfully.");
	 * @endcode
	 */
	function saveMessage($str){
		$_SESSION['--msg'] = $str;
	}
	
	/**
	 * @brief Sets the cookie that causes sessions to be enabled by default.  
	 * In order to maximize performance Xataface will try not to start a
	 * session until it absolutely has to .  This allows public sites
	 * to not rack up huge amounts of Session files unnecessarily.
	 *
	 * @see disableSessions()
	 * @see sessionsEnabled()
	 */
	function enableSessions(){
		setcookie($this->sessionCookieKey, 1, 0, DATAFACE_SITE_URL);
	}
	
	/**
	 * @brief Unsets the cookie that causes sessions to be enabled by default.  Despite
	 * the name, this doesn't actually disable sessions.  Sessions will still be enabled
	 *	when they are needed, eg for login.  This will just enable them always by default.
	 *
	 * @see enableSessions()
	 * @see sessionEnabled()
	 * @see startSession()
	 */
	function disableSessions(){
		setcookie($this->sessionCookieKey, 1, time()-3600*25, DATAFACE_SITE_URL);
	}
	
	/**
	 * @brief Checks if sessions are enabled by default.
	 * @returns boolean
	 *
	 * @see enableSessions()
	 * @see disableSessions()
	 * @see startSession()
	 */
	function sessionEnabled(){
		return @$_COOKIE[$this->sessionCookieKey];
	}
	
	/**
	 * @brief Starts a session if one does not already exist.  If you are writing code
	 * that needs to use session data it is a good idea to explicitly call this before 
	 * doing anything with the $_SESSION array.  It is safe to call this multiple times.
	 *
	 * @param array $conf Optional configuration data that should follow the format
	 * of $app->_conf['_auth']
	 *
	 * @see enableSessions()
	 * @see disableSessions()
	 */
	function startSession($conf=null){
		if ( defined('XATAFACE_NO_SESSION') and XATAFACE_NO_SESSION ) return;
		//echo "In startSession()";
		if ( !$this->sessionEnabled() ){
			$this->enableSessions();
		}
		if ( session_id() == "" ){
			if ( !isset($conf) ){
				if ( isset($this->_conf['_auth']) ) $conf = $this->_conf['_auth'];
				else $conf = array();
			}
			
			$delegate =& $this->getDelegate();
			if ( isset($delegate) and method_exists($delegate, 'startSession') ){
				$delegate->startSession($conf);
			} else {
				
				// path for cookies
				$parts = parse_url(DATAFACE_SITE_URL);
				$cookie_path = $parts['path'];
				if ( isset($conf['cookie_path']) ){
					$cookie_path = $conf['cookie_path'];
					if ( substr($cookie_path,0,4) == 'php:' ){
						$cookie_path_expr = substr($cookie_path,4);
						eval('$cookie_path = '.$cookie_path_expr.';');
					}
				}
				
				if (strlen($cookie_path)==0) $cookie_path = '/';
				if ( $cookie_path{strlen($cookie_path)-1} != '/' ) $cookie_path .= '/';
				
				// timeout value for the cookie
				$cookie_timeout = (isset($conf['session_timeout']) ? intval($conf['session_timeout']) : 24*60*60);
				
				
				// timeout value for the garbage collector
				//   we add 300 seconds, just in case the user's computer clock
				//   was synchronized meanwhile; 600 secs (10 minutes) should be
				//   enough - just to ensure there is session data until the
				//   cookie expires
				$garbage_timeout = $cookie_timeout + 600; // in seconds
				
				// set the PHP session id (PHPSESSID) cookie to a custom value
				session_set_cookie_params($cookie_timeout, $cookie_path);
				
				// set the garbage collector - who will clean the session files -
				//   to our custom timeout
				ini_set('session.gc_maxlifetime', $garbage_timeout);
				if ( isset($conf['session_timeout']) and ini_get('session.save_handler') == 'files' ){
					// we need a distinct directory for the session files,
					//   otherwise another garbage collector with a lower gc_maxlifetime
					//   will clean our files aswell - but in an own directory, we only
					//   clean sessions with our "own" garbage collector (which has a
					//   custom timeout/maxlifetime set each time one of our scripts is
					//   executed)
					strstr(strtoupper(substr(@$_SERVER["OS"], 0, 3)), "WIN") ? 
						$sep = "\\" : $sep = "/";
					$sessdir = session_save_path(); //ini_get('session.save_path');
					$levels = '';
					if (strpos($sessdir, ";") !== FALSE){
						$levels = substr($sessdir, 0, strpos($sessdir, ";")).';';
						 $sessdir = substr($sessdir, strpos($sessdir, ";")+1);
					}
					if ( !$sessdir ) $sessdir = sys_get_temp_dir(); //'/tmp';
					if ( $sessdir and $sessdir{strlen($sessdir)-1} == '/' ) $sessdir = substr($sessdir,0, strlen($sessdir)-1);
					
					if ( @$conf['subdir'] ) $subdir = $conf['subdir'];
					else $subdir = md5(DATAFACE_SITE_PATH);
					if ( !$subdir ) $subdir = 'dataface';
					$sessdir .= "/".$subdir;
					
			
					if (!is_dir($sessdir)) { 
						$res = @mkdir($sessdir, 0777);
						if ( !$res ){
							error_log("Failed to create session directory '$sessdir' to store session files in ".__FILE__." on line ".__LINE__);
							
						}
					}
					if (is_dir($sessdir) ){
						session_save_path($sessdir);
					} else {
					}
				} else {
					// We need to set a unique session name if we're not changing the directory
					if ( !@$conf['session_name'] ){
						$conf['session_name'] = md5(DATAFACE_SITE_PATH);
					}
				}
				if ( @$conf['session_name'] ) session_name($conf['session_name']);
				//echo "Starting session with ".session_name();
				session_start();	// start the session
				header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"');
				
				// This updates the session timeout on page load
				if ( isset($_COOKIE[session_name()]) ){
					setcookie(session_name(), $_COOKIE[session_name()], time() + $cookie_timeout, $cookie_path);
				}
			}
		} else {
			//echo "Session already started";
		}
		
		if ( isset( $_SESSION['--msg'] ) ){
			$this->addMessage($_SESSION['--msg']);
			unset($_SESSION['--msg']);
		}
	
	
	}
	
	/**
	 * @private
	 */
	function writeSessionData(){
	
		if ( isset($this->locations) ) $_SESSION['locations'] = serialize($this->locations);
	}
	
	/**
	 * @private
	 */
	function encodeLocation($url){
		if ( !isset($this->locations) and isset($_SESSION['locations']) ) $this->locations = unserialize($_SESSION['locations']);
		else if ( !isset($this->locations) ) $this->locations = array();
		$key = md5($url);
		$this->locations[$key] = $url;
		return $key;
	}
	
	/**
	 * @private
	 */
	function decodeLocation($key){
		if ( !isset($this->locations) and isset($_SESSION['locations']) ) $this->locations = unserialize($_SESSION['locations']);
		else if ( !isset($this->locations) ) $this->locations = array();
		
		if ( isset($this->locations[$key]) ){
			$url = $this->locations[$key];
			unset($this->locations[$key]);
			return $url;
		
		} else {
			return null;
		}
	
	}
	 
	 
	 /**
	 * @brief Obtains reference to the authentication tool.
	 *
	 * @returns Dataface_AuthenticationTool
	 *
	 */
	function &getAuthenticationTool(){
		$null = null;
		if ( !isset($this->authenticationTool) ){
			
			if ( isset($this->_conf['_auth']) ){
				import('Dataface/AuthenticationTool.php');
				$this->authenticationTool = Dataface_AuthenticationTool::getInstance($this->_conf['_auth']);
			} else {
				return $null;
			}
		}
			
		return $this->authenticationTool;
	}
	
	
	 
	 
	 
	// @}
	// END Session Handling
	//=====================================================================================================
	
	// @{
	/**
	 * @name Template & UI Interaction
	 *
	 * Methods for customizing the output.  This includes error messages, and inclusion
	 * of content in the head of the document.
	 *
	 */
	 
	 
	/**
	 * @brief Adds some content meant to be inserted in the head of the application.
	 * @param string $content
	 * @returns void
	 *
	 * @par Example
	 * Adding A CSS stylesheet in the &lt;head&gt; of the page
	 * @code
	 * $app->addHeadContent('<link rel="stylesheet" type="text/css" href="styles.css"/>');
	 * @endcode
	 * 
	 *
	 * @attention If possible, you should try to use the @ref Dataface_JavascriptTool
	 * class for adding javascripts and CSS stylesheets to your application's output.
	 *
	 * @since 1.0
	 * @see Dataface_JavascriptTool
	 * @see Dataface_CSSTool
	 *
	 */
	function addHeadContent($content){
		$this->headContent[] = $content;
	}
	
	
	/**
	 * @brief Returns the nav item info for a key.  This is a wrapper around the nav items defined
	 * in the [_tables] section of the conf.ini file.
	 *
	 * This can be overridden using the Application Delegate class method of the same name.
	 *
	 * @param string $key The key of the nav item.  This would be the table name if using the 
	 *		traditional simple table nav items.
	 *
	 * @param string $label The label for the nav item.  This would be the table label if using
	 *		the traditional simple table nav items.
	 *
	 * @returns array @code
	 *		href => The URL where the nav item is to link
	 * 		label => The label for the nav item.
	 *		selected => boolean value indicating whether the item is currently selected.
	 * @endcode
	 *
	 * @see isNavItemSelected()
	 */
	function getNavItem($key, $label=null){
		$del =& $this->getDelegate();
		$override = array();
		if ( isset($del) and method_exists($del, 'getNavItem') ){
			try {
				$override = $del->getNavItem($key, $label?$label:$key);
			} catch (Exception $ex){}
		}
		if ( !isset($override) ){
			return $override;
		}
		return array_merge(array(
			'href'=> DATAFACE_SITE_HREF.'?-table='.urlencode($key),
			'label'=> $label ? $label:$key,
			'selected' => $this->isNavItemSelected($key)
		), $override);
	}
	
	
	/**
	 * @brief Checks whether the specified nav item is currently selected.  This is used
	 * by the default implementation of getNavItem() and it an be used also in
	 * custom implementations.  It can also be overridden by the application delegate
	 * class method of the same name.
	 *
	 * @param string $key The nav item key.  Traditionally the table name if using simple
	 *		table navigation items.
	 *
	 * @returns boolean True if the item is meant to be selected.
	 *
	 * @see getNavItem()
	 * @see ApplicationDelegateClass::isNavItemSelected()
	 *           
	 */
	function isNavItemSelected($key){
		$del =& $this->getDelegate();
		if ( isset($del) and method_exists($del, 'isNavItemSelected') ){
			try {
				return $del->isNavItemSelected($key);
			} catch (Exception $ex){}
		}
		$query =& $this->getQuery();
		return ($query['-table'] == $key);
	}
	
	
	
	/**
	 * @brief Adds an error to be displayed in the UI in the messages block.
	 * @param PEAR_Error $err The error that is being added.
	 *
	 * @returns void
	 * 
	 * @see numErrors()
	 * @see getErrors()
	 * @see addMessage() To add string messages instead of Error objects.
	 */
	function addError($err){
		$this->errors[] = $err;
	}
	
	/**
	 * @brief Returns the number of errors that are to be displayed to the user in the
	 * messages block.
	 * @returns int
	 *
	 * @see addError()
	 * @see getErrors()
	 */
	function numErrors(){ return count($this->errors); }
	
	/**
	 * @brief Returns an array of the errors that are set to be displayed to the user
	 * in the messages block.
	 * @returns PEAR_Error[]
	 * @see addError()
	 * @see numErrors()
	 */
	function getErrors(){
		return $this->errors;
	}
	
	/**
	 * @brief Adds a message to be displayed in the messages block.
	 *
	 * @param string $msg The messag to be displayed.
	 * @returns void
	 *
	 * @see addError()
	 */
	function addMessage($msg){
		$this->messages[] = $msg;
	}
	
	/**
	 * @brief Gets the messages that are to be displayed in the messages block.  This 
	 * will look in multiple sources for possible messages to display.  It will include
	 * the following:
	 * -# $_SESSION['msg']
	 * -# $app->messages
	 * -# $app->response['--msg']
	 *
	 * @returns string[]
	 *
	 */
	function getMessages(){
		if ( trim(@$_SESSION['msg']) ){
			array_push($this->messages, $_SESSION['msg']);
			unset($_SESSION['msg']);
		}
		$msgs = $this->messages;
		$response = $this->getResponse();
		if ( @$response['--msg'] ){
			array_push($msgs, $response['--msg']);
		}
		//print_r($msgs);
		return $msgs;
	}
	
	/**
	 * @brief Clears all of the message to be displayed.
	 * @returns void
	 */
	function clearMessages(){
		$this->messages = array();
	}
	
	/**
	 * @brief Returns the number of messages to be displayed to the user.
	 * @returns int
	 */
	function numMessages(){
		$count = count($this->messages);
		$response = $this->getResponse();
		if ( @$response['--msg'] ) $count++;
		return $count;
	}
	
	
	/**
	 * @brief Returns the response array used for compiling response.  The response may include
	 * messages that need to be displayed to the screen as an alert.  Currently the response
	 * array only includes a single key: --msg
	 *
	 * @returns array A response array with the following keys: @code
	 *	--msg => <string> 
	 * @endcode
	 *
	 */
	public static function &getResponse(){
		static $response = 0;
		if ( !$response ){
			$response = array('--msg'=>'');
		}
		return $response;
	}
	 
	 
	// @}
	// END Template & UI Interaction
	//====================================================================================
	
	// {@
	/**
	 * @name Event Handling
	 *
	 * Methods for dealing with the dispatch of events.
	 */
	 
	 
	 
	 
	/**
	 * @brief Fires an event to all event listeners.
	 * @param string $name The name of the event. e.g. afterInsert
	 * @param array $params Array of parameters to pass to the event listener.
	 * @returns mixed Result of event.  May be PEAR_Error if the event throws an error.
	 *
	 * @see registerEventListener()
	 */
	function fireEvent($name, $params=null){
		$listeners = $this->getEventListeners($name);
		foreach ($listeners as $listener){
			$res = call_user_func($listener, $params);
			if ( PEAR::isError($res) ) return $res;
		}
		return true;
	}
	
	/**
	 * @brief Registers an event listener to respond to events of a certain type.
	 * @param string $name The name of the event to register for. e.g. afterInsert
	 * @param mixed $callback A standard PHP callback.  Either a function name or an array of the form array(&$object,'method-name').
	 * @returns void.
	 *
	 * @see fireEvent()
	 * @see unregisterEventListener()
	 *
	 */
	function registerEventListener($name, $callback){
		if ( !isset($this->eventListeners[$name]) ) $this->eventListeners[$name] = array();
		$this->eventListeners[$name][] = $callback;
	}
	
	
	/**
	 * @brief Unregisters an event listener.
	 *
	 * @see registerEventListener()
	 * @see fireEvent()
	 */
	function unregisterEventListener($name, $callback){
		if ( isset($this->eventListeners[$name]) ){
			$listeners =& $this->eventListeners[$name];
			foreach ( $listeners as $key=>$listener ){
				if ( $listener == $callback ) unset($listeners[$key]);
			}
		}
	}
	
	/**
	 * @brief Gets a list of the callbacks that are registered for a given event.
	 * @param $name The name of the event for which the callbacks are registered.
	 * @returns array Either an array of callbacks for the event.  Or associative array of array of callbacks for all events with the key on the event name.
	 */
	function getEventListeners($name=null){
		if ( !isset($name) ) return $this->eventListeners;
		else if (isset($this->eventListeners[$name])){
			return $this->eventListeners[$name];
		} else {
			return array();
		}
	}
	
	 
	 
	// @}
	// END Event Handling
	//=====================================================================================
	
	
	
	// @{
	/**
	 * @name Request Handling
	 *
	 * Methods for handling the main requests.  These methods are responsible for doing
	 * the heaving lifting of displaying a page.
	 */
	 
	 
	 

	/**
	 * @brief Handle a request.  This method is the starting point for all Dataface application requests.
	 * It will delegate the request to the appropriate handler.
	 * The order of delegation is as follows:
	 *  -# Uses the ActionTool to check permissions for the action.  If permissions are not granted,
	 *		dispatch the error handler.  If permissions are granted then we continue down the delegation
	 *		chain.
	 *  -# If the current table's delegate class defines a handleRequest() method, then call that.
	 *	-# If the current table's delegate class does not have a handleRequest() method or that method
	 *		returns a PEAR_Error object with code E_DATAFACE_REQUEST_NOT_HANDLED, then check for a handler
	 *		bearing the name of the action in one of the actions directories.  Check the directories 
	 *		in the following order:
	 *		a. <site url>/tables/<table name>/actions
	 *		b. <site url>/actions
	 *		b. <dataface url>/actions
	 *
	 * @param boolean $disableCache Whether to disable the cache or not for this request.
	 *
	 * @see ApplicationDelegateClass::beforeHandleRequest()
	 */
	function handleRequest($disableCache=false){
		
		
		if ( !$disableCache and (@$_GET['-action'] != 'getBlob') and isset( $this->_conf['_output_cache'] ) and @$this->_conf['_output_cache']['enabled'] and count($_POST) == 0){
			import('Dataface/OutputCache.php');
			$oc = new Dataface_OutputCache($this->_conf['_output_cache']);
			$oc->ob_start();
			
		}
		import('Dataface/ActionTool.php');
		import('Dataface/PermissionsTool.php');
		import('Dataface/Table.php');
		
		if ( isset($this->_conf['_modules']) and count($this->_conf['_modules']) > 0 ){
			$mt = Dataface_ModuleTool::getInstance();
			foreach ($this->_conf['_modules'] as $modname=>$modpath){
				$mt->loadModule($modname);
				
			}
		}
		
		$this->fireEvent('beforeHandleRequest');
		$applicationDelegate = $this->getDelegate();
		if ( isset($applicationDelegate) and method_exists($applicationDelegate, 'beforeHandleRequest') ){
			// Do whatever we need to do before the request is handled.
			$applicationDelegate->beforeHandleRequest();
		}
		
		// Set up security filters
		$query =& $this->getQuery();
		$table = Dataface_Table::loadTable($query['-table']);

		//$table->setSecurityFilter();
		/*
		 * Set up some preferences for the display of the application.
		 * These can be overridden by the getPreferences() method in the
		 * application delegate class.
		 */
		if ( isset($this->_conf['_prefs']) and is_array($this->_conf['_prefs']) ){
			$this->prefs = array_merge($this->prefs,$this->_conf['_prefs']);
		}
		if ( @$this->_conf['hide_nav_menu'] ){
			$this->prefs['show_tables_menu'] = 0;
		}
		
		if ( @$this->_conf['hide_view_tabs'] ){
			$this->prefs['show_table_tabs'] = 0;
		}
		
		if ( @$this->_conf['hide_result_controller'] ){
			$this->prefs['show_result_controller'] = 0;
		}
		
		if ( @$this->_conf['hide_table_result_stats'] ){
			$this->prefs['show_result_stats'] = 0;
		}
		
		if ( @$this->_conf['hide_search'] ){
			$this->prefs['show_search'] = 0;
		}
		
		if ( !isset($this->prefs['disable_ajax_record_details']) ){
			$this->prefs['disable_ajax_record_details'] = 1;
		}
		
		if ( $query['-action'] == 'login_prompt' ) $this->prefs['no_history'] = 1;
		
		
		if ( isset($applicationDelegate) and method_exists($applicationDelegate, 'getPreferences') ){
			$this->prefs = array_merge($this->prefs, $applicationDelegate->getPreferences());
		}
		$this->prefs = array_map('intval', $this->prefs);
		
		// Check to make sure that this table hasn't been disallowed
		$disallowed = false;
		if ( isset($this->_conf['_disallowed_tables']) ){
			foreach ( $this->_conf['_disallowed_tables'] as $name=>$pattern ){
				if ( $pattern{0} == '/' and preg_match($pattern, $query['-table']) ){
					$disallowed = true;
					break;
				} else if ( $pattern == $query['-table'] ){
					$disallowed = true;
					break;
				}
			}
		}
		
		if ( $disallowed and isset($this->_conf['_allowed_tables']) ){
			foreach ($this->_conf['_allowed_tables'] as $name=>$pattern ){
				if ( $pattern{0} == '/' and preg_match($pattern, $query['-table']) ){
					$disallowed = false;
					break;
				} else if ( $pattern == $query['-table'] ){
					$disallowed = false;
					break;
				}
			}
		}
		
		
		if ( $disallowed ){
			return Dataface_Error::permissionDenied(
				Dataface_LanguageTool::translate(
					/*i18n id*/
					"Permission Denied. This table has been disallowed in the conf.ini file",
					/* default error message */
					"Permission denied because this table has been disallowed in the conf.ini file '"
				)
			);
			
		}
		
		
		$actionTool = Dataface_ActionTool::getInstance();
		
		//if ( $this->_conf['multilingual_content'] ){
			//import('I18Nv2/I18Nv2.php');
     		//I18Nv2::autoConv();
     	//}
		
		$params = array(
			'table'=>$query['-table'],
			'name'=>$query['-action']);
		if ( strpos($query['-action'], 'custom_') === 0 ){
			$action = array(
				'name' => $query['-action'],
				'page' => substr($query['-action'], 7),
				'permission' => 'view',
				'mode' => 'browse',
				'custom' => true
				);
		} else {
			$action = $actionTool->getAction($params);
			if ( is_array($action)  and @$action['related'] and @$query['-relationship'] and preg_match('/relationships\.ini/', @$action['allow_override']) ){
				// This action is to be performed on the currently selected relationship.
				$raction = $table->getRelationshipsAsActions(array(), $query['-relationship']);
				if ( is_array($raction) ){
					$action = array_merge($action,$raction); 
				}
			}
			if ( is_array($action) and isset($action['delegate']) ){
				$params['name'] = $query['-action'] = $action['delegate'];
				$tmp = $actionTool->getActions($params);
				unset($action);
				$action =& $tmp;
				unset($tmp);
			} 
			if ( is_array($action) and isset($action['auth_type']) ){
				$authTool = $this->getAuthenticationTool();
				$authTool->setAuthType($action['auth_type']);
			}
			
		}
	
	
		if ( (PEAR::isError($action) or !@$action['permission']) and $this->_conf['security_level'] >= DATAFACE_STRICT_PERMISSIONS ){
			
                        // The only reason getAction() will return an error is if the specified action could not be found.
			// If the application is set to use strict permissions and no action was defined in the ini file
			// then this action cannot be performed.  Strict permissions mode requires that permissions be 
			// strictly set or permission will be denied.
			return Dataface_Error::permissionDenied(
				Dataface_LanguageTool::translate(
					/*i18n id*/
					"Permission Denied. No action found in strict permissions mode",
					/* default error message */
					"Permission denied for action '".
						$query['-action'].
					"'.  No entry for this action was found in the actions.ini file.  
					You are currently using strict permissions mode which requires that you define all actions that you want to use in the actions.ini file with appropriate permissions information.", 
					/* i18n parameters */
					array('action'=>$query['-action'])
				)
			);
			
		} 
		
		else if ( PEAR::isError($action) ){
			$action = array('name'=>$query['-action'], 'label'=>$query['-action']);
		}
		
		// Step 1:  See if the delegate class has a handler.
		
		$delegate = $table->getDelegate();
		$handled = false;
		if ( method_exists($delegate,'handleRequest') ){
			$result = $delegate->handleRequest();
			if ( PEAR::isError($result) and $result->getCode() === DATAFACE_E_REQUEST_NOT_HANDLED ){
				$handled = false;
			} else if ( PEAR::isError($result) ){
				return $result;
			} else {
				$handled = true;
			}
		}
		if ( isset($action['mode']) and $action['mode'] ) $query['-mode'] = $action['mode'];
		
		// Step 2: Look to see if there is a handler defined
		if ( isset($action['custom']) ){
			$locations = array( DATAFACE_PATH.'/actions/custom.php'=>'dataface_actions_custom');
		} else {
			$locations = array();
			
			$locations[ Dataface_Table::getBasePath($query['-table']).'/tables/'.basename($query['-table']).'/actions/'.basename($query['-action']).'.php' ] = 'tables_'.$query['-table'].'_actions_'.$query['-action'];
			$locations[ DATAFACE_SITE_PATH.'/actions/'.basename($query['-action']).'.php' ] = 'actions_'.$query['-action'];
			
			if ( isset($this->_conf['_modules']) and count($this->_conf['_modules']) > 0 ){
				$mt = Dataface_ModuleTool::getInstance();
				foreach ($this->_conf['_modules'] as $modname=>$modpath){
					$mt->loadModule($modname);
					if ( $modpath{0} == '/' )
						$locations[ dirname($modpath).'/actions/'.basename($query['-action']).'.php' ] = 'actions_'.$query['-action'];
					else {
						$locations[ DATAFACE_SITE_PATH.'/'.dirname($modpath).'/actions/'.basename($query['-action']).'.php' ] = 'actions_'.$query['-action'];
						$locations[ DATAFACE_PATH.'/'.dirname($modpath).'/actions/'.basename($query['-action']).'.php' ] = 'actions_'.$query['-action'];
					}
				}
			}
			
			$locations[ DATAFACE_PATH.'/actions/'.basename($query['-action']).'.php' ] = 'dataface_actions_'.$query['-action'];
			$locations[ DATAFACE_PATH.'/actions/default.php' ] = 'dataface_actions_default';
				
		}
		$doParams = array('action'=>&$action);
			//parameters to be passed to the do method of the handler
			
		
		foreach ($locations as $handlerPath=>$handlerClassName){
			if ( is_readable($handlerPath) ){
				import($handlerPath);
				$handler = new $handlerClassName;
				$params  = array();
				if ( is_array($action) and @$action['related'] and @$query['-relationship'] ){
					$params['relationship'] = $query['-relationship'];
				}
				if ( !PEAR::isError($action) and method_exists($handler, 'getPermissions') ){
					// check the permissions on this action to make sure that we are 'allowed' to perform it
					// this method will return an array of Strings that are names of permissions granted to
					// the current user.
					
					
					//echo "Checking permissions:";
					//print_r($params);
					$permissions = $handler->getPermissions($params);
				//} else if ( $applicationDelegate !== null and method_exists($applicationDelegate, 'getPermissions') ){
				//	$permissions =& $applicationDelegate->getPermissions($params);
					
			
				
				} else {
					//print_r($params);
					//print_r($action);
					$permissions = $this->getPermissions($params);
				}
				
				if ( isset($action['permission']) && !(isset($permissions[$action['permission']]) and $permissions[$action['permission']]) ){
				
                                    if ( !$permissions ){
                                        return Dataface_Error::permissionDenied(df_translate(
                                            "Permission Denied for action no permissions",
                                            "Permission to perform action '".$action['name']."' denied. ".
                                            "Requires permission ".$action['permission']." but you currently ".
                                            " have no permissions granted.",
                                            array('action' => $action)
                                        ));
                                    } else {
                                    
                                        return Dataface_Error::permissionDenied(
                                                    Dataface_LanguageTool::translate(
                                                            "Permission Denied for action.", /*i18n id*/
                                                            /* Default error message */
                                                            "Permission to perform action '".
                                                            $action['name'].
                                                            "' denied.  
                                                            Requires permission '".
                                                            $action['permission'].
                                                            "' but only granted '".
                                                            Dataface_PermissionsTool::namesAsString($permissions)."'.", 
                                                            /* i18n parameters */
                                                            array('action'=>$action, 'permissions_granted'=>Dataface_PermissionsTool::namesAsString($permissions))
                                                    )
                                            );
                                    }
				
				}
				
				if ( method_exists($handler, 'handle') ){
					
					
					$result = $handler->handle($doParams);
					if ( PEAR::isError($result) and $result->getCode() === DATAFACE_E_REQUEST_NOT_HANDLED ){
						continue;
					}
					return $result;
				}
				
				
			}
			
		}
		
		throw new Exception(df_translate('scripts.Dataface.Application.handleRequest.NO_HANDLER_FOUND',"No handler found for request.  This should never happen because, at the very least, the default handler at dataface/actions/default.php should be called.  Check the permissions on dataface/actions/default.php to make sure that it is readable by the web server."), E_USER_ERROR);
		
		
		
	
	}
	 
	 
	 
	/**
	 * @brief Displays the Dataface application.
	 *
	 * @param boolean $main_content_only Whether to only show the main content or to show the full page with header and 
	 *		footer.  This parameter is not respected by many of the current templates and may be removed in later releases.
	 *
	 * @param boolean $disableCache Whether to disable the output cache.  It is enabled by default.
	 *
	 * @par Flow Chart
	 *
	 * <img src="http://media.weblite.ca/files/photos/Display_flow_control.png?max_width=640"/>
	 * <a href="http://media.weblite.ca/files/photos/Display_flow_control.png" target="_blank" title="Enlarge">Enlarge</a>.
	 */
	function display($main_content_only=false, $disableCache=false){
		// ---------------- Set the Default Character set for output -----------
		foreach ($this->_tables as $key=>$value){
			$this->_tables[$key] = $this->_conf['_tables'][$key] = df_translate('tables.'.$key.'.label', $value);
		}
		
		$this->main_content_only = $main_content_only;
		if ( $this->autoSession or $this->sessionEnabled() ){
			$this->startSession();
		}
		if ( isset($this->_conf['disable_session_ip_check']) and !@$this->_conf['disable_session_ip_check'] ){
			if ( !@$_SESSION['XATAFACE_REMOTE_ADDR'] ){
				$_SESSION['XATAFACE_REMOTE_ADDR'] = df_IPv4To6($_SERVER['REMOTE_ADDR']);
			}
			$ipAddressError = null;
			if ( df_IPv4To6($_SESSION['XATAFACE_REMOTE_ADDR']) != df_IPv4To6($_SERVER['REMOTE_ADDR']) ){
				$msg = sprintf(
					"Session address does not match the remote address.  Possible hacking attempt.  Session address was '%s', User address was '%s'",
					df_escape(df_IPv4To6($_SESSION['XATAFACE_REMOTE_ADDR'])),
					df_escape(df_IPv4To6($_SERVER['REMOTE_ADDR']))
				);
				error_log($msg);
				//die('Your IP address doesn\'t match the session address.  To continue, please clear your cookies or restart your browser and try again.');
				session_destroy();
				$this->startSession();
				if ( !@$_SESSION['XATAFACE_REMOTE_ADDR'] ){
					$_SESSION['XATAFACE_REMOTE_ADDR'] = df_IPv4To6($_SERVER['REMOTE_ADDR']);
				}
				
			}
		}
		// handle authentication
		if ( !(defined('XATAFACE_DISABLE_AUTH') and XATAFACE_DISABLE_AUTH) and isset($this->_conf['_auth']) ){
			// The config file _auth section is there so we will be using authentication.
	
			$loginPrompt = false;	// flag to indicate if we should show the login prompt
			$permissionDenied = false;// flag to indicate if we should show permission denied
			$permissionError = ''; //Placeholder for permissions error messages
			$loginError = ''; // Placeholder for login error messages.
			
			$authTool = $this->getAuthenticationTool();
			
			$auth_result = $authTool->authenticate();
			
			if ( PEAR::isError($auth_result) and $auth_result->getCode() == DATAFACE_E_LOGIN_FAILURE ){
				// There was a login failure, show the login prompt
				$loginPrompt = true;
				$loginError = $auth_result->getMessage();
			} else if ( $authTool->isLoggedIn() ){
				// The user is logged in ok
				// Handle the request
				$result = $this->handleRequest();
				if ( Dataface_Error::isPermissionDenied($result) ){
					// Permission was denied on the request.  Since the user is already
					// logged in, there is no use giving him the login prompt.  Just give
					// him the permission denied screen.
					$permissionDenied = true;
					$permissionError = $result->getMessage();
				}
			} else if ( isset($this->_conf['_auth']['require_login']) and $this->_conf['_auth']['require_login'] ){
				// The user is not logged in and login is required for this application
				// Show the login prompt
				$loginPrompt = true;

			} else {
				// The user is not logged in, but login is not required for this application.
				// Allow the user to perform the action.

				$result = $this->handleRequest($disableCache);
				if ( Dataface_Error::isPermissionDenied($result) ){
					// The user did not have permission to perform the action
					// Give the user a login prompt.
					
					$loginPrompt = true;
				}
				
			}
			if ( $loginPrompt ){
				// The user is supposed to see a login prompt to log in.
				// Show the login prompt.
				
				$authTool->showLoginPrompt($loginError);
			} else if ($permissionDenied) {
				// The user is supposed to see the permissionm denied page.
				$query =& $this->getQuery();
				
				if ( $query['--original_action'] == 'browse' and $query['-action'] != 'view' ){
					$this->redirect($this->url('-action=view'));
				}
				$this->addError($result);
				header("HTTP/1.1 403 Permission Denied");
				df_display(array(), 'Dataface_Permission_Denied.html');
			} else if ( PEAR::isError($result) ){
				// Some other error occurred in handling the request.  Just show an
				// ugly stack trace.
				
				throw new Exception($result->toString().$result->getDebugInfo(), E_USER_ERROR);
			}
		} else {
			// Authentication is not enabled for this application.
			// Just process the request.
			
			$result = $this->handleRequest($disableCache);
			if ( Dataface_Error::isPermissionDenied($result) ){
				$query =& $this->getQuery();
				
				if ( $query['--original_action'] == 'browse' and $query['-action'] != 'view' ){
					$this->redirect($this->url('-action=view'));
				}
				$this->addError($result);
				header("HTTP/1.1 403 Permission Denied");
				df_display(array(), 'Dataface_Permission_Denied.html');
			} else if ( PEAR::isError($result) ){
				
				throw new Exception($result->toString().$result->getDebugInfo(), E_USER_ERROR);
			}
		}
	
	}
	
	/**
	 *
	 * @brief Blob requests are ones that only want the content of a blob field in the database.
	 * These requests are special in that they will not generally return a content-type of
	 * text/html.  These are often images.
	 *
	 * @param $request  A reference to the global $_REQUEST variable generally.
	 * @private
	 */
	function _handleGetBlob($request){
		import('Dataface/Application/blob.php');
		return Dataface_Application_blob::_handleGetBlob($request);
	}
	
	 
	// @}
	// END Request Handling
	//======================================================================================
	
	
	// @{
	/**
	 * @name Utility Functions
	 *
	 * Useful functions that are informed by the current context to provide useful 
	 * functionality to the application as a whole.
	 */
	
	var $_parseStringContext=array();
	/**
	 * @brief Evaluates a string expression replacing PHP variables with appropriate values
	 * in the current record.
	 * @param string $expression A string containing PHP variables that need to be evaluated.
	 * @param Dataface_Record $context A Dataface_Record, Dataface_RelatedRecord object, or array whose values are treated as local
	 *		  variables when evaluating the expression.
	 *
	 * @par Example expressions:
	 *		'${site_href}?-table=Profiles&ProfileID==${ProfileID}'
	 *			-- in the above example, ${site_href} would be replaced with the url (including 
	 *				script name) of the site, and ${ProfileID} would be replaced with
	 *				the value of the ProfileID field in the current record.
	 *
	 * @par Expression Context
	 * The following variables are set up in the context and can be used in the expression:
	 * <table>
	 * 		<tr>
	 *			<th>Variable</th><th>Type</th><th>Description</th>
	 *		<tr>
	 *			<td>$site_url</td><td>String</td>
	 *			<td>The Site URL (i.e. DATAFACE_SITE_URL).  This includes the URL
	 *				to the folder containing the application.  It doesn't include the 
	 *				index.php file.
	 *			</td>
	 *		</tr>
	 *		<tr>
	 *			<td>$site_href</td><td>String</td>
	 *			<td>The Site HREF (i.e. DATAFACE_SITE_HREF).  This is the URL
	 *				to the index.php file.
	 *			</td>
	 *		</tr>
	 *		<tr>
	 *			<td>$dataface_url</td><td>String</td>
	 *			<td>The URL to the Xataface directory.</td>
	 *		</tr>
	 *		<tr>
	 *			<td>$table</td><td>String</td>
	 *			<td>The name of the current table.</td>
	 *		</tr>
	 *		<tr>
	 *			<td>$tableObj</td><td>@ref Dataface_Table</td>
	 *			<td>Reference to the Dataface_Table object encapsulating the current table.</td>
	 *		</tr>
	 *		<tr>
	 *			<td>$query</td><td>array</td>
	 *			<td>The current query array.  
	 *				See @ref getQuery()
	 *			</td>
	 *		</tr>
	 *		<tr>
	 *			<td>$resultSet</td><td>@ref Dataface_QueryTool</td>
	 *			<td>The current query result set.  
	 *				See @ref getResultSet()
	 *			</td>
	 *		</tr>
	 *		<tr>
	 *			<td>$record</td><td>@ref Dataface_Record</td>
	 *			<td>The current record.  This may be obtained from the 'record' key of the $context parameter.  If omitted, then this will be the result of getRecord()</td>
	 *		</tr>
	 *		<tr>
	 *			<td>$relationship</td><td>@ref Dataface_Relationship</td>
	 *			<td>The current relationship.  This may be passed as the 'relationship' key of the $context parameter.  If omitted then it is loaded from the current table and the $query['-relationship'] parameter.</td>
	 *		</tr>
	 *	</table>
	 *
	 *
	 */
	function parseString($expression, $context=null){
		// make sure that the expression doesn't try to break the double quotes.
		if ( strpos($expression, '"') !== false ){
			throw new Exception(
				df_translate(
					'scripts.Dataface.Application.parseString.ERROR_PARSING_EXPRESSION_DBL_QUOTE',
					"Invalid expression (possible hacking attempt in Dataface_Application::eval().  Expression cannot include double quotes '\"', but recieved '".$expression."'.",
					array('expression'=>$expression))
					, E_USER_ERROR);
		}
 
		$site_url = DATAFACE_SITE_URL;
		$site_href = DATAFACE_SITE_HREF;
		$dataface_url = DATAFACE_URL;
		$table = $this->_currentTable;
		$tableObj = Dataface_Table::loadTable($table);
		if ( PEAR::isError($tableObj) ){
			throw new Exception($tableObj->getMessage(), $tableObj->getCode());
		}
		$query =& $this->getQuery();
		$app = $this;
		$resultSet = $app->getResultSet();
		if ( isset($context['record']) ){

			$record = $context['record'];
		} else {
			$record = $app->getRecord();
		}
		
		if ( isset($context['relationship']) ){
			//$tableObj = Dataface_Table::loadTable($table);
			
			if ( is_string($context['relationship']) ){
				$relationship = $tableObj->getRelationship($context['relationship']);
				if ( !($relationship instanceof Dataface_Relationship) ){
					$relationship = null;
				}
			} else if ( $context['relationship'] instanceof Dataface_Relationship ){
				$relationship = $context['relationship'];
			}
			//unset($tableObj);
		}
		if ( !@$app->_conf['debug'] ){
			@eval('$parsed = "'.$expression.'";');
		} else {
			eval('$parsed = "'.$expression.'";');
		}
		
		if ( !isset( $parsed ) ){
			throw new Exception(df_translate('scripts.Dataface.Application.parseString.ERROR_PARSING_EXPRESSION',"Error parsing expression '$expression'. ", array('expression'=>$expression)), E_USER_ERROR);
		}
		return $parsed;
	
	}
	
	
	/**
	 * Used by preg_replace_callback to replace a match with its PHP parsed equivalent.
	 * @private
	 */
	function _parsePregMatch($matches){
		extract($this->_parseStringContext);
		if ( !@$this->_conf['debug'] ){
			return @eval('return '.$matches[1].$matches[2].';');
		} else {
			return eval('return '.$matches[1].$matches[2].';');
		}
	}
	
	
	/**
	 * @brief Tests an expression for a boolean result.  This is primarly used
	 * by the condition directive of actions to be able to evaluate boolean expressions
	 * to determine if an action should be visible or not.
	 *
	 * @param string $condition A string that is executed in the sandbox context.
	 * @param array $context Extra context information to include.  This does not allow
	 * 	arbitrary variables to be added to the context, only specific ones.
	 * @returns boolean
	 *
	 * @par Expression Context
	 * The following variables are set up in the context and can be used in the expression:
	 * <table>
	 * 		<tr>
	 *			<th>Variable</th><th>Type</th><th>Description</th>
	 *		<tr>
	 *			<td>$site_url</td><td>String</td>
	 *			<td>The Site URL (i.e. DATAFACE_SITE_URL).  This includes the URL
	 *				to the folder containing the application.  It doesn't include the 
	 *				index.php file.
	 *			</td>
	 *		</tr>
	 *		<tr>
	 *			<td>$site_href</td><td>String</td>
	 *			<td>The Site HREF (i.e. DATAFACE_SITE_HREF).  This is the URL
	 *				to the index.php file.
	 *			</td>
	 *		</tr>
	 *		<tr>
	 *			<td>$dataface_url</td><td>String</td>
	 *			<td>The URL to the Xataface directory.</td>
	 *		</tr>
	 *		<tr>
	 *			<td>$table</td><td>String</td>
	 *			<td>The name of the current table.</td>
	 *		</tr>
	 *		<tr>
	 *			<td>$tableObj</td><td>@ref Dataface_Table</td>
	 *			<td>Reference to the Dataface_Table object encapsulating the current table.</td>
	 *		</tr>
	 *		<tr>
	 *			<td>$query</td><td>array</td>
	 *			<td>The current query array.  
	 *				See @ref getQuery()
	 *			</td>
	 *		</tr>
	 *		<tr>
	 *			<td>$resultSet</td><td>@ref Dataface_QueryTool</td>
	 *			<td>The current query result set.  
	 *				See @ref getResultSet()
	 *			</td>
	 *		</tr>
	 *		<tr>
	 *			<td>$record</td><td>@ref Dataface_Record</td>
	 *			<td>The current record.  This may be obtained from the 'record' key of the $context parameter.  If omitted, then this will be the result of getRecord()</td>
	 *		</tr>
	 *		<tr>
	 *			<td>$relationship</td><td>@ref Dataface_Relationship</td>
	 *			<td>The current relationship.  This may be passed as the 'relationship' key of the $context parameter.  If omitted then it is loaded from the current table and the $query['-relationship'] parameter.</td>
	 *		</tr>
	 *	</table>
	 *
	 * @see Dataface_ActionTool
	 * @see parseString()
	 *
	 */
	function testCondition($condition, $context=null){

		$site_url = DATAFACE_SITE_URL;
		$site_href = DATAFACE_SITE_HREF;
		$dataface_url = DATAFACE_URL;
		$table = $this->_currentTable;
		$tableObj = Dataface_Table::loadTable($table);
		if ( PEAR::isError($tableObj) ) throw new Exception($tableObj->getMessage(), $tableObj->getCode());
		$query =& $this->getQuery();
		$app = $this;
		$resultSet = $app->getResultSet();
		if ( isset($context['record']) ) $record = $context['record'];
		else $record = $app->getRecord();
		
		if ( isset($context['relationship']) ){
			//$tableObj =& Dataface_Table::loadTable($table);
			if ( is_string($context['relationship'])  ){
				$relationship = $tableObj->getRelationship($context['relationship']);
				if ( !($relationship instanceof Dataface_Relationship) ){
					$relationship = null;
				}
			} else if ( $context['relationship'] instanceof Dataface_Relationship ){
				$relationship = $context['relationship'];
			}
			//unset($tableObj);
		}
		if ( !@$this->_conf['debug'] ){
			return @eval('return ('.$condition.');');
		} else {
			error_log($condition);
			return eval('return ('.$condition.');');
		}
	}
	
	
	
	/**
	 * @brief Builds a link to somewhere in the application.  This will maintain the existing
	 * query information.
	 * @param mixed $query Either a query string or a query array.
	 * @param boolean $useContext Whether to use the existing context variables or not.
	 * @param boolean $forceContext Whether to force context.
	 *
	 * @see Dataface_LinkTool::buildLink()
	 *
	 * @par Example Using Query Array
	 * Given that the current page is located at 
	 * http://example.com/path/to/app/index.php?-table=foo&-action=bar&username=ted
	 * @code
	 * echo $app->url(array('-action'=>'browse'));
	 * @endcode
	 * Output is:
	 * @code
	 * /path/to/app/index.php?-table=foo&action=browse&username=ted
	 * @endcode
	 *
	 * (Actually this isn't entirely correct... the entire context will also be included
	 * and Xataface calculates some default context parameters at the beginning of 
	 * every request so the actually result will include parameters like -skip, -limit, -cursor,
	 * etc.. as well).
	 *
	 */
	function url($query, $useContext=true, $forceContext=false){
		import('Dataface/LinkTool.php');
		return Dataface_LinkTool::buildLInk($query, $useContext, $forceContext);
	
	}
	
	
		
	
	
	
	
	
	
	
	
	/**
	 * @brief Registers a filter that acts on URLs that are build with link builder. 
	 * This allows modules to affect URLs as they are built to add, remove, or change
	 * parameters.
	 *
	 * @param callback $filter A callback function that follow the PHP callback conventions
	 * and call be called with <a href="http://php.net/call_user_func">call_user_func</a>.  This
	 * 	should accept a string as a input and provide the modified string as output.
	 *
	 * @returns void
	 *
	 * @par Example
	 *
	 * A filter to add -foo=1 to the end of all URLs:
	 * @code
	 * function addFoo($string){
	 *     return $string.'&-foo=1';
	 * }
	 * $app = Dataface_Application::getInstance();
	 * $app->registerUrlFilter('addFoo');
	 * 
	 * // Now any URL produced will include &-foo=1 at the end of it
	 * $url = $app->url('');
	 *     // Outputs index.php?-table=test&-action=list&-foo=1
	 * @endcode
	 *
	 * @see filterUrl()
	 */
	function registerUrlFilter( $filter ){
		$this->_url_filters[] = $filter;
	}
	
	
	/**
	 * @brief Filters a URL to add the current table and apply any filters
	 * that have been registered using registerUrlFilter()
	 *
	 * @param string $url The URL to be filtered.
	 * @returns string The filtered URL.
	 *
	 * @see registerUrlFilter()
	 */
	function filterUrl($url){
		if ( !preg_match( '/[&\?]-table/i', $url ) ){
			if ( preg_match( '/\?/i', $url ) ){
				$url .= '&-table='.$this->_currentTable;
			} else {
				$url .= '?-table='.$this->_currentTable;
			}
		}
		
		foreach ($this->_url_filters as $filter){
			$url = call_user_func($filter, $url);
		}
		return $url;
	
	}
	
	
	
	/**
	 * @private 
	 */
	function init(){
	
	}
	
	
	/**
	 * @brief Redirects the browser to a particular URL.  Using this method
	 * rather than using a Location HTTP header directly is preferred as it allows
	 * modules to hook in to provide their own redirect handler to override the
	 * redirect.
	 *
	 * @param string $url The URL to redirect to.
	 * @return void
	 *
	 * @par Redirect Handlers
	 * You can assign your own redirect behavior by setting the redirect handler fo the application.
	 */
	function redirect($url){
		if ( isset($this->redirectHandler) and method_exists('redirect', $this->redirectHandler) ){
			$this->redirectHandler->redirect($url);
			throw new Dataface_Application_RedirectException($url);
		}
		header('Location: '.$url);
		exit;
		
	}
	
	// @}
	// End Utility Functions
	//=======================================================================================
	
	
	
	// @{
	/**
	 * @name Delegate Class
	 *
	 * Methods for obtaining and working with the delegate class.
	 */
	 
	 
	 
	/**
	 * @brief Returns a reference to the delegate object for this application.
	 * The delegate object can be used to define custom functionality for the application.
	 *
	 * @return ApplicationDelegateClass The application delegate class instance.  Or null if none defined.
	 *
	 * @see ApplicationDelegateClass
	 */
	function &getDelegate(){
		if ( $this->delegate === -1 ){
			$delegate_path = DATAFACE_SITE_PATH.'/conf/ApplicationDelegate.php';
			if ( is_readable($delegate_path) ){
				import($delegate_path);
				$this->delegate = new conf_ApplicationDelegate();
			} else {
				$this->delegate = null;
			}
		}
		return $this->delegate;
				
	}
	
	
	 
	 
	// END Delegate Class
	// @}
	//========================================================================================


	// @{
	/**
	 * @name Permissions
	 *
	 * Method wrappers for working with permissions.
	 */
	 
	 
	
	/**
	 * @brief Returns the permissions that are currently available to the user in the current 
	 * context.  If we are in browse mode then permissions are checked against the 
	 * current record.  Otherwise, permissions are checked against the table.
	 *
	 * This will first try to get the permissions on the current record (as retrieved via
	 * getRecord()), and if no record is currently selected, it will get the permissions
	 * on the current table.
	 *
	 * @param array $params Parameters that can be passed to getPermissions to specify
	 * a particular field or relationship.
	 * @returns array Array of permissions / permissions matrix.
	 *
	 * @see Dataface_Record::getPermissions()
	 * @see Dataface_Table::getPermissions()
	 * @see checkPermission()
	 */
	function getPermissions($params=array()){
		$query =& $this->getQuery();
		$record = $this->getRecord();
		if ( @$query['-relationship'] ){
			$params['relationship'] = $query['-relationship'];
		}
		if ( $record and is_a($record, 'Dataface_Record') ){
			//$params = array();
			return Dataface_PermissionsTool::getPermissions($record, $params);
		} else {
			$table = Dataface_Table::loadTable($query['-table']);
			//$params = array();
			return Dataface_PermissionsTool::getPermissions($table, $params);
		}
		
	}
	
	/**
	 * @brief Checks if a permission is granted in the current context.
	 *
	 * This is essentially a wrapper around the getPermissions() method that
	 * goes on to check for the existence of a permission.
	 *
	 *
	 * @param string $perm The name of a permission to check.
	 * @returns boolean True if the permission is granted.
	 *
	 * @see getPermissions()
	 *
	 */
	function checkPermission($perm){
		$perms = $this->getPermissions();
		$result = (isset($perms[$perm]) and $perms[$perm]);
		return $result;
	}
	
	
	 
	 
	// @}
	// END Permissions
	//=========================================================================================
	
	
	
	/**
	 *  @brief Updates the metadata tables to make sure that they are current.
	 * Meta data tables are tables created by dataface to enrich the database.
	 * For example, if workflow is enabled via the enable_workflow flag in the
	 * conf.ini file, then dataface will maintain a workflow table to correspond
	 * to each table in the database.  This method will make sure that the
	 * workflow table is consistent with base table.
	 *
	 * @deprecated
	 * @private
	 *
	 */
	 function refreshSchemas($tablename){
		if ( @$this->_conf['metadata_enabled'] ){
			$metadataTool = new Dataface_MetadataTool();
			$metadataTool->updateWorkflowTable($tablename);
		}
	}
	
	
	
	
	
	/**
	 * @brief Parses a request to obtain a related blob object.
	 * @private 
	 * Requests can ask for a related record's blob field.  When this happens
	 * it has to be converted to a normal blob request.
	 *
	 * @param array $request The _REQUEST array.
	 * @return array
	 */
	function _parseRelatedBlobRequest($request){
		import('Dataface/Application/blob.php');
		return Dataface_Application_blob::_parseRelatedBlobRequest($request);
	}
	

	//@{
	/**
	 * @name Custom Pages
	 *
	 * Methods for working with custom pages.  These are seldom used and are not
	 * the recommended way to make actions in Xataface.
	 */
	 
	 
	
	
	
	
	
	
	
	
	/**
	 * @brief PHP files located in the 'pages' directory of the site are considered to be 
	 * custom pages.  Passing the GET parameter -action=custom_<pagename> will cause
	 * the Application controller to display the page <pagename>.php from the pages
	 * directory.  This method just returns an array of full paths to the custom 
	 * pages that are available in the 'pages' directory.
	 *
	 * @return array Array of all of the pages in the pages directory that can be
	 *	used as custom pages.
	 *
	 */
	function &getCustomPages(){
		if ( !isset( $this->_customPages ) ){
			$this->_customPages = array();
			$path = DATAFACE_SITE_PATH.'/pages/';
			if ( is_dir($path) ){
				if ( $dh = opendir($path) ){
					while ( ( $file = readdir($dh) ) !== false ){
						if ( preg_match('/\.php$/', $file) ){
							list($name) = explode('.', $file);
							//$name = str_replace('_', ' ', $name);
							
							$this->_customPages[$name] = $path.$file;
						}
					}
				}
			}
		}
		return $this->_customPages;
	}
	
	/**
	 * @brief Obtains the full path (read for inclusion) of the custom page with name $name
	 *
	 * @param string $name The name of a custom page.
	 * @return string The path to the custom page.
	 *
	 */
	function getCustomPagePath($name){
		$pages =& $this->getCustomPages();
		return $pages[$name];
	}
	
	/**
	 * @brief Obtains the label for a custom page.  The label is the same as the name except
	 * with capitalization of words and replacement of underscores with spaces.
	 *
	 * @param string $name The custom page name.
	 * @returns string The label for the custom page.
	 *
	 */
	function getCustomPageLabel($name){
		$name = str_replace('_',' ', $name);
		return ucwords($name);
	}
	

	// @}
	// END Custom Pages
	//=====================================================================================


	
	/**
	 * @brief Adds debug info to the debug log.
	 * @private
	 * @todo Show examples of how the debug log is used.
	 */
	function addDebugInfo($info){
		$this->debugLog[] = $info;
	}
	
	/**
	 * @brief Displays info from the debug log.
	 * @private
	 * @returns void
	 */
	function displayDebugInfo(){
		echo '<ul class="debug-info"><li>
		'; echo implode('</li><li>', $this->debugLog);
		echo '</li></ul>';
	}
	
	/**
	 * @private
	 */
	function _cleanup(){
		if ( session_id() != "" ){
			$this->writeSessionData();
		}
		if ( @$this->_conf['support_transactions'] ){
			@mysql_query('COMMIT', $this->_db);
		}
	}
	
	

	
	
	
	
	

}

/**
 * @brief An exception class that is raised when the application is requesting to 
 * redirect to a different URL.
 */
class Dataface_Application_RedirectException extends Exception {
	private $url;
	public function __construct($url, $code = 0, Exception $previous = null ){
		$this->url = $url;
		parent::__construct('Request to redirect to '.$url, $code, $previous);
	}
	
	public function getURL(){
		return $this->url;
	}

}

