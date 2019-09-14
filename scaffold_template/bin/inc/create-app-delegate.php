<?php
chdir(dirname(__FILE__).'/../../app');
require_once 'xataface/public-api.php';
df_init(dirname(__FILE__).'/../../app/index.php', 'xataface');
import(XFROOT.'Dataface/Table.php');



if (!file_exists('conf')) {
    mkdir('conf');
}

$delegateFile = 'conf/ApplicationDelegate.php';
if (file_exists($delegateFile)) {
    fwrite(STDERR, "The application delegate class already exists.\nFound $delegateFile\n");
    exit(1);
}
file_put_contents($delegateFile, <<<END
<?php
class conf_ApplicationDelegate {

    function getPermissions(Dataface_Record \$record = null) {
        // Override permissions here

        //insert:getPermissions
        return null;
    }

    //insert:methods
}
END
);
echo "Created $delegateFile\n";