#!/bin/sh
# Copyright Abandoned 1996 TCX DataKonsult AB & Monty Program KB & Detron HB
# This file is public domain and comes with NO WARRANTY of any kind

# MariaDB daemon start/stop script.

# Usually this is put in /etc/init.d (at least on machines SYSV R4 based
# systems) and linked to /etc/rc3.d/S99mysql and /etc/rc0.d/K01mysql.
# When this is done the mysql server will be started when the machine is
# started and shut down when the systems goes down.

# Comments to support chkconfig on RedHat Linux
# chkconfig: 2345 64 36
# description: A very fast and reliable SQL database engine.

# Comments to support LSB init script conventions
### BEGIN INIT INFO
# Provides: mysql
# Required-Start: $local_fs $network $remote_fs
# Should-Start: ypbind nscd ldap ntpd xntpd
# Required-Stop: $local_fs $network $remote_fs
# Default-Start:  2 3 4 5
# Default-Stop: 0 1 6
# Short-Description: start and stop MariaDB
# Description: MariaDB is a very fast and reliable SQL database engine.
### END INIT INFO

# have to do one of the following things for this script to work:
#
# - Run this script from within the MariaDB installation directory
# - Create a /etc/my.cnf file with the following information:
#   [mysqld]
#   basedir=<path-to-mysql-installation-directory>
# - Add the above to any other configuration file (for example ~/.my.ini)
#   and copy my_print_defaults to /usr/bin
# - Add the path to the mysql-installation-directory to the basedir variable
#   below.
#
# If you want to affect other MariaDB variables, you should make your changes
# in the /etc/my.cnf, ~/.my.cnf or other MariaDB configuration files.
SCRIPTPATH="$( cd "$(dirname "$0")" ; pwd -P )"

if test -z ${XATAFACE}; then
	XATAFACE=$HOME/xataface
fi
export XFServiceScript=$XATAFACE/tools/service.php
# Add this instance to Xataface's services list so that
# we can more easily monitor all of the Xataface projects
# that are running
if test -f "$XFServiceScript"; then
	php "$XFServiceScript" add "$SCRIPTPATH/.."
fi

# If you change base dir, you must also change datadir. These may get
# overwritten by settings in the MariaDB configuration files.
scaffolddir="$SCRIPTPATH"/..
basedir=
datadir="$scaffolddir"/data

# Default value, in seconds, afterwhich the script should timeout waiting
# for server start.
# Value here is overridden by value in my.cnf.
# 0 means don't wait at all
# Negative numbers mean to wait indefinitely
service_startup_timeout=30


lock_file_path="$scaffolddir/tmp/mysql"

# The following variables are only set for letting mysql.server find things.

# Set some defaults
mysqld_pid_file_path=
if test -f "/Applications/XAMPP/xamppfiles"
then
  basedir=/Applications/XAMPP/xamppfiles
  bindir=/Applications/XAMPP/xamppfiles/bin
  #if test -z "$datadir"
  #then
    #datadir=/Applications/XAMPP/xamppfiles/var/mysql
  #fi
  sbindir=/Applications/XAMPP/xamppfiles/sbin
  libexecdir=/Applications/XAMPP/xamppfiles/sbin
else
    MYSQL_PATH=`which mysql`
    if [ -z "$MYSQL_PATH" ]
    then
        echo "Cannot find mysql"
        exit 1
    fi
    bindir=$(dirname "$MYSQL_PATH")
    basedir=$(dirname "$bindir")
    sbindir="$basedir/sbin"
  if test -f "$basedir/bin/mysqld"
  then
    libexecdir="$basedir/bin"
  else
    libexecdir="$basedir/libexec"
  fi
fi

# datadir_set is used to determine if datadir was set (and so should be
# *not* set inside of the --basedir= handler.)
datadir_set=1


log_success_msg()
{
echo " SUCCESS! $@"
}
log_failure_msg()
{
echo " ERROR! $@"
}


PATH="/sbin:/usr/sbin:/bin:/usr/bin:$basedir/bin"
export PATH

mode=$1    # start or stop

[ $# -ge 1 ] && shift

case `echo "testing\c"`,`echo -n testing` in
    *c*,-n*) echo_n=   echo_c=     ;;
    *c*,*)   echo_n=-n echo_c=     ;;
    *)       echo_n=   echo_c='\c' ;;
esac


user=$USER

