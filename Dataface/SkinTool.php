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

import( 'Smarty/Smarty.class.php');
import( 'Dataface/LanguageTool.php');
/**
 * Handles the display of content in Dataface using templates.  Abstracts all templating.
 *
 * <h3>Usage example</h3>
 * <code>
 *  $tool =& Dataface_SkinTool::getInstance();
 *  $context = array('var1'=>'value1', 'var2'=>'value2');
 *  	// context to provide information to the template.
 *  	// var1 will be available as {$var1} in the template, etc...
 *  	
 *  $template = 'MyTemplate.html';
 *  	// The name of the template to use.  Location of templates directory stored in
 *  	// $GLOBALS['Dataface_Globals_Templates']
 *  	
 *  $tool->display($context, $template);
 *  	// prints the template
 * </code>
 * <p>Templates can use any of the standard smarty markup in addition to new 
 * markup that is added by the Skin Tool.  Dataface-specific variables are 
 * available via the $ENV variable, which is an associative array of values
 * relating to Dataface and the current context.</p>
 * <p>Some useful variables include:
 *	<dl>
 *		<dt>$ENV.DATAFACE_PATH</dt><dd>The file path (not the URL path) to the current Dataface installation directory. e.g. <em>/var/www/dataface</em></dd>
 *		<dt>$ENV.DATAFACE_URL</dt><dd>The URL to the Dataface installation directory.  Useful for including resources such as images in your templates. e.g. <em>http://yourdomain.com/path/to/dataface</em></dd>
 *		<dt>$ENV.DATAFACE_SITE_PATH</dt><dd>The file path (not the URL path) to your application's directory. e.g. <em>/var/www/your/app</em></dd>
 *		<dt>$ENV.DATAFACE_SITE_URL</dt><dd>The URL to your application's directory. e.g. <em>http://yourdomain.com/path/to/your/app</em></dd>
 *		<dt>$ENV.DATAFACE_SITE_HREF</dt><dd>The URL to your application's entry point script.  e.g. <em>http://yourdomain.com/path/to/your/app/index.php</em></dd>
 *		<dt>$ENV.APPLICATION</dt><dd>The application's configuration array.  
 *				This includes any settings that you added to your <em>conf.ini</em> file.  e.g. <em>{ENV.APPLICATION._database.host}</em>
 *				will display the host name of the mysql database as specified in the conf.ini file.</dd>
 *		<dt>$ENV.APPLICATION_OBJECT</dt>
 *		<dd>A reference to the application object (Dataface_Application).</dd>
 *		<dt>$ENV.action</dt><dd>The name of the action that is currently being performed.  e.g. view, edit, new_related_record, etc..</dd>
 *		<dt>$ENV.table</dt><dd>The name of the current database table.</dd>
 *		<dt>$ENV.relationship</dt><dd>If there is a relationship currently specified in the request, this is the name of it.</dd>
 *		<dt>$ENV.limit</dt><dd>The maximum number of records to show per page.</dd>
 *		<dt>$ENV.start</dt><dd>The start position in the current result set.</dd>
 *		<dt>$ENV.resultSet</dt><dd>A reference to the current record result set.  This is a Dataface_QueryTool object.</dd>
 *		<dt>$ENV.record</dt><dd>A reference to the current record. (if in browse mode).</dd>
 *		<dt>$ENV.mode</dt><dd>The current application mode.  e.g. browse, list, find</dd>
 *		<dt>$ENV.language</dt><dd>The 2-digit language code of the currently selected language.</dd>
 *		<dt>$ENV.prefs</dt><dd>The user's preferences array which decides which parts of the interface should be visible (among other settings).</dd>
 *		<dt>$ENV.search</dt><dd>The current search term if a search has been performed in the search box.</dd>
 *	</dl>
 *	</p>
 *
 * 
 * @see df_display()
 * @see df_register_skin()
 *
 * @ref http://smarty.php.net The Smarty website. 
 * Contains extensive documentation about smarty.
 * 
 * @author Steve Hannah <shannah@sfu.ca>
 * @created October 15, 2005
 **/
class Dataface_SkinTool extends Smarty{


	var $compile_dir;
	var $ENV;
	var $skins = array();
	var $locals = array();
	var $languageTool;
	var $app;
	var $resultController = null;
	
