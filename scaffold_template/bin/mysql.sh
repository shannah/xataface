#!/bin/sh
export PATH=/Applications/XAMPP/xamppfiles/bin:$PATH
SCRIPTPATH="$( cd "$(dirname "$0")" ; pwd -P )"
scaffolddir="$SCRIPTPATH/..";
ACMD="$1"
case $ACMD in
init)
    #echo "Doing mysql init"
    mysql --socket="$scaffolddir"/tmp/mysql.sock
    ERROR=$?
;;
*)
    #echo "Doing regular mysql"
    mysql --socket="$scaffolddir"/tmp/mysql.sock -u `php $SCRIPTPATH/print_config_var.php _database.user` `php $SCRIPTPATH/print_config_var.php _database.name` "$@"
    ERROR=$?
;;
esac
exit $ERROR