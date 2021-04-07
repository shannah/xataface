<?php
class XFProject {
    private $basedir;
    var $dbName;
    var $bootstrapSqlFiles;

    function __construct($basedir) {
        $this->basedir = $basedir;
    }

    function user_home_dir() {
        return realpath($_SERVER['HOME']);
    }

    function user_xataface_dir() {
        return $this->user_home_dir() . DIRECTORY_SEPARATOR . '.xataface';
    }

    /**
     * Cache directory where we can cach copies of phpmyadmin
     */
    function xataface_cache_dir() {
        return $this->user_xataface_dir() . DIRECTORY_SEPARATOR . 'cache';
    }

	private function tools_dir() {
		return dirname(dirname(__FILE__)); 
	}

    /**
     * Path to the centeral xataface directory
     */
    function xataface_dir() {
        return realpath($this->tools_dir(). DIRECTORY_SEPARATOR  . '..');
    }

    /**
     * Path to the project's xataface directory
     */
    function local_xataface_dir() {
        return $this->www_dir() . DIRECTORY_SEPARATOR . 'xataface';
    }

    /**
     * Path to the site_skeleton directory of xataface.
     */
    function site_skeleton_dir() {
        return $this->xataface_dir() . DIRECTORY_SEPARATOR . 'site_skeleton';
    }

    /**
     * Path to the www directory (the doc root) of the project.
     */
    function www_dir() {
        return $this->basedir . DIRECTORY_SEPARATOR . 'www';
    }

    /**
     * The path to the xataface app directory.  By default this will by a 
     * symlink to the www directory, but in cases where the xataface app
     * is in a subdirectory of the docroot, then this may be a symlink 
     * to that subdirectory.
     */
    function app_dir() {
        return $this->basedir . DIRECTORY_SEPARATOR . 'app';
    }

    function templates_c_dir() {

        return $this->www_dir() . DIRECTORY_SEPARATOR . 'templates_c';
    }

    /**
     * Path to the project's mysql data directory.
     */
    function data_dir() {
        return $this->basedir . DIRECTORY_SEPARATOR . 'data';
    }

    /**
     * Path to the project's log directory.
     */
    function log_dir() {
        return $this->basedir . DIRECTORY_SEPARATOR . 'log';
    }

    /**
     * Path to project's mysql error log file.
     */
    function error_log_path() {
        return $this->log_dir() . DIRECTORY_SEPARATOR . 'mysql-errors.log';
    }

    /**
     * Path to project's tmp directory.
     */
    function tmp_dir() {
        return $this->basedir . DIRECTORY_SEPARATOR . 'tmp';
    }

    /**
     * Path to the mysql pid file.
     */
    function pid_file_path() {
        return $this->tmp_dir() . DIRECTORY_SEPARATOR . 'mysql.pid';
    }

    /**
     * Path to the mysql socket file.
     */
    function socket_path() {
        return $this->tmp_dir() . DIRECTORY_SEPARATOR . 'mysql.sock';
    }

    /**
     * Path to the project's bin directory.
     */
    function bin_dir() {
        return $this->basedir . DIRECTORY_SEPARATOR . 'bin';
    }

    /**
     * Path to the project's lib directory.
     */
    function lib_dir() {
        return $this->basedir . DIRECTORY_SEPARATOR . 'lib';
    }

    /**
     * Path to the project's etc directory.
     */
    function etc_dir() {
        return $this->basedir . DIRECTORY_SEPARATOR . 'etc';
    }

