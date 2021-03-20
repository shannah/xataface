<?php
if (!@$argv) {
    die("CLI only");
}
define('MODULE_INDEX_URL', 'https://raw.githubusercontent.com/shannah/xataface-modules/master/index.json');
function modules_dir() {
    return 'modules';
}


function installModuleFromZipUrl($moduleName, $moduleUrl, $version) {
	echo "Installing module from ZIP at url $moduleUrl\n";
    $dest = tempnam(sys_get_temp_dir(), $moduleName);
    echo "Downloading $moduleUrl...";
    if (!file_put_contents($dest, fopen($moduleUrl, 'rb'))) {
        fwrite(STDERR, "Failed.\n");
        exit(1);
    }
    echo "Done\n";

    installModuleFromZipFile($moduleName, $dest, $version);
    unlink($dest);

}

function installModuleFromGit($moduleName, $gitRepoUrl, $version) {
	echo "Installing module from git repo: $gitRepoUrl\n";
    $moduleUrl = $gitRepoUrl.'/archive/master.zip';
    if ($version) {
        $moduleUrl = $gitRepoUrl.'/archive/'.$version.'.zip';
    }
    return installModuleFromZipUrl($moduleName, $moduleUrl, $version);
}

function extractVersionNumber($dir) {
    $versionFile = $dir . DIRECTORY_SEPARATOR . 'version.txt';
    if (!file_exists($versionFile)) {
        return 0;
    }
    $versionString = trim(file_get_contents($versionFile));
    $spacePos = strpos($versionString, ' ');
    if ($spacePos === false) {
        fwrite(STDERR, "\nWARNING: Invalid version number in $versionFile\n");
        return 0;
    }
    return intval(substr($versionString, $spacePos+1));
}

function installModuleFromZipFile($moduleName, $zipPath, $version) {
	echo "Installing module from Zip at path $zipPath\n";
    $zip = new ZipArchive;
    echo "Extracting ZIP archive...";
    $res = $zip->open($zipPath);
    if (!$res) {
        fwrite(STDERR, "Failed\n");
        exit(1);
    }
    $dest = tempnam(sys_get_temp_dir(), $moduleName);
    if (!file_exists($dest)) {
        fwrite(STDERR, "Failed\n");
        fwrite(STDERR, "Couldn't create temp file.");
        exit(1);
    }
    unlink($dest);
    if (!mkdir($dest)) {
        fwrite(STDERR, "Failed\n");
        fwrite(STDERR, "Couldn't create temp directory to extract to.");
        exit(1);
    }
    if (!$zip->extractTo($dest)) {
        fwrite(STDERR, "Failed\n");
        fwrite(STDERR, "Failed to extract archive to $dest");
        exit(1);
    }
    $zip->close();
    echo "Done\n";
    foreach (scandir($dest) as $child) {
        if ($child[0] === '.') {
            continue;
        }
        $dest = $dest . DIRECTORY_SEPARATOR . $child;
        break;
    }

    $installDir = modules_dir() . DIRECTORY_SEPARATOR . $moduleName;

    echo "Moving to $installDir...";
    if (!file_exists(modules_dir())) {
        if (!@mkdir(modules_dir())) {
            fwrite(STDERR, "Failed\n");
            fwrite(STDERR, "Couldn't create modules dir.\n");
            exit(1);
        }
    }
    
    if (!rename($dest, $installDir)) {
        fwrite(STDERR, "Failed\n");
        exit(1);
    }
    echo "Done.\n";
    echo "Module $moduleName successfully installed at $installDir\n";
    
    
}