	function Dataface_SkinTool() {
		
		if ( is_writable($GLOBALS['Dataface_Globals_Local_Templates_c']) ){
			
			$this->compile_dir = $GLOBALS['Dataface_Globals_Local_Templates_c'];
		} else if ( is_writable($GLOBALS['Dataface_Globals_Templates_c'])){
			$this->compile_dir = $GLOBALS['Dataface_Globals_Templates_c'];
		} else {
			throw new Exception("<h1>No appropriate directory could be found to save 
			Dataface's compiled templates.</h1>
			
			<p>Dataface uses the Smarty Template engine for its templates, which compiles
			templates and stores them on the server for improved performance.  You can
			either store these templates in the Dataface directory or your application's
			directory.</p>
			
			<p>To store the templates in the Dataface directory, please ensure that the
			<pre>$GLOBALS[Dataface_Globals_Templates_c]</pre> directory exists and is writable by the
			web server. </p>
			<p>You can make it writable by the web server on most unix and linux systems,
			by issuing the following command in the shell:
			<code><pre>chmod 777 $GLOBALS[Dataface_Globals_Templates_c] </pre></code>.</p>
			
			<p>To store the templates in your application's directory, please ensure 
			that the <pre>$GLOBALS[Dataface_Globals_Local_Templates_c]</pre> directory exists and is 
			writable by the web server.</p>", E_USER_ERROR);
		}
		
		$this->languageTool =& Dataface_LanguageTool::getInstance();
		
		
		$this->register_skin('dataface', $GLOBALS['Dataface_Globals_Templates']);
		
		$this->register_skin('default', $GLOBALS['Dataface_Globals_Local_Templates']);
		$this->register_plugins($GLOBALS['Dataface_Globals_Local_Plugins']);

		$app =& Dataface_Application::getInstance();
		$this->app =& $app;
		$conf =& $app->_conf;
		$resultSet =& $app->getResultSet();
		if ( isset( $conf['auto_load_results'] ) and $conf['auto_load_results'] ){
			$currentRecord =& $resultSet->loadCurrent();
		} else {
			$currentRecord = null;
		}
		
		if ( isset($app->_conf['_themes']) and is_array($app->_conf['_themes']) ){
			foreach ( $app->_conf['_themes'] as $themename=>$themepath ){
				$this->register_skin($themename, $themepath.'/templates');
			}
		}
		
		$this->ENV = array(
			'REQUEST' => &$_REQUEST,
			'SESSION' => &$_SESSION,
			'DATAFACE_PATH' => DATAFACE_PATH,
			'DATAFACE_URL' => DATAFACE_URL,
			'DATAFACE_SITE_PATH' => DATAFACE_SITE_PATH,
			'DATAFACE_SITE_URL' => DATAFACE_SITE_URL,
			'DATAFACE_SITE_HREF' => DATAFACE_SITE_HREF,
			'SCRIPT_NAME' => DATAFACE_SITE_URL.'/'.basename($_SERVER['SCRIPT_NAME']),
			'APPLICATION' => &$conf,
			'APPLICATION_OBJECT' => &$app,
			'SERVER' => &$_SERVER,
			'QUERY'=>&$app->_query,
			'action'=>@$app->_query['-action'],
			'table'=>@$app->_query['-table'],
			'table_object'=> Dataface_Table::loadTable($app->_query['-table']),
			'relationship'=>@$app->_query['-relationship'],
			'limit'=>@$app->_query['-limit'],
			'start'=>@$app->_query['-start'],
			'resultSet'=>&$resultSet,
			'record'=>&$currentRecord,
			'mode'=>&$app->_query['-mode'],
			'language'=>$app->_conf['lang'],
			'prefs'=>&$app->prefs,
			'search'=>@$_REQUEST['-search']
			
		);
		
		$authTool =& $app->getAuthenticationTool();
		if ( isset($authTool) ){
			$user =& $authTool->getLoggedInUser();
			if ( isset($user) ){
				$this->ENV['user'] = &$user;
				$this->ENV['username'] = $authTool->getLoggedInUsername();
			}
		}
		$context = array();
		$context['APP'] =& $this->app;
		$context['ENV'] =& $this->ENV;
		if ( $del = $app->getDelegate() and method_exists($del, 'getTemplateContext') ){
			$c =& $del->getTemplateContext();
			if ( is_array($c) ){
				foreach ($c as $k=>$v){
					$context[$k] = $v;
				}
			}
		}
		
		$this->assign($context);
		$this->register_function('load_record', array(&$this, 'load_record'));
		$this->register_function('group',array(&$this,'group'));
		$this->register_function('img', array(&$this,'img'));
		$this->register_function('actions', array(&$this, 'actions'));
		$this->register_function('actions_menu', array(&$this, 'actions_menu'));
		$this->register_function('record_actions', array(&$this, 'record_actions'));
		$this->register_function('record_tabs', array(&$this, 'record_tabs'));
		$this->register_function('result_controller', array(&$this, 'result_controller'));
		$this->register_function('result_list', array(&$this,'result_list'));
		$this->register_function('filters', array(&$this, 'filters'));
		$this->register_function('related_list',array(&$this,'related_list'));
		$this->register_function('bread_crumbs', array(&$this, 'bread_crumbs'));
		$this->register_function('search_form', array(&$this, 'search_form'));
		$this->register_function('language_selector', array(&$this, 'language_selector'));
		$this->register_function('next_link', array(&$this, 'next_link'));
		$this->register_function('prev_link', array(&$this, 'prev_link'));
		$this->register_function('jump_menu', array(&$this, 'jump_menu'));
		$this->register_function('limit_field', array(&$this, 'limit_field'));
		$this->register_function('result_index', array(&$this, 'result_index'));
		$this->register_function('block', array(&$this, 'block'));
		$this->register_function('summary_list',array(&$this,'summary_list'));
		$this->register_function('sort_controller',array(&$this,'sort_controller'));
		$this->register_function('glance_list', array(&$this,'glance_list'));
		$this->register_function('record_view', array(&$this,'record_view'));
		$this->register_function('feed', array(&$this,'feed'));
		$this->register_function('records', array(&$this, 'records'));
		$this->register_function('form_context', array(&$this, 'form_context'));		
		$this->register_block('translate', array(&$this, 'translate'));
		$this->register_block('use_macro',array(&$this,'use_macro'));
		$this->register_block('define_slot', array(&$this,'define_slot'));
		$this->register_block('fill_slot', array(&$this,'fill_slot'));
		$this->register_block('if_allowed', array(&$this, 'if_allowed'));
		$this->register_block('editable', array(&$this, 'editable'));
		$this->register_block('abs', array(&$this, 'abs'));
		
		
		

	}
	
	
	/**
	 * Obtains a reference to the result controller for this request.  The result 
	 * controller is the control that allows users to navigate between records of
	 * the current result set.
	 *
	 * @return Dataface_ResultController
	 */
	function &getResultController(){
		if ( !isset($this->resultController) ){
			import('Dataface/ResultController.php');

			$query =& $this->app->getQuery();
		
			$this->resultController = new Dataface_ResultController($query['-table'], $this->app->db(), DATAFACE_SITE_HREF);
		}
		
		return $this->resultController;
	}
	
	/**
     * Get the compile path for this resource.
     *
     * <p>This method is used internally by SkinTool to find out where the compiled
     *	version of a particular template is located.</p>
     *
     * @param string $resource_name
     * @return string results of {@link _get_auto_filename()}
     */
    function _get_compile_path($resource_name)
    {
    	$params = array('resource_name'=>$resource_name, 'resource_base_path' => $this->template_dir);
    	$name = $this->_parse_resource_name($params);
    	$template_dir = dirname($params['resource_name']);
    	$skin = $this->skins[$template_dir];
    	if ( strlen($skin) > 0 and preg_match('/^[0-9a-zA-Z_]+$/', $skin) ){
    		$compile_dir = $this->compile_dir.'/'.$skin;
    		if ( !file_exists($compile_dir) ){
    			$res = @mkdir($compile_dir);
    			
    			if ( !$res ){
					echo "<h2>Configuration Required</h2>
						<p>Dataface was unable to create the directory '$compile_dir' 
						to store compiled template files.</p>
						<h3>Possible reasons for this:</h3>
						<ul>
							<li>The script does not have permission to create the directory.</li>
							<li>The server is operating in safe mode.</li>
						</ul>
						<h3>Possible Solutions for this:</h3>
						<ul>
							<li>Make the ".dirname($compile_dir)." writable by the web server.  E.g. chmod 0777 ".dirname($compile_dir).".</li>
							<li>Manually create the '$compile_dir' directory and make it writable by the web server.</li>
							<li>If none of these solves the problem, visit the Dataface forum
							 at <a href=\"http://xataface.com/forum\">http://xataface.com/forum</a> 
							 and ask for help.
							 </li>
					    </ul>
					    ";
					exit;
				}
    			
    		}
    		if ( !file_exists($compile_dir) ){
    			error_log("Failed to create compile directory '$compile_dir'");
    			throw new Exception("Failed to compile templates due to a configuration problem.  See error log for details.", E_USER_ERROR);
    		}
    	} else {
    		$compile_dir = $this->compile_dir;
    	}
    	$fname= $this->_get_auto_filename($compile_dir, $resource_name,
                                         $this->_compile_id) . '.php';

       return $fname;
    }
    
    
	/**
	 * Displays a template.
	 *
	 * @param array &$context An associative array of variables that can be used
	 *	in the template.
	 * @param string $template The name of the template to be displayed.  It will
	 *	look inside any registered template directory.  By default it will check
	 * the application's <em>templates</em> directory, then look in the 
	 * <em>Dataface/templates</em> directory to find the template.
	 */
	function display($context, $template=null, $compile_id=null){
		if ( !is_array($context) ) {
			return parent::display($context);
		}
		$event = new StdClass;
		$event->context =& $context;
		Dataface_Application::getInstance()->fireEvent('filterTemplateContext', $event);
		$this->assign($context);
		return parent::display($template);
	
	}
	

	
	/**
	 * Returns a singleton instance to the skin tool.
	 * @return Dataface_SkinTool
	 */
	public static function &getInstance(){
		static $instance = 0;
		static $count = 0;
		if ( $count++ < 1 ) {

			$instance = new Dataface_SkinTool();
		}
		
		return $instance;
	}
	
	
	
	/**
	 * Registers a skin to be used as the default skin.  This skin is added to 
	 * the top of the stack so it has the highest priority.  If a template is
	 * requested and this skin does not contain that template, then the SkinTool
	 * will check the next skin in the stack. And so on...
	 *
	 * @param string $template_dir The directory to the templates for this skin.
	 * @param string $compile_dir The directory where the compiled templates should be stored.
	 */
	function register_skin( $name, $template_dir){
		if ( !is_array($this->template_dir) ){
			if ( strlen($this->template_dir) > 0 ){
				$this->template_dir = array($this->template_dir);
			} else {
				$this->template_dir = array();
			}
		}
		array_unshift($this->template_dir, $template_dir);
		$this->skins[$template_dir] = $name;
	
	}
	
	/**
	 * Registers a directory to be used as the default smarty plugin directory.  
	 * This directory is added to the top of the stack so it has the highest priority.  
	 * If a plugin is
	 * requested and this skin does not contain that template, then the SkinTool
	 * will check the next skin in the stack. And so on...
	 *
	 * @param string $plugin_dir The directory to the templates for this skin.
	 *
	 * @since 2.0
	 * @thanks OODavo
	 *
	 */
	function register_plugins( $plugin_dir){
		if ( !is_array($this->plugins_dir) ){
			if ( strlen($this->plugins_dir) > 0 ){
				$this->plugins_dir = array($this->plugins_dir);
			} else {
				$this->plugins_dir = array();
			}
		}
		array_unshift($this->plugins_dir, $plugin_dir);
	
	}
	
	
	//------------------SMARTY TEMPLATE FUNCTIONS---------------------------
	// These are functions to be used inside templates to get information
	// from the database.
	//
	
	/**
	 * Loads a record from the database and assigns it to a template variable.
	 *
	 * <code>
	 *  {load_record var=myrecord} {*loads current record as specified by 
	 *								request's found set & query parameters.*}
	 *  {$myrecord->val('FirstName')} {*Displays the value of the loaded record's 
	 *									'FirstName' field. *}
	 * </code>
	 *
	 * @param array $params Associative array of parameters.
	 * @param Smarty &$smarty Reference to the SkinTool object context.
	 * @return void
	 * 
	 * @smarty-function load_record
	 * @smarty-param string table The name of the table from which to load the record. (Optional - will default to current table).
	 * @smarty-param string var The name of the variable into which the record should be loaded.
	 * @smarty-param % Optional key-value pairs of field names / values to find a particular record.  If none are provided, the current record will be returned.
	 *
	 */
	function load_record($params, &$smarty){
		import( 'dataface-public-api.php');
		if ( empty($params['table']) ){
			$params['table'] = $this->ENV['table'];
		}
		
		if ( empty($params['var']) ){
			$params['var'] = null;
		}
		$table = $params['table'];
		unset($params['table']);
		$varname = $params['var'];
		unset($params['var']);
		$vars =& $smarty->get_template_vars();
			
		if ( count($params) <= 0 ){
			if ( !$this->app->recordLoaded() ){
				$record =& $this->ENV['resultSet']->loadCurrent();
			} else {
				$record =& $this->app->getRecord();
			}
		
		} else {
			$record =& df_get_record($table, $params);
			
		}
		if ( isset($varname) ) $vars[$varname] =& $record;
		else 
			$vars['ENV']['record'] =& $record;
		
		
	}
	
	
	
	
	function record_view($params, &$smarty){
		import('Dataface/RecordView.php');
	
		if ( empty($params['record']) ) $params['record'] =& $this->app->getRecord();
		if ( empty($params['var']) ) $params['var'] = 'rv';
		
		$vars =& $smarty->get_template_vars();
		$vars[$params['var']] = new Dataface_RecordView($params['record']);
		
	}
	
	/**
	 * Groups an array of Records (or associative arrays) together based on a specific field.
	 * @param array $params Array of parameters
	 * @param Dataface_SkinTool &$smarty Reference to Smarty template engine.
	 * @param array $params[from] The array that is to be grouped.
	 * @param string $params[var] The name of the variable to assign the grouped structure to.
	 * @param string $params[on] The name of the field on which to group the records.
	 * @param string $params[order] A comma-delimited string of order directives to specify the 
	 *		order in which the records should be displayed.
	 * @param string $params[titles] Titles for the groups in a format similar to css attributes.
	 *
	 */
	function group($params, &$smarty){
		
		import( 'Dataface/Utilities.php');
		if ( empty($params['from']) ){
			throw new Exception('group: Please specify a from parameter.', E_USER_ERROR);
		}
		if ( empty($params['var']) ){
			throw new Exception('group: Please specify a var parameter.', E_USER_ERROR);
		}
		if ( empty($params['on'])){
			throw new Exception('group: Please specify a field parameter.', E_USER_ERROR);
		}
		
		if ( !empty($params['order']) ){
			$order = explode(',',$params['order']);
		} else {
			$order = array();
		}
		
		if ( !empty($params['titles']) ){
			$titles = array_map('trim',explode(';', $params['titles']));
			$titles2 = array();
			foreach ($titles as $title){
				list($titleKey, $titleValue) = array_map('trim',explode(':',$title));
				$titles2[$titleKey] = $titleValue;
			}
		} else {
			$titles2 = array();
		}
		
		$cats = Dataface_Utilities::groupBy($params['on'], $params['from'], $order, $titles2);
		$context = array($params['var']=>&$cats);
		$smarty->assign($context);
	
	}
	
	/**
	 * Prints an 'img' tag that will show a thumbnail of the requested image using
	 * phpThumb.  This function is registered with smarty so that it can be used
	 * in the form of an {img} tag.
	 *
	 * @param $params = {
	 *			"src" -> The url to the image (required)
	 *			"width" -> The max width of the thumbnail (optional)
	 *			"height" -> The max height of the thumbnail.  (optional)
	 *			""" Any other parameters that are appropriate for the img tag.
	 *			}
	 */
	function img($params, &$smarty){
	
		// We have to have at least the src parameter set
		if ( !isset( $params['src'] ) ) return '';
		
		
		if ( isset( $params['width'] ) ){
			$width= '&w='.$params['width'];
			unset($params['width']);
		} else {
			$width = '';
		}
		
		if ( isset( $params['height']) ){
			$height= '&h='.$params['height'];
			unset($params['height']);
		} else {
			$height = '';
		}
		
		$url = DATAFACE_URL;
		if ( strlen($url) > 0 and $url{0} != '/' ){
			$url = DATAFACE_SITE_URL.'/'.$url;
		} else if ( strlen($url) == 0 ){
			$url = DATAFACE_SITE_URL;
		} else {
			$url = '';
		}
		$src = $_SERVER['HOST_URI'].$url.'/lib/phpThumb/phpThumb.php?'.$width.$height.'&src='.urlencode($params['src']);
		unset($params['src']);
		
		
		
		$tag = "<img src=\"$src\" ";
		foreach ( array_keys($params) as $key){
			$tag .= $key.'="'.$params[$key].'" ';
		}
		
		$tag .= "/>";
		
		return $tag;
	
	}
	
	
	/**
	 * Returns an associative array of actions matching the criteria.
	 *
	 * @param var The name of the variable to store the actions in.
	 * @param record A Dataface record object.
	 * @param table The name of a table
	 * @param category The category of actions.
	 */
	function actions($params, &$smarty){
		if ( !isset($params['var']) ) throw new Exception('actions: var is a required parameter.', E_USER_ERROR);
		$varname = $params['var'];
		unset($params['var']);
		import( 'Dataface/ActionTool.php');
		$actionTool =& Dataface_ActionTool::getInstance();
		if ( !isset($params['record']) ){
			$params['record'] =& $this->ENV['record'];
		}
		$actions = $actionTool->getActions($params);
		$context = array($varname=>$actions);
		$smarty->assign($context);
	
	}
	
	
	function actions_menu($params, &$smarty){
		
		$context = array();
		if ( isset( $params['id'] ) ) {
			$context['id'] = $params['id'];
			unset($params['id']);
		} else {
			$context['id'] = '';
		}
		if ( isset( $params['class'] ) ) {
			$context['class'] = $params['class'];
			unset($params['class']);
		} else {
			$context['class'] = '';
		}
		
		if ( isset( $params['id_prefix'] ) ) {
			$context['id_prefix'] = $params['id_prefix'];
			unset($params['id_prefix']);
		} else {
			$context['id_prefix'] = '';
		}
		
		if ( isset( $params['selected_action'] ) ) {
			$context['selected_action'] = $params['selected_action'];
			unset($params['selected_action']);
		} else {
			$context['selected_action'] = '';
		}
		
		if ( isset( $params['actions'] ) ){
			$addon_actions = & $params['actions'];
		} else {
			$addon_actions = null;
		}
		
		
		
			
		//$params['var'] = 'actions';
		//$this->actions($params, $smarty);
		//print_r($
		import( 'Dataface/ActionTool.php');
		$actionTool =& Dataface_ActionTool::getInstance();
		$actions = $actionTool->getActions($params);
		if ( $addon_actions !== null ){
			$p2 = $params;
			unset($p2['category']);
			$actions = array_merge($actions, $actionTool->getActions($p2,$addon_actions));
			usort($actions, array(&$actionTool, '_compareActions'));
		}
		
		foreach ($actions as $k=>$a){
			if ( @$a['subcategory'] ){
				$p2 = $params;
				$p2['category'] = $a['subcategory'];
				$subactions = $actionTool->getActions($p2);
				
				$actions[$k]['subactions'] = $subactions;

			}
			
		}
		//print_r($actions);
		$context['actions'] =& $actions;
		//$smarty->assign($context);
		if ( isset($params['mincount']) and intval($params['mincount']) > count($context['actions']) ) return;
		if ( isset($params['maxcount']) and intval($params['maxcount']) < count($context['actions']) ){
			$more = array(
				'name'=>'more',
				'label'=> df_translate('actions_menu.more.label', 'More'),
				'subactions' => array(),
				'description' => df_translate('actions_menu.more.description','More actions...'),
				'url'=>'#'
				
				
			);
			
			$existing = array();
			
			$i = 0;
			$lastExistingKey = null;
			foreach ($actions as $k=>$a){
				$i++;
				if ( $i< $params['maxcount'] ){
					$existing[$k] = $a;
					$lastExistingKey = $k;
				} else {
					$more['subactions'][$k] = $a;
					
				}
			}
			$existing['more'] = $more;
			$context['actions'] = $existing;
		}
		$smarty->display($context, 'Dataface_ActionsMenu.html');
	
	}
	
	function record_actions($params, &$smarty){
		$params['category'] = 'record_actions';
		return $this->actions_menu($params, $smarty);
	}
	
	function record_tabs($params, &$smarty){
		$params['category'] = 'record_tabs';
		if ( is_a($this->ENV['record'], 'Dataface_Record') ){
			$params['record'] =& $this->ENV['record'];
		}
		$table =& Dataface_Table::loadTable($this->ENV['table']);
		$params2 = array();
		
		$params['actions'] = $table->getRelationshipsAsActions($params2);
		return $this->actions_menu($params, $smarty);
		
	}
	
	
	function summary_list($params, &$smarty){
		import('Dataface/SummaryList.php');
		$sl = new Dataface_SummaryList($params['records']);
		return $sl->toHtml();
	}
	
	function glance_list($params, &$smarty){
		import('Dataface/GlanceList.php');
		$gl = new Dataface_GlanceList($params['records']);
		return $gl->toHtml();
	}
	
	function sort_controller($params, &$smarty){
		import('Dataface/SortControl.php');
		if ( !isset($params['fields']) ){
			if ( !isset($params['table']) ) $params['table'] = $this->ENV['QUERY']['-table'];
			$params['fields'] = $params['table'];
		}
		
		$fields = $params['fields'];
		if ( isset($params['prefix']) ){
			$params['prefix'] = null;	
		}
		$sc = new Dataface_SortControl($fields, $params['prefix']);
		return $sc->toHtml();
	}

	
	function use_macro($params, $content, &$smarty){
		if ( isset( $content ) ){
			
			$smarty->display($params['file']);
			$stack =& $smarty->get_template_vars('__macro_stack__');
			array_pop($stack);
			
		} else {
			$vars =& $smarty->get_template_vars();
			if ( !isset($vars['__macro_stack__']) || !is_array($vars['__macro_stack__']) ){
				$stack = array();
			
				$vars['__macro_stack__'] =& $stack;
			}
			array_push($vars['__macro_stack__'], array());
			
		}
	
	}
	
	function editable($params, $content, &$smarty){
		if ( isset($content) ){
			if ( $this->app->_conf['usage_mode'] == 'edit' ){
				return <<<END
				<span id="{$params['id']}" class="editable">{$content}</span>
END;
			} else {
				return $content;
			}
		
		}
	}

	
	
	function define_slot($params, $content,  &$smarty, &$repeat){
		if ( isset($content) ) {
			if ( $repeat) echo "We are repeating $params[name]";
			if ( @$this->app->_conf['debug'] ) $content = '<!-- Begin Slot '.$params['name'].' -->'.$content.'<!-- End Slot '.$params['name'].' -->';
			return $content;
		}
		
		// From this point on we can assume we're in the first iteration
		$stack =& $smarty->get_template_vars('__macro_stack__');
		$local_vars =& $stack[count($stack)-1];
		foreach ( array_reverse(array_keys($stack) ) as $macroIndex) {
			$local_vars =& $stack[$macroIndex];
			if ( isset( $local_vars['__slots__'][$params['name']]) ){
				// we found a slot to display here.
				// tell smarty not to execute the inside of this
				$repeat=false;	// 
				echo $local_vars['__slots__'][$params['name']];
				//display the slot and return
				return;
			} 
			unset($local_vars);
		}
		if ( isset($params['table']) ) $tname = $params['table'];
		else $tname = $this->ENV['table'];
		
		$table =& Dataface_Table::loadTable($tname);
		$out = $table->getBlockContent($params['name']);
		if ( isset($out) ) {
			// We found a block to display here.
			$repeat = false;	// tell smarty not to execute inside of tag
			
			// Display the block and return
			echo $out;
			return;
		}
		
	
	}
	
	function fill_slot($params, $content, &$smarty){
		if ( isset($content) ){
			// we are opening the tag
			$stack =& $smarty->get_template_vars('__macro_stack__');
			$vars =& $stack[count($stack)-1];
			$vars['__slots__'][$params['name']] = $content;
			return '';
		}
	
	}
	
	function translate($params, $content, &$smarty){
		if ( isset($content) ){
			if ( !isset($params['id']) )  return $content;
			$id = $params['id'];
			unset($params['id']);
			return $this->languageTool->translate($id, $content, $params);
		}
	}
	
	function result_controller($params,&$smarty){
		
		if ( isset($params['table']) ){
			import('Dataface/ResultController.php');
			$base_url = ( isset($params['base_url']) ? $params['base_url'] : '');
			$query = ( isset($params['query']) ? $params['query'] : array('-table'=>$params['table']));
			$query['-table'] = $params['table'];
			$controller = new Dataface_ResultController($params['table'], '', $base_url, $query);
		
		} else {
			$controller =& $this->getResultController();
		}
		echo $controller->toHtml();
		
	}
	
	
	function next_link($params, &$smarty){
		$controller =& $this->getResultController();
		echo $controller->getNextLinkHtml(null, @$params['mode']);
		
	}
	
	function prev_link($params, &$smarty){
		$controller =& $this->getResultController();
		echo $controller->getPrevLinkHtml(null, @$params['mode']);
	}
	
	function jump_menu($params,&$smarty){
		$controller =& $this->getResultController();
		echo $controller->jumpMenu();
	}
	
	function limit_field($params, &$smarty){
		$controller =& $this->getResultController();
		echo $controller->limitField();
	}
	
	function result_index($params, &$smarty){
		$controller =& $this->getResultController();
		echo $controller->getPageIndexHtml();
	}
	function result_list($params, &$smarty){
		import( 'Dataface/ResultList.php');
		$query =& $this->app->getQuery();
		
		if ( isset($params['columns']) ){
			$columns = explode(',',$params['columns']);
		} else {
			$columns = array();
		}
		$list = new Dataface_ResultList( $query['-table'], $this->app->db(), $columns, $query);
		echo $list->toHtml();
	
	}
	
	
	function filters($params, &$smarty){
		import( 'Dataface/ResultList.php');
		$query =& $this->app->getQuery();
		
		if ( isset($params['columns']) ){
			$columns = explode(',',$params['columns']);
		} else {
			$columns = array();
		}
		$list = new Dataface_ResultList( $query['-table'], $this->app->db(), $columns, $query);
		echo $list->getResultFilters();
	}
	
	function records($params, &$smarty){
		$table = null;
		if ( isset($params['table']) ){
			$table = $params['table'];
			unset($params['table']);
		}
		
		if ( isset($params['var']) ){
			$varname = $params['var'];
			unset($params['var']);
		} else {
			throw new Exception("{records} tag requires the var parameter to be set.");
		}
		
		$q = array_merge($this->app->getQuery(), $params);
		if ( isset($table) ) $q['-table'] = $table;
		$vars =& $smarty->get_template_vars();
		$vars[$varname] = df_get_records_array($q['-table'], $q);
	}
	
	function related_list($params, &$smarty){
		import('Dataface/RelatedList.php');
		$query =& $this->app->getQuery();
		if ( isset($params['record']) ) $record =& $params['record'];
		else $record =& $this->ENV['resultSet']->loadCurrent();
		
		if ( !$record ) {
			throw new Exception('No record found from which to form related list.', E_USER_ERROR);
		}
		
		if ( isset($params['relationship']) ){
			$relationship = $params['relationship'];
		} else if ( isset($query['-relationship']) ){
			$relationship = $query['-relationship'];
		} else {
			throw new Exception('No relationship specified for related list.', E_USER_ERROR);
		}
		
		$relatedList = new Dataface_RelatedList($record, $relationship);
		echo $relatedList->toHtml();
	}
	
	function bread_crumbs($params, &$smarty){
		$base = null;
		if ( $this->app->_query['-mode'] === 'browse' and $this->app->_query['-action'] != 'new'){
			$record =& $this->app->getRecord();
			$base = '';
			if ( $record ){
				foreach ( $record->getBreadCrumbs() as $label=>$url){
					$base .= ' :: <a href="'.$url.'" id="bread-crumbs-'.str_replace(' ','_', $label).'">'.$label.'</a>';
				}
			}
			$base = substr($base, 4);
			
		} 
		
		$del = Dataface_Application::getInstance()->getDelegate();
		if ( !$base and $del and method_exists($del, 'getBreadCrumbs') ){
			$bc = $del->getBreadCrumbs();
			if ($bc ){
				$base = '';
			
				foreach ( $bc as $label=>$url){
					$base .= ' :: <a href="'.$url.'" id="bread-crumbs-'.str_replace(' ','_', $label).'">'.$label.'</a>';
				}
			}
		}
		if ( !$base ){
			$table =& Dataface_Table::loadTable($this->ENV['table']);
			$base = $table->getLabel();
		}
		
		
		
		$action =& $this->app->getAction();
		if ( PEAR::isError($action) ){
			return '';
		}
		$base .= ' :: '.Dataface_LanguageTool::translate(
			$action['label_i18n'],
			$action['label']);
		return "<b>".df_translate('scripts.Dataface_SkinTool.LABEL_BREADCRUMB', "You are here").":</b> ".$base;
	}
	
	function search_form($params, &$smarty){	
		$query =& $this->app->getQuery();
		$table = isset($params['table']) ? $params['table'] : $query['-table'];
		$form =& df_create_search_form($table, $query);
		ob_start();
		$form->display();
		$out = ob_get_contents();
		ob_end_clean();
		return $out;
	
	}
	
	/**
	 * Checks to see if the current user has a particular permission on a given record.
	 *
	 * @param array $params Associative array of parameters.
	 * @param string $content Since this method acts as a block tag, the second time
	 * it is called, it is passed the content of the block in this parameter.
	 * @param Smarty &$smarty Reference to the SkinTool object.
	 * @smarty-block boolean if_allowed
	 * @smarty-param string permission The name of the permission that is being checked.  e.g. 'edit', or 'view'.
	 * @smarty-param Dataface_Record record The record to check the permission against.
	 */
	function if_allowed($params, $content, &$smarty){
		if ( isset( $content ) ){
			if ( !isset( $params['permission'] ) ){
				throw new Exception('Missing permission parameter in if_allowed tag.', E_USER_ERROR);
			}
			if ( isset( $params['record']) ) {
				$allowed = $params['record']->checkPermission($params['permission'],$params);
			} else if ( isset($params['table']) ){
				$table =& Dataface_Table::loadTable($params['table']);

				$perms = $table->getPermissions($params);
				$allowed = @$perms[$params['permission']];
			} else {
				$perms = Dataface_Application::getInstance()->getPermissions();
				if ( @$perms[$params['permission']] ) $allowed = true;
				else $allowed = false;
			}
			if ( $allowed ) return $content;
			return '';
		}
	}
	
	function language_selector($params, &$smarty){
		$languageTool =& Dataface_LanguageTool::getInstance();
		echo $languageTool->getLanguageSelectorHtml($params);
	}
	
	function block($params, &$smarty){
		ob_start();
		df_block($params);
		$out = ob_get_contents();
		ob_end_clean();
		return $out;
		
	}
	
	function feed($params, &$smarty){
		if ( isset($params['query']) ) parse_str($params['query'], $query);
		else $query = array();
		unset($params['query']);
		
		if ( isset($params['table']) ){
			$query['-table'] = $params['table'];
			unset($params['table']);
		}
		
		if ( isset($params['relationship']) ){
			$query['-relationship'] = $params['relationship'];
			unset($params['relationship']);
		}
		
		if ( isset($params['format']) ){
			$query['--format'] = $params['format'];
			unset($params['format']);
		}
		
		if ( isset($params['url']) ){
			$url = $params['url'];
		} else {
			$url = DATAFACE_SITE_HREF;
		}
		
		if ( isset($params['size']) and $params['size'] == 'large' ){
			$icon = 'feed-icon-28x28.png';
		} else {
			$icon = 'feed-icon-14x14.png';
		}
		
		$query['-action'] = 'feed';
		$app =& Dataface_Application::getInstance();
		$appq = $app->url($query);
		$url = $url .'?'.substr( $appq, strpos($appq,'?')+1);
		echo '<a style="display:inline !important" class="feed-link" href="'.df_escape($url).'" title="Subscribe to feed"><img style="display:inline !important" src="'.DATAFACE_URL.'/images/'.$icon.'" alt="Feed"/></a>';
	}
	
	function abs($params, $url, &$smarty){
		return df_absolute_url($url);
	}
	
	function form_context($params, &$smarty){
		$query = Dataface_Application::getInstance()->getQuery();
		$exclude = array();
		if ( @$params['exclude'] ){
			$tmp = array_map('trim', explode(',', $params['exclude']));
			foreach ($tmp as $t){
				$exclude[$t] = $t;
			}
		}
		$fields = array();
		foreach ($query as $k=>$v){
			if ( isset($exclude[$k]) ) continue;
			if ( is_string($v) and strlen($k)>1 and $k{0} === '-' and $k{1} !== '-' ){
				$fields[] = '<input type="hidden" name="'.df_escape($k).'" value="'.df_escape($v).'"/>';
			} else if ( @$params['filters'] and is_string($v) and strlen($v)>0 and strlen($k)>0 and $k{0} !== '-'){
				$fields[] = '<input type="hidden" name="'.df_escape($k).'" value="'.df_escape($v).'"/>';
			}
		}
		
		
		return implode("\n", $fields);
	}
	
	
	
	
	


}
