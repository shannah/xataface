<?php

// Bench script of Cache_Lite
// $Id: bench.php,v 1.1.1.1 2005/11/29 19:21:53 sjhannah Exp $

require_once('Cache/Lite.php');

$options = array(
    'caching' => true,
    'cacheDir' => '/tmp/',
    'lifeTime' => 10
);

$Cache_Lite = new Cache_Lite($options);

if ($data = $Cache_Lite->get('123')) {
    echo($data);
} else {
    $data = '';
    for($i=0;$i<1000;$i++) {
        $data .= '0123456789';
    }
    echo($data);
    $Cache_Lite->save($data);
}

?>
