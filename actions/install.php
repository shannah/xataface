<?php
/********************************************************************************
 *
 *  Xataface Web Application Framework for PHP and MySQL
 *  Copyright (C) 2006  Steve Hannah <shannah@sfu.ca>
 *  
 *  This library is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU Lesser General Public
 *  License as published by the Free Software Foundation; either
 *  version 2.1 of the License, or (at your option) any later version.
 *  
 *  This library is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 *  Lesser General Public License for more details.
 *  
 *  You should have received a copy of the GNU Lesser General Public
 *  License along with this library; if not, write to the Free Software
 *  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 *
 *===============================================================================
 */
 
/**
 * An action to handle the installation and updating of Dataface applications.
 *
 * @author Steve Hannah <steve@weblite.ca>
 * @created Feb. 10, 2008
 */
 
class dataface_actions_install {

	function handle(&$params){
	
		$app =& Dataface_Application::getInstance();
		
		if ( df_get_database_version() == df_get_file_system_version() ){
			$app->redirect(DATAFACE_SITE_HREF.'?--msg='.urlencode('The application database is up to date at version '.df_get_database_version()));
		}
		
		if ( df_get_database_version() > df_get_file_system_version() ){
			$app->redirect(DATAFACE_SITE_HREF.'?--msg='.urlencode('The database version is greater than the file system version.  Please upgrade your application to match the version in the database (version '.df_get_database_version()));
		}
		
		$res = mysql_query("select count(*) from dataface__version", df_db());
		if ( !$res ){
			throw new Exception(mysql_error(df_db()));
		}
		$row = mysql_fetch_row($res);
		if ( $row[0] == 0 ){
			$res2 = mysql_query("insert into dataface__version (`version`) values (0)", df_db());
			if ( !$res2 ) throw new Exception(mysql_error(df_db()));
		}
		
		if ( file_exists('conf/Installer.php') ){
			import('conf/Installer.php');
			$installer = new conf_Installer;
			
			$methods = get_class_methods('conf_Installer');
			$methods = preg_grep('/^update_([0-9]+)$/', $methods);
			
			$updates = array();
			
			foreach ($methods as $method){
				preg_match('/^update_([0-9]+)$/', $method, $matches);
				$version = intval($matches[1]);
				if ( $version > df_get_database_version() and $version <= df_get_file_system_version() ){
					$updates[] = $version;
				}
			}
			
			sort($updates);
			
			foreach ($updates as $update ){
				$method = 'update_'.$update;
				$res = $installer->$method();
				if ( PEAR::isError($res) ) return $res;
				$res = mysql_query("update dataface__version set `version`='".addslashes($update)."'", df_db());
				if ( !$res ) throw new Exception(mysql_error(df_db()), E_USER_ERROR);	
			}
			
			
			
		}
		
		
		$res = mysql_query("update dataface__version set `version`='".addslashes(df_get_file_system_version())."'", df_db());
		if ( !$res ) throw new Exception(mysql_error(df_db()), E_USER_ERROR);
		
                if ( function_exists('apc_clear_cache') ){
                    apc_clear_cache('user');
                }
                df_clear_views();
                df_clear_cache();
                
		$app->redirect(DATAFACE_SITE_HREF.'?--msg='.urlencode('The database has been successfully updated to version '.df_get_file_system_version()));

		
	}
}
