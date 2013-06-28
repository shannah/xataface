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
 
function init($site_path, $dataface_url){
	if (defined('DATAFACE_SITE_PATH')){
		trigger_error("Error in ".__FILE__."
			DATAFACE_SITE_PATH previously defined when trying to initialize the site."/*.Dataface_Error::printStackTrace()*/, E_USER_ERROR);
	}
	
	if (defined('DATAFACE_URL')){
		trigger_error("Error in ".__FILE__."
			DATAFACE_URL previously defined when trying to initialize the site."/*.Dataface_Error::printStackTrace()*/, E_USER_ERROR);
	}
	define('DATAFACE_SITE_PATH', str_replace('\\','/', dirname($site_path)));
	$temp_site_url = dirname($_SERVER['PHP_SELF']);
	if ( $temp_site_url{strlen($temp_site_url)-1} == '/'){
		$temp_site_url = substr($temp_site_url,0, strlen($temp_site_url)-1);
	}
	define('DATAFACE_SITE_URL', str_replace('\\','/',$temp_site_url));
	define('DATAFACE_SITE_HREF', (DATAFACE_SITE_URL != '/' ? DATAFACE_SITE_URL.'/':'/').basename($_SERVER['PHP_SELF']) );
	if ( !preg_match('#^https?://#', $dataface_url) and $dataface_url and $dataface_url{0} != '/' ){
		$dataface_url = DATAFACE_SITE_URL.'/'.$dataface_url;
	}
	define('DATAFACE_URL', str_replace('\\','/',$dataface_url));
	
	require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'config.inc.php');
	if ( @$_GET['-action'] == 'js' ){
		include dirname(__FILE__).DIRECTORY_SEPARATOR.'js.php';
	}
	if ( @$_GET['-action'] == 'css' ){
		include dirname(__FILE__).DIRECTORY_SEPARATOR.'css.php';
	}
	
	if ( !is_writable(DATAFACE_SITE_PATH.DIRECTORY_SEPARATOR.'templates_c') ){
		die(
			sprintf(
				'As of Xataface 1.3 all applications are now required to have its own templates_c directory to house its compiled templates.  Please create the directory "%s" and ensure that it is writable by the web server.',
				DATAFACE_SITE_PATH.DIRECTORY_SEPARATOR.'templates_c'
			)
		);
	}
}

