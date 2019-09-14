#!/bin/sh

SCRIPTPATH="$( cd "$(dirname "$0")" ; pwd -P )"
PROJECT_DIR=`pwd`
cd ..
mkdir tests
cd tests
set -e

function print_errors() {
    echo "mysql-errors.log:\n"
    cat $PROJECT_DIR/../tests/testapp/log/mysql-errors.log
}
trap print_errors EXIT
php $PROJECT_DIR/tools/create.php testapp