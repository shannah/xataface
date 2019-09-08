#!/bin/sh
export PATH=/Applications/XAMPP/xamppfiles/bin:$PATH
SCRIPTPATH="$( cd "$(dirname "$0")" ; pwd -P )"
scaffolddir="$SCRIPTPATH/..";
ACMD="$1"
mysqldump --socket="$scaffolddir"/tmp/mysql.sock -u `php $SCRIPTPATH/print_config_var.php _database.user` `php $SCRIPTPATH/print_config_var.php _database.name`
ERROR=$?
exit $ERROR