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

        $this->create_local_xataface();
        mkdir($this->templates_c_dir());
        chmod($this->templates_c_dir(), 0777);
        $this->create_deny_all_htaccess($this->templates_c_dir() . '/.htaccess');

        $this->init_db();

        
        
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