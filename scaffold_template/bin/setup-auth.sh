#!/bin/bash
echo "Setting up auth..."
set -e
SCRIPTPATH="$( cd "$(dirname "$0")" ; pwd -P )"
TABLES_DIR=$SCRIPTPATH/../app/tables
[ -d "$TABLES_DIR" ] || mkdir "$TABLES_DIR"
echo "Checking mysql server status..."
set +e
bash $SCRIPTPATH/mysql.server.sh status
status=$?
set -e
echo "$status\n"
if [[ $status != 0 ]]; then
    bash $SCRIPTPATH/mysql.server.sh start || (echo "Failed to start mysql" && exit 1)
    function finish() {
        bash $SCRIPTPATH/mysql.server.sh stop
    }
    trap finish EXIT
fi
bash $SCRIPTPATH/php.sh $SCRIPTPATH/inc/setup-auth.php "$@"