<?php
ini_set('display_errors', 'on');
error_reporting(E_ALL);

if (php_sapi_name() != "cli") {
    fwrite(STDERR, "CLI ONLY");
    exit(1);
}

function is_scaffold($dir) {
    return file_exists($dir . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'appctl.sh');
}

function replaceConfDbSample($newConfDb, $dir) {
    foreach (array('conf.db.ini.sample', 'conf.db.ini.php.sample') as $sampleFile) {
        $samplePath = $dir . DIRECTORY_SEPARATOR . $sampleFile;
        if (file_exists($samplePath)) {
            $fixedPath = substr($samplePath, 0, strrpos($samplePath, '.'));
            copy($newConfDb, $fixedPath);
            unlink($samplePath);
            return true;
        }
    }
    foreach (scandir($dir) as $child) {
        if ($child[0] == '.') {
            continue;
        }
        $childPath = $dir . DIRECTORY_SEPARATOR . $child;
        if (is_dir($childPath)) {
            $res = replaceConfDbSample($newConfDb, $childPath);
            if ($res) {
                return true;
            }
        }

    }
    return false;
}

function load_conf_ini_files($dir) {
    $out = array();
    foreach (array('conf.ini', 'conf.ini.php', 'conf.db.ini', 'conf.db.ini.php') as $iniFile) {
        $iniPath = $dir . DIRECTORY_SEPARATOR . $iniFile;
        if (!file_exists($iniPath)) {
            continue;
        }
        $data = parse_ini_file($iniPath, true);
        
        $out = array_merge_recursive($out, $data);
        if (@$out['_database'] and @$out['_database']['name']) {
            // We only need to find the database name
            break;
        }
    }
    if (!@$out['_database'] or !$out['_database']['_name']) {
        foreach (scandir($dir) as $child) {
            if ($child[0] == '.') {
                continue;
            }
            if (!is_dir($dir . DIRECTORY_SEPARATOR .$child)) {
                continue;
            }
            if (strpos($child, 'xataface') === 0) {
                continue;
            }
            $data = load_conf_ini_files($dir . DIRECTORY_SEPARATOR . $child);
            if (@$data['_database'] and @$data['_database']['name']) {
                return $data;
            }
        }
    }
    return $out;
}

function find_xapp_dir($dir) {
    foreach (array('conf.ini', 'conf.db.ini', 'conf.ini.php', 'conf.db.ini.php') as $file) {
        $path = $dir . DIRECTORY_SEPARATOR . $file;
        if (file_exists($path)) {
            return $dir;
        }
    }
    foreach (scandir($dir) as $child) {
        if ($child[0] == '.') {
            continue;
        }
        $path = $dir . DIRECTORY_SEPARATOR . $child;
        if (!is_dir($path)) {
            continue;
        }
        $res = find_xapp_dir($path);
        if ($res) {
            return $res;
        }
       
    }
    return false;

}

if (count($argv) < 2) {
    fwrite(STDERR, "Usage clone.php REPO_URL\n");
    exit(1);
}
$repoUrl = $argv[1];
if ($repoUrl{-1} == '/') {
    $repoUrl = substr($repoUrl, 0, -1);
}
$dest = substr($repoUrl, strrpos($repoUrl, '/')+1);
if (count($argv) >= 3) {
    $dest = $argv[2];
}

echo "Cloning $repoUrl into $dest ...";
exec("git clone ".escapeshellarg($repoUrl)." ".escapeshellarg($dest), $buffer, $res);
if ($res !== 0) {
    fwrite(STDERR, "Failed.\n");
    print_r($buffer);
    exit($res);
}
echo "Done\n";

if (!is_scaffold($dest)) {

    
    $destName = basename($dest);
    $tempDest = $dest.'-'.time();
    rename($dest, $tempDest);
    $config = load_conf_ini_files($tempDest);
    $create = dirname(__FILE__) .DIRECTORY_SEPARATOR . 'create.php';
    echo "Creating scaffold for app ...\n";
    if (@$config['_database']['name']) {
        passthru("php ".escapeshellarg($create)." ".escapeshellarg($dest).' -db.name='.escapeshellarg($config['_database']['name']), $res);
    } else {
        passthru("php ".escapeshellarg($create)." ".escapeshellarg($dest), $res);
    }
    
    if ($res !== 0) {
        fwrite(STDERR, "Failed to create scaffold\n");
        
        exit($res);
    }
    $s = DIRECTORY_SEPARATOR;
    rename($dest . $s . 'www', $dest . $s . 'www-old');
    rename($tempDest, $dest . $s . 'www');
    
    $appPath = find_xapp_dir($dest . $s . 'www');
    if (!$appPath) {
        fwrite(STDERR, "No app directory found.");
        exit(1);
    }
    if (realpath($appPath) != realpath($dest . $s . 'www')) {
        // The xataface app is in a subdirectory.
        unlink($dest . $s . 'app');
        $folderTmp = getcwd();
        if (!chdir(realpath($dest))) {
            fwrite(STDERR, "Failed to change directory to $dest [".realpath($dest)."], cwd=".getcwd()."\n");
            exit(1);
        }
        if (!symlink(find_xapp_dir('www'), 'app')) {
            fwrite(STDERR, "Failed to create symlink from app to www\n");
            exit(1);
        }
        if (!chdir($folderTmp)) {
            fwrite(STDERR, "Failed to change directory back to $folderTmp\n");
            exit(1);
        }

    }

    if (!file_exists($dest . $s . 'app' . $s . '.htaccess')) {
        copy($dest . $s . 'www-old' . $s . '.htaccess', $dest . $s . 'app' . $s . '.htaccess' );
    }
    echo "Done\n";
    echo "Looking for sample conf.db.ini files to replace...";
    replaceConfDbSample($dest . $s . 'www-old' . $s . 'conf.db.ini.php', $dest . $s . 'app');
    
    $xatafacePath = $appPath . $s . 'xataface';
    if (!file_exists($xatafacePath)) {
        if (!rename($dest . $s . 'www-old' .$s.'xataface', $xatafacePath)) {
            fwrite(STDERR, "Failed to rename xataface to $xatafacePath\n");
            exit(1);
        }
    }
    $templatesCPath = $appPath . $s . 'templates_c';
    if (!file_exists($templatesCPath)) {
        if (!rename($dest . $s . 'www-old' . $s . 'templates_c', $templatesCPath)) {
            fwrite(STDERR, "Failed to move templates_c into app.\n");
            exit(1);
        }
    }

    echo "Done\n";
    
}

