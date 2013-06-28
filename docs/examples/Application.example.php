<?php
// Sample application
require_once '/path/to/dataface/dataface-public-api.php';
df_init(__FILE__, '/dataface');
$app =& Dataface_Application::getInstance();
$app->display();
?>
