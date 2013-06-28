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
 *  File: config.inc.php
 *  Author: Steve Hannah <shannah@sfu.ca>
 *  Created: September 2005
 *  Description:
 *  -------------
 *  
 *  Initializes configuration information for Dataface.
 */
if ( !defined('XATAFACE_INI_EXTENSION') ){
	define('XATAFACE_INI_EXTENSION', '');
}
//Prevent Magic Quotes from affecting scripts, regardless of server settings

//Make sure when reading file data,
//PHP doesn't "magically" mangle backslashes!
//set_magic_quotes_runtime(FALSE);
ini_set('magic_quotes_runtime', false);
if ( !function_exists('microtime_float') ){
	function microtime_float()
	{
	   list($usec, $sec) = explode(" ", microtime());
	   return ((float)$usec + (float)$sec);
	}
}

function stripslashes_array($data) {
   if (is_array($data)){
       foreach ($data as $key => $value){
           $data[$key] = stripslashes_array($value);
       }
       return $data;
   }else{
       return stripslashes($data);
   }
}

if (get_magic_quotes_gpc()) {
	define('MAGIC_QUOTES_STRIPPED_SLASHES',1);
   /*
   All these global variables are slash-encoded by default,
   because    magic_quotes_gpc is set by default!
   (And magic_quotes_gpc affects more than just $_GET, $_POST, and $_COOKIE)
   */
   $_SERVER = stripslashes_array(@$_SERVER);
   $_GET = stripslashes_array(@$_GET);
   $_POST = stripslashes_array(@$_POST);
   $_COOKIE = stripslashes_array(@$_COOKIE);
   $_FILES = stripslashes_array(@$_FILES);
   $_ENV = stripslashes_array(@$_ENV);
   $_REQUEST = stripslashes_array(@$_REQUEST);
   $HTTP_SERVER_VARS = stripslashes_array(@$HTTP_SERVER_VARS);
   $HTTP_GET_VARS = stripslashes_array(@$HTTP_GET_VARS);
   $HTTP_POST_VARS = stripslashes_array(@$HTTP_POST_VARS);
   $HTTP_COOKIE_VARS = stripslashes_array(@$HTTP_COOKIE_VARS);
   $HTTP_POST_FILES = stripslashes_array(@$HTTP_POST_FILES);
   $HTTP_ENV_VARS = stripslashes_array(@$HTTP_ENV_VARS);
   if (isset($_SESSION)) {    #These are unconfirmed (?)
       $_SESSION = stripslashes_array($_SESSION, '');
       $HTTP_SESSION_VARS = stripslashes_array(@$HTTP_SESSION_VARS, '');
   }
   /*
   The $GLOBALS array is also slash-encoded, but when all the above are
   changed, $GLOBALS is updated to reflect those changes.  (Therefore
   $GLOBALS should never be modified directly).  $GLOBALS also contains
   infinite recursion, so it's dangerous...
   */
}






// first we resolve some differences between CGI and Module php
if ( !isset( $_SERVER['QUERY_STRING'] ) ){
	$_SERVER['QUERY_STRING'] = @$_ENV['QUERY_STRING'];	
} 

// define a HOST_URI variable to contain the host portion of all urls
$host = $_SERVER['HTTP_HOST'];
$port = $_SERVER['SERVER_PORT'];
$protocol = $_SERVER['SERVER_PROTOCOL'];
if ( strtolower($protocol) == 'included' ){
	$protocol = 'HTTP/1.0';
}
$protocol = substr( $protocol, 0, strpos($protocol, '/'));
$protocol = ((@$_SERVER['HTTPS']  == 'on' || $port == 443) ? $protocol.'s' : $protocol );
$protocol = strtolower($protocol);
$_SERVER['HOST_URI'] = $protocol.'://'.$host;//.($port != 80 ? ':'.$port : '');
if ( (strpos($_SERVER['HTTP_HOST'], ':') === false) and !($protocol == 'https' and $port == 443 ) and !($protocol == 'http' and $port == 80) ){
	$_SERVER['HOST_URI'] .= ':'.$port;
}
 
if ( defined('DATAFACE_DEBUG') and DATAFACE_DEBUG){
	/*
	 * Debug with APD.
	 */
	apd_set_pprof_trace();
}

// Define a constant for use as quotes in INI files.
// INI files can use quotes as follows:
// key = "Quoted string: "_Q"I'm in quotes"_Q"."
// I.e. just replace '"' with '"_Q"'  (excluding single quotes).
define('_Q', '"');
define('XATAFACEQ', _Q);
 
