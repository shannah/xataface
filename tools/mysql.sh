#!/bin/sh
SCRIPTPATH="$( cd "$(dirname "$0")" ; pwd -P )"
scaffolddir="$SCRIPTPATH/..";
mysql --socket="$scaffolddir"/tmp/mysql.sock -u `php print_config_var.php _database.user` `php print_config_var.php _database.name`