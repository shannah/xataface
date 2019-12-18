<?php
chdir(dirname(__FILE__).'/../../app');
require_once 'xataface/public-api.php';
df_init(dirname(__FILE__).'/../../app/index.php', 'xataface');
import(XFROOT.'Dataface/Table.php');

if (count($argv) < 2) {
    fwrite(STDERR, "create-fieldsini requires at least 1 arg:  The table name\n");
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
$iniFile = $tableDirectory.'/fields.ini';
$iniPhpFile = $tableDirectory.'/fields.ini.php';
if (file_exists($iniFile)) {
    fwrite(STDERR, "The fields.ini file for table $tableName already exists.\nFound $iniFile\n");
    exit(1);
}
if (file_exists($iniPhpFile)) {
    fwrite(STDERR, "The fields.ini file for table $tableName already exists.\nFound $iniPhpFile\n");
    exit(1);
}

$table = Dataface_Table::loadTable($tableName);
$fieldNames = array_keys($table->fields());
$content = ";<?php exit;\n";
foreach ($fieldNames as $fieldName) {
    $content .= "[".$fieldName."]\n\n";
}

file_put_contents($iniPhpFile, $content);
echo "Created $iniPhpFile\n";