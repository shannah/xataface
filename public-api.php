<?php
/*
 * Xataface Web Application Framework
 * Copyright (C) 2005-2011 Web Lite Solutions Corp (shannah@sfu.ca)
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
 *
 * @file dataface-public-api.php
 * Author:	Steve Hannah <shannah@sfu.ca>
 * Created:	December 14, 2005
 *
 * Description:
 * ------------
 * A Procedural API to the Dataface framework.  This is designed to same developers
 * time trying to figure out what all the classes are and what they do.  This api
 * provides functions to access all importand aspects of the framework.
 *
 */


if ( !defined( 'DATAFACE_PUBLIC_API_LOADED' ) ){
define('DATAFACE_PUBLIC_API_LOADED', true);
define('XFROOT', dirname(__FILE__).DIRECTORY_SEPARATOR);
define('XFLIB', XFROOT.'lib'.DIRECTORY_SEPARATOR);
if (!defined('XF_USE_OPCACHE')) {
    define('XF_USE_OPCACHE', false);
}
/**
 * 
 * Initializes the dataface framework.
 *
 * @param string $site_path 
 *		The path to your site's access point.
 * @param string $dataface_url 
 *		The URL to the Xataface directory.
 * @param array $conf
 *		(optional) Configuration parameters to override the defaults in conf.ini
 *
 * @return Dataface_Application The Dataface_Application object.
 *
 */
function df_init($site_path, $dataface_url, $conf=null){
	define('XFAPPROOT', dirname($site_path).DIRECTORY_SEPARATOR);
	require_once dirname(__FILE__).'/init.php';
	init($site_path, $dataface_url);

	import( XFROOT.'PEAR.php');
	import( XFROOT.'Dataface/Application.php'); 
			
	
	$app = Dataface_Application::getInstance($conf);
	if ( df_get_file_system_version() != df_get_database_version() ){
		$res = df_update();
		if (PEAR::isError($res) ){
			throw new Exception($res->getMessage(), E_USER_ERROR);
		}
	}
	return $app;

}
/* @} */

function df_error_log($arg) {
	$app = Dataface_Application::getInstance();
	$del = $app->getDelegate();
	$uuid = df_uuid();
	if ($del and method_exists($del, 'error_log')) {
		$del->error_log($arg, $uuid);
	} else {
		
		error_log($uuid.">");
		error_log($arg);
		error_log("<".$uuid);
	}
	return $uuid;
}

function df_uuid() {
    return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        // 32 bits for "time_low"
        mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),

        // 16 bits for "time_mid"
        mt_rand( 0, 0xffff ),

        // 16 bits for "time_hi_and_version",
        // four most significant bits holds version number 4
        mt_rand( 0, 0x0fff ) | 0x4000,

        // 16 bits, 8 bits for "clk_seq_hi_res",
        // 8 bits for "clk_seq_low",
        // two most significant bits holds zero and one for variant DCE1.1
        mt_rand( 0, 0x3fff ) | 0x8000,

        // 48 bits for "node"
        mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
    );
}

function df_secure(&$records, $secure=true){
	foreach ($records as $record){
		$record->secureDisplay = $secure;
	}
}

if ( !function_exists('xmlentities') ){
	function xmlentities($string) {
		return str_replace ( array ( '&', '"', "'", '<', '>', '' ), array ( '&amp;' , '&quot;', '&apos;' , '&lt;' , '&gt;', '&apos;' ), $string );
	}
}

function df_update(){
	import(XFROOT.'actions/install.php');
	$action = new dataface_actions_install;
	$params = array();
	$res = $action->handle($params);
	if ( PEAR::isError($res) ){
		return $res;
	}

}



function &df_create_new_record_form($table, $fields=null){
	import( XFROOT.'Dataface/QuickForm.php');
	$form = Dataface_QuickForm::createNewRecordForm($table, $fields);
	return $form;
}

function &df_create_edit_record_form(&$table, $fields=null){
	import(XFROOT.'Dataface/QuickForm.php');
	$form = Dataface_QuickForm::createEditRecordForm($table, $fields);
	return $form;

}

function &df_create_new_related_record_form(&$record, $relationshipName, $fieldNames=null){
	import( XFROOT.'Dataface/ShortRelatedRecordForm.php');
	$form = new Dataface_ShortRelatedRecordForm($record,$relationshipName,'', $fieldNames);
	return $form;

}


function &df_create_existing_related_record_form(&$record, $relationshpName){
	import( XFROOT.'Dataface/ExistingRelatedRecordForm.php');
	$form = new Dataface_ExistingRelatedRecordForm($record, $relationshipName);
	return $form;
}




function &df_create_import_form(&$table, $relationshipName=null){
	import( XFROOT.'Dataface/ImportForm.php');
	$form = new Dataface_ExistingRelatedRecordForm($record, $relationshipName);
	return $form;

}

function &df_create_search_form($tablename, $query=array(), $fields=null){
	import( XFROOT.'Dataface/SearchForm.php');
	$app = Dataface_Application::getInstance();
	$form = new Dataface_SearchForm($tablename, $app->db(), $query, $fields);
	return $form;
}



