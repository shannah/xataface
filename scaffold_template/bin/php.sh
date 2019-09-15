#!/bin/bash
SCRIPTPATH="$( cd "$(dirname "$0")" ; pwd -P )"
MysqlDefaultSocket="$SCRIPTPATH/../tmp/mysql.sock"
status=`bash $SCRIPTPATH/mysql.server.sh status`
if [[ $status == *"ERROR!"* ]]; then
    bash $SCRIPTPATH/mysql.server.sh start > /dev/null || (echo "Failed to start mysql" && exit 1)
    function finish() {
        bash $SCRIPTPATH/mysql.server.sh stop > /dev/null
    }
    trap finish EXIT
fi
ERROR=0
php -d mysqli.default_socket="${MysqlDefaultSocket}" "$@"
ERROR=$?
exit $ERROR