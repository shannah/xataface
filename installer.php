<?php
// Change this 
require_once('PEAR.php');
if ( !file_exists('installer.enabled') ){
	die("The installer is currently disabled.  To enable it, please rename the 'installer.disabled' file to 'installer.enabled'.  You can find this file inside the root xataface directory.");
	
}

if ( !defined('FILE_APPEND') ){
	define('FILE_APPEND', 1);
}
if ( !function_exists('df_escape') ){
    function df_escape($content){
        return htmlspecialchars($content, ENT_COMPAT);
    }
}
if ( !function_exists('file_put_contents')  ) {
	
	function file_put_contents($n, $d, $flag = false) {
		$mode = ($flag == FILE_APPEND || strtoupper($flag) == 'FILE_APPEND') ? 'a' : 'w';
		$f = @fopen($n, $mode);
		if ($f === false) {
			return 0;
		} else {
			if (is_array($d)) $d = implode($d);
			$bytes_written = fwrite($f, $d);
			fclose($f);
			return $bytes_written;
		}
	}
}
if ( !defined('XF_DB_DRIVER') ){
    define('XF_DB_DRIVER','mysql');
}
define('DB_HOST', 'localhost');  // This is the host of your mysql dbms
ini_set('include_path','.'.PATH_SEPARATOR.'lib');
set_time_limit(1500);
class Dataface_Installer {
	
	function createApplicationArchive($conf, $path=null){}
	function installApplicationArchive($path){}
	function prepareApplicationArchive($path){}
	function authenticate(){
		header('WWW-Authenticate: Basic realm="Dataface Installer"');
		header('HTTP/1.0 401 Unauthorized');
		setcookie('logged_in',1);
		echo 'Please enter your MySQL Username and password to access this page';
		
		exit;
	
	}
	
	
	function logout(){
		//echo "here";
		setcookie("logged_in", "", time() - 3600);
		header('Location: '.$_SERVER['PHP_SELF']);
		exit;
	}
	
	function mainMenu(){
		include('install'.DIRECTORY_SEPARATOR.'mainMenu.inc.php');
	}
	
	
	function infoLink($id){
		return '<img src="images/info.gif" onclick="fieldInfo(\''.$id.'\');" />';
	
	}
	
	function archive2app(){
	
		require_once 'HTML/QuickForm.php';
		$form = new HTML_QuickForm('fromarchive');
		
		$form->addElement('hidden', '-action', 'archive2app');

		$form->addElement('file','archive', 'Installation Archive'.$this->infoLink('archive2app.archive'));
		$form->addElement('text','database_name','Database Name '.$this->infoLink('archive2app.database_name'));

		
		$form->addElement('text','mysql_user', 'MySQL Username '.$this->infoLink('archive2app.mysql_user'));
		$form->addElement('password', 'mysql_password', 'MySQL Password');
		$form->addElement('checkbox', 'create_user', 'Create user '.$this->infoLink('archive2app.create_user'));
		
		$form->addElement('select','install_type', 'Installation type '.$this->infoLink('archive2app.install_type'), array(
			'' => 'Please select ...',
			'download_tarball' => 'Download Tarball',
			'ftp_install' => 'Install on server (using FTP)'
			),
			array('onchange'=>"listeners.install_type.onchange(this);")
		);
		
		$form->addElement('header', 'ftp_info', 'FTP Connection Info');
		$form->addElement('text', 'ftp_host', 'FTP Host');
		$form->addElement('checkbox', 'ftp_ssl', 'Use SSL');
		$form->addElement('text', 'ftp_path', 'FTP Path');
		$form->addElement('text', 'ftp_username', 'FTP Username');
		$form->addElement('password', 'ftp_password', 'FTP Password');
		
		$form->addElement('submit','submit','Submit');
		
		
		$form->addRule('database_name','Please select a database', 'required', null,'client');
		$form->addRule('mysql_user', 'Please enter a mysql username that the application can connect as.', 'required',null,'client');
		$form->addRule('install_type', 'Please select an installation type and then click submit.', 'required', null, 'client');
		$form->addRule('archive', 'Please choose the application tar.gz file to upload', 'uploadedfile',null,'client');
		
		$form->setDefaults(array(
			'mysql_user'=>$_SERVER['PHP_AUTH_USER'],
			'mysql_password'=>$_SERVER['PHP_AUTH_PW']
			)
		);
		
		if ( $form->validate() ){
			$res = $form->process(array(&$this,'archive2app__process'), true);
			if ( PEAR::isError($res) ){
				die($res->getMessage());
			}
		}
		require_once 'HTML/QuickForm/Renderer/Array.php';
		$renderer = new HTML_QuickForm_Renderer_Array(true,true,true);
		$form->accept($renderer);
		
		$context = $renderer->toArray();
		
		ob_start();
		$form->display();
		$out = ob_get_contents();
		ob_end_clean();
		include 'install'.DIRECTORY_SEPARATOR.'archive2app.inc.php';
	}
	