function findModuleUrl($moduleName, $version) {
    echo "Loading module index...";
    $index = file_get_contents(MODULE_INDEX_URL);
    if (!$index) {
        fwrite(STDERR, "Failed to load module index from ".MODULE_INDEX_URL."\n");
        return null;
    }
    echo "Done\n";
    $json = json_decode($index, true);
    
    if (!$json) {
        fwrite(STDERR, "Failed to parse JSON module index\n");
        return null;
    }

    $match = null;

    echo count($json['modules']).' modules in index.'."\n";
    foreach ($json['modules'] as $moduleArr) {
        if (strcasecmp($moduleName, $moduleArr['name']) === 0) {
            $match = $moduleArr;
            break;
        }
    }

    if (!isset($match)) {
        fwrite(STDERR, "Did not find any matches for $moduleName in the index.\n");
        return null;
    }
    echo "Found module in index matching $moduleName\n";
    if ($version) {
        $repoUrl = @$match['repoUrl'];
        if (!$repoUrl) {
            fwrite(STDERR, "No repoUrl was specified for module, which is required to pin a version.\n");
            return null;
        }
        if (!preg_match('#^https://github.com#', $repoUrl)) {
            fwrite(STDERR, "Only github repo urls are supported with versions, but found repo URL ".$repoUrl."\n");
            return null;
        }
        return $repoUrl . '/archive/'.$version.'.zip';
    } else {
        if (@$match['zipUrl']) {
            return $match['zipUrl'];
        } else {
            if (@$match['repoUrl'] and preg_match('#^https://github.com/#', $match['repoUrl'])) {
                //https://github.com/shannah/xataface-module-calendar/archive/master.zip
                return $match['repoUrl'].'/archive/master.zip';
            } else {
                fwrite(STDERR, "No zipUrl specified for module $moduleName, and repoUrl is either not specified or not supported.  Only github repoUrls are supported\n");
                return null;
            }
        }
    }
}

function checkExistingVersion($moduleName) {

}

if (!chdir(dirname(__FILE__).'/../../app')) {
    fwrite(STDERR, "Failed to change directory to app\n");
    exit(1);
}
require_once 'xataface/public-api.php';
df_init(dirname(__FILE__).'/../../app/index.php', 'xataface');
import(XFROOT.'Dataface/Table.php');

if (count($argv) < 2) {
    fwrite(STDERR, "Module name or url required\n");
    exit(1);
}
$scriptName = array_shift($argv);
$moduleName = array_shift($argv);
$moduleUrl = array_shift($argv);
$version = null;
if (isset($moduleUrl)) {
    if (preg_match('/^[0-9]/', $moduleUrl)) {
        $version = $moduleUrl;
        $moduleUrl = null;
    }
}
$version = $version ? $version : array_shift($argv);
if (!isset($version)) {
    $version = 0;
}

$modulePath = modules_dir() . DIRECTORY_SEPARATOR . $moduleName;

if (file_exists($modulePath)) {
    fwrite(STDERR, "ERROR: Module $moduleName is already installed at $modulePath\n");
    exit(1);
}

if (!isset($moduleUrl)) {
    $moduleUrl = findModuleUrl($moduleName, $version);

    if (!isset($moduleUrl)) {
        fwrite(STDERR, "Failed to find module URL for module $moduleName\n");
        exit(1);
    }
}

$isZip = (isset($moduleUrl) and preg_match('/\.zip$/', $moduleUrl));

echo "Is Zip? $isZip for $moduleUrl\n";


if (strpos($moduleUrl, 'https://github.com/') === 0) {
    if ($isZip) {
        installModuleFromZipUrl($moduleName, $moduleUrl, $version);
    } else {
        installModuleFromGit($moduleName, $moduleUrl, $version);
    }
} else if (strpos($moduleUrl, 'https://') === 0 or strpos($moduleUrl, 'http://') === 0) {
    installModuleFromZipUrl($moduleName, $moduleUrl, $version);
} else if (file_exists($moduleUrl) and $isZip) {
    installModuleFromZipFile($moduleName, $moduleUrl, $version);
} else {
    fwrite(STDERR, "Don't know how to install this module.\n");
    exit(1);
}  


$modulesDir = 'modules';
