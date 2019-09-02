<?php
ini_set('display_errors', 'on');
error_reporting(E_ALL);

if (!@$argv) {
    fwrite(STDERR, "CLI ONLY");
    exit(1);
}

function help() {

}

class XFProject {
    private $basedir;

    function __construct($basedir) {
        $this->basedir = $basedir;
    }

    function xataface_dir() {
        return realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR  . '..');
    }

    function local_xataface_dir() {
        return $this->www_dir() . DIRECTORY_SEPARATOR . 'xataface';
    }

    function site_skeleton_dir() {
        return $this->xataface_dir() . DIRECTORY_SEPARATOR . 'site_skeleton';
    }

    function www_dir() {
        return $this->basedir . DIRECTORY_SEPARATOR . 'www';
    }

    function templates_c_dir() {

        return $this->www_dir() . DIRECTORY_SEPARATOR . 'templates_c';
    }

    function data_dir() {
        return $this->basedir . DIRECTORY_SEPARATOR . 'data';
    }

    function log_dir() {
        return $this->basedir . DIRECTORY_SEPARATOR . 'log';
    }

    function error_log_path() {
        return $this->log_dir() . DIRECTORY_SEPARATOR . 'mysql-errors.log';
    }

    function tmp_dir() {
        return $this->basedir . DIRECTORY_SEPARATOR . 'tmp';
    }

    function pid_file_path() {
        return $this->tmp_dir() . DIRECTORY_SEPARATOR . 'mysql.pid';
    }

    function socket_path() {
        return $this->tmp_dir() . DIRECTORY_SEPARATOR . 'mysql.sock';
    }

    function bin_dir() {
        return $this->basedir . DIRECTORY_SEPARATOR . 'bin';
    }

    function lib_dir() {
        return $this->basedir . DIRECTORY_SEPARATOR . 'lib';
    }

    function etc_dir() {
        return $this->basedir . DIRECTORY_SEPARATOR . 'etc';
    }

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

    private function tools_dir() {
        return $this->xataface_dir() . DIRECTORY_SEPARATOR . 'tools';
    }

    function create_deny_all_htaccess($path) {
        $out = <<<END
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
        $this->install_composer();
        $this->install_yarn();
        $this->install_php_my_admin();
        $this->create_local_xataface();
        mkdir($this->templates_c_dir());
        chmod($this->templates_c_dir(), 0777);
        $this->create_deny_all_htaccess($this->templates_c_dir() . '/.htaccess');

        $this->init_db();

        
        
    }

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

    function install_php_my_admin() {
        mkdir($this->lib_dir());
        $phpMyAdmin = $this->lib_dir() . DIRECTORY_SEPARATOR . 'phpmyadmin';
        $tmpPath = $this->lib_dir() . DIRECTORY_SEPARATOR . 'phpmyadmin.zip';
        //$phpMyAdminUrl = 'https://github.com/shannah/phpmyadmin/archive/master.zip';
        $phpMyAdminUrl = 'https://github.com/phpmyadmin/phpmyadmin/archive/master.zip';
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
        $zip->extractTo($this->lib_dir());
        $zip->close();
        unlink($tmpPath);
        if (!rename($phpMyAdmin.'-master', $phpMyAdmin)) {
            fwrite(STDERR, "Failed.\n");
            exit(1);
        }
        echo "Done\n";

        echo 'Installing phpMyAdmin using composer...';
        $quotedComposer = escapeshellarg(realpath($this->lib_dir() . DIRECTORY_SEPARATOR . 'composer.phar'));
        $quotedPhpMyAdmin = escapeshellarg(realpath($phpMyAdmin));
        exec('cd '.$quotedPhpMyAdmin.' && php '.$quotedComposer.' update --no-dev', $buf, $res);
        if ($res !== 0) {
            fwrite(STDERR, "Failed\n");
            //fwrite(STDERR, $buf);
            print_r($buf);
            exit(1);
        }
        exec('cd '.$quotedPhpMyAdmin.' && ../yarn/bin/yarn install', $buf, $res);
        if ($res !== 0) {
            fwrite(STDERR, "Failed\nYarn install failure\n");
            print_r($buf);
            exit(1);
        }
        $configSample = $phpMyAdmin . DIRECTORY_SEPARATOR . 'config.sample.inc.php';
        $config = $phpMyAdmin .DIRECTORY_SEPARATOR . 'config.inc.php';
        if (!rename($configSample, $config)) {
            fwrite(STDERR, "Failed\n");
            fwrite(STDERR, "Failed to rename config sample to config.");
            exit(1);
        }
        $contents = file_get_contents($config);
        $contents = str_replace(
            '$cfg[\'Servers\'][$i][\'auth_type\'] = \'cookie\';', 
            '$cfg[\'Servers\'][$i][\'auth_type\'] = \'config\';', 
            $contents
        );
        $contents = str_replace('$cfg[\'Servers\'][$i][\'AllowNoPassword\'] = false;',
            '$cfg[\'Servers\'][$i][\'AllowNoPassword\'] = true;',
            $contents
        );
        if (!file_put_contents($config, $contents)) {
            fwrite(STDERR, "Failed\n");
            fwrite(STDERR, "Failed to write config contents.");
            exit(1);

        }

        echo "Done\n";

        


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
    function init_db() {
        $conf_db_ini_path = $this->www_dir() . DIRECTORY_SEPARATOR . 'conf.db.ini.php';
        if (!file_exists($conf_db_ini_path)) {
            fwrite(STDERR, "$conf_db_ini_path not found.");
            exit(1);
        }
        echo "Initializing database ... ";
        $conf = parse_ini_file($conf_db_ini_path, true);
        $contents = file_get_contents($conf_db_ini_path);
        if ($conf['_database']['name'] == '{__DATABASE_NAME__}') {
            $name = basename($this->basedir);
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
        exec('sh '.escapeshellarg($mysql_server).' start', $buf, $res);
        if ($res !== 0) {
            fwrite(STDERR, "Failed to start mysql server.");
            exit(1);
        }
        $install_sql_path = $this->basedir . DIRECTORY_SEPARATOR . 'install.sql';

        

        file_put_contents($install_sql_path, <<<END
CREATE DATABASE IF NOT EXISTS `{$conf['_database']['name']}`;
USE `{$conf['_database']['name']}`;
CREATE TABLE IF NOT EXISTS `test` (
    test_id INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    test_field VARCHAR(100) 
);
END
    );

        exec('sh '.escapeshellarg($mysql).' init < '.escapeshellarg($install_sql_path), $buf, $res);
        if ($res !== 0) {
            fwrite(STDERR, "Failed. Error attempting to create database.\n");
            exec('sh '.escapeshellarg($mysql_server).' stop', $buf, $res);
            if ($res !== 0) {
                fwrite(STDERR, "Failed to stop mysql server.\n");
            }
            exit(1);
        }



        exec('sh '.escapeshellarg($mysql_server).' stop', $buf, $res);
        if ($res !== 0) {
            fwrite(STDERR, "Failed to stop mysql server.\n");
            exit(1);
        }    
        
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
if (count(@$argv) < 2) {
    help();
    exit(1);
}
$p = $argv[1];
echo "Create project at {$p}\n";
$proj = new XFPRoject($p);
$proj->create_scaffold();