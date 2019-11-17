<?php
chdir(dirname(__FILE__).'/../../app');
require_once 'xataface/public-api.php';
df_init(dirname(__FILE__).'/../../app/index.php', 'xataface');
import(XFROOT.'Dataface/Table.php');


if (count($argv) < 3) {
    fwrite(STDERR, "add-user requires at least 2 arguments: username and email\n");
    fwrite(STDERR, "usage: add-user.sh username email [role=USER] [password]]\n");
    exit(1);
}

function randomPassword() {
    $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
    $pass = array(); //remember to declare $pass as an array
    $alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
    for ($i = 0; $i < 8; $i++) {
        $n = rand(0, $alphaLength);
        $pass[] = $alphabet[$n];
    }
    return implode($pass); //turn the array into a string
}
$username = $argv[1];
$email = $argv[2];
$role = count($argv) > 3 ? $argv[3] : 'USER';
$useRandomPass = count($argv) <= 4;
$password = count($argv) > 4 ? $argv[4] : randomPassword();

$app = Dataface_Application::getInstance();
$conf = $app->conf();
if (!@$conf['_auth']) {
    fwrite(STDERR, "Failed.  Set up authentication first.\n");
    exit(1);
}
$usersTable = $conf['_auth']['users_table'];
if (!Dataface_Table::tableExists($usersTable)) {
    fwrite(STDERR, "Failed.  Users table doesn't exist");
    exit(1);
}
import(XFROOT.'Dataface/Record.php');
$newUser = new Dataface_Record($usersTable, array());
$newUser->setValue($conf['_auth']['username_column'], $username);
$newUser->setValue($conf['_auth']['email_column'], $email);
$newUser->setValue($conf['_auth']['password_column'], $password);
$newUser->setValue('role', $role);
$res = $newUser->save();
if (PEAR::isError($res)) {
    fwrite(STDERR, "Failed to create new user due to a database error.\n");
    fwrite(STDERR, $res->getMessage());
    exit(1);
}
$withPass = $useRandomPass ? " with password '".$password."'" : '';
echo "Successfully created user $username $withPass\n";