function &df_get_records($table, $query=null, $start=null, $limit=null, $preview=true){
	import( XFROOT.'Dataface/QueryTool.php');
	$app = Dataface_Application::getInstance();
	if ( $query === null and $start === null and $limit === null ){
		$queryTool = Dataface_QueryTool::loadResult($table);
	} else {
		if ( $query === null or !$query ) $query = array();
		if ( $start !== null ) $query['-skip'] = $start;
		if ( $limit !== null ) $query['-limit'] = $limit;
		
		$queryTool = new Dataface_QueryTool($table, null, $query);
	}
	
	$queryTool->loadSet('',false,false,$preview);
	$it = $queryTool->iterator();
	return $it;
}

function &df_get_records_array($table, $query=null, $start=null, $limit=null, $preview=true){
	$records = array();
	$it = df_get_records($table,$query,$start,$limit,$preview);
	if ( PEAR::isError($it) )return $it;
	while ($it->hasNext()){
		$records[] = $it->next();
	}
	return $records;
}

function &df_get_related_records($query=array()){
	if ( !isset($query['-relationship']) ) return PEAR::raiseError("No relationship specified");
	
	$source = df_get_record($query['-table'],$query);
	if ( !$source ) return PEAR::raiseError("Source record not found");
	if ( PEAR::isError($source) ) return $source;
	
	$relationship = $source->_table->getRelationship($query['-relationship']);
	
	if ( isset( $query['-related:sort']) ){
		$sortcols = explode(',', trim($query['-related:sort']));
		$sort_columns = array();
		foreach ($sortcols as $sortcol){
			$sortcol = trim($sortcol);
			if (strlen($sortcol) === 0 ) continue;
			$sortcol = explode(' ', $sortcol);
			if ( count($sortcol) > 1 ){
				$sort_columns[$sortcol[0]] = strtolower($sortcol[1]);
			} else {
				$sort_columns[$sortcol[0]] = 'asc';
			}
		}
		unset($sortcols);	// this was just a temp array so we get rid of it here
	} else {
		$sort_columns = array();
	}
	$sort_columns_arr = array();
	foreach ( $sort_columns as $colkey=>$colorder) {
		$sort_columns_arr[] =  '`'.$colkey.'`'. $colorder;
	}
	if ( count($sort_columns_arr) > 0 ){
		$sort_columns_str = implode(', ',$sort_columns_arr);
	} else {
		$sort_columns_str = 0;
	}
	
	if ( isset($query['-related:search']) ){
		$rwhere = array();
		foreach ($relationship->fields() as $rfield){
			//list($garbage,$rfield) = explode('.', $rfield);
			$rwhere[] = '`'.str_replace('.','`.`',$rfield).'` LIKE \'%'.addslashes($query['-related:search']).'%\'';
		}
		$rwhere = implode(' OR ', $rwhere);
	} else {
		$rwhere = 0;
	}
	$start = isset($query['-related:start']) ? $query['-related:start'] : 0;
 	$limit = isset($query['-related:limit']) ? $query['-related:limit'] : 30;
 	
 	$out =& $source->getRelatedRecordObjects($query['-relationship'], $start, $limit, $rwhere, $sort_columns_str);
 	return $out;
}


function df_singularize($label){
	if ( preg_match('/s$/i', $label) ){
		if ( preg_match('/ies$/i', $label) ){
			return preg_replace('/ies$/i', 'y', $label);
		} else if ( preg_match('/([^aeiouy]{2})es$/i', $label) 
				and !preg_match('/[^l]les$/i', $label)
				and !preg_match('/ees$/i', $label)
				and !preg_match('/[aeoiuy][qwrtpsdfghjklzxcvbnm]es$/i', $label)
				
		){
			return preg_replace('/es$/', '', $label);
		} else {
			return preg_replace('/s$/', '', $label);
		}
	}
	return $label;
}


function df_append_query($url, $query){
	if ( strpos($url,'?') === false ){
		$url .= '?';
	}
	foreach ($query as $k=>$v){
		$url .= '&'.urlencode($k).'='.urlencode($v);
	}
	return $url;
}

/**
 * Clears all of the calculated views in the database.
 */
function df_clear_views(){
    import(XF_ROOT.'actions/clear_views.php');
    $action = new dataface_actions_clear_views();
    $action->clear_views();
}

/**
 * Clears all of the caches in this application.  This includes opcache, views,
 * templates_c directory, and output_cache.
 */
function df_clear_cache(){
    import(XFROOT.'actions/clear_cache.php');
    $action = new dataface_actions_clear_cache();
    $params = [];
    $action->clear_cache($params);
}


function &df_get_table_info($tablename){
	import( XFROOT.'Dataface/Table.php');
	$table = Dataface_Table::loadTable($tablename);
	return $table;
}