su_kill() {
  if test "$USER" = "$user"; then
    kill $* >/dev/null 2>&1
  else
    su - $user -s /bin/sh -c "kill $*" >/dev/null 2>&1
  fi
}

#
# Read defaults file from 'basedir'.   If there is no defaults file there
# check if it's in the old (depricated) place (datadir) and read it from there
#

extra_args=""
if test -r "$scaffolddir/my.cnf"
then
  extra_args="-e $scaffolddir/my.cnf"
else
  if test -r "$datadir/my.cnf"
  then
    extra_args="-e $datadir/my.cnf"
  fi
fi


# wait for the pid file to disappear
wait_for_gone () {
  pid="$1"           # process ID of the program operating on the pid-file
  pid_file_path="$2" # path to the PID file.

  i=0
  crash_protection="by checking again"

  while test $i -ne $service_startup_timeout ; do

    if su_kill -0 "$pid" ; then
      :  # the server still runs
    else
      if test ! -s "$pid_file_path"; then
        # no server process and no pid-file? great, we're done!
        log_success_msg
        return 0
      fi

      # pid-file exists, the server process doesn't.
      # it must've crashed, and mysqld_safe will restart it
      if test -n "$crash_protection"; then
        crash_protection=""
        sleep 5
        continue  # Check again.
      fi

      # Cannot help it
      log_failure_msg "The server quit without updating PID file ($pid_file_path)."
      return 1  # not waiting any more.
    fi

    echo $echo_n ".$echo_c"
    i=`expr $i + 1`
    sleep 1

  done

  log_failure_msg
  return 1
}

wait_for_ready () {

  i=0
  while test $i -ne $service_startup_timeout ; do
    echo "Waiting for ready"
    if $bindir/mysqladmin --socket="$scaffolddir/tmp/mysql.sock" ping >/dev/null 2>&1; then
        echo "We are ready"
      log_success_msg
      return 0
    elif kill -0 $! ; then
        echo "kill -0 says wait some more..."
      :  # mysqld_safe is still running
    else
      # mysqld_safe is no longer running, abort the wait loop
      break
    fi

    echo $echo_n ".$echo_c"
    i=`expr $i + 1`
    sleep 1

  done

  log_failure_msg
  cat "$scaffolddir/log/mysql-errors.log"
  return 1
}
#
# Set pid file if not given
#
mysqld_pid_file_path=$datadir/`hostname`.pid

# source other config files
[ -f /etc/default/mysql ] && . /etc/default/mysql
[ -f /etc/sysconfig/mysql ] && . /etc/sysconfig/mysql
[ -f /etc/conf.d/mysql ] && . /etc/conf.d/mysql

