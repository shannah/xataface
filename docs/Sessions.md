#Xataface Sessions

##Contents

1. [Introduction](#intro)
2. [Session Configuration](#session-configuration)
   1. [Configuration Options](#configuration-options)
   2. [PHP Constants](#php-constants)
   3. [Delegate Class Methods](#delegate-class-methods)
   4. [Xataface API For Sessions](#xataface-api-for-sessions)
3. [Troubleshooting](#troubleshooting)

<a name="intro"></a>

Xataface uses regular PHP sessions for its sessions, but each application creates its own subdirectory inside the PHP sessions directory to store its own sessions.  It does this to avoid conflicts between the sessions of different Xataface apps on the same server.

##Session Configuration

Xataface tries to use sensible defaults for session management, but it provides a number of configuration options to override the defaults.  Most (perhaps all?) session-related configuration options should be placed inside the `_auth` section of the `conf.ini` file.  

###Configuration options

| Name | Description | Default Value |
|---|---|---|
| `session_timeout` | Number of seconds of inactivity after which the user will be logged out. Note: Arithmetic don't work in the conf.ini, use seconds. | 24 hours (i.e. 86400) |
| `cookie_path` | The [cookie path](http://en.wikipedia.org/wiki/HTTP_cookie#Domain_and_Path).  This may be a static string, or (if prefixed by `php:`) it may be a PHP expression that resolves to a string. | The app root |
| `subdir` | The name of the subdirectory within the PHP sessions dir that should be used to store sessions. | An `md5` hash of the app path |
| `session_name` | A session name.  This allows it to be distinguished from other PHP apps that may be using the same session path. | `null` |

####Example Config Options

*conf.ini file*:

~~~
;.. etc...

[_auth]
   users_table="users"
   username_column="username"
   password_column="password"
   
   ;; Session handling stuff starts here (must be in [_auth] section)
   session_timeout="500"   ; 500 seconds
   cookie_path="/"         ; Cookies accessible to root of domain
   subdir="myapp"          ; Session directory at /var/lib/php/sessions/myapp
   session_name="myapp"    ; Session name is myapp
~~~

###PHP Constants

You can also set the following PHP constants before the initialization of Xataface occurs to affect the operation of sessions:

| Name | Description |
|---|---|
| `XATAFACE_NO_SESSION` | If set to a non-falsey value, this will prevent Xataface from starting sessions at all.  This may be useful if you are running a public-facing site and don't want to incur the overhead of sessions. |

####Example Use of Constants

*In index.php:*

~~~
require_once 'xataface/public-api.php';

// Disable sessions
define('XATAFACE_NO_SESSION', 1);

// Display app
df_init(__FILE__, 'xataface')->display();
~~~


###Delegate Class Methods

The following methods can be implemented in the Application delegate class to override some aspects of session handling.

| Name | Description |
|---|---|
| `startSession` | Overrides the session starting logic completely.  If you implement this method, you will be defining your own session handling logic (i.e. all `conf.ini` options will be ignored.  This method should at least call `session_start()`. |

####Example Delegate Class Implementation

*In `conf/ApplicationDelegate.php`:*

~~~

<?php
class conf_ApplicationDelegate {
   function startSession($conf=array()){
      session_name($conf['session_name']);
      session_start();
   }
}

~~~

###Xataface API for Sessions

The Xataface API, particularly the `Dataface_Application` class includes a few useful methods for interacting with sessions as well.

| Name | Description |
|---|---|
| `enableSessions` | Enables sessions so that they will be started at the beginning of each request. |
| `disableSessions` | Disables sessions so that they won't be started at the beginning of each request. |
| `sessionEnabled` | Returns a boolean value indicating whether sessions are enabled. |
| `startSession` | Starts the session |


####Enabling/Disabling Sessions

Xataface tries to be conservative about when it starts a session.  When a user initially accesses a Xataface app, no session is started.  If they try to log in, then the session will be started, and it will automatically be started for that user in every request thereafter.  The `enableSessions`, `disableSessions`, and `sessionEnabled` methods are a mechanism to interact with this functionality.  Initially `sessionEnabled` will be false.  When the user tries to log in, Xataface will call `enableSessions()` to activate sessions.  Thereafter, `sessionEnabled()` will return `true` until `disableSessions()` is called.

##Troubleshooting

###Permission Denied Errors

E.g. 

~~~
Warning: session_start() [function.session-start]: \
open(/var/lib/php/session/66dfb80f5fe38bb49749552cd5432a36/sess_outsjoh0l0kvubgr64j95hdug1, O_RDWR) failed: \
Permission denied (13) in /var/www/webfiles/[site].com/web/[Xataface folder]/xataface/Dataface/Application.php on line 1653
~~~

This error happens if Xataface is unable to create its subdirectory for sessions inside the standard PHP session directory - or if it creates the directory but doesn't have read/write permissions to that directory.  If you have SSH access to the server, you should begin by looking at the session directory permissions.  In the example message above, the main PHP session directory is at `/var/lib/php/session` and the Xataface session directory is `66dfb80f5fe38bb49749552cd5432a36` (inside that directory).  Make sure that both of these directories are readable and writable by the web server process.

You might want to experiment with the `subdir` directive (in the `conf.ini` file) to specify a different directory for storing sessions.  Also note that if you are running PHP in safe mode, it may cause errors like this.  Currently Xataface cannot be run in safe mode.
