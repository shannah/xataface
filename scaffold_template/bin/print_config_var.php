<?php
if (!@$argv) {
    fwrite(STDERR, "CLI ONLY");
    exit(1);
}

$files = array('conf.ini', 'conf.ini.php', 'conf.db.ini', 'conf.db.ini.php');
$conf = array();
foreach ($files as $file) {
    $file_path = dirname(__FILE__) 
        . DIRECTORY_SEPARATOR . '..' 
        . DIRECTORY_SEPARATOR . 'www' 
        . DIRECTORY_SEPARATOR . $file;
    if (file_exists($file_path)) {
        $conf = array_merge_recursive($conf, parse_ini_file($file_path, true));
    }
}
if (count($argv) < 2) {
    print_r($conf);
    exit;
}
$key = $argv[1];
if (strpos($key, '.') !== false) {
    list($key1, $key2) = explode('.', $key);
    echo @$conf[$key1][$key2];

} else {
    switch ($key) {
        case 'XFServerRoot' :
            echo dirname(dirname(realpath(__FILE__)));
            exit;
        case 'XFServerPort':
            echo '9090';
            exit;

    }
    echo $conf[$key];
}


