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
      return Dataface_PermissionsTool::READ_ONLY();
   } else {
      return null;
   }
}
~~~

**Notice** that we return null for all users except for logged in users with *role*="USER".  This means that, for other users, the default permissions (defined in the Application Delegate Class) should be used.

###Regular Users have EDIT access to their Own Product Records

In order to provide this type of functionality, we will assume that we have a mechanism to determine whether a particular user is the "owner" of a particular product record.  For this example, we will use the following mechanism:

1. The `products` table has an `owner` field with the username of the user that owns it.
2. We define a `beforeInsert` trigger in the `products` table to set the `owner` field to the currently logged-in user.

I.e. Assume the `products` table is defined as follows:

~~~
CREATE TABLE products (
    product_id INT(11) NOT NULL auto_increment PRIMARY KEY,
    product_name VARCHAR(128),
    owner VARCHAR(50)
~~~

Also we define the `beforeInsert` trigger in the `products` delegate class (i.e. `tables/products/products.php`:

~~~
function beforeInsert(Dataface_Record $record){
   $user = Dataface_AuthenticationTool::getInstance()->getLoggedInUser();
   if ( $user and !$record->val('owner') ){
       $record->setValue('owner', $user->val('username'));
   }
}
~~~

Now we can define our permissions in the `products` table delegate class (i.e. `tables/products/products.php`:

~~~
function getPermissions(Dataface_Record $record = null){
   $user = Dataface_AuthenticationTool::getInstance()->getLoggedInUser();
   if ( $user and $user->val('role') === 'USER' ){
      if ( $record and $user->val('username') === $record->val('owner') ){
          return Dataface_PermissionsTool::getRolePermissions('EDIT');
      } else {
          return Dataface_PermissionsTool::READ_ONLY();
      }
   } else {
       return null;
   }
}
~~~

There are two small wrinkles in this implementation, however:

1. Regular users only have READ-ONLY permission in the `products` table so they will never be able to create products that they own.
2. Product owners have full edit privileges to all fields of the `products` table so they could theoretically change the owner.  We may want to prevent this.

In order to resolve the first issue, we'll add the `new` permission to the regular user permissions.  For the 2nd issue, we'll add a `owner__permissions` method in the `products` table delegate class to disallow editing by owners. 

So the changed permission methods in the `products` delegate class is:

~~~
function getPermissions(Dataface_Record $record = null){
   $user = Dataface_AuthenticationTool::getInstance()->getLoggedInUser();
   if ( $user and $user->val('role') === 'USER' ){
      if ( $record and $user->val('username') === $record->val('owner') ){
          return Dataface_PermissionsTool::getRolePermissions('EDIT');
      } else {
          $perms = Dataface_PermissionsTool::READ_ONLY();
          $perms['new'] = 1;
          return $perms;
      }
   } else {
       return null;
   }
}

function owner__permissions(Dataface_Record $record = null){
   $user = Dataface_AuthenticationTool::getInstance()->getLoggedInUser();
   if ( $user and $user->val('role') === 'USER' 
           and $record and $user->val('username') === $record->val('owner')
           ){
      return array('edit' => 0);
   } else {
      return null;
   }
}
~~~


In these examples we were introduced to 3 new concepts:

1. The `Dataface_PermissionsTool::getRolePermissions()` method.
2. That the `Dataface_PermissionsTool` methods simply return an associative array assigning `0` or `1` to each permission.
3. The `fieldname__permissions()` method.