function &df_get_record($table, $query, $io=null){
	import( XFROOT.'Dataface/Record.php');
	import( XFROOT.'Dataface/IO.php');
	
	$record = new Dataface_Record($table, array());
	if ( !isset($io) ){
		$io = new Dataface_IO($table);
	}
	
	$query['-limit'] = 1;
	if ( @$query['-cursor'] > 0 ){
		$query['-skip'] = $query['-cursor'];
	} 
	
	$res = $io->read($query, $record);
	if ( PEAR::isError($res) ) {
		//print_r($query);
		//echo $res->toString();
		$null = null;
		return $null;
	}
	return $record;
}

function &df_get_record_by_id($id){
	import(XFROOT.'Dataface/IO.php');
	@list($id,$fieldname) = explode('#', $id);
	$record = Dataface_IO::getByID($id);
	return $record;
}
/**
 * Parses a Dataface Record URI into an array.  URIs can be used to uniquely
 * identify records or fields in the database.
 * URIs are of the form:
 * [action://]tablename[/relationship]?[query_string][#fieldname]
 *
 * Examples:
 * ---------
 * Products?ProductID=1 
 *		- References the record from the Products table with ProductID=1
 * Products/Features?ProductID=1&Features::FeatureID=10
 *		- References the related record in the Features relationship
 *		  which is related to the Product with ProductID=1 and such that the
 *		  FeatureID of the feature is 10.
 * Products?ProductID=1#Name
 *		- References the name field in the Products table.  Specifically the
 *		  value of the name field for the Product with ID=1
 * new://Products
 *		- References a new product in the Products table.
 * new://Products?ProductName=Video
 *		- References a new product in the Products table, with initial ProductName
 *		  set to 'Video'
 *
 * The output array will look like:
 * array(
 *  	'table'=> 			string(The name of the table),
 *		'relationship'=>	string(The name of the relationship or null),
 *		'query'=>			array(The query parameters - this can be passed to df_get_record)
 *		'related_where'=>	string(The Where clause to identify the related record or null)
 *		'field'=>			string(The name of the field or null)
 *		'action'=>			string(The name of the action or null.  Either 'new' or null).
 * )
 */
function df_parse_uri($uri){
	static $cache = 0;
	if ( $cache === 0 ) $cache = array();
	
	if ( !isset($cache[$uri])  ){
		$out = array(
			'table'=>null,
			'relationship'=>null,
			'query'=>null,
			'related_where'=>null,
			'field'=>null,
			'action'=>null
			);
		if ( strpos($uri, '?') !== false ){
			list($table,$query) = explode('?', $uri);
		} else {
			if ( strpos($uri, '#') !== false ){
				list($table, $fieldname) = explode('#', $uri);
				$query = null;
			} else {
				$table = $uri;
				$query = null;
				$fieldname = null;
			}
		}
		if ( strpos($query,'#') !== false ) 
			list($query,$fieldname) = explode('#', $query);
		
		//if ( !isset($table) || !isset($query)) return PEAR::raiseError("Dataface_IO::getByID expects an id of a specific form, but received ".$uri, DATAFACE_E_ERROR);
		
		if ( strpos($table, '://') !== false ){
			list($action, $table) = explode('://', $table);
			$out['action'] = $action;
		}
		
		@list($table, $relationship) = explode('/', $table);
		
		// Find the keys for this one
		$params = explode('&',$query);
		$params2 = array();
		foreach ($params as $param){
			if ( !$param) continue;
			list($key,$val) = explode('=', $param);
			$params2[trim(urldecode($key))] = trim(urldecode($val));
		}
		
		$out['table'] = $table;
		
		if ( !isset($relationship) ){
			$out['query'] = $params2;
			
			if ( isset($fieldname) ){
				$out['field'] = $fieldname;
			}
			
		
		} else {
			$out['relationship'] = $relationship;
			$primary_params = array();
			$related_params = array();
			foreach ($params2 as $key=>$val){
				@list($key1,$key2) = explode('::',$key);
				if ( !isset($key2) ){
					$primary_params[trim(urldecode($key1))] = trim(urldecode($val));
				} else {
					$related_params[trim(urldecode($key2))] = trim(urldecode($val));
				}	
			}
			
			if ( count($related_params) > 0 ){
				$sql = array();
				foreach ($related_params as $k=>$v){
					$sql[] = "`{$k}`='{$v}'";
				}
				$sql = implode(' and ', $sql);
				$out['related_where'] = $sql;
			}
			
			$out['query'] = $primary_params;
			
			if ( isset($fieldname) ) {
				$out['field'] = $fieldname;
			}
			
		
		}
		// Let's make sure we don't overload it.  If the array is greater
		// than 250 items, we'll remove the first one in order to add this
		// next one.
		if ( count($cache) > 200 ) array_shift($cache);
		$cache[$uri] = $out;
	}
	return $cache[$uri];
		
		

}

function df_get_selected_records($query){
	if ( isset($query['--selected-ids']) ){
		$selected = $query['--selected-ids'];
	} else if ( isset($query['-selected-ids']) ){
		$selected = $query['-selected-ids'];
	} else {
		return array();
	}
	
	$ids = explode("\n", $selected);
	$records = array();
	foreach ($ids as $id){
		$records[] = df_get_record_by_id($id);
	}
	return $records;
}

