#!/bin/sh

user=`whoami`

log_failure_msg()
  {
    echo " ERROR! $@"
  }

su_kill() {
  if test "$USER" = "$user"; then
    kill $* >/dev/null 2>&1
  else
    su - $user -s /bin/sh -c "kill $*" >/dev/null 2>&1
  fi
}

mysqld_pid_file_path=tmp/mysql.pid
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