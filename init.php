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
	ini_set('pcre.jit', '0');
        $originalUrl = isset($_SERVER['HTTP_X_ORIGINAL_URL']) ? parse_url($_SERVER['HTTP_X_ORIGINAL_URL']) : null;
        if ($originalUrl) {
            $host = @$originalUrl["host"];
            $port = @$originalUrl["port"];
            
            $protocol = $originalUrl["scheme"];
            if (!$port) {
                if ($protocol == 'https') {
                    $port = 443;
                } else {
                    $port = 80;
                }
            }
            $_SERVER['QUERY_STRING'] = @$originalUrl["query"];
            $_SERVER['REQUEST_URI'] = @$originalUrl["path"];
            $_SERVER['PHP_SELF'] = @$originalUrl['path'];
            if (@$originalUrl["query"]) {
                $_SERVER['REQUEST_URI'] .= '?' . $originalUrl['query'];
            }
            
            if (strpos($dataface_url, 'http:') === 0 or strpos($dataface_url, 'https:') === 0) {
                // We leave dataface_url alone
            } else {
                if ($dataface_url[0] !== '/') {
                    $dataface_url = substr($_SERVER['PHP_SELF'], 0, strrpos($_SERVER['PHP_SELF'], '/')) . '/' . $dataface_url;
                }
            }

        } else {
            // first we resolve some differences between CGI and Module php
            if ( !isset( $_SERVER['QUERY_STRING'] ) ){
                    $_SERVER['QUERY_STRING'] = @$_ENV['QUERY_STRING'];	
            } 
            // define a HOST_URI variable to contain the host portion of all urls
            $host = @$_SERVER['HTTP_HOST'];
            if (!$host) $host = 'localhost';
            if (isset($_SERVER['HTTP_X_FORWARDED_HOST'])) {
                $host = $_SERVER['HTTP_X_FORWARDED_HOST'];
                if (strpos($host, ',') !== false) {
                	$host = trim(substr($host, 0, strpos($host, ',')));
                }
            }
            $port = @$_SERVER['SERVER_PORT'];
            if (!$port) {
                $port = 80;
            }
            if (isset($_SERVER['HTTP_X_FORWARDED_HOST'])) {
            	if (isset($_SERVER['HTTP_X_FORWARDED_PORT'])) {
                	$port = $_SERVER['HTTP_X_FORWARDED_PORT'];
                	if (strpos($port, ',') !== false) {
                		$port = trim(substr($port, 0, strpos($port, ',')));
                	} 
                	$port = intval($port);
            	} else {
            		$port = 80;
            	}
            }
            $protocol = 'http';
            
            $protocol = ((@$_SERVER['HTTPS']  == 'on' || "$port" == "443") ? $protocol.'s' : $protocol );

            if (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
                $protocol = $_SERVER['HTTP_X_FORWARDED_PROTO'];
                if (strpos($protocol, ',') !== false) {
                	$protocol = trim(substr($protocol, 0, strpos($protocol, ',')));
                }
            }
            if ($protocol == 'https' and "$port" == "80") {
                $port = 443;
            } else if ($protocol == 'http' and "$port" == "443") {
                $port = 80;
            }

            if (isset($_SERVER['HTTP_X_FORWARDED_PATH'])) {
            	$path = $_SERVER['HTTP_X_FORWARDED_PATH'];
            	if (strpos($path, ',') !== false) {
            		$path = trim(substr($path, 0, strpos($path, ',')));
            	}
            	
                $_SERVER['REQUEST_URI'] = $path;
                if (strpos($_SERVER['REQUEST_URI'], '?') === false and @$_SERVER['QUERY_STRING']) {
                    $_SERVER['REQUEST_URI'] .= '?' . $_SERVER['QUERY_STRING'];
                }
                $_SERVER['PHP_SELF'] = $path;
                if (strpos($dataface_url, 'http:') === 0 or strpos($dataface_url, 'https:') === 0) {
                    // We leave dataface_url alone
                } else {
                    if ($dataface_url[0] !== '/') {
                        $dataface_url = substr($_SERVER['PHP_SELF'], 0, strrpos($_SERVER['PHP_SELF'], '/')) . '/' . $dataface_url;
                    }
                }

            }
        }
        $_SERVER['HOST_URI'] = $protocol.'://'.$host;//.($port != 80 ? ':'.$port : '');
        if ( (strpos($host, ':') === false) and !($protocol == 'https' and "$port" == "443" ) and !($protocol == 'http' and "$port" == "80") ){
                $_SERVER['HOST_URI'] .= ':'.$port;
        }
        
    
	if (defined('DATAFACE_SITE_PATH')){
		trigger_error("Error in ".__FILE__."
			DATAFACE_SITE_PATH previously defined when trying to initialize the site."/*.Dataface_Error::printStackTrace()*/, E_USER_ERROR);
	}
	
	if (defined('DATAFACE_URL')){
		trigger_error("Error in ".__FILE__."
			DATAFACE_URL previously defined when trying to initialize the site."/*.Dataface_Error::printStackTrace()*/, E_USER_ERROR);
	}
	define('DATAFACE_SITE_PATH', str_replace('\\','/', dirname($site_path)));
	if (!@$_SERVER['PHP_SELF'] and @$_SERVER['SCRIPT_NAME']){
	    $_SERVER['PHP_SELF'] = $_SERVER['SCRIPT_NAME'];
	}
	$temp_site_url = dirname($_SERVER['PHP_SELF']);
	if ( $temp_site_url[strlen($temp_site_url)-1] == '/'){
		$temp_site_url = substr($temp_site_url,0, strlen($temp_site_url)-1);
	}
	define('DATAFACE_SITE_URL', str_replace('\\','/',$temp_site_url));
	define('DATAFACE_SITE_HREF', (DATAFACE_SITE_URL != '/' ? DATAFACE_SITE_URL.'/':'/').basename($_SERVER['PHP_SELF']) );
	if ( !preg_match('#^https?://#', $dataface_url) and $dataface_url and $dataface_url[0] != '/' ){
		$dataface_url = DATAFACE_SITE_URL.'/'.$dataface_url;
	}
	define('DATAFACE_URL', str_replace('\\','/',$dataface_url));
	
	require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'config.inc.php');
	
	//print_r($_SERVER);exit;
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
        if (preg_match('/[\'"<>]/', DATAFACE_SITE_HREF)) {
            
            die("Request blocked. Illegal characters in URL");
        }
}