function df_save_record(&$record, $keys=null, $lang=null, $secure=false){
	import( XFROOT.'Dataface/Record.php');
	import( XFROOT.'Dataface/IO.php');
	
	$io = new Dataface_IO($record->_table->tablename);
	if ( isset($lang) ) $io->lang = $lang;
	$res = $io->write($record, $keys, null, $secure);
	$io->__destruct();
	unset($io);
	return $res;

}

/**
 * Adds a javascript script dependency in the current response.
 * @param string $script The path or URL of the script to include.
 * @param boolean $useJavascriptTool Whether to use the javascript tool.
 */
function xf_script($script, $useJavascriptTool=true) {
    $app = Dataface_Application::getInstance();
    if ($useJavascriptTool) {
        $pos = strpos($script, ':');
        if ($pos == 4 and substr($script, 0, 4) == 'http') {
            $useJavascriptTool = false;
        } else if ($pos == 5 and substr($script, 0, 5) == 'https') {
            $useJavascriptTool = false;
        } else if (strlen($script) > 2 and substr($script, 0, 2) == '//') {
            $useJavascriptTool = false;
        }
        
    }
    if ($useJavascriptTool) {
        import(XFROOT.'Dataface/JavascriptTool.php');
        Dataface_JavascriptTool::getInstance()->import($script);
    } else {
        if (strpos($script, '?') !== false) {
            $script .= '&v='.$app->getApplicationVersion();
        } else {
            $script .= '?v='.$app->getApplicationVersion();
        }
        Dataface_Application::getInstance()->addHeadContent('<script src="'.htmlspecialchars($script).'"></script>');
    }
}

/**
 * Adds a CSS stylesheet dependency to the current response.
 * @param string $sheet The path or URL of the stylesheet.
 * @param $useCSSTool Whether to use the CSS tool.
 */
function xf_stylesheet($sheet, $useCSSTool=true) {
    $app = Dataface_Application::getInstance();
    if ($useCSSTool) {
        $pos = strpos($sheet, ':');
        if ($pos == 4 and substr($sheet, 0, 4) == 'http') {
            $useCSSTool = false;
        } else if ($pos == 5 and substr($sheet, 0, 5) == 'https') {
            $useCSSTool = false;
        } else if (strlen($sheet) > 2 and substr($sheet, 0, 2) == '//') {
            $useCSSTool = false;
        }
        
    }
    if ($useCSSTool) {
        import(XFROOT.'Dataface/CSSTool.php');
        Dataface_CSSTool::getInstance()->import($sheet);
    } else {
        if (strpos($sheet, '?') !== false) {
            $sheet .= '&v='.$app->getApplicationVersion();
        } else {
            $sheet .= '?v='.$app->getApplicationVersion();
        }
        Dataface_Application::getInstance()->addHeadContent('<link rel="stylesheet" type="text/css" href="'.htmlspecialchars($sheet).'"/>');
    }
}

/**
 * Gets a valuelist (an associative array of key-value pairs) for the given table.
 * @param string $tablename The name of the table.
 * @param string $valuelistname The name of the valuelist.
 * @return [string => string] Values in the valuelist as associative array.
 */
function &df_get_valuelist($tablename, $valuelistname){
	$table = Dataface_Table::loadTable($tablename);
	$vl =& $table->getValuelist($valuelistname);
	return $vl;
}



function &df_get_relationship_info($tablename, $relationshipname){
	$table = df_get_table_info($tablename);
	$relationship = $table->getRelationship($relationshipname);
	return $relationship;
}

/**
 * Registers a skin with the skin tool.
 * @param string $name The name of the skin.
 * @param string $template_dir The path to the templates directory for this skin.
 */
function df_register_skin($name, $template_dir){
	import( XFROOT.'Dataface/SkinTool.php');
	$st = Dataface_SkinTool::getInstance();
	$st->register_skin($name, $template_dir);

}

/**
 * Displays a template.
 * @param string $context Context info to pass to the template
 * @param string The path to the template in the template include path.
 * @see Dataface_SkinTool::display()
 */
function df_display($context, $template_name){
	import( XFROOT.'Dataface/SkinTool.php');
	$st = Dataface_SkinTool::getInstance();
	$app = Dataface_Application::getInstance();
	$query =& $app->getQuery();
	if (@$query['-ui-root']) {
	    ob_start();
	    $st->display($context, $template_name);
	    $contents = ob_get_contents();
	    
	    ob_end_clean();
	    
	    if (preg_match('/<\!--ui-root='.preg_quote($query['-ui-root'], '/').'-->([\s\S]*)<\!--\/ui-root='.preg_quote($query['-ui-root'], '/').'-->/', $contents, $matches)) {
	        $placeholder = '__placeholder__'.time();
	        $contents = preg_replace('/(<body[^>]*>)[\s\S]*(<\!-- end-html-body-->)/', '$1'.$placeholder.'$2', $contents);
	        $contents = str_replace($placeholder, $matches[1], $contents);
	        echo $contents;
	        return true;
	    } else {
            echo $contents;
            return true;
	    }
	} else {
	    return $st->display($context, $template_name);
	}
}

