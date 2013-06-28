<?php
require_once dirname(__FILE__).'/config.inc.php';
header('Content-type: text/javascript');
?>
// Global Plone variables that need to be accessible to the Javascripts

//portal_url = 'http://localhost/~shannah/lesson_plans';
portal_url = '<?php echo DATAFACE_URL; ?>';
DATAFACE_URL = portal_url;
DATAFACE_SITE_URL = '<?php echo DATAFACE_SITE_URL; ?>';
DATAFACE_SITE_HREF = '<?php echo DATAFACE_SITE_HREF; ?>';