	function archive2app__process($values){
		require_once 'Archive/Tar.php';
		
		if ( preg_match('/\.gz$/', $_FILES['archive']['name']) ){
			$compression = 'gz';
		} else {
			$compression = null;
		}
		$archive = new Archive_Tar($_FILES['archive']['tmp_name'], $compression);
		$files = $archive->listContent();
		foreach ( $files as $file ){
			if ( !preg_match('/(\.ini)|(\.php)$/', $file['filename']) ){
				continue;
			}
			$content = $archive->extractInString($file['filename']);
			$content = str_replace(
				array(
					'%%DATAFACE_URL%%',
					'%%DATAFACE_PATH%%',
					'%%MYSQL_USER%%',
					'%%MYSQL_PASSWORD%%',
					'%%MYSQL_HOST%%',
					'%%MYSQL_DATABASE_NAME%%'
				),
				array(
					addslashes(dirname($_SERVER['PHP_SELF'])),
					addslashes(dirname(__FILE__)),
					addslashes($values['mysql_user']),
					addslashes($values['mysql_password']),
					addslashes(DB_HOST),
					addslashes($values['database_name'])
				),
				$content
			);
			$archive->addString($file['filename'], $content);
					
		}
		$root = $files[0]['filename'];
		
		$install = $archive->extractInString($root.'install/install.sql');
		$res = xf_db_select_db($values['database_name'], db());
		if ( !$res ){
			$dbname = str_replace('`','',$values['database_name']);
			$res = xf_db_query("create database `".addslashes($dbname)."`", db());
			if ( !$res ){
				return PEAR::raiseError("Failed to create database '$dbname'");
			}
			$res = xf_db_select_db($dbname);
			if ( !$res ){
				return PEAR::raiseError("Problem selecting database $dbname.");
			}
		}
		
		if ( $install ){
			$installFile = tempnam(null, 'install.sql');
			file_put_contents($installFile, $install);
			

			$file = file($installFile);
			$queries = array();
			$ctr = 0;
			foreach ($file as $line){
				
				if ( isComment($line) ) continue;
				$queries[$ctr] .= $line;
				$trimmed = trim($line);
				if ( $trimmed{strlen($trimmed)-1} == ';' ) $ctr++;
				
			}
			
			//$file = implode("",$out);
			foreach ($queries as $query){
			
				$res = @xf_db_query($query, $db);
				if ( !$res ){
					$my_errs[]  = xf_db_error($db);
				}
			}
		}
			
			
		
		switch ($values['install_type'] ){
			case 'ftp_install':
				//echo 'here';
				require_once 'install/FTPExtractor.class.php';
				$extractor = new FTPExtractor($archive);
				$res = $extractor->connect($values['ftp_host'], $values['ftp_username'], $values['ftp_password']);

				if ( PEAR::isError($res) ){
					die($res->getMessage());
				}
				$res = $extractor->extract($values['ftp_path'],'/');
				//if ( PEAR::isError($res) ){
				//	die($res->getMessage());
				//}
				$context = array();
				if ( PEAR::isError($res) ){
					$context['result'] = 'Error: '.$res->getMessage();
				} else {
					$context = $res;
				}
				include 'install'.DIRECTORY_SEPARATOR.'archive2app-results.inc.php';
				exit;
			
			default: // download_tarball
				$tarpath =  $_FILES['archive']['tmp_name'];
				if ( $compression == 'gz' ){
					$mimetype = 'application/x-gzip';
				} else {
					$mimetype = 'application/x-tar';
				}
				header('Content-type: '.$mimetype);
				header('Content-Disposition: attachment; filename="'.basename($_FILES['archive']['name']).'.tar.gz"');
				echo file_get_contents($tarpath);
				exit;
				
		}
		
		
		
	
	}
	