/**
 * Gets a value from the app's config.
 */
function df_config_get($varname){
	$app = Dataface_Application::getInstance();
	return $app->_conf[$varname];
}

/**
 * Sets a value in the app's config.
 */
function df_config_set($varname, $value){
	$app = Dataface_Application::getInstance();
	$app->_conf[$varname] = $value;
}

/**
 * Gets a reference to the app's database handle.
 */
function df_db(){
	$app = Dataface_Application::getInstance();
	return $app->_db;
}

/**
 * Executes an SQL query.  This is higher-level than xf_db_query() as it
 * will be subject to query caching, logging, internationalization, etc..
 * @param string $sql The SQL query.
 * @param string $lang The language of the query.  If query translation is enabled, the query 
 *  will be translated to the appropriate language.
 * @param boolean $as_array If true, return the result as an associative array.
 * @param boolean $enumerated If $as_array is true, this dictates whether to use fetch_row or fetch_assoc
 *  for each row.  Default is false (fetch_assoc).
 * @return mixed.  A database result handle if $as_array is false.  An array if $as_array is true.  If
 * the query failed. This will return false.
 *
 */
function df_query($sql, $lang=null, $as_array=false, $enumerated=false){
	import(XFROOT.'Dataface/DB.php');
	$db = Dataface_DB::getInstance();
	return $db->query($sql,null,$lang,$as_array, $enumerated);
}

/**
 * A wrapper for xf_db_insert_id() that will work correctly when query translation is enabled.
 * When query translation is enabled, a single insert query may be split up into multiple inserts
 * as it needs to insert into the translation tables as well.  Therefore xf_db_insert_id() won't 
 * work as expected.  Use this function to get the insert id of the main table.
 * @return int The insert ID of the last database query.
 */
function df_insert_id(){
	import(XFROOT.'Dataface/DB.php');
	$db = Dataface_DB::getInstance();
	return $db->insert_id();
}

/**
 * Translates a phrase using Xataface's internationalization support.
 * @param string $id The phrase identifier.
 * @param string $default Default value if no tranlsation is found.
 * @param [string => string] $params parameters to inject into the translation.
 * @param string $lang The language-code of the language to retrieve the translation for.
 * @return string The translated phrase.
 */
function df_translate($id, $default=null, $params=array(), $lang=null){
	return Dataface_LanguageTool::getInstance($lang)->translate($id,$default,$params, $lang);
}

/**
 * Loads a translation realm.  This loads some translations that would not normally be 
 * loaded.
 */
function df_load_realm($realm, $lang=null){
	Dataface_LanguageTool::getInstance($lang)->loadRealm($realm);
}

/**
 * Checks to see if the user is granted a permission on the given object.
 * @param string The permission to check.  E.g. view, edit, etc..
 * @param mixed The object to check the permission against.  Supports Dataface_Table and Dataface_Record
 * @param array $params Added parameters to pass to the permission tool.  Supported keys include 'field', and 'relationship'.
 * @return [string => int] Associative array of permissions that are granted. Keys are permissions, Values are 0 or 1.
 */
function df_check_permission($permission, &$object, $params=array() ){
	return Dataface_PermissionsTool::checkPermission($permission, $object, $params);
}

function df_permission_names_as_array(&$perms){
	$ptool = Dataface_PermissionsTool::getInstance();
	return $ptool->namesAsArray($perms);
}

function df_permission_names_as_string(&$perms){
	$ptool = Dataface_PermissionsTool::getInstance();
	return $ptool->namesAsString($perms);
}

/**
 * Displays a block with the given parameters.
 * @param [string => mixed] $params The parameters for the block.
 * @param string $params.table The table name on which the block is executed.  This dictates,
 *      which table's delegate class is checked first for the block definition.
 * @param Dataface_Record $params.record The record on which the block is run.
 * @param string $params.name The block name.  Required.  This is used to look up the correct
 *  method of the delegate class.
 *
 */
function df_block($params){
	$app = Dataface_Application::getInstance();
	$query =& $app->getQuery();
	
	if ( isset($params['table']) ) $table = Dataface_Table::loadTable($params['table']);
	else if ( isset($params['record']) ) $table = $params['record']->_table;
	else $table = Dataface_Table::loadTable($query['-table']);
		
	if ( isset($params['name']) ) $name = $params['name'];
	else throw new Exception('No name specified for block.', E_USER_ERROR);
	
	unset($params['name']); unset($params['table']);
	
	return $table->displayBlock($name, $params);
}


function df_translation_warning(&$record, $language=null){
	import(XFROOT.'Dataface/TranslationTool.php');
	$tt = new Dataface_TranslationTool();
	$tt->printTranslationStatusAlert($record, $language);
}