    /**
     * Create the project's xataface directory.
     */
    function create_local_xataface() {
        if (file_exists($this->local_xataface_dir())) {
            echo $this->local_xataface_dir() . " exists.  Skipping.\n";
        } else {
            $quoted_xataface = escapeshellarg($this->xataface_dir());
            $quoted_local_xataface = escapeshellarg($this->local_xataface_dir());
            
            echo "Copying xataface to {$this->local_xataface_dir()} ...";
            exec("cp -r $quoted_xataface $quoted_local_xataface", $buf, $res);
            if ($res !== 0) {
                fwrite(STDERR, "Failed to copy xataface into site\n");
                exit(1);
            }
            echo "Done.\n";
            $local_site_skeleton = $this->local_xataface_dir() . DIRECTORY_SEPARATOR . 'site_skeleton';
            if (file_exists($local_site_skeleton)) {
                $quoted_local_site_skeleton = escapeshellarg($local_site_skeleton);
                echo "Removing $local_site_skeleton ...";
                exec("rm -rf $quoted_local_site_skeleton", $buf, $res);
                if ($res !== 0) {
                    fwrite(STDERR, "Failed to delete site_skeleton\n");
                    exit(1);
                }
                echo "Done.\n";
            }

            
        }
    }


    /**
     * Creates an htaccess file at the given path which denies all
     * access.
     * @param string $path The path where the .htaccess file should be created.  E.g. /www/.htaccess
     */
    function create_deny_all_htaccess($path) {
        $out = <<<END
RedirectMatch 404 /templates_c
RedirectMatch 404 /\.git
# Apache 2.2
<IfModule !authz_core_module>
	Order Deny,Allow
	Deny from all
</IfModule>

# Apache 2.4+
<IfModule authz_core_module>
    Require all denied
</IfModule>
END;
        file_put_contents($path, $out);
    }


    /**
     * Creates the scaffold for the project.  The scaffold is a directory
     * structure with bin, lib, etc, log, tmp, and www directories.  And 
     * a symlink app pointing to the root of the Xataface app.  Usually this
     * just points to the www directory.
     */
    function create_scaffold() {
        if (file_exists($this->basedir)) {
            fwrite(STDERR, "Base directory {$this->basedir} already exists.\n");
            exit(1);
        }
        mkdir($this->basedir);
        $scaffold_template = $this->xataface_dir() . DIRECTORY_SEPARATOR . 'scaffold_template';
        $quoted_scaffold_template = escapeshellarg($scaffold_template);
        $quoted_basedir = escapeshellarg($this->basedir);
        echo "Setting up scaffold at {$this->basedir} ...";
        exec("cp -r $quoted_scaffold_template/* $quoted_basedir/", $buf, $res);
        if ($res !== 0) {
            fwrite(STDERR, "Failed.\n");
            exit(1);
        }
        echo "Done\n";
        // We don't need to install composer anymore because we are hosting phpmyadmin 
        // fully built
        //$this->install_composer();
        // Don't need yarn anymore for the same reason
        //$this->install_yarn();
        $folderTmp = getcwd();
        chdir(realpath($this->basedir));
        if (!symlink('www', 'app')) {
            fwrite(STDERR, "Failed to create symlink from www to app\n");
            exit(1);
        }
        chdir($folderTmp);
        
        $this->install_php_my_admin();
        $this->create_local_xataface();
        mkdir($this->templates_c_dir());
        chmod($this->templates_c_dir(), 0777);
        $this->create_deny_all_htaccess($this->templates_c_dir() . '/.htaccess');

        $this->init_db();

        
        
    }

    /**
     * Not used right now... was used before because we needed yarn to build
     * php myadmin, but we are prebuilding phpmyadmin now.
     */
    function install_yarn() {

        echo "Installing Yarn (required for PhpMyAdmin javascript dependencies)...";
        $tmpPath = $this->lib_dir() . DIRECTORY_SEPARATOR . 'yarn.tgz';
        $yarnUrl = 'https://yarnpkg.com/latest.tar.gz';

        if (!file_put_contents($tmpPath, fopen($yarnUrl, 'rb'))) {
            fwrite(STDERR, "Failed\n");
            fwrite(STDERR, "Failed to download yarn from ".$yarnUrl);
            exit(1);
        }
        echo "Done\n";
        echo 'Extracting yarn...';
        try {
            $zip = new PharData($tmpPath);
            $res = $zip->extractTo($this->lib_dir());
            if ($res !== TRUE) {
                fwrite(STDERR, "Failed to open yarn zip archive from ". $tmpPath . "\n");
                exit(1);
            }
        } catch (Exception $ex) {
            fwrite(STDERR, "Failed\n");
            fwrite(STDERR, $ex->getMessage()."\n");
            exit(1);
        }
        foreach (glob($this->lib_dir() . DIRECTORY_SEPARATOR . 'yarn-*') as $yarnDir) {
            if (!rename($yarnDir, $this->lib_dir() . DIRECTORY_SEPARATOR . 'yarn')) {
                fwrite(STDERR, "Failed\nFailed to rename yarn\n");
                exit(1);
            }
            break;
        }
        unlink($tmpPath);
        echo "Done\n";
    }

