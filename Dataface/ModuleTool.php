<?php
/**
 * A tool to help manage and work with modules.  Use this class to load and install
 * modules, and perform maintenance on them.
 */
class Dataface_ModuleTool {

	var $_modules;
	
	var $_db_versions;
	
	
	public static function &getInstance(){
		static $instance = 0;
		
		if ( !is_object($instance) ){
			$instance = new Dataface_ModuleTool();
			
		}
		return $instance;
	
	}
	
	
	/**
	 * @brief Returns the current database version of the module.
	 */
	public function getDbVersion($modname){
		if ( !isset($this->_db_versions) ){
			$this->_db_versions = array();
			$sql = "select module_name, module_version from dataface__modules";
			$res = mysql_query($sql, df_db());
			if ( !$res ){
				$res = mysql_query("create table dataface__modules (
					module_name varchar(255) not null primary key,
					module_version int(11)
				)", df_db());
				if ( !$res ) throw new Exception(mysql_error(df_db()));
				$res = mysql_query($sql, df_db());
			}
			if ( !$res ) throw new Exception(mysql_error(df_db()));
			while ($row = mysql_fetch_assoc($res) ){
				$this->_db_versions[$row['module_name']] = $row['module_version'];
			}
			@mysql_free_result($res);
			
		}
		$out = @$this->_db_versions[$modname];
		if ( !$out ) return 0;
		return $out;
	}
	
	/**
	 * @brief Returns the file system version of the specified module.
	 *
	 * The file system version should be stored in a file named version.txt
	 *	inside the module's directory.  It should be in the format:
	 *
	 * 
	 *
	 * @param string $modname The name of the module (e.g. modules_grid)
	 * @param string $path The path to the module's file (e.g. modules/grid/grid.php)
	 * @return int The file system version.
	 */
	public function getFsVersion($modname, $path){
		$versionPath = dirname($path).DIRECTORY_SEPARATOR.'version.txt';
		if ( !file_exists($versionPath) ) return 0;
		$str = trim(file_get_contents($versionPath));
		if ( preg_match('/(\d+)$/', $str, $matches) ){
			return intval($matches[1]);
		} else {
			return 0;
		}
	}
	
