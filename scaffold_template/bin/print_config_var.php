<?php
error_reporting(E_ALL);
ini_set('display_errors', 'on');
if (!@$argv) {
    fwrite(STDERR, "CLI ONLY");
    exit(1);
}
$XATAFACE_CONFIG_PATHS = array();

$cliConfPath = dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'cli-conf.ini';

if (is_readable($cliConfPath)) {
	$cliConf = parse_ini_file($cliConfPath, true);
	if (isset($cliConf['XATAFACE_CONFIG_PATHS'])) {
		$paths = explode(":", $cliConf['XATAFACE_CONFIG_PATHS']);
		foreach ($paths as $path) {
			if (!trim($path)) {
				continue;
			}
			$XATAFACE_CONFIG_PATHS[] = dirname($cliConfPath).DIRECTORY_SEPARATOR.$path;
		}
	}
}
$XATAFACE_CONFIG_PATHS[] = dirname(__FILE__). DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'app';
$files = array('conf.ini.php', 'conf.ini');
$conf = array();
foreach ($files as $file) {
	foreach ($XATAFACE_CONFIG_PATHS as $path) {
	    $file_path = $path . DIRECTORY_SEPARATOR . $file;
	    if (file_exists($file_path)) {
			$tmp = parse_ini_file($file_path, true);
			if ( @$tmp['__include__'] ){
				$includes = array_map('trim',explode(',', $tmp['__include__']));
				foreach ($includes as $i){
					if (!trim($i)) {
						continue;
					}
	                $p = dirname($file_path) . DIRECTORY_SEPARATOR . $i;             
					if ( is_readable($p) ){
						$tmp = array_merge($tmp, parse_ini_file($p, true));
					} else if ( is_readable($p.'.php') ){
						$tmp = array_merge($tmp, parse_ini_file($p.'.php', true));
					}
				}
			}
	        $conf = array_replace_recursive($tmp, $conf);
	    }
	}
    
}
if (count($argv) < 2) {
    print_r($conf);
    exit;
}
$key = $argv[1];
if (strpos($key, '.') !== false) {
    list($key1, $key2) = explode('.', $key);
	if (is_array($key1)) {
		throw new Exception("Key1 is array");
	}
	if (is_array($key2)) {
		throw new Exception('Key 2 is array');
	}
	if (is_array($conf[$key1][$key2])) {
		ob_start();
		print_r($conf[$key1][$key2]);
		$arr = ob_get_contents();
		ob_end_clean();
		throw new Exception('conf[key1][key2] is array '.$arr);
	}
    echo @$conf[$key1][$key2];

} else {
    switch ($key) {
        case 'XFServerRoot' :
            echo dirname(dirname(realpath(__FILE__)));
            exit;
        case 'XFServerPort':
			if (@$conf['XFServerPort']) {
				echo $conf['XFServerPort'];
			} else {
				echo '9090';
			}
            
            exit;
        case 'XFShortVersionString':
            $version_path = dirname(__FILE__) 
            . DIRECTORY_SEPARATOR . '..' 
            . DIRECTORY_SEPARATOR . 'app' 
            . DIRECTORY_SEPARATOR . 'version.txt';
            if (file_exists($version_path)) {
                $version = file_get_contents($version_path);
                if (strpos($version, ' ') !== false) {
                    echo trim(substr($version, strpos($version, ' ')));
                } else {
                    echo trim($version);
                }
            } else {
                echo '1.0';
            }
            exit;
            

    }
    echo $conf[$key];
}


