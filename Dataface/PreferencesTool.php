<?php
class Dataface_PreferencesTool {

	var $refreshLifetime = 3600;
	var $prefs = array();
	var $refreshTimes = array();

	var $cachedPrefs = array();
	var $_transientCache = array();
	
	public static function &getInstance(){
		static $instance = -1;
		if ( $instance == -1 ){
			if ( isset($_SESSION['dataface__preferences'])  ){
				$instance = @unserialize($_SESSION['dataface__preferences']);
				unset($instance->cachedPrefs);
			} 
			if ( !is_a($instance, 'Dataface_PreferencesTool') ){
				$instance = new Dataface_PreferencesTool();
			}
			register_shutdown_function(array(&$instance, 'save'));
		}
		return $instance;
	}
	
	function _createPreferencesTable(){
		import(dirname(__FILE__).'/PreferencesTool/_createPreferencesTable.php');
		return Dataface_PreferencesTool__createPreferencesTable();
	}
	
	function loadPreferences($table=null){
		if ( !isset($table) ){
			$app =& Dataface_Application::getInstance();
			$query =& $app->getQuery();
			$table = $query['-table'];
		}
		$this->prefs[$table] = array();
		if ( class_exists('Dataface_AuthenticationTool') ){
			$auth =& Dataface_AuthenticationTool::getInstance();
			$username = $auth->getLoggedInUsername();
		} else {
			$username = '*';
		}
		$sql = "select * from `dataface__preferences` where `username` in ('*','".addslashes($username)."') and `table` in ('*','".addslashes($table)."')";
		
		$res = mysql_query($sql, df_db());
		if ( !$res ){
			$this->_createPreferencesTable();
			$res = mysql_query($sql, df_db());
			if ( !$res ) trigger_error(mysql_error(df_db()), E_USER_ERROR);
		}
		
		while ($row = mysql_fetch_assoc($res) ){
			if ( $row['table'] == '*' ){
				$this->prefs['*'][ $row['key'] ] = $row['value'];
			} else {
				$this->prefs[$row['table']][$row['record_id']][$row['key']] = $row['value'];
			}
		}
		
		@mysql_free_result($res);

		$this->refreshTimes[ $table ] = time();
		
		
	}
	
	
	function &getPreferences($uri){
		if ( !isset($this->_transientCache[ $uri ]) ){
			if ( !isset( $this->cachedPrefs[ $uri ] ) ){
				$parts = df_parse_uri($uri);
				if ( !isset( $this->prefs[$parts['table']] ) or ( (time() - $this->refreshLifeTime) > @$this->refreshTimes[ $parts['table'] ])){
					$this->loadPreferences($parts['table']);
				}
				
			
				$this->cachedPrefs[ $uri ] = array();
				if ( isset( $this->prefs['*'] ) ) $this->cachedPrefs[$uri] = array_merge($this->cachedPrefs[$uri], $this->prefs['*']);
				if ( isset( $this->prefs[ $parts['table'] ]['*'] ) ){
					$this->prefs[ $uri ] = array_merge( $this->cachedPrefs[$uri], $this->prefs[$parts['table']]['*']);
				}
				if ( isset($this->prefs[ $parts['table'] ][ $uri ]) ){
					$this->prefs[$uri] = array_merge( $this->cachedPrefs[$uri], $this->prefs[$parts['table']][$uri]);
					
				}
					
			}
			if ( isset( $this->cachedPrefs['*'] ) ){
				$this->_transientCache[$uri] = $this->cachedPrefs['*'];
			} else {
				$this->_transientCache[$uri] = array();
			}
			
			$this->_transientCache[ $uri ] = array_merge( $this->_transientCache[ $uri ], $this->cachedPrefs[ $uri ]);
			
		}
		return $this->_transientCache[$uri];
	}
	
	function savePreference( $uri, $key, $value, $username=null ){
	
		// First let's find out the username of the user who is currently logged
		// in because we may want to do some clever cacheing/clearing of caches
		// if we are setting the preferences for the currently logged in user.
		$loggedInUsername = null;
		if ( class_exists('Dataface_AuthenticationTool') ){
			$auth =& Dataface_AuthenticationTool::getInstance();
			if ( $auth->isLoggedIn() ){
				$loggedInUsername = $auth->getLoggedInUsername();
			}
		}
		
		
		// If no user was specified, we will set the preferences for the 
		// currently logged in user.
		if ( !isset($username) ){
			$username = $loggedInUsername;
		}
		
		// If we are setting preferences for the currently logged in user,
		// then we will update the caches as well.
		// We also do this for users who aren't logged in.
		if ( ($username == $loggedInUsername) or !isset($username) ){
			//$prefs =& $this->getPreferences($uri);
			//$prefs[$key] = $value;
			$this->cachedPrefs[$uri][$key] = $value;
			$this->prefs[$uri][$key] = $value;
		}
		
		$parts = df_parse_uri($uri);
		
		if ( $username == '*' ) {
			// If we are making changes to all users, we should clear our
			// own preference caches for this table.
			
			unset($this->cachedPrefs[$uri]);
			
			unset($this->prefs[$parts['table']]);
			unset($this->prefs['*']);
			
		}
		
		if ( $uri == '*' and isset($username) ){
			// If we are updating preferences on ALL records, then we should
			// need to clear all caches.
			$this->prefs = array();
			$this->cachedPrefs = array();
			$this->refreshTimes = array();
		}
		
		if ( isset($username) ){
			
			
			// First we have to delete conflicts.
			// If we are setting a global value (ie a value for all tables)
			// we will clear out all previous values.
			$sql = "delete from `dataface__preferences` where `key` = '".addslashes($key)."' ";
			if ( $uri != '*' ){
				if ( $parts['table'] != $uri ) $sql .= " and `record_id` = '".addslashes($uri)."'";
				else $sql .= " and `table` = '".addslashes($parts['table'])."'";
			}
			
			if ( $username != '*' ){
				$sql .= " and `username` = '".addslashes($username)."'";
			}
			
			$res = mysql_query($sql, df_db());
			if ( !$res ){
				$this->_createPreferencesTable();
				$res = mysql_query($sql, df_db());
				if ( !$res ) trigger_error(mysql_error(df_db()), E_USER_ERROR);
			}
			
			$sql = "insert into `dataface__preferences` 
				(`table`,`record_id`,`username`,`key`,`value`) values
				('".addslashes($parts['table'])."','".addslashes($uri)."','".addslashes($username)."','".addslashes($key)."','".addslashes($value)."')";
				
			$res = mysql_query($sql, df_db());
			if ( !$res ){
				$this->createPreferencesTable();
				$res = mysql_query($sql, df_db());
				if ( !$res ) trigger_error(mysql_error(df_db()), E_USER_ERROR);
			}
		}
		
		
			
		

	}
	
	function save(){
		unset($this->_transientCache);
		$_SESSION['dataface__preferences'] = serialize($this);
	}
	
	function __wakeup(){
		$this->_transientCache = array();
	}
	
	
}
