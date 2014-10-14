#Xataface Permissions

Xataface includes a fine-grained, extensible, expressive permissions infrastructure that allows you (the developer) to define exactly who gets access to which actions in which context.

You can define permission rules for your application at 4 different levels:

1. **Application Level** By defining a `getPermissions()` method in the Application Delegate Class.
2. **Table Level** By defining a `getPermissions()` method in your table delegate classes.
3. **Record Level** By using case-by-case logic inside your `getPermissions()` method.
4. **Field Level** By defining `fieldname__permissions()` methods in your table delegate classes.

It is recommended that you define very restrictive permissions at the application level, then selectively remove restrictions at the table level as required to provide users access to only those areas that they need.

##Example Permission Configurations

In order for permissions to be meaningful, your application should probably have authentication enabled.  So assume that authentication is enabled, and your database has a `users` table defined as follows:

~~~
CREATE TABLE `users` (
   username VARCHAR(50) NOT NULL PRIMARY KEY,
   password VARCHAR(128),
   role ENUM('USER','ADMIN') DEFAULT 'USER',
   email VARCHAR(255)
)
~~~ 

And that your `conf.ini` file contains the following:

~~~
[_auth]
   users_table=users
   username_column=username
   password_column=password
   email_column=email
~~~


###Full Access to Administrator, No Access Otherwise

In your Application Delegate class (i.e. `conf/ApplicationDelegate.php`):

~~~
function getPermissions(Dataface_Record $record = null){
   $user = Dataface_AuthenticationTool::getInstance()->getLoggedInUser();
   if ( $user and $user->val('role') === 'ADMIN' ){
      return Dataface_PermissionsTool::ALL();
   } else {
      return Dataface_PermissionsTool::NO_ACCESS();
   }
}
~~~


###Regular Users have READ-ONLY access to the "products" table

In your Application Delegate class (i.e. `conf/ApplicationDelegate.php`) you disallow access to everyone except ADMIN users:

~~~
function getPermissions(Dataface_Record $record = null){
   $user = Dataface_AuthenticationTool::getInstance()->getLoggedInUser();
   if ( $user and $user->val('role') === 'ADMIN' ){
      return Dataface_PermissionsTool::ALL();
   } else {
      return Dataface_PermissionsTool::NO_ACCESS();
   }
}
~~~

Then in your *products* delegate class (i.e. `tables/products/products.php`) you provide read-only access to regular users.

~~~
function getPermissions(Dataface_Record $record = null){
   $user = Dataface_AuthenticationTool::getInstance()->getLoggedInUser();
   if ( $user and $user->val('role') === 'USER' ){
      return Dataface_PermissionsTool::ALL();
   } else {
      return null;
   }
}
~~~

**Notice** that we return null for all users except for logged in users with *role*="USER".  This means that, for other users, the default permissions (defined in the Application Delegate Class) should be used.