function df_editable($content, $id){
	$skinTool = Dataface_SkinTool::getInstance();
	return $skinTool->editable(array('id'=>$id), $content, $skinTool);
}

/**
 * Gets date as an offset (time ago).
 * @param string $date The date as a string.
 * @param boolean $long Whether to include the formatted date and the offset in the output.
 * @return string The date offset.
 */
function df_offset($date, $long=true){
	if ( !$date ){
		return df_translate('scripts.global.MESSAGE_UNKNOWN','Unknown');
	}
	
	$xWeeksAgoStr = df_translate('x weeks ago', "%d weeks ago");
	$xDaysAgoStr = df_translate('x days ago', "%d days ago");
	$todayStr = df_translate('Today',"Today");
	$yesterdayStr = df_translate('Yesterday', "Yesterday");
	$aWeekAgoStr = df_translate("a week ago", "a week ago");
	
	$date = strtotime($date);
	$offset = (strftime("%j")+strftime("%Y")*365)-
	(strftime("%j",$date)+strftime("%Y",$date)*365);
	if ($offset>7){
	$offset = (strftime("%W")+strftime("%Y")*52)-
	(strftime("%W",$date)+strftime("%Y",$date)*52);
	$end=($offset!=0?($offset>1?sprintf($xWeeksAgoStr, $offset):$aWeekAgoStr):$todayStr);
	} else
	$end=($offset!=0?($offset>1?sprintf($xDaysAgoStr, $offset):$yesterdayStr):$todayStr);
    if ($long) {
        return strftime("%A, %B %d, %Y",$date)." - ". $end;
    } else {
        return $end;
    }
	
}
/**
 * @see Dataface_IO::getByID()
 */
function &df_get($uri, $filter=null){
	$res = Dataface_IO::getByID($uri, $filter);
	return $res;
}

/**
 * @see Dataface_IO::setByID()
 */
function df_set($uri, $value){
	$res = Dataface_IO::setByID($uri, $value);
	return $res;
}

if ( !function_exists('array_merge_recursive_unique') ){
	// array_merge_recursive which override value with next value.
	// based on: http://www.php.net/manual/hu/function.array-merge-recursive.php 09-Dec-2006 03:38
	function array_merge_recursive_unique($array0, $array1){
		$func = __FUNCTION__;
		$result = array();
		$arrays = func_get_args();
		$keyarrs = array_map('array_keys', $arrays);
		$keys = array_merge($keyarrs[0], $keyarrs[1]);
		foreach ($keys as $key){
			foreach ( $arrays as $array ){
				if ( array_key_exists($key, $array) ){
					if ( is_array($array[$key]) ){
						if ( is_array(@$result[$key]) ) $result[$key] = $func($result[$key], $array[$key]);
						else $result[$key] = $array[$key];
					} else {
						$result[$key] = $array[$key];
					}
				}
			}
		}
		
		return $result;
	}
	
	
	function array_merge_recursive_unique2($array0, $array1)
	{
		$arrays = func_get_args();
		$remains = $arrays;
	
		// We walk through each arrays and put value in the results (without
		// considering previous value).
		$result = array();
	
		// loop available array
		foreach($arrays as $array) {
	
			// The first remaining array is $array. We are processing it. So
			// we remove it from remaing arrays.
			array_shift($remains);
	
			// We don't care non array param, like array_merge since PHP 5.0.
			if(is_array($array)) {
				// Loop values
				foreach($array as $key => $value) {
					if(is_array($value)) {
						// we gather all remaining arrays that have such key available
						$args = array();
						foreach($remains as $remain) {
							if(array_key_exists($key, $remain)) {
								array_push($args, $remain[$key]);
							}
						}
	
						if(count($args) > 2) {
							// put the recursion
							$result[$key] = call_user_func_array(__FUNCTION__, $args);
						} else {
							foreach($value as $vkey => $vval) {
								$result[$key][$vkey] = $vval;
							}
						}
					} else {
						// simply put the value
						$result[$key] = $value;
					}
				}
			}
		}
		return $result;
	}
}

/**
 * Checks if the user is currently logged in.
 * @return boolean True if the user is logged in.
 */
function df_is_logged_in(){
	return ( class_exists('Dataface_AuthenticationTool') and ($auth = Dataface_AuthenticationTool::getInstance()) and $auth->isLoggedIn());
}

/**
 * Converts the given URL to an absolute URL.
 */
function df_absolute_url($url){
	if ( !$url ) return $_SERVER['HOST_URI'];
	else if ( $url[0] == '/' ){
		return $_SERVER['HOST_URI'].$url;
	} else if ( preg_match('/http(s)?:\/\//', $url) ){
		return $url;
	} else {
		$host_uri = $_SERVER['HOST_URI'];
		$site_url = DATAFACE_SITE_URL;
		if ( $site_url ) {
			if ($site_url[0] == '/' ) $host_uri = $host_uri.$site_url;
			else $host_uri = $host_uri.'/'.$site_url;
		}
		
		return $host_uri.'/'.$url;
	}
}


