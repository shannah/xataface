#!/bin/bash

set -e
ERROR=
function print_errors() {
    exit_status=$?
    if [ -z $exit_status ]
    then
        echo "Exit status $exit_status"
        echo "mysql-errors.log:\n"
        cat testapp/log/mysql-errors.log
    else
        echo "OK.  Exit status $exit_status\n"  
    fi
}
trap print_errors EXIT
php $XATAFACE/tools/create.php testapp
cd testapp/bin
bash install-module.sh calendar 1.0
bash setup-auth.sh
