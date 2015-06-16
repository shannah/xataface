#Xataface Authentication

##Contents

1. [Introduction](#introduction)
2. [Setting up Basic Authentication](#setting-up-basic-authentication)
3. [Using MD5 Encryption For the Password](#using-md5-encryption-for-the-password)
4. [Limiting Access Based on User](#limiting-access-based-on-user)
5. [Checking Who is Logged In](#checking-who-is-logged-in)

<a name="introduction"></a>

Xataface comes with authentication ready to roll out of the box. With a couple of configuration options in the conf.ini file, you can activate the default authentication scheme which uses a table (of your choice) in the database to authenticate against. It supports password encryption, and even includes a registration form if you choose to allow registrations for your application.

In addition Xataface's authentication is pluggable, meaning you can write your own plug-ins to integrate your application with any authentication scheme you choose. Some authentication modules that already exist include:

* [Yale CAS](http://weblite.ca/svn/dataface/modules/Auth/cas/trunk/)
* [LDAP](http://weblite.ca/svn/dataface/modules/Auth/ldap/trunk/)
  * And a more advanced LDAP module with more options developed by Viharm: [Advanced LDAP](https://bitbucket.org/viharm/xatafaceldapauth/)
* Facebook
* [HTTP](http://weblite.ca/svn/dataface/modules/Auth/http/trunk/)

##Setting up Basic Authentication

1. Create a table (if you haven't already) to store your application's users. At the bare minimum, this table should have fields to store the username and password (you may call these fields whatever you like). An example table might be:

 ~~~
 CREATE TABLE `users` (
   `username` VARCHAR(32) NOT NULL,
   `password` VARCHAR(32) NOT NULL,
   PRIMARY KEY (`username`)
 )
 ~~~
2. Add the following (`_auth` section) to your `conf.ini` file:

 ~~~
 [_auth]
      users_table=users
      username_column=username
      password_column=password
 ~~~
This tells Xataface which table you are storing your user accounts in, and the names of the username and password columns.
3. Add a sample user record to the *users* table if one does not exist yet.

 ~~~
 INSERT INTO `users` (`username`,`password`) VALUES ('steve','mypass')
 ~~~
4. Load your application in your web browser and you'll notice a "login" link in the upper right that allows you to log in.

##Using MD5 Encryption for the Password

It is good practice to perform some type of encryption on passwords that you store in a database, so that they will be safe, even if your server's security is compromised. One common form of encryption it MD5. You can apply encryption to your passwords by defining the encryption property to the `password` field's section of the users table `fields.ini file`. E.g.

~~~
[password]
    encryption=md5
~~~

This tells Xataface to save data to the password field of the users table with MD5 encryption.

In order to switch to MD5 encryption with an existing Xataface installation, all un-encrypted (plain text) passwords must be first converted to MD5. There are several ways to do this. One method is to directly convert the passwords in the database with the MySQL MD5 function. This can be done from the command-line or using a tool such as phpMyAdmin. It can also be done solely within Xataface as follows, assuming a small number of users where you either know all of the passwords or are planning to change them:

1. Log in to your Xataface application as an existing user with ADMIN-level access.
2. Navigate to the users table. If you do not already have a link to it, modify your URL to include `-table=` and the name of your users table.
3. While logged in, add the `encryption=md5` parameter to the fields.ini file as described above.
4. Select each user and re-enter or reset their password. It will now be stored with MD5 encryption.
5. After completing the above step for all users, you may log out and log back in to verify the change.

##Limiting Access Based on User

Authentication and permissions are distinct issues, but they are related. It is quite common to require a user to log in to access a section of an application. Permissions can be defined in either the Application delegate class or a table's delegate class - or both.

As an example, if we want to require users to log in to access our application we could define the following `getPermissions()` method to our application delegate class:

~~~
<?php
class conf_ApplicationDelegate {
    function getPermissions(Dataface_Record $record=null){
        // $record is a Dataface_Record object
        $auth = Dataface_AuthenticationTool::getInstance();
        $user = $auth->getLoggedInUser();
        if ( $user ) return Dataface_PermissionsTool::ALL();
        else return Dataface_PermissionsTool::NO_ACCESS();
    }
}
~~~

##Checking Who Is Logged In

The `Dataface_AuthenticationTool` class handles all of the dirty work of Xataface's authentication. It provides public methods to check who is logged in and perform authentication if necessary. Anywhere inside your Xataface application you can find out who is logged in using one of the following two methods:

* `getLoggedInUser()` - Returns a Dataface_Record object representing a record from the users table.
* `getLoggedInUsername()` - Returns a string.

It is quite useful in the `getPermissions()` method of your delegate classes to find out who is logged in:

~~~
function getPermissions(Dataface_Record $record=null){
    $auth = Dataface_AuthenticationTool::getInstance();
    $user = $auth->getLoggedInUser();
    if ( $user and $user->val('username') == 'shannah' ){
        // Steve is logged in so we give him special permissions
        return Dataface_PermissionsTool::ALL();
    } else {
        // Steve is not logged in so we give only read only permissions
        return Dataface_PermissionsTool::READ_ONLY();
    }
}
~~~

##Checking Who is Logged In from a Template

All templates in Xataface have access to the `$ENV` array that contains references to lots of useful information, including the currently logged in user:

* `$ENV.user` - The user object of the currently logged in user (or null if nobody is logged in). This is a Dataface_Record object.
* `$ENV.username` - The name of the currently logged in user. A string.

For example:

~~~
<!-- 
     Print 'Hello Steve' if Steve is logged in,
     'Hello Helen' if Helen is logged in, or just 
     'Hello' if nobody is logged in. 
-->
Hello {$ENV.username}

<!-- Print some personal user info -->
{if $ENV.user}
    Phone number: {$ENV.user->val('phone')}<br/>
    Email address: {$ENV.user->val('email')}<br/>
{/if}
~~~

This example presumes that the users table has `phone` and `email` fields.