	function db2app(){
		require_once 'HTML/QuickForm.php';
		$form = new HTML_QuickForm('db2app');
		$res = xf_db_query("SHOW DATABASES", db());
		if ( !$res ) trigger_error(xf_db_error(db()), E_USER_ERROR);
		$options = array('' => 'Please Select Database ...');
		while ( $row = xf_db_fetch_row($res) ) $options[$row[0]] = $row[0];
		$form->addElement('hidden','-action','db2app');
		$form->addElement('select', 'database_name','Select Database'.$this->infoLink('archive2app.database_name'), $options, array('onchange'=>'listeners.database_name.onchange(this)'));
		$form->addElement('header','db_info','Database connection details');
		//$form->addElement('html', 'this is a test');
		$form->addElement('text', 'mysql_user', 'MySQL Username '.$this->infoLink('archive2app.mysql_user'));
		$form->addElement('password', 'mysql_password', 'MySQL Password');
		//$form->addElement('radio','output_format','Output options','Download as tar.gz archive','download');
		//$form->addElement('radio','output_format','','Install on webserver in apps directory','install');
		
		$form->addElement('select','install_type', 'Installation type '.$this->infoLink('archive2app.install_type'), array(
			'' => 'Please select ...',
			'download_tarball' => 'Download Tarball',
			'ftp_install' => 'Install on server (using FTP)'
			),
			
			array('onchange'=>"listeners.install_type.onchange(this);")
		);
		
		$form->addElement('header', 'ftp_info', 'FTP Connection Info');
		$form->addElement('text', 'ftp_host', 'FTP Host');
		$form->addElement('checkbox', 'ftp_ssl', 'Use SSL');
		$form->setDefaults(array('ftp_host'=>DB_HOST));
		$form->addElement('text', 'ftp_path', 'FTP Path',array('size'=>50));
		$form->setDefaults(array('ftp_path'=>$_SERVER['DOCUMENT_ROOT']));
		$form->addElement('text', 'ftp_username', 'FTP Username');
		$form->addElement('password', 'ftp_password', 'FTP Password');
		
		
		$form->addElement('submit','submit','Submit');
		
		
		$form->addRule('database_name','Please select a database', 'required', null,'client');
		$form->addRule('mysql_user', 'Please enter a mysql username that the application can connect as.', 'required',null,'client');
		$form->addRule('install_type', 'Please select an installation type and then click submit.', 'required', null, 'client');
		$form->setDefaults(array(
			'mysql_user'=>$_SERVER['PHP_AUTH_USER'],
			'mysql_password'=>$_SERVER['PHP_AUTH_PW']
			)
		);
		
		if ( $form->validate() ){
			$tarpath = $form->process(array(&$this,'db2app__process'), true);
			header('Content-type: application/x-gzip');
			header('Content-Disposition: attachment; filename="'.basename($tarpath).'.tar.gz"');
			echo file_get_contents($tarpath);
			exit;
		}
		
		require_once 'HTML/QuickForm/Renderer/Array.php';
		$renderer = new HTML_QuickForm_Renderer_Array(true,true,true);
		$form->accept($renderer);
		
		$context = $renderer->toArray();
		//print_r($context);
		
		ob_start();
		$form->display();
		$out = ob_get_contents();
		ob_end_clean();
		include 'install'.DIRECTORY_SEPARATOR.'db2app.inc.php';
	}
	
