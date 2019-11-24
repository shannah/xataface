#!/bin/bash
set -e
TESTS=$XATAFACE/tests
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
[ -d testapp/tables ] || mkdir testapp/app/tables
cp -r $TESTS/tables/* testapp/app/tables/
cp -r $TESTS/lib/* testapp/app/
cp -r $TESTS/tests/* testapp/app/
cd testapp/app
#ls .
bash ../bin/php.sh runTests.php