case "$mode" in
  'start')
    # Start daemon

    # Safeguard (relative paths, core dumps..)
    cd $basedir

    echo $echo_n "Starting MariaDB"
    if test -x $bindir/mysqld_safe
    then
      # Give extra arguments to mysqld with the my.cnf file. This script
      # may be overwritten at next upgrade.
      echo "PID FILE $mysqld_pid_file_path\n"
      if test -f "$scaffolddir/data/index.txt"; then
        rm "$scaffolddir/data/index.txt"
        $bindir/mysqld_safe --initialize --tmpdir="$scaffolddir/tmp" --datadir="$scaffolddir/data" --innodb_data_home_dir="$scaffolddir/data" --innodb_log_group_home_dir="$scaffolddir/data" --log-error="$scaffolddir/log/mysql-errors.log" --user=`whoami` --pid-file="$mysqld_pid_file_path"
      fi
      $bindir/mysqld_safe --skip-grant-tables --skip-networking --tmpdir="$scaffolddir/tmp" --datadir="$scaffolddir/data" --innodb_data_home_dir="$scaffolddir/data" --innodb_log_group_home_dir="$scaffolddir/data" --log-error="$scaffolddir/log/mysql-errors.log" --socket="$scaffolddir/tmp/mysql.sock" --user=`whoami` --pid-file="$mysqld_pid_file_path" "$@" >/dev/null &
      #$bindir/mysqld_safe --datadir="$datadir" --pid-file="$mysqld_pid_file_path" "$@" &
      wait_for_ready; return_value=$?
      echo "About to touch lock file $lock_file_path"
      touch "$lock_file_path"
      echo "Exiting with return value $return_value"
      exit $return_value
    else
      log_failure_msg "Couldn't find MariaDB server ($bindir/mysqld_safe)"
    fi
    ;;

  'stop')
    # Stop daemon. We use a signal here to avoid having to know the
    # root password.
    if test -s "$mysqld_pid_file_path"
    then
      mysqld_pid=`cat "$mysqld_pid_file_path"`
      if su_kill -0 $mysqld_pid ; then
        echo $echo_n "Shutting down MariaDB"
        su_kill $mysqld_pid
        # mysqld should remove the pid file when it exits, so wait for it.
        wait_for_gone $mysqld_pid "$mysqld_pid_file_path"; return_value=$?
      else
        log_failure_msg "MariaDB server process #$mysqld_pid is not running!"
        rm "$mysqld_pid_file_path"
      fi

      # Delete lock for RedHat / SuSE
      if test -f "$lock_file_path"
      then
        rm -f "$lock_file_path"
      fi
      exit $return_value
    else
      log_failure_msg "MariaDB server PID file could not be found!"
    fi
    ;;

  'restart')
    # Stop the service and regardless of whether it was
    # running or not, start it again.
    if $0 stop  "$@"; then
      if ! $0 start "$@"; then
        log_failure_msg "Failed to restart server."
        exit 1
      fi
    else
      log_failure_msg "Failed to stop running server, so refusing to try to start."
      exit 1
    fi
    ;;

  'reload'|'force-reload')
    if test -s "$mysqld_pid_file_path" ; then
      read mysqld_pid <  "$mysqld_pid_file_path"
      su_kill -HUP $mysqld_pid && log_success_msg "Reloading service MariaDB"
      touch "$mysqld_pid_file_path"
    else
      log_failure_msg "MariaDB PID file could not be found!"
      exit 1
    fi
    ;;
  'status')
    # First, check to see if pid file exists
    if test -s "$mysqld_pid_file_path" ; then
      read mysqld_pid < "$mysqld_pid_file_path"
      if su_kill -0 $mysqld_pid ; then
        log_success_msg "MariaDB running ($mysqld_pid)"
        exit 0
      else
        log_failure_msg "MariaDB is not running, but PID file exists"
        exit 1
      fi
    else
      # Try to find appropriate mysqld process
      mysqld_pid=`pgrep $libexecdir/mysqld`

      # test if multiple pids exist
      pid_count=`echo $mysqld_pid | wc -w`
      if test $pid_count -gt 1 ; then
        log_failure_msg "Multiple MariaDB running but PID file could not be found ($mysqld_pid)"
        exit 5
      elif test -z $mysqld_pid ; then
        if test -f "$lock_file_path" ; then
          log_failure_msg "MariaDB is not running, but lock file ($lock_file_path) exists"
          exit 2
        fi
        log_failure_msg "MariaDB is not running"
        exit 3
      else
        log_failure_msg "MariaDB is running but PID file could not be found"
        exit 4
      fi
    fi
    ;;
  'configtest')
    # Safeguard (relative paths, core dumps..)
    cd $basedir
    echo $echo_n "Testing MariaDB configuration syntax"
    daemon=$bindir/mysqld
    if test -x $libexecdir/mysqld
    then
      daemon=$libexecdir/mysqld
    elif test -x $sbindir/mysqld
    then
      daemon=$sbindir/mysqld
    elif test -x `which mysqld`
    then
      daemon=`which mysqld`
    else
      log_failure_msg "Unable to locate the mysqld binary!"
      exit 1
    fi
    help_out=`$daemon --help 2>&1`; r=$?
    if test "$r" != 0 ; then
      log_failure_msg "$help_out"
      log_failure_msg "There are syntax errors in the server configuration. Please fix them!"
    else
      log_success_msg "Syntax OK"
    fi
    exit $r
    ;;
  'bootstrap')
      if test "$_use_systemctl" == 1 ; then
        log_failure_msg "Please use galera_new_cluster to start the mariadb service with --wsrep-new-cluster"
        exit 1
      fi
      # Bootstrap the cluster, start the first node
      # that initiate the cluster
      echo $echo_n "Bootstrapping the cluster.. "
      $0 start $other_args --wsrep-new-cluster
      exit $?
      ;;
  *)
      # usage
      basename=`basename "$0"`
      echo "Usage: $basename  {start|stop|restart|reload|force-reload|status|configtest|bootstrap}  [ MariaDB server options ]"
      exit 1
    ;;
esac

exit 0