    /**
     * Install php myadmin
     */
    function install_php_my_admin() {
        @mkdir($this->lib_dir());
        @mkdir($this->user_xataface_dir());

        
        echo "Checking for PHPMyAdmin installation...";
        $phpMyAdmin = $this->user_xataface_dir() . DIRECTORY_SEPARATOR . 'phpmyadmin';
        if (!file_exists($phpMyAdmin)) {
            echo "Not found\n";

            $tmpPath = $this->user_xataface_dir() . DIRECTORY_SEPARATOR . 'phpmyadmin.zip';
            $phpMyAdminUrl = 'https://github.com/shannah/phpmyadmin/archive/master.zip';
            //$phpMyAdminUrl = 'https://github.com/phpmyadmin/phpmyadmin/archive/master.zip';
            echo 'Downloading phpMyAdmin from '.$phpMyAdminUrl.'...';
            $res = file_put_contents($tmpPath, fopen($phpMyAdminUrl, 'rb'));
            if (!$res) {
                fwrite(STDERR, "Failed to download phpmyadmin from ".$phpMyAdminUrl);
                exit(1);
            }
            echo "Done\n";
            echo 'Extracting phpmyadmin...';
            $zip = new ZipArchive;
            $res = $zip->open($tmpPath);
            if ($res !== TRUE) {
                fwrite(STDERR, "Failed to open phpmyadmin zip archive from ". $tmpPath . "\n");
                exit(1);
            }
            $zip->extractTo($this->user_xataface_dir());
            $zip->close();
            unlink($tmpPath);
            if (!rename($phpMyAdmin.'-master', $phpMyAdmin)) {
                fwrite(STDERR, "Failed.\n");
                exit(1);
            }
            echo "Done\n";
        } else {
            echo "Found.\n";
        }
        $phpMyAdminLink = $this->lib_dir() . DIRECTORY_SEPARATOR . 'phpmyadmin';
        echo "Linking $phpMyAdmin to $phpMyAdminLink ...";
        if (!symlink($phpMyAdmin, $phpMyAdminLink)) {
            fwrite(STDERR, "Failed to create link.\n");
            exit(1);
        }
        echo "Done.\n";

       
        


    }

    function install_composer() {
        mkdir($this->lib_dir());
        $composerPhar = $this->lib_dir() . DIRECTORY_SEPARATOR . 'composer.phar';
        $composerUrl = 'https://github.com/composer/composer/releases/download/1.6.5/composer.phar';
        echo "Installing composer to ".$composerPhar." ...";
        
        $res = file_put_contents($composerPhar, fopen($composerUrl, 'rb'));
        if (!$res) {
            fwrite(STDERR, "Failed");
            exit(1);
        }
        echo "Done\n";
    }
	
	function currentServer() {
		if (getenv('XATAFACE_SERVER') !== null) {
			require_once dirname(__FILE__).DIRECTORY_SEPARATOR.'XFServers.class.php';
			$servers = new XFServers();
			return $servers->getServerByName(getenv('XATAFACE_SERVER'));
			
		}
		return null;
	}
	
