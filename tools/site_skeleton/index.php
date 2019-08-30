<?php
/**
 * File: index.php
 * Description:
 * -------------
 *
 * This is an entry file for this Dataface Application.  To use your application
 * simply point your web browser to this file.
 */
require_once '{__DATAFACE_PATH__}/public-api.php';
df_init(__FILE__, '{__DATAFACE_URL__}')->display();