if ( !defined('DATAFACE_PATH') ){
	// Path to the Dataface installation
	define('DATAFACE_PATH', str_replace('\\','/',dirname(__FILE__)));
}
if ( !defined('DATAFACE_URL') ){
	// Webserver path to the Dataface installation
	define('DATAFACE_URL', str_replace('\\', '/', dirname($_SERVER['PHP_SELF'])));
}
if ( !defined('DATAFACE_FCKEDITOR_BASEPATH') ){
	// Webserver path to the FCKEditor installation for use with Dataface
	define('DATAFACE_FCKEDITOR_BASEPATH', DATAFACE_URL.'/lib/FCKeditor/');
	$GLOBALS['HTML_QuickForm_htmlarea']['FCKeditor_BasePath'] = DATAFACE_FCKEDITOR_BASEPATH;
	
	
}
$GLOBALS['HTML_QuickForm_htmlarea']['FCKeditor_BasePath'] = DATAFACE_FCKEDITOR_BASEPATH;

if ( !defined('DATAFACE_TINYMCE_BASEPATH') ){
	define('DATAFACE_TINYMCE_BASEPATH', DATAFACE_URL.'/lib/tiny_mce');
	
}
$GLOBALS['HTML_QuickForm_htmlarea']['TinyMCE_BasePath'] = DATAFACE_TINYMCE_BASEPATH;

if ( !defined('DATAFACE_JSCALENDAR_BASEPATH') ){
	define('DATAFACE_JSCALENDAR_BASEPATH', DATAFACE_URL.'/lib/jscalendar/');
}
$GLOBALS['HTML_QuickForm_calendar']['jscalendar_BasePath'] = DATAFACE_JSCALENDAR_BASEPATH;

if ( !defined('DATAFACE_SITE_PATH') ){
	// Path to the Site that is using Dataface.  This may be different if than DATAFACE_PATH
	// if there are multiple sites being run on a single Dataface installation.
	define('DATAFACE_SITE_PATH', DATAFACE_PATH);
}



if ( !defined('DATAFACE_SITE_URL') ){
	//  Webserver path to the current site.
	// This may be different than DATAFACE_URL if there are multiple sites being run on a single
	// dataface installation.
	define('DATAFACE_SITE_URL', DATAFACE_URL);
	define('DATAFACE_SITE_HREF', (DATAFACE_URL != '/' ? DATAFACE_URL.'/':'/').basename($_SERVER['PHP_SELF']) );
}

if ( !defined('DATAFACE_DEFAULT_CONFIG_STORAGE') ){
	// The type of storage to use for application configuration by default.
	// Options include: ini or db.
	define('DATAFACE_DEFAULT_CONFIG_STORAGE', 'ini');
}

if ( !defined('DATAFACE_CACHE_PATH') ) {
	// Find teh cache directory to cache important stuff.
	if ( file_exists( DATAFACE_SITE_PATH.'/templates_c') ){
		define('DATAFACE_CACHE_PATH', DATAFACE_SITE_PATH.'/templates_c/__cache');
	} else {
		define('DATAFACE_CACHE_PATH', DATAFACE_PATH.'/Dataface/templates_c/__cache');
	}
}

if ( !defined('TRANSLATION_PAGE_TABLE') ){
	// The name of the table that should be used to store temporary content that 
	// is being translated by a machine translator in whole web page mode.
	define('TRANSLATION_PAGE_TABLE', '__pages_to_be_translated');
}


//------------------------------------------------------------------------------------------
// Now we set the include path to include the necessary libraries in the lib directory.

$include_path = ini_get('include_path');


// If the current dir is currently first in the list, then it should remain that way
if ( preg_match('/^\.'.PATH_SEPARATOR.'/', $include_path) ){
	$include_path = preg_replace('/^\.'.PATH_SEPARATOR.'/','', $include_path);
	$curr_dir_first = true;
} else {
	$curr_dir_first = false;
}

$includePathArr = explode(PATH_SEPARATOR, $include_path);

if ( DATAFACE_SITE_PATH != DATAFACE_PATH and !in_array(DATAFACE_PATH,$includePathArr)){
	$include_path = DATAFACE_PATH.PATH_SEPARATOR.DATAFACE_PATH.'/lib'.PATH_SEPARATOR.$include_path;
} else if ( !in_array(DATAFACE_PATH.'/lib', $includePathArr)) {
	$include_path = DATAFACE_PATH.'/lib'.PATH_SEPARATOR.$include_path;
}

if ( $curr_dir_first ){
	$include_path = ".".PATH_SEPARATOR.$include_path;
}


set_include_path($include_path );

//ini_set('display_errors', 'on');

if ( !defined('DATAFACE_EXTENSION_LOADED_APC') ){
	
	define('DATAFACE_EXTENSION_LOADED_APC',extension_loaded('apc'));

}

if ( !defined('DATAFACE_EXTENSION_LOADED_MEMCACHE' ) ){
	define('DATAFACE_EXTENSION_LOADED_MEMCACHE', extension_loaded('memcache'));
}	





function import($file){
	
	static $imports = 0;
	if ( !$imports ){
		$imports = array();
	}
	
	//$class = str_replace('/','_', $file);
	//$class = substr($class, 0, strpos($class,'.'));
	if ( !isset($imports[$file]) ){
		$imports[$file] = true;
		//error_log("importing ".$file);
		require_once $file;
	}
}


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


require_once dirname(__FILE__).'/Dataface/Globals.php';