	/**
	 * Installs the project in the given central server.  This may involve copying the 
	 * database if it isn't already installed.
	 */
	function installOnServer(XFServer $server, $hostName=null) {
		$dbs = $server->executeSQLQuery("show databases");
		$dbName = $this->get_config_var('_database.name');
		$configPath = $server->getConfigPath();
		if (in_array($dbName, $dbs)) {
			throw new Exception("MySQL server for {$server->getName()} already contains a database named {$dbName}");
		}
		if ($hostName !== null) {
			$vhostConfFile = $this->tmp_dir() . DIRECTORY_SEPARATOR . 'private' . DIRECTORY_SEPARATOR . basename($server->getName()) . '.httpd.conf';
		
			if (file_exists($vhostConfFile)) {
				throw new Exception("vhost conf file already exists.");
			}
			
			
			if (!$configPath or !file_exists($configPath)) {
				throw new Exception("Config file $configPath was not found.");
			}
		}
		
		$dumpFile = $this->tmp_dir() . DIRECTORY_SEPARATOR . basename($dbName). '-' . date('Y-m-d_His').'.sql';
		$this->mysqldump($dumpFile);
		
		$dbUser = $this->get_config_var('_database.user');
		$dbPass = $this->get_config_var('_database.password');
		
		try {
			$server->executeSQLQuery('show databases', $dbUser, $dbPass);
		} catch (Exception $ex) {
			$sql = "CREATE USER '".addslashes($dbUser)."'@'localhost' identified by '".addslashes($dbPass)."'; FLUSH PRIVILEGES;";
			$server->executeSQLQuery($sql);
		}
		
		// Now verify that the user can login
		$server->executeSQLQuery('show databases', $dbUser, $dbPass);
		
		// Now create the database
		$server->executeSQLQuery('create database `'.$dbName.'`');
		
		// Now grant user all privileges on the database
		$server->executeSQLQuery("GRANT ALL PRIVILEGES ON `{$dbName}` TO '".addslashes($dbUser)."'@'localhost'; FLUSH PRIVILEGES;");
		
		// Now import the SQL file
		$server->executeSQLFile($dumpFile, $dbUser, $dbPass, $dbName);
		
		if ($hostName !== null) {
			if (!file_exists(dirname($vhostConfFile))) {
				mkdir(dirname($vhostConfFile));
			}
			touch($vhostConfFile);
		
			$vhost = new XFVirtualHost($server);
			$vhost->setAddress('*');
			$vhost->setPort('*');
			$vhost->setDocRoot(realpath($this->www_dir()));
			$vhost->setName($hostName);
			file_put_contents($vhostConfFile, $vhost->toString());
			$sudo = !is_writable($configPath);
			if (!$sudo) {
				$configContents = file_get_contents($configPath);
				if (strpos($configContents, $vhost->getDocRoot()) === false) {
					$configContents .= "\nInclude \"{$vhost->getDocRoot()}\"\n";
					file_put_contents($configPath, $configContents);
				}
			} else {
				exec("sudo cat ".escapeshellarg($configPath), $buffer, $res);
				if ($res !== 0) {
					throw new Exception("Failed to load contents of $configPath for appending Include statement");
				}
				if (strpos(implode('', $buffer), $vhost->getDocRoot()) === false) {
					$buffer[] = "Include \"{$vhost->getDocRoot()}\"";
					passthru("sudo ".escapeshellarg(implode('', $buffer))." > ".escapeshellarg($configPath), $res);
					if ($res !== 0) {
						throw new Exception("Failed to append Include into config file $configPath");
					}
				}
				
				
			}
			
			
		}
		
		
		
		
		
	}
	
	function mysqldump($destFile) {
		$mysqldump = $this->bin_dir() . DIRECTORY_SEPARATOR . 'mysqldump.sh';
		$cmd = $mysqldump . ' > ' . escapeshellarg($destFile);
		exec($cmd, $buffer, $res);
		if ($res !== 0) {
			throw new Exception("mysqldump to $destFile failed. Error code $res");
		}
		
		
	}
	
	function get_config_var($name) {
		$print_config_var = $this->bin_dir() . DIRECTORY_SEPARATOR . 'print_config_var.php';
		$cmd = "php ".escapeshellarg($print_config_var).' '.escapeshellarg($name);
		exec($cmd, $buffer, $res);
		if ($res !== 0) {
			throw new Exception("Failed to get config var $name.  Exit code $res");
		}
		if ($buffer and count($buffer) > 0) {
			return trim($buffer[0]);
		}
		return null;
	}
	
