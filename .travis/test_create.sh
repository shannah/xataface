#!/bin/sh
SCRIPTPATH="$( cd "$(dirname "$0")" ; pwd -P )"
PROJECT_DIR=`pwd`
cd ..
mkdir tests
cd tests
set -e
php $PROJECT_DIR/tools/create.php testapp