/**
 * This is perhaps named poorly.  It returns the user's timezone. This is either
 * the value of the TZ environment variable if it is set, or 'SYSTEM',
 * which is the special value referring to the MySQL system timezone.
 */
function df_utc_offset(){
	$diff = preg_replace('/^([+-])(\d{1,2})(\d{2,2})$/', '$1$2:$3',date('O'));
	return $diff;
	
}


function df_tz_or_offset(){

	$tz = @date('e');
	if ( $tz ) return $tz;
	else return df_utc_offset();
	
}



	
	/**
	 * Returns the version number on the file system.
	 * The version number is stored in the version.txt file.
	 * The number that counts is the last integer.
	 *
	 * E.g. if the version is 5.6 beta-1 4356
	 * then the version returned here would be 4356.
	 * This allows the version to be a running total and makes it
	 * easy to apply patches and updates for new versions.
	 * @return int The version number on the file system.
	 */
	function df_get_file_system_version($refresh = false){
		static $version = -1;
		
		if ( $version == -1 ){
            
			if ( file_exists('version.txt') ){
				$varr = file('version.txt');
			
				$fs_version='0';
				if ( $varr ){
					list($fs_version) = $varr;
				}
			} else {
				$fs_version = '0';
			}
		
			$fs_version = explode(' ', $fs_version);
			$fs_version = intval($fs_version[count($fs_version)-1]);
			$version = $fs_version;
                
            
			
		} 
		if ( !$version ) return df_get_database_version();
		
		return $version;
	
	}
	
	/**
	 * Returns the application version in the database.
	 * @return int The version number in the database.
	 */
	function df_get_database_version($db=null, $refresh = false){
		if (!$db ) $db = df_db();
		static $version = -1;
		if ( $version == -1 ){
			$sql = "select `version` from dataface__version limit 1";
            
			$res = @xf_db_query($sql, $db);
			if ( !$res ){
				$res = xf_db_query("create table dataface__version ( `version` int(5) not null default 0)", $db);
				if ( !$res ) throw new Exception(xf_db_error($db), E_USER_ERROR);
				//$fs_version = df_get_file_system_version();
				$res = xf_db_query("insert into dataface__version values ('0')", $db);
				if ( !$res ) throw new Exception(xf_db_error($db), E_USER_ERROR);
			
				$res = xf_db_query($sql, $db);
				if ( !$res ){
					throw new Exception(xf_db_error($db), E_USER_ERROR);
				}
		
			}
			list($version) = xf_db_fetch_row($res);
            xf_db_free_result($res);
               
            
			
		}
		return $version;
	}




	function df_q($sql){
	
		if ( is_array($sql) ){
			if (count($sql) == 0) {
				throw new Exception("df_q cannot take an empty sql query but received ".json_encode($sql));
			}
			foreach ($sql as $q){
				$res = df_q($q);
			}
			return $res;
		} else {
			$res = xf_db_query($sql, df_db());
			if ( !$res ){
				//error_log("Error executing SQL: $sql");
				//error_log(xf_db_error(df_db()));
				throw new Exception(xf_db_error(df_db()));
			}
			return $res;
		}
	}

	/**
	 * Returns the value from a struct using a path.
	 * Paths are of the form key:subkey:subsubkey
	 */
	function df_get_from_struct(&$struct, $path){
		$parts = explode(':', $path);
		$c =& $struct;
		foreach ( $parts as $p ){
			if ( is_object($c) ){
				if ( isset($c->{$p}) ){
					$tmp =& $c->{$p};
					unset($c);
					$c =& $tmp;
				} else {
					return null;
				}
			} else if ( is_array($c) ){
				if ( isset($c[$p]) ){
					$tmp =& $c[$p];
					unset($c);
					$c =& $tmp;
				} else {
					return null;
				}
			} else {
				return null;
			}
		}
		return $c;
			
	}

	function df_IPv4To6($ip) {
		if ( strpos($ip, ':') !== false ){
			if ( $ip === '::1' ) return 'fe80::1';
			return $ip;
		}
		if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) === true) {
			if (strpos($ip, '.') > 0) {
		  		$ip = substr($ip, strrpos($ip, ':')+1);
		 	} else { //native ipv6
		  		return $ip;
		 	}
		}
		$is_v4 = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
		if (!$is_v4) { return false; }
		$iparr = array_pad(explode('.', $ip), 4, 0);
		$Part7 = base_convert(($iparr[0] * 256) + $iparr[1], 10, 16);
		$Part8 = base_convert(($iparr[2] * 256) + $iparr[3], 10, 16);
		return '::ffff:'.$Part7.':'.$Part8;
	}
	
	function df_escape($content){
	    if ( !is_string($content) ){
	        $content = "$content";
	    }
	    return htmlspecialchars($content, ENT_COMPAT, XF_OUTPUT_ENCODING);
	}
        
        function df_write_json($data){
            header('Content-type: application/json; charset="'.
                    Dataface_Application::getInstance()->_conf['oe'].'"');
            echo json_encode($data);
        }
        
        function df_post($url, $data=array(), $json=true) {

            // use key 'http' even if you send the request to https://...
            $options = array(
                'http' => array(
                    'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                    'method'  => 'POST',
                    'content' => http_build_query($data)
                )
            );
            $context  = stream_context_create($options);
            $result = file_get_contents($url, false, $context);
            if ($result === FALSE) {
                throw new Exception("HTTP request failed");
            }
            if ($json) {
                return json_decode($result, true);
            }
            return $result;
		}
		
	function df_count_actions($params=array(), $actions=null) {
		import(XFROOT.'Dataface/ActionTool.php');
		return Dataface_ActionTool::getInstance()->countActions($params, $actions);
	}
	
    function df_http_parse_headers($headers) {
        $head = array();
        foreach( $headers as $k=>$v ) {
            $t = explode( ':', $v, 2 );
            if( isset( $t[1] ) ) {
                $head[ trim($t[0]) ] = trim( $t[1] );
            } else {
                $head[] = $v;
                if( preg_match( "#HTTP/[0-9\.]+\s+([0-9]+)#",$v, $out ) ) {
                    $head['response_code'] = intval($out[1]);
                }
            }
        }
        //print_r($head);
        return $head;
    }
    
    
    function df_http_response_code() {
        import(XFROOT.'xf/io/HttpClient.php');
		return xf\io\df_http_response_code();
    }
    
    function df_http_response_headers() {
        import(XFROOT.'xf/io/HttpClient.php');
		return xf\io\df_http_response_headers();
    }
    
    function df_http_post($url, $data=array(), $json=true) {
        import(XFROOT.'xf/io/HttpClient.php');
		return xf\io\df_http_post($url, $data, $json);
    }
    
    function df_http_get($url, $headers = null, $json = true) {
        import(XFROOT.'xf/io/HttpClient.php');
		return xf\io\df_http_get($url, $headers, $json);
    }
	
    
    function xf_opcache_path($filepath) {
        return XFTEMPLATES_C . 'xf_opcache' . DIRECTORY_SEPARATOR . basename($filepath) . md5($filepath) . '.php';
    }
    
    function xf_opcache_query_path($sql) {
        return XFTEMPLATES_C . 'xf_opcache_queries' . DIRECTORY_SEPARATOR . md5($sql) . '.php';
    }
    
    function xf_opcache_is_query_cached($sql) {
        return opcache_is_script_cached(xf_opcache_query_path($sql));
    }
    
    function xf_opcache_is_script_cached($filepath) {
        return opcache_is_script_cached(xf_opcache_path($filepath));
    }
    
    function xf_opcache_cache_array($filePath, $array) {
        $varname = '$xf_opcache_export';
        $opcachePath = xf_opcache_path($filePath);
        if (file_exists($opcachePath)) {
            // For some it doesn't work to compile the file directly after adding it to the opcache.
            // This is because the timestamp has a resolution of 1 second so if the file was just written
            // it wont' be seen as eligible.
            // So this case is basically catching the 2nd time after loading the page to the disk cache.
            include($opcachePath);
            return;
        }
        $dirPath = dirname($opcachePath);
        if (!is_dir($dirPath)) mkdir($dirPath, 0777, true);
        file_put_contents($opcachePath, '<'.'?php'."\n$varname = ".var_export($array, true).';?>', LOCK_EX );
        opcache_compile_file($opcachePath);
        
    }
    
    function xf_opcache_cache_query($sql, $array) {
        $varname = '$xf_opcache_export';
        $opcachePath = xf_opcache_query_path($sql);
        if (file_exists($opcachePath)) {
            // For some it doesn't work to compile the file directly after adding it to the opcache.
            // This is because the timestamp has a resolution of 1 second so if the file was just written
            // it wont' be seen as eligible.
            // So this case is basically catching the 2nd time after loading the page to the disk cache.
            include($opcachePath);
            return;
        }
        $dirPath = dirname($opcachePath);
        if (!is_dir($dirPath))  mkdir($dirPath, 0777, true);
        file_put_contents($opcachePath, '<'.'?php'."\n$varname = ".var_export($array, true).';?>');
        opcache_compile_file($opcachePath);
    }
    
    function xf_opcache_reset() {
        opcache_reset();
        
    }
    
    function xf_is_readable($file) {
        $app = Dataface_Application::getInstance();
        $query =& $app->getQuery();
        
        if (!empty($app->_conf['use_manifest'])) {

            $manifest = $app->getManifest();
            if (!empty($manifest)) {
                            
                return !empty($manifest[$file]);
            }
            
        }
        return is_readable($file);
        
    }
    
    function xf_touch($tablename, $record = null) {
        if (!class_exists('Dataface_IO')) {
            import(XFROOT.'Dataface/IO.php');
        }
        Dataface_IO::touchTable($tablename, $record);
    }
      
} // end if ( !defined( DATAFACE_PUBLIC_API_LOADED ) ){