	function bootstrap_db_production(XFServer $server) {
        $conf_db_ini_path = $this->www_dir() . DIRECTORY_SEPARATOR . 'conf.db.ini.php';
        if (!file_exists($conf_db_ini_path)) {
            fwrite(STDERR, "$conf_db_ini_path not found.");
            exit(1);
        }
        echo "Initializing database ... \n";
        $conf = parse_ini_file($conf_db_ini_path, true);
        $contents = file_get_contents($conf_db_ini_path);
        if ($conf['_database']['name'] == '{__DATABASE_NAME__}') {
            $name = basename($this->basedir);
            if ($this->dbName) {
                $name = $this->dbName;
            }
            if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_-]+$/', $name)) {
                fwrite(STDERR, "Failed. Illegal database name $name.\n");
                exit(1);
            }
            $contents = str_replace('{__DATABASE_NAME__}', $name, $contents);
        }
        if ($conf['_database']['user'] == '{__DATABASE_USER__}') {
            $name = basename($this->basedir);
            if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_-]+$/', $name)) {
                fwrite(STDERR, "Failed. Illegal user name $name.\n");
                exit(1);
            }
            $contents = str_replace('{__DATABASE_USER__}', $name, $contents);
        }
        if ($conf['_database']['password'] == '{__DATABASE_PASSWORD__}') {
            $password = $this->randomPassword();
            $contents = str_replace('{__DATABASE_PASSWORD__}', $password, $contents);

        }
        file_put_contents($conf_db_ini_path, $contents);
        $conf = parse_ini_file($conf_db_ini_path, true);
		print_r($conf);
		$user = $conf['_database']['user'];
		$pass = $conf['_database']['password'];
		$database = $conf['_database']['name'];
		
		echo "Creating database '$database'\n";
		$server->executeSQLQuery("CREATE DATABASE `$database`");
		echo "Creating mysql user '$user'\n";
		$server->executeSQLQuery("CREATE USER '".addslashes($user)."'@'localhost' IDENTIFIED BY '".addslashes($pass)."'");
		echo "Granting all privileges on database '$database' to user '$user'\n";
		$server->executeSQLQuery("GRANT ALL PRIVILEGES ON `$database`.* TO '".addslashes($user)."'@'localhost'");
		$server->executeSQLQuery("FLUSH PRIVILEGES");
		
		echo "Creating test table in '$database'\n";
		$server->executeSQLQuery("USE `$database`; CREATE TABLE IF NOT EXISTS `test` (
		    test_id INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
		    test_field VARCHAR(100) 
		)", $user, $pass);
		
		
		// Now to add the virtual host
		

        echo "Done\n";
	}
	
    function init_db() {
		
        $conf_db_ini_path = $this->www_dir() . DIRECTORY_SEPARATOR . 'conf.db.ini.php';
        if (!file_exists($conf_db_ini_path)) {
            fwrite(STDERR, "$conf_db_ini_path not found.");
            exit(1);
        }
        echo "Initializing database ... \n";
        $conf = parse_ini_file($conf_db_ini_path, true);
        $contents = file_get_contents($conf_db_ini_path);
        if ($conf['_database']['name'] == '{__DATABASE_NAME__}') {
            $name = basename($this->basedir);
            if ($this->dbName) {
                $name = $this->dbName;
            }
            if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_-]+$/', $name)) {
                fwrite(STDERR, "Failed. Illegal database name $name.\n");
                exit(1);
            }
            $contents = str_replace('{__DATABASE_NAME__}', $name, $contents);
        }
        if ($conf['_database']['user'] == '{__DATABASE_USER__}') {
            $name = basename($this->basedir);
            if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_-]+$/', $name)) {
                fwrite(STDERR, "Failed. Illegal user name $name.\n");
                exit(1);
            }
            $contents = str_replace('{__DATABASE_USER__}', $name, $contents);
        }
        if ($conf['_database']['password'] == '{__DATABASE_PASSWORD__}') {
            $password = $this->randomPassword();
            $contents = str_replace('{__DATABASE_PASSWORD__}', $password, $contents);

        }
        file_put_contents($conf_db_ini_path, $contents);
        $conf = parse_ini_file($conf_db_ini_path, true);
        $mysql_server = $this->bin_dir() . DIRECTORY_SEPARATOR . 'mysql.server.sh';
        $mysql = $this->bin_dir() . DIRECTORY_SEPARATOR . 'mysql.sh';
		
        echo "Starting mysql server...";
        exec('bash '.escapeshellarg($mysql_server).' start', $buffer, $res);
        if ($res !== 0) {
            fwrite(STDERR, "Failed to start mysql server.\n");
            exit(1);
        }
        echo "Started Successfully\n";
			
			
		
       
        
        $install_sql_path = $this->basedir . DIRECTORY_SEPARATOR . 'install.sql';
        
        $bootstrapSqlString = <<<END
