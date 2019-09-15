<?php
echo "Setting up authentication on app...\n";
chdir(dirname(__FILE__).'/../../app');
require_once 'xataface/public-api.php';
df_init(dirname(__FILE__).'/../../app/index.php', 'xataface');
$app = Dataface_Application::getInstance();
$conf = $app->conf();
if (@$conf['_auth']) {
    fwrite(STDERR, "Authentication is already setup for this app.\n");
    exit(1);
}
$confIni = 'conf.ini';
if (file_exists('conf.ini.php')) {
    $confIni = 'conf.ini.php';
}
if (!file_exists($confIni)) {
    fwrite(STDERR, "Cannot find conf.ini file\n");
    exit(1);
}

import('Dataface/Table.php');
if (Dataface_Table::tableExists('users')) {
    fwrite(STDERR, "users table already exists.\n");
    exit(1);
}

$res = xf_db_query("create table users (
    `username` varchar(100) not null primary key,
    `password` varchar(64) not null,
    `email` varchar(255) not null,
    `role` enum('USER', 'ADMIN') default 'USER',
    CONSTRAINT unique_email UNIQUE (`email`) 
)", df_db());
if (!$res) {
    fwrite(STDERR, "Failed to create users table\n");
    exit(1);
}

$confIniContents = file_get_contents($confIni);
$confIniContents .=  <<<END
\n\n[_auth]
  users_table=users
  username_column=username
  password_column=password
  email_column=email
  ; Uncomment next line to allow users to register
  ; for account
  ;allow_register=1

  ; Uncomment next line to override session timeout (in seconds)
  ;session_timeout=86400

END;

echo "Updating $confIni ...";
file_put_contents($confIni, $confIniContents);
echo "Done\n";
// Now make sure that the password field is encrypted

if (!file_exists('tables')) {
    mkdir('tables');
}
if (!file_exists('tables/users')) {
    mkdir('tables/users');
}
echo "Adding fields.ini.php file for users table...";
if (file_exists('tables/users/fields.ini.php') or file_exists('tables/users/fields.ini')) {
    fwrite(STDERR, "Failed\n");
    fwrite(STDERR, "Already exists.  Make sure that the password field is setup with some form of encryption.\n");
    exit(1);
}
file_put_contents('tables/users/fields.ini.php', <<<END
[password]
    encryption=sha1

END
);
echo "Done\n";

// Now add utility library for checking user level
if (!file_exists('inc')) {
    mkdir('inc');
}
echo "Creating inc/functions.inc.php ...";
if (!copy('xataface/snippets/functions.inc.php', 'inc/functions.inc.php')) {
    fwrite(STDERR, "Failed\n");
    exit(1);
}
$indexContents = file_get_contents('index.php');
if (!strpos($indexContents, "functions.inc.php")) {
    echo "Adding functions.inc.php include into index.php...";
    $indexContents = str_replace("<?php", "<?php\nrequire_once 'inc/functions.inc.php';\n", $indexContents);
    if (!file_put_contents('index.php', $indexContents)) {
        fwrite(STDERR, "Failed\n");
        exit(1);
    }
    echo "Done\n";

}

// Now add central permissions file.
$appDelegate = 'conf/ApplicationDelegate.php';
if (!file_exists($appDelegate)) {
    echo "Creating application delegate class...";
    chdir("../bin");
    exec("bash create-app-delegate.sh", $buffer, $res);
    if ($res !== 0) {
        fwrite(STDERR, "Failed\n");
        print_r($buffer);
        exit(1);
    }
    echo "Done\n";
    chdir("../app");
}
$appDelegateContents = file_get_contents($appDelegate);
$updated = false;
if (strpos($appDelegateContents, 'function getPermissions(') !== false) {
    // getPermissions exists
    if (strpos($appDelegateContents, '//insert:getPermissions') !== false) {
        
        $appDelegateContents = str_replace('//insert:getPermissions', "if (isAdmin()) return Dataface_PermissionsTool::ALL();\n        return Dataface_PermissionsTool::NO_ACCESS();\n", $appDelegateContents);
        $updated = true;
    } else {
        fwrite(STDERR, "WARNING: AppDelegate already contains a getPermissions() method.  It was left untouched. \n");
    }
} else {
    if (strpos($appDelegateContents, '//insert:methods') !== false) {
        $appDelegateContents = str_replace('//insert:methods', "//insert:methods\n\n    function getPermissions(Dataface_Record \$record = null) {\n        if (isAdmin()) return Dataface_PermissionsTool::ALL();\n        return Dataface_PermissionsTool::NO_ACCESS();\n    }\n    ", $appDelegateContents);
        $updated = true;
    } else {
        fwrite(STDERR, "WARNING: AppDelegate does not contains an //insert:methods marker, so I am unable to generate the getPermissions() method.  Make sure you add one yourself.\n");

    }
}

if ($updated) {
    echo "Updating $appDelegate with default permissions...";
    if (!file_put_contents($appDelegate, $appDelegateContents)) {
        fwrite(STDERR, "FAILED\n");
        exit(1);
    }
    echo "Done\n";
    echo "A default implementation of getPermissions() has been added to the application delegate class at $appDelegate\n";
}

echo "Adding default admin user...";
chdir("../bin");
exec("bash add-user.sh admin 'admin@example.com' ADMIN password", $buffer, $res);
if ($res !== 0) {
    fwrite(STDERR, "Failed\n");
    print_r($buffer);
    exit(1);
}
echo "Done.\nUser 'admin' created with password 'password'\n";
echo "You should change this user's password after logging in.\n";
chdir("../app");