	function db2app__process($values){
		require_once 'Archive/Tar.php';
		$tarpath = tempnam('/tmp',strval($values['database_name']));
		//echo $tarpath;
		$compression='gz';
		$archive = new Archive_Tar($tarpath,$compression);
		$path = strval($values['database_name']);
		$archive->addString($path.'/.htaccess', '<FilesMatch "\.ini$">
    # Apache 2.2
    <IfModule !mod_authz_core.c>
        Deny from all
    </IfModule>
    
    # Apache 2.4
    <IfModule mod_authz_core.c>
        Require all denied
    </IfModule>
</FilesMatch>');
		$archive->addString($path.'/Web.config', file_get_contents(dirname(__FILE__).DIRECTORY_SEPARATOR.'site_skeleton'.DIRECTORY_SEPARATOR.'Web.config'));
		
		
		

		xf_db_select_db($values['database_name'], db());
		$res = xf_db_query('show tables', db());
		if ( !$res ) trigger_error(xf_db_error(db()), E_USER_ERROR);
		$tables = array();
		while ( $row = xf_db_fetch_row($res) ){
			if ( $row[0]{0} == '_' ) continue;
			if ( strpos($row[0], 'dataface_') === 0 ) continue;
			if ( preg_match('/__history$/', $row[0]) ) continue;
			$tables[] = $row[0].' = "'.ucwords(str_replace('_',' ', $row[0])).'"';
		}
		
		$archive->addString($path.'/conf.ini',';;Configuration settings for application
title="'.addslashes($values['database_name']).'"

[_database]
	host="'.DB_HOST.'"
	name="'.addslashes($values['database_name']).'"
	user="'.addslashes($values['mysql_user']).'"
	password="'.addslashes($values['mysql_password']).'"
	
[_tables]
'.implode("\n",$tables).'
'
		);
		
		$archive->addString($path.'/index.php','<?php //Main Application access point
require_once "'.addslashes(dirname(__FILE__).DIRECTORY_SEPARATOR.'public-api.php').'";
df_init(__FILE__, "'.addslashes(dirname($_SERVER['PHP_SELF'])).'")->display();
'
		);
		
		
		switch ($values['install_type'] ){
			case 'ftp_install':
				//echo 'here';
				require_once 'install/FTPExtractor.class.php';
				$extractor = new FTPExtractor($archive);
				$res = $extractor->connect($values['ftp_host'], $values['ftp_username'], $values['ftp_password']);

				if ( PEAR::isError($res) ){
					die($res->getMessage());
				}
				
				
				$res = $extractor->extract($values['ftp_path'],'/');
				//if ( PEAR::isError($res) ){
				//	die($res->getMessage());
				//}
				$context = array();
				if ( PEAR::isError($res) ){
					$context['result'] = 'Error: '.$res->getMessage();
				} else {
					$context = $res;
					
					
				}
				include 'install'.DIRECTORY_SEPARATOR.'archive2app-results.inc.php';
				exit;
			
			default: // download_tarball
				//$tarpath =  $_FILES['archive']['tmp_name'];
				if ( $compression == 'gz' ){
					$mimetype = 'application/x-gzip';
				} else {
					$mimetype = 'application/x-tar';
				}
				header('Content-type: '.$mimetype);
				header('Content-Disposition: attachment; filename="'.basename($tarpath).'.tar.gz"');
				echo file_get_contents($tarpath);
				exit;
				
		}
		
		//return $tarpath;
		
	}
	
	function test_db_access($dbname, $username, $password){
		if ( !function_exists('xf_db_connect') ){
			require_once 'xf/db/drivers/'.basename(XF_DB_DRIVER).'.php';
		}
		$db = @xf_db_connect(DB_HOST, $username, $password);
		if ( !$db ){
			return PEAR::raiseError("Could not connect to the MySQL server with username $username.");
		}
		
		$res = xf_db_select_db($dbname, $db);
		if ( !$res ) return PEAR::raiseError("Could not access the database $dbname as user $username.");
		
		return true;
	}
	
	function test_ftp_access($host, $path, $user, $password, $ssl=false){
		require_once 'install/ftp.api.php';
		require_once 'install/ftp.class.php';
		if ( $ssl ){
			$conn = ftp_ssl_connect($host);
		} else {
			$conn = ftp_connect($host);
		}
		if ( !$conn ) return PEAR::raiseError("Could not connect to FTP server");
		
		$res = @ftp_login($conn, $user, $password);
		if ( !$res ) return PEAR::raiseError("Failed to login to FTP server with the provided username ($user) and password");
		
		$res = @ftp_chdir($conn, $path);
		if ( !$res ){
			return PEAR::raiseError("Failed: The directory $path on the server $host does not exist.");
			
		}
		
		return true;
	
	}
	
	function testdb(){
		if ( !@$_REQUEST['-dbname'] || !$_REQUEST['-dbuser'] || !isset($_REQUEST['-dbpass']) ){
			trigger_error("Please provide all of -dbname, -dbuser, and -dbpass parameters in the POST variables.", E_USER_ERROR);
			
		}
		
		$res = $this->test_db_access($_REQUEST['-dbname'], $_REQUEST['-dbuser'], $_REQUEST['-dbpass']);
		if ( PEAR::isError($res) ){
			$msg = array(
				'success' => false,
				'message' => $res->getMessage()
				);
			
			
		} else {
			$msg = array(
				'success' => true,
				'message' => 'Connected to database successfully'
				);
		}
		
		header('Content-type: text/json');
		require_once 'Services/JSON.php';
		$json = new Services_JSON;
		echo $json->encode($msg);
		exit;
	}
	
	function testftp(){
		if ( !@$_REQUEST['-ftphost'] || !$_REQUEST['-ftpuser'] || !isset($_REQUEST['-ftppass']) ){
			trigger_error("Please provide all of -ftphost, -ftpuser, and -ftppass parameters in the POST variables.", E_USER_ERROR);
			
		}
		
		$res = $this->test_ftp_access($_REQUEST['-ftphost'], @$_REQUEST['-ftppath'], $_REQUEST['-ftpuser'], $_REQUEST['-ftppass'], @$_REQUEST['-ftpssl']);
		if ( PEAR::isError($res) ){
			$msg = array(
				'success' => false,
				'message' => $res->getMessage()
				);
			
			
		} else {
			$msg = array(
				'success' => true,
				'message' => 'Connected to FTP server successfully'
				);
		}
		
		header('Content-type: text/json');
		require_once 'Services/JSON.php';
		$json = new Services_JSON;
		echo $json->encode($msg);
		exit;
	}

}
//print_r($_SERVER);
function db(){
	static $db=-1;
	if ( $db === -1 ){
		$installer = new Dataface_Installer;
		if (!@$_SERVER['PHP_AUTH_USER'] || !$_COOKIE['logged_in'] ){
			$installer->authenticate();
		}
		if ( !function_exists('xf_db_connect') ){
			require_once 'xf/db/drivers/'.basename(XF_DB_DRIVER).'.php';
		}
		$db = @xf_db_connect(DB_HOST,@$_SERVER['PHP_AUTH_USER'], @$_SERVER['PHP_AUTH_PW']);
		if ( !$db ){
			$installer->authenticate();
   		}
   	}
   	return $db;
}

function isComment($line){
	$line = trim($line);
	if ( strlen($line) > 1 and $line{0} == '-' and $line{1} == '-') return true;
	return false;
}


db();


$installer = new Dataface_Installer;
switch (@$_REQUEST['-action']){
	case 'testdb':
		$installer->testdb();
		break;
		
	case 'testftp':
		$installer->testftp();
		break;
	
	case 'logout':
		$installer->logout();
		break;
	
	case 'db2app':
		$installer->db2app();
		break;
		
	case 'archive2app':
		$installer->archive2app();
		break;
		
	default:
		$installer->mainMenu();

}
