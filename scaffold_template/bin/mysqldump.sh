#!/bin/sh
export PATH=/Applications/XAMPP/xamppfiles/bin:$PATH
SCRIPTPATH="$( cd "$(dirname "$0")" ; pwd -P )"
status=`bash $SCRIPTPATH/mysql.server.sh status`
if [[ $status == *"ERROR!"* ]]; then
    $SCRIPTPATH/mysql.server.sh start || (echo "Failed to start mysql" && exit 1)
    function finish() {
        $SCRIPTPATH/mysql.server stop
    }
    trap finish EXIT
fi
scaffolddir="$SCRIPTPATH/..";
ACMD="$1"
DATABASE=`php $SCRIPTPATH/print_config_var.php _database.name`
IGNORE_TABLES=""
for table in $(sh bin/mysql.sh -e "show tables like 'dataface__view_%'")
do
	IGNORE_TABLES+="--ignore-table $DATABASE.$table "
done

mysqldump $IGNORE_TABLES --socket="$scaffolddir"/tmp/mysql.sock -u `php $SCRIPTPATH/print_config_var.php _database.user` $DATABASE
ERROR=$?
exit $ERROR