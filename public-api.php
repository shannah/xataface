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
	require_once dirname(__FILE__).'/init.php';
	init($site_path, $dataface_url);

	import( 'PEAR.php');
	import( 'Dataface/Application.php'); 
			
	
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
	import('actions/install.php');
	$action = new dataface_actions_install;
	$params = array();
	$res = $action->handle($params);
	if ( PEAR::isError($res) ){
		return $res;
	}

}



function &df_create_new_record_form($table, $fields=null){
	import( 'Dataface/QuickForm.php');
	$form = Dataface_QuickForm::createNewRecordForm($table, $fields);
	return $form;
}

function &df_create_edit_record_form(&$table, $fields=null){
	import('Dataface/QuickForm.php');
	$form = Dataface_QuickForm::createEditRecordForm($table, $fields);
	return $form;

}

function &df_create_new_related_record_form(&$record, $relationshipName, $fieldNames=null){
	import( 'Dataface/ShortRelatedRecordForm.php');
	$form = new Dataface_ShortRelatedRecordForm($record,$relationshipName,'', $fieldNames);
	return $form;

}


function &df_create_existing_related_record_form(&$record, $relationshpName){
	import( 'Dataface/ExistingRelatedRecordForm.php');
	$form = new Dataface_ExistingRelatedRecordForm($record, $relationshipName);
	return $form;
}




function &df_create_import_form(&$table, $relationshipName=null){
	import( 'Dataface/ImportForm.php');
	$form = new Dataface_ExistingRelatedRecordForm($record, $relationshipName);
	return $form;

}

function &df_create_search_form($tablename, $query=array(), $fields=null){
	import( 'Dataface/SearchForm.php');
	$app = Dataface_Application::getInstance();
	$form = new Dataface_SearchForm($tablename, $app->db(), $query, $fields);
	return $form;
}



function &df_get_records($table, $query=null, $start=null, $limit=null, $preview=true){
	import( 'Dataface/QueryTool.php');
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


function df_clear_views(){


	
	
	$res = mysql_query("show tables like 'dataface__view_%'", df_db());
	$views = array();
	while ( $row = mysql_fetch_row($res) ){
		$views[] = $row[0];
	}
	if ( $views ) {
		$sql = "drop view `".implode('`,`', $views)."`";
		//echo $sql;
		//echo "<br/>";
		$res = mysql_query("drop view `".implode('`,`', $views)."`", df_db());
		if ( !$res ) throw new Exception(mysql_error(df_db()));
	}
	
	
}

function df_clear_cache(){
	$res = @mysql_query("truncate table __output_cache", df_db());
	//if ( !$res ) throw new Exception(mysql_error(df_db()));
	return $res;
}


function &df_get_table_info($tablename){
	import( 'Dataface/Table.php');
	$table = Dataface_Table::loadTable($tablename);
	return $table;
}

function &df_get_record($table, $query, $io=null){
	import( 'Dataface/Record.php');
	import( 'Dataface/IO.php');
	
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
	import('Dataface/IO.php');
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
	import( 'Dataface/Record.php');
	import( 'Dataface/IO.php');
	
	$io = new Dataface_IO($record->_table->tablename);
	if ( isset($lang) ) $io->lang = $lang;
	$res = $io->write($record, $keys, null, $secure);
	$io->__destruct();
	unset($io);
	return $res;

}

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


function df_register_skin($name, $template_dir){
	import( 'Dataface/SkinTool.php');
	$st = Dataface_SkinTool::getInstance();
	$st->register_skin($name, $template_dir);

}

function df_display($context, $template_name){
	import( 'Dataface/SkinTool.php');
	$st = Dataface_SkinTool::getInstance();
	
	return $st->display($context, $template_name);
}

function df_config_get($varname){
	$app = Dataface_Application::getInstance();
	return $app->_conf[$varname];
}

function df_config_set($varname, $value){
	$app = Dataface_Application::getInstance();
	$app->_conf[$varname] = $value;
}

function df_db(){
	$app = Dataface_Application::getInstance();
	return $app->_db;
}

function df_query($sql, $lang=null, $as_array=false, $enumerated=false){
	import('Dataface/DB.php');
	$db = Dataface_DB::getInstance();
	return $db->query($sql,null,$lang,$as_array, $enumerated);
}

function df_insert_id(){
	import('Dataface/DB.php');
	$db = Dataface_DB::getInstance();
	return $db->insert_id();
}

function df_translate($id, $default=null, $params=array(), $lang=null){
	return Dataface_LanguageTool::getInstance($lang)->translate($id,$default,$params, $lang);
}

function df_load_realm($realm, $lang=null){
	Dataface_LanguageTool::getInstance($lang)->loadRealm($realm);
}

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
	import('Dataface/TranslationTool.php');
	$tt = new Dataface_TranslationTool();
	$tt->printTranslationStatusAlert($record, $language);
}

function df_editable($content, $id){
	$skinTool = Dataface_SkinTool::getInstance();
	return $skinTool->editable(array('id'=>$id), $content, $skinTool);
}

function df_offset($date){
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
	return strftime("%A, %B %d, %Y",$date)." - ". $end;
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

function df_is_logged_in(){
	return ( class_exists('Dataface_AuthenticationTool') and ($auth = Dataface_AuthenticationTool::getInstance()) and $auth->isLoggedIn());
}

function df_absolute_url($url){
	if ( !$url ) return $_SERVER['HOST_URI'];
	else if ( $url{0} == '/' ){
		return $_SERVER['HOST_URI'].$url;
	} else if ( preg_match('/http(s)?:\/\//', $url) ){
		return $url;
	} else {
		$host_uri = $_SERVER['HOST_URI'];
		$site_url = DATAFACE_SITE_URL;
		if ( $site_url ) {
			if ($site_url{0} == '/' ) $host_uri = $host_uri.$site_url;
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
	function df_get_file_system_version(){
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
	function df_get_database_version($db=null){
		if (!$db ) $db = df_db();
		static $version = -1;
		if ( $version == -1 ){
			$sql = "select `version` from dataface__version limit 1";
			$res = @mysql_query($sql, $db);
			if ( !$res ){
				$res = mysql_query("create table dataface__version ( `version` int(5) not null default 0)", $db);
				if ( !$res ) throw new Exception(mysql_error($db), E_USER_ERROR);
				//$fs_version = df_get_file_system_version();
				$res = mysql_query("insert into dataface__version values ('0')", $db);
				if ( !$res ) throw new Exception(mysql_error($db), E_USER_ERROR);
				
				$res = mysql_query($sql, $db);
				if ( !$res ){
					throw new Exception(mysql_error($db), E_USER_ERROR);
				}
			
			}
			list($version) = mysql_fetch_row($res);
		}
		return $version;
	}




	function df_q($sql){
	
		if ( is_array($sql) ){
			foreach ($sql as $q){
				$res = df_q($q);
			}
			return $res;
		} else {
			$res = mysql_query($sql, df_db());
			if ( !$res ){
				error_log("Error executing SQL: $sql");
				error_log(mysql_error(df_db()));
				throw new Exception(mysql_error(df_db()));
			}
			return $res;
		}
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
	    return htmlspecialchars($content, ENT_COMPAT, XF_OUTPUT_ENCODING);
	}
        
        function df_write_json($data){
            header('Content-type: application/json; charset="'.
                    Dataface_Application::getInstance()->_conf['oe'].'"');
            echo json_encode($data);
        }
		
} // end if ( !defined( DATAFACE_PUBLIC_API_LOADED ) ){
