#!/bin/bash
SCRIPTPATH="$( cd "$(dirname "$0")" ; pwd -P )"
TABLES_DIR=$SCRIPTPATH/../app/tables
[ -d "$TABLES_DIR" ] || mkdir "$TABLES_DIR"

status=`bash $SCRIPTPATH/mysql.server.sh status`
if [[ $status == *"ERROR!"* ]]; then
    sh $SCRIPTPATH/mysql.server.sh start || (echo "Failed to start mysql" && exit 1)
    function finish() {
        sh $SCRIPTPATH/mysql.server.sh stop
    }
    trap finish EXIT
fi
bash $SCRIPTPATH/php.sh $SCRIPTPATH/inc/install-module.php "$@"