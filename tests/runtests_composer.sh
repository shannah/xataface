#!/bin/bash
set -e
SCRIPTPATH="$( cd "$(dirname "$0")" ; pwd -P )"
export XATAFACE=$(cd "$SCRIPTPATH/.."; pwd -P)
echo "Using XATAFACE=$XATAFACE"

TIMESTAMP=$(date +"%s")
TEMP=/tmp/
TEST_OUTPUT_DIR="${TEMP}xf_test_out"
echo "Creating test output in $TEST_OUTPUT_DIR"
if [ -d "$TEST_OUTPUT_DIR" ]; then
    rm -rf "$TEST_OUTPUT_DIR"
fi
mkdir "$TEST_OUTPUT_DIR"
cd "$TEST_OUTPUT_DIR"
bash "$XATAFACE/tests/runtests.sh"