	/**
	 * @brief Updates a module so that the database version is the same
	 * as the file system version.
	 * @param string $modname The name of the module.
	 * @param string $path The path to the module.
	 */
	public function updateModule($modname, $path){
		$installpath = dirname($path).DIRECTORY_SEPARATOR.'installer.php';
		if ( file_exists($installpath) ){
			import($installpath);
			$classname = $modname.'_installer';
			$installer = new $classname;
			
			$methods = get_class_methods($classname);
			$methods = preg_grep('/^update_([0-9]+)$/', $methods);
			
			$updates = array();
			$fsversion = $this->getFsVersion($modname, $path);
			$dbversion = $this->getDbVersion($modname);
			
			foreach ($methods as $method){
				preg_match('/^update_([0-9]+)$/', $method, $matches);
				$version = intval($matches[1]);
				if ( $version > $dbversion and $version <= $fsversion ){
					$updates[] = $version;
				}
			}
			
			sort($updates);
			
			if ( $dbversion == 0 ){
				$res = mysql_query("insert into dataface__modules (module_name,module_version)
					values ('".addslashes($modname)."',-1)", df_db());
				if ( !$res ) throw new Exception(mysql_error(df_db()));
			}
			
			foreach ($updates as $update ){
				$method = 'update_'.$update;
				$res = $installer->$method();
				if ( PEAR::isError($res) ) return $res;
				$res = mysql_query("update dataface__modules set `module_version`='".addslashes($update)."'", df_db());
				if ( !$res ) throw new Exception(mysql_error(df_db()), E_USER_ERROR);	
			}
			
			$res = mysql_query("update dataface__modules set `module_version`='".addslashes($fsversion)."'", df_db());
			if ( !$res ) throw new Exception(mysql_error(df_db()), E_USER_ERROR);
			
			
		}
		
	
	}
	
	/**
	 * @brief Returns the URL of the module that contains the given file.  This will
	 *  either return a URL in the DATAFACE_SITE_URL/modules directory or the
	 *  DATAFACE_URL/modules depending on where the file is located.
	 *
	 * This method is designed for modules to be able to figure out where they are
	 * so that they can include resources like stylesheets and javascripts.
	 *
	 * @param string $file The path to a file in the module's directory.
	 * @return string The URL to the module's directory.
	 * @throw Exception If this doesn't appear to be in the module's directory.
	 *
	 * @section Example
	 *
	 * Inside the module modules_tagger, the method to include javascripts contains:
	 *
	 * @code
	 * $baseURL = Dataface_ModuleTool::getInstance()->getModuleURL(__FILE__);
	 * $app->addHeadContent(
	 *     '<script src="'.df_escape($baseURL.'/js/tagger.js').'"></script>'
	 * );
	 * @endcode
	 */
	public function getModuleURL($file){
		$s = DIRECTORY_SEPARATOR;
		if ( strtolower(realpath($file)) == strtolower(realpath(DATAFACE_SITE_PATH.$s.'modules'.$s.basename(dirname($file)).$s.basename($file))) ){
			return DATAFACE_SITE_URL.'/modules/'.rawurlencode(basename(dirname($file)));
		} else if (realpath($file) == realpath(DATAFACE_PATH.$s.'modules'.$s.basename(dirname($file)).$s.basename($file)) ){ 
			return DATAFACE_URL.'/modules/'.rawurlencode(basename(dirname($file)));
		} else {
			throw new Exception("Could not find URL for file $file in module tool");
		}
	}
	
	/**
	 * Displays a block as defined in all of the registered modules.
	 * @param string $blockName The name of the block.
	 * @param array $params Parameters that are passed to the block.
	 * @returns boolean True if at least one module defines this block.
	 *					False otherwise.
	 * @since 0.6.14
	 * @author Steve Hannah <shannah@sfu.ca>
	 * @created Feb. 27, 2007
	 */
	function displayBlock($blockName, $params=array()){
		//echo "here";
		$app =& Dataface_Application::getInstance();
		if ( !isset($app->_conf['_modules']) or count($app->_conf['_modules']) == 0 ){
			return false;
		}
		$out = false;
		foreach ($app->_conf['_modules'] as $name=>$path){
			//echo "Checking $name : $path";
			$mod =& $this->loadModule($name);
			if ( method_exists($mod,'block__'.$blockName) ){
				//echo "Method exists";
				$res = call_user_func(array(&$mod, 'block__'.$blockName), $params);
				if ( !$res !== false ){
					$out = true;
				}
				
			}
		}
		return $out;
	}
	
	/**
	 * Loads a module and returns a reference to it.
	 * @param string $name The name of the module's class.
	 *
	 */
	function &loadModule($name, $path=null){
		if ( !isset($path) ){
			if ( preg_match('/^modules_/', $name) ){
				$s = DIRECTORY_SEPARATOR;
				$path = preg_replace('/^modules_/', 'modules'.$s, $name).$s.substr($name, strpos($name, '_')+1).'.php';
			}
		
		}
		$app =& Dataface_Application::getInstance();
		
		if ( isset($this->_modules[$name]) ) return $this->_modules[$name];
		if ( class_exists($name) ){
			$this->_modules[$name] = new $name;
			return $this->_modules[$name];
		}
		
		if ( !isset($path) and (!@$app->_conf['_modules'] or !is_array($app->_conf['_modules']) or !isset($app->_conf['_modules'][$name])) ){
			return PEAR::raiseError(
				df_translate(
					'scripts.Dataface.ModuleTool.loadModule.ERROR_MODULE_DOES_NOT_EXIST',
					"The module '$name' does not exist.",
					array('name'=>$name)
					)
				);
		}
		if ( !isset($app->_conf['_modules'][$name]) and isset($path) ){
			$app->_conf['_modules'][$name] = $path;
			import($path);
			if ( $this->getFsVersion($name, $path) > $this->getDbVersion($name) ){
				$this->updateModule($name, $path);
			}
			
		} else {
			import($app->_conf['_modules'][$name]);
			if ( $this->getFsVersion($name, $app->_conf['_modules'][$name]) > $this->getDbVersion($name) ){
				$this->updateModule($name, $app->_conf['_modules'][$name]);
			}
		}
		if ( !class_exists($name) ){
			return PEAR::raiseError(
				df_translate(
					'scripts.Dataface.ModuleTool.loadModule.ERROR_CLASS_DOES_NOT_EXIST',
					"Attempted to load the module '$name' from path '{$app->_conf['_modules'][$name]}' but after loading - no such class was found.  Please check to make sure that the class is defined.  Or you can disable this module by commenting out the line that says '{$name}={$app->_conf['_modules'][$name]}' in the conf.ini file.",
					array('name'=>$name,'path'=>$app->_conf['_modules'][$name])
					)
				);
		}
		$this->_modules[$name] = new $name;
		return $this->_modules[$name];
	}
	
	/**
	 * Load modules.
	 */
	function loadModules(){
		$app =& Dataface_Application::getInstance();
		if ( @$app->_conf['_modules'] and is_array($app->_conf['_modules']) ){
			foreach ( array_keys($app->_conf['_modules']) as $module){
				$this->loadModule($module);
			}
		}
	}
	
	
	/**
	 * Returns an array of modules that require migrations.  [Module Name] -> [Description of migration]
	 * performed.
	 */
	function getMigrations(){
		$this->loadModules();
		$out = array();
		foreach ($this->_modules as $name=>$mod ){
			if ( method_exists($mod, 'requiresMigration') and ( $req = $mod->requiresMigration()) ){
				$out[$name] = $req;
			}
		}
		
		return $out;
		
	}
	
	
	
	
	/**
	 * Performs migrations on the specified modules.
	 * @param $modules The names of the modules to migrate. If omitted, all modules will be migrated.
	 * @returns an associative array of log entries for each migration.
	 */
	function migrate($modules=array()){
		$log = array();
		$this->loadModules();
		$migrations = $this->getMigrations();
		foreach ($modules as $mod){
			$mod_obj = $this->loadModule($mod);
			if ( isset($migrations[$mod]) and method_exists( $mod_obj, 'migrate' ) ){
				$log[$mod] = $mod_obj->migrate();
			}
			unset($mod_obj);
		}
		
		return $log;
	}
	
	/**
	 * Installs the specified modules.
	 * @param array $modules Array of module names to install.
	 * @returns Associative array of status messages: [Module Name]-> [Install status]
	 */
	function install($modules=array()){
		$log = array();
		$this->loadModules();
		$migrations = $this->getMigrations();
		foreach ($modules as $mod){
			$mod_obj = $this->loadModule($mod);
			
			if ( !$this->isInstalled($mod) and method_exists($mod_obj,'install') ){
				$log[$mod] = $mod_obj->install();
			}
			
			unset($mod_obj);
		}
		
		return $log;
	}
	
	/**
	 * Indicates whether a given module is currently installed.
	 * @returns boolean True if it is installed.
	function isInstalled($moduleName){
		$mod_obj = $this->loadModule($mod);
		if ( PEAR::isError($mod_obj) ) return false;
		if ( method_exists($mod_obj,'isInstalled')) return $mod_obj->isInstalled();
		return false;
	}
	
	/**
	 * Returns a list of names of modules that are currently installed.
	 */
	function getInstalledModules(){
		$out = array();
		$this->loadModules();
		foreach ($this->_modules as $name=>$mod){
			if ( $this->isInstalled($name) ) $out[] = $name;
		}
		return $out;
	}
	
	/**
	 * Returns an array of names of modules that have not been installed, but
	 * can be installed.
	 */
	function getUninstalledModules(){
		$out = array();
		$this->loadModules();
		foreach ($this->_modules as $name=>$mod){
			if ( !$this->isInstalled($name) ) $out[] = $name;
		}
		return $out;
	}
}