CREATE DATABASE IF NOT EXISTS `{$conf['_database']['name']}`;
USE `{$conf['_database']['name']}`;


END
;
        if (!empty($this->bootstrapSqlFiles) and is_array($this->bootstrapSqlFiles)) {
            foreach ($this->bootstrapSqlFiles as $bootstrapSqlFile) {
                if (file_exists($bootstrapSqlFile)) {
                    echo "Adding bootstrap SQL file $bootstrapSqlFile\n";
                    $bootstrapSqlString .= "\r\n" . preg_replace('/^CREATE (DATABASE|SCHEMA) .*$/', '', file_get_contents($bootstrapSqlFile)) ."\r\n";
                }
                
            }
        } 
        
        $bootstrapSqlString .= <<<END
            
        CREATE TABLE IF NOT EXISTS `test` (
            test_id INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
            test_field VARCHAR(100) 
        );
END
;
        
        file_put_contents($install_sql_path, $bootstrapSqlString);
        echo "Bootstrapping database...";
        exec('sh '.escapeshellarg($mysql).' init < '.escapeshellarg($install_sql_path), $buf, $res);
        if ($res !== 0) {
            fwrite(STDERR, "Failed. Error attempting to create database.\n");
            echo "Stopping mysql server...";
            exec('sh '.escapeshellarg($mysql_server).' stop', $buf, $res);
            if ($res !== 0) {
                fwrite(STDERR, "Failed to stop mysql server.\n");
            }
            echo "Stopped.\n";
            exit(1);
        }

        echo "Done\n";

        echo "Stopping mysql server...";
        exec('sh '.escapeshellarg($mysql_server).' stop', $buf, $res);
        if ($res !== 0) {
            fwrite(STDERR, "Failed to stop mysql server.\n");
            exit(1);
        }   
        echo "Stopped\n"; 
        
        echo "Done\n";
    }
        
    


    function randomPassword() {
        $alphabet = "abcdefghijklmnopqrstuwxyzABCDEFGHIJKLMNOPQRSTUWXYZ0123456789";
        for ($i = 0; $i < 8; $i++) {
            $n = rand(0, strlen($alphabet)-1);
            $pass[$i] = $alphabet[$n];
        }
        return implode('', $pass);
    }
    

    function start() {
        $data = $this->data_dir();
        $quotedData = escapeshellarg($data);
        $quotedTmp = escapeshellarg($this->tmp_dir());
        $quotedLog = escapeshellarg($this->error_log_path());
        $quotedPid = escapeshellarg($this->pid_file_path());
        $quotedSocket = escapeshellarg($this->socket_path());
        $quotedUser = escapeshellarg(get_current_user());
        echo "Starting mysqld...";
        exec("mysqld_safe --skip-grant-tables --skip-networking --tmpdir=$quotedTmp --datadir=$quotedData --innodb_data_home_dir=$quotedData --innodb_log_group_home_dir=$quotedData --log-error=$quotedLog --pid-file=$quotedPid --socket=$quotedSocket --port=93306 --user=$quotedUser &", $buffer, $res);
        if ($res !== 0) {
            throw new \Exception("Failed to start mysql.  Response code $res");
        } else {
            echo "done.\n";
            print_r($buffer);
        }
    }
}
?>