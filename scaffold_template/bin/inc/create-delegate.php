<?php
chdir(dirname(__FILE__).'/../../app');
require_once 'xataface/public-api.php';
df_init(dirname(__FILE__).'/../../app/index.php', 'xataface');
import(XFROOT.'Dataface/Table.php');

if (count($argv) < 2) {
    fwrite(STDERR, "create-delegate requires at least 1 arg:  The table name\n");
    exit(1);
}
$tableName = $argv[1];
if (!Dataface_Table::tableExists($tableName)) {
    fwrite(STDERR, "The table $tableName does not exist.\n");
    exit(1);
}
if (!file_exists('tables')) {
    mkdir('tables');
}
$tableDirectory = 'tables/'.basename($tableName);
if (!file_exists($tableDirectory)) {
    mkdir($tableDirectory);
}
$delegateFile = $tableDirectory.'/'.basename($tableName).'.php';
if (file_exists($delegateFile)) {
    fwrite(STDERR, "The delegate class for table $tableName already exists.\nFound $delegateFile\n");
    exit(1);
}
file_put_contents($delegateFile, <<<END
<?php
class tables_$tableName {

    function init(Dataface_Table \$table) {
        // Custom initialization here

        //insert:init
    }

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