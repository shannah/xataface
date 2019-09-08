#!/bin/sh
SCRIPTPATH="$( cd "$(dirname "$0")" ; pwd -P )"
export XFServerRoot=`php $SCRIPTPATH/print_config_var.php XFServerRoot`
export XFServerPort=`php $SCRIPTPATH/print_config_var.php XFServerPort`
echo "Starting apache on port $XFServerPort\n"
ACMD="$1"
ARGV="$@"

ERROR=0
if [ "x$ARGV" = "x" ] ; then 
    ARGV="-h"
fi

case $ACMD in
start|stop|restart|graceful|graceful-stop)
    sh $SCRIPTPATH/mysql.server.sh $ARGV
    if [ $? -eq 0 ]
    then
        echo "Did $ARGV mysql server\n"
    else
        echo "Failed to $ARGV mysql server.\n"
        exit $?
    fi
    sh $SCRIPTPATH/apachectl.sh $ARGV
    if [ $? -eq 0 ]
    then
        echo "Did $ARGV apache server\n"
    else
        echo "Failed to $ARGV apache server.\n"
        exit $?
    fi
    ;;

*)

esac

exit $ERROR
