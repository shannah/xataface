#!/bin/sh
#
# Licensed to the Apache Software Foundation (ASF) under one or more
# contributor license agreements.  See the NOTICE file distributed with
# this work for additional information regarding copyright ownership.
# The ASF licenses this file to You under the Apache License, Version 2.0
# (the "License"); you may not use this file except in compliance with
# the License.  You may obtain a copy of the License at
#
#     http://www.apache.org/licenses/LICENSE-2.0
#
# Unless required by applicable law or agreed to in writing, software
# distributed under the License is distributed on an "AS IS" BASIS,
# WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
# See the License for the specific language governing permissions and
# limitations under the License.
#
#
# Apache control script designed to allow an easy command line interface
# to controlling Apache.  Written by Marc Slemko, 1997/08/23
# 
# The exit codes returned are:
#   XXX this doc is no longer correct now that the interesting
#   XXX functions are handled by httpd
#	0 - operation completed successfully
#	1 - 
#	2 - usage error
#	3 - httpd could not be started
#	4 - httpd could not be stopped
#	5 - httpd could not be started during a restart
#	6 - httpd could not be restarted during a restart
#	7 - httpd could not be restarted during a graceful restart
#	8 - configuration syntax error
#
# When multiple arguments are given, only the error from the _last_
# one is reported.  Run "apachectl help" for usage info
#
SCRIPTPATH="$( cd "$(dirname "$0")" ; pwd -P )"
if test -z ${XATAFACE}; then
	XATAFACE=$HOME/xataface
fi
export XFServiceScript=$XATAFACE/tools/service.php
export XFServerRoot=`php $SCRIPTPATH/print_config_var.php XFServerRoot`
export XFServerPort=`php $SCRIPTPATH/print_config_var.php XFServerPort`
export MysqlDefaultSocket="$SCRIPTPATH/../tmp/mysql.sock"
#echo "Starting apache on port $XFServerPort\n"
ACMD="$1"
ARGV="$@"
#
# |||||||||||||||||||| START CONFIGURATION SECTION  ||||||||||||||||||||
# --------------------                              --------------------
# 
# the path to your httpd binary, including options if necessary
HTTPD=`php $SCRIPTPATH/inc/find_httpd.php`
export XFApacheServerRoot=`php $SCRIPTPATH/inc/find_httpd.php ServerRoot`
if test -f "$HTTPD"; then
    FOUND=1
else
    echo "Cannot find $HTTPD"
    exit 1
fi
#
# pick up any necessary environment variables
if test -f /Applications/XAMPP/xamppfiles/bin/envvars; then
  . /Applications/XAMPP/xamppfiles/bin/envvars
fi

#
# the URL to your server's mod_status status page.  If you do not
# have one, then status and fullstatus will not work.
STATUSURL="http://localhost:$XFServerPort/server-status?auto"
#
# Set this variable to a command that increases the maximum
# number of file descriptors allowed per child process. This is
# critical for configurations that use many file descriptors,
# such as mass vhosting, or a multithreaded server.
ULIMIT_MAX_FILES=""
# --------------------                              --------------------
# ||||||||||||||||||||   END CONFIGURATION SECTION  ||||||||||||||||||||

# Set the maximum number of file descriptors allowed per child process.
if [ "x$ULIMIT_MAX_FILES" != "x" ] ; then
    $ULIMIT_MAX_FILES
fi

ERROR=0
if [ "x$ARGV" = "x" ] ; then 
    ARGV="-h"
fi

# Add this instance to Xataface's services list so that
# we can more easily monitor all of the Xataface projects
# that are running
if test -f "$XFServiceScript"; then
	php "$XFServiceScript" add "$SCRIPTPATH/.."
fi

case $ACMD in
start|stop|restart|graceful|graceful-stop)
    $HTTPD -k $ARGV -f "$SCRIPTPATH"/../etc/httpd.conf -c"PidFile  $SCRIPTPATH/../tmp/httpd.pid"
    ERROR=$?
    ;;
status)
	if [ ! -f "$SCRIPTPATH/../tmp/httpd.pid" ]; then
		echo "STOPPED"
		exit 1
	fi
	pid=$(cat "$SCRIPTPATH/../tmp/httpd.pid")
	if [ -z "$pid" ]; then
		echo "STOPPED"
		exit 1
	fi
	if [ ! `kill -0 $pid` ]; then
		echo "RUNNING"
		HTTP_STATUS=$(php $SCRIPTPATH/inc/http-response-code.php $STATUSURL)
		if [ "$HTTP_STATUS" = "200" ]; then
			$HTTPD -S -f "$SCRIPTPATH"/../etc/httpd.conf
			curl -s $STATUSURL
		fi
		exit 0
	fi
	echo "STOPPED"
	exit 1
    ;;
fullstatus)
    $LYNX $STATUSURL
    ;;
*)
    $HTTPD "$@"
    ERROR=$?
esac

exit $ERROR

