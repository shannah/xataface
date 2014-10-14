#Xataface Permissions

##Contents

1. [Intro](#intro)
2. [Example Permission Configurations](#examples)
   1. [Full Access to Admin - No access to Others](#full-admin-no-access-other)
   2. [Regular Users READ ONLY access to Table](#read-only-to-table)
   3. [Regular Users have EDIT access to own Records](#edit-access-to-own)
3. [Using `Dataface_PermissionsTool` to get Permissions](#permissions-tool)
   1. [Permission Sets / `getRolePermissions()`](#get-role-permissions)
4. [Xataface Core Permissions](#xataface-core-permissions)
5. [Xataface Core Permission-Sets](#xataface-core-permission-sets)
6. [Custom Permission Sets](#custom-permission-sets)
   1. [The permissions.ini file](#permissions-ini-file)
   2. [Permission-Set Inheritance](#permission-set-inheritance)
7. [Table-level Permissions](#table-level-permissions)
8. [Field-level Permissions](#field-level-permissions)
   1. [Default field permissions](#default-field-permissions)
<a name="intro"></a>

Xataface includes a fine-grained, extensible, expressive permissions infrastructure that allows you (the developer) to define exactly who gets access to which actions in which context.

You can define permission rules for your application at 4 different levels:

1. **Application Level** By defining a `getPermissions()` method in the Application Delegate Class.
2. **Table Level** By defining a `getPermissions()` method in your table delegate classes.
3. **Record Level** By using case-by-case logic inside your `getPermissions()` method.
4. **Field Level** By defining `fieldname__permissions()` methods in your table delegate classes.

It is recommended that you define very restrictive permissions at the application level, then selectively remove restrictions at the table level as required to provide users access to only those areas that they need.

<a name="examples"></a>

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

<a name="full-admin-no-access-other"></a>

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

<a name="read-only-to-table"></a>

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


<a name="edit-access-to-own"></a>

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

<a name="permissions-tool"></a>

##Using `Dataface_PermissionsTool` To Get Permissions

In the above examples, we have used the `Dataface_PermissionsTool` class to return the permissions that should be granted to a user.  We have seen, the following methods so far:

* `DatafacePermissionsTool::ALL()` : Returns *ALL* permissions that have been defined for the application.
* `DatafacePermissionsTool::NO_ACCESS()` : Returns an array mask that includes all of the defined permissions set to zero.  I.e. it disables ALL permissions.
* `Dataface_PermissionsTool::READ_ONLY()` : Returns all permissions related to reading data, but does not include permissions related to editing, adding, or deleting data.
* `Dataface_PermissionsTool::getRolePermissions()` : Returns all of the permissions from a specified named group of permissions.  

All of these methods essentially just return associative arrays mapping permission names to boolean (more correctly *zero-one*) values. As an exercise, you may want to try a `print_r()` on the results of these methods just to see what they look like.  I.e.:

~~~
print_r(Dataface_PermissionsTool::ALL());
~~~

would result in something like:

~~~
Array
(
    [view] => 1
    [link] => 1
    [view in rss] => 1
    [list] => 1
    [calendar] => 1
    [edit] => 1
    [new] => 1
    [select_rows] => 1
    [post] => 1
    [copy] => 1
    [update_set] => 1
    [update_selected] => 1
    [add new related record] => 1
    [add existing related record] => 1
    [delete] => 1
    [delete selected] => 1
    [delete found] => 1
    [show all] => 1
    [remove related record] => 1
    [delete related record] => 1
    [view related records] => 1
    [view related records override] => 1
    [related records feed] => 1
    [update related records] => 1
    [find related records] => 1
    [edit related records] => 1
    [link related records] => 1
    [find] => 1
    [import] => 1
    [export_csv] => 1
    [export_xml] => 1
    [export_json] => 1
    [translate] => 1
    [history] => 1
    [edit_history] => 1
    [navigate] => 1
    [reorder_related_records] => 1
    [ajax_save] => 1
    [ajax_load] => 1
    [ajax_form] => 1
    [find_list] => 1
    [find_multi_table] => 1
    [register] => 1
    [rss] => 1
    [xml_view] => 1
    [view xml] => 1
    [manage_output_cache] => 1
    [clear views] => 1
    [manage_migrate] => 1
    [manage] => 1
    [manage_build_index] => 1
    [install] => 1
    [expandable] => 1
    [show hide columns] => 1
    [view schema] => 1
    [add_feedburner_feed] => 1
    [subscribed] => 1
)
~~~

and 

~~~
print_r(Dataface_PermissionsTool::READ_ONLY());
~~~

results in:

~~~
Array
(
    [view in rss] => 1
    [view] => 1
    [link] => 1
    [list] => 1
    [calendar] => 1
    [view xml] => 0
    [show all] => 1
    [find] => 1
    [navigate] => 1
    [ajax_load] => 1
    [find_list] => 1
    [find_multi_table] => 1
    [rss] => 1
    [export_csv] => 0
    [export_xml] => 0
    [export_json] => 1
    [view related records] => 1
    [related records feed] => 1
    [expandable] => 1
    [find related records] => 1
    [link related records] => 1
    [show hide columns] => 1
    [add_feedburner_feed] => 0
    [subscribed] => 1
)
~~~

and

~~~
print_r(Dataface_PermissionsTool::NO_ACCESS());
~~~

results in

~~~

Array
(
    [view] => 0
    [link] => 0
    [view in rss] => 0
    [list] => 0
    [calendar] => 0
    [edit] => 0
    [new] => 0
    [select_rows] => 0
    [post] => 0
    [copy] => 0
    [update_set] => 0
    [update_selected] => 0
    [add new related record] => 0
    [add existing related record] => 0
    [delete] => 0
    [delete selected] => 0
    [delete found] => 0
    [show all] => 0
    [remove related record] => 0
    [delete related record] => 0
    [view related records] => 0
    [view related records override] => 0
    [related records feed] => 0
    [update related records] => 0
    [find related records] => 0
    [edit related records] => 0
    [link related records] => 0
    [find] => 0
    [import] => 0
    [export_csv] => 0
    [export_xml] => 0
    [export_json] => 0
    [translate] => 0
    [history] => 0
    [edit_history] => 0
    [navigate] => 0
    [reorder_related_records] => 0
    [ajax_save] => 0
    [ajax_load] => 0
    [ajax_form] => 0
    [find_list] => 0
    [find_multi_table] => 0
    [register] => 0
    [rss] => 0
    [xml_view] => 0
    [view xml] => 0
    [manage_output_cache] => 0
    [clear views] => 0
    [manage_migrate] => 0
    [manage] => 0
    [manage_build_index] => 0
    [install] => 0
    [expandable] => 0
    [show hide columns] => 0
    [view schema] => 0
    [add_feedburner_feed] => 0
    [subscribed] => 0
)
~~~

These methods are just returning an associative array that indicates whether permissions are granted or disallowed in a particular context.  All of these permissions are defined in the [permissions.ini file](../permissions.ini).  In addition, modules can define their own permissions in their own `permissions.ini files`, and so can applications.  

<a name="get-role-permissions"></a>

###Permission Sets / getRolePermissions()

If you look inside the [permissions.ini file](../permissions.ini), you'll notice that all of the individual permission names are defined at the beginning of the file as name/description pairs.  But the end of the file consists of named sections whose properties assign `0`-`1` values to the individual permissions.  These sections are permission sets (sometimes called *roles*).  For example, this section of the permissions.ini file defines the "READ ONLY" permission set, which is actually used by the `Dataface_PermissionsTool::READ_ONLY()` method as a source for its permission set:

~~~
[READ ONLY]
	view in rss=1
	view = 1
	link = 1
	list = 1
	calendar = 1
	view xml = 1
	show all = 1
	find = 1
	navigate = 1
	ajax_load = 1
	find_list = 1
	find_multi_table = 1
	rss = 1
	export_csv = 1
	export_xml = 1
	export_json = 1
	view related records=1
	related records feed=1
	expandable=1
	find related records=1
	link related records=1
	show hide columns = 1
~~~

This section explicitly sets which permissions are granted as part of the `READ ONLY` permission set.  We can obtain this permission set from code using the `Dataface_PermissionsTool::getRolePermissions()` method:

~~~
return Dataface_PermissionsTool::getRolePermissions('READ ONLY');
~~~

##Xataface Core Permissions

Xataface defines the following core permissions:

| Permission Name | Description | Included in  | Excluded in |
|---|---|---|---|
| `view` | View record contents | `READ BASIC`, `READ ONLY` |  |
| `link` | Link to given record. | `READ BASIC`, `READ ONLY` |  |
| `view in rss` | View record as part of RSS feed | `READ ONLY` |  |
| `list` | Access to the *list* view | `READ BASIC`, `READ ONLY` | |
| `calendar` | Access to the *calendar* action | `READ BASIC`, `READ ONLY` | |
| `edit` | Edit access to a record or field. | `EDIT BASIC`, `EDIT` |  |
| `new` | Access to create new record, or edit field on new record form. | `EDIT BASIC`, `EDIT` | `OWNER` |
| `select_rows` | Select rows in the list view | `EDIT BASIC`, `EDIT` | |
| `copy` | Copy a record | `EDIT BASIC`, `EDIT` | |
| `update_set` | Access to the update result set action | `EDIT BASIC`, `EDIT` | |
| `update_selected` | Access to the update selected records action | `EDIT BASIC`, `EDIT` | |
| `add new related record` | Add a new related record to a relationship | `EDIT`, `EDIT BASIC`, `USER` |  |
| `add existing related record` | Add an existing record to a relationship | `EDIT`, `EDIT BASIC` |  |
| `delete` | Delete a record | `DELETE`, `DELETE BASIC` | |
| `delete selected` | Delete selected records in list view | `DELETE`, `DELETE BASIC` | |
| `delete found` | Delete the full found set. | `DELETE`, `DELETE BASIC` | |
| `show all` | Show all records in the found set. | `READ ONLY`, `READ BASIC` | |
| `remove related record` | Remove a record from a relationship. | `EDIT BASIC`, `EDIT`| |
| `delete related record` | Remove a record from a realtionship, and delete the source record. |  |  |
| `view related records` | View the records of a relationship | `READ BASIC`, `READ ONLY` | |
| `view related records override` | An override permission for related records to allow viewing of a record when through the lens of a relationship even if the record itself does not allow the `view` permission. | |
| `related records feed` | Access to the RSS feed for a relationship. | `READ ONLY` | |
| `update related records` | Access to the update related records action (update multiple at once) | `EDIT BASIC`, `EDIT` | |
| `find related records` | Access to find related records | `READ BASIC`, `READ ONLY` | |
| `edit related records` | Access to edit related records.  Overrides permissions of the source record. | `EDIT BASIC`, `EDIT` | |
| `link related records` | Permission to click link on records in a relationship. | `READ BASIC`, `READ ONLY` |  |
| `find` | Permission to perform the find action. | `READ BASIC`, `READ ONLY` | |
| `import` | Permission to import records. | `EDIT BASIC`, `EDIT` | |
| `export_csv` | Access to *Export CSV* action | `READ ONLY` |  |
| `export_xml` | Access to *Export XML* action | `READ ONLY` |  |
| `export_json` | Access to *Export JSON* action | `READ ONLY` | |
| `translate` | Access to translation form for records | `EDIT`, `EDIT BASIC` | |
| `history` | Access to the history for a record | `EDIT`, `EDIT BASIC` | |
| `edit_history` | Edit history information for a record | `EDIT`, `EDIT BASIC` | |
| `ajax_save` | Permission to save records using the ajax actions. | `EDIT`, `EDIT BASIC` | |
| `ajax_load` | Load records via ajax actions. | `READ ONLY` | |
| `ajax_form` | Access to the edit form via AJAX | `EDIT`, `EDIT BASIC` | |
| `find_list` | Search the current table | `READ ONLY`, `READ BASIC` | |
| `find_multi_table` | Perform multi-table search | `READ ONLY`, `READ BASIC` | |
| `register` | Allowed to register for an account (i.e. access the *register* action. | `NO ACCESS` | |
| `rss` | Access to RSS feeds. | `READ ONLY` | |
| `view xml` | Allows a record to be viewed as part of an XML feed. | `READ ONLY` | |
| `manage_output_cache` | Permission to manage the output cache | `MANAGER` | |
| `manage` | Permission to access control panel | `MANAGER` | |
| `manage_build_index` | Permission to rebuild the search index for the full-text search feature. | `MANAGER` | |
| `install` | Permission to install and update applications. | `MANAGER` | |
| `expandable` | Whether a record can be expanded in the left nav menu. | `READ BASIC`, `READ ONLY` | |
| `clear views` | Permission to clear autogenerated views | `MANAGER` | |
| `show hide columns` | Permission to modify which columns are shown (for oneself) in the result list | `READ ONLY`, `READ BASIC` | |

##Xataface Core Permission-Sets

Xataface provides the following core permission sets:

###`NO ACCESS`

Defines permissions granted to users with no access. Note that this permission set is different than the set returned by `Dataface_PermissionsTool::NO_ACCESS()` which returns a full set of all permissions with *zero* values.  This set simply grants one permission, and doesn't explicitly deny any permissions.

~~~
register=1
~~~

###`READ BASIC`

Defines permissions pertaining to viewing, but not modifying data.  This is more restricted than `READ ONLY` as it doesn't provide access to the data in a structured form, like CSV, XML, RSS, JSON, etc...

~~~
view = 1
link = 1
list = 1
calendar = 1
show all =1
find = 1
navigate = 1
find_list =1
find_multiple_table = 1
view related records = 1
find related records = 1
link related records = 1
expandable = 1
show hide columns = 1
~~~


###`READ ONLY`

Defines permissions pertaining to viewing, but not modifying data.

~~~
view in rss=1
view = 1
link = 1
list = 1
calendar = 1
view xml = 1
show all = 1
find = 1
navigate = 1
ajax_load = 1
find_list = 1
find_multi_table = 1
rss = 1
export_csv = 1
export_xml = 1
export_json = 1
view related records=1
related records feed=1
expandable=1
find related records=1
link related records=1
show hide columns = 1
~~~

###`EDIT BASIC`

Defines all of the permissions in `READ BASIC`, but adds permissions pertaining to modifying (but not necessarily deleting) data.

~~~
edit = 1
add new related record = 1
add existing related record = 1
add new record = 1
remove related record = 1
reorder_related_records = 1
import = 1
translate = 1
new = 1
ajax_save = 1
ajax_form = 1
history = 1
edit_history = 1
copy = 1
update_set = 1
update_selected=1
select_rows = 1
update related records = 1
edit related records = 1
~~~

###`EDIT`

Defines all of the permissions in `READ ONLY`, but adds permissions pertaining to modifying (but not necessarily deleting) data.

~~~
edit = 1
add new related record = 1
add existing related record = 1
add new record = 1
remove related record = 1
reorder_related_records = 1
import = 1
translate = 1
new = 1
ajax_save = 1
ajax_form = 1
history = 1
edit_history = 1
copy = 1
update_set = 1
update_selected=1
select_rows = 1
update related records = 1
edit related records =1
~~~

###`DELETE BASIC`

Defines all of the permissions in `EDIT BASIC`, but adds permissions pertaining to deleting data.

~~~
delete = 1
delete found = 1
delete selected = 1
~~~

###`DELETE`

Defines all of the permissions in `EDIT`, but adds permissions pertaining to deleting data.

~~~
delete = 1
delete found = 1
delete selected = 1
~~~

###`OWNER`

Defines all of the permissions in `DELETE` but explicitly denies the `new` and `delete found` permissions.  This is intended to provide the permissions that you would grant to the owner of a record.

~~~
navigate = 0
new = 0
delete found = 0
~~~

###`REVIEWER`

Defines all of the permissions in `READ ONLY` but also provides access to `edit` and `translate`.  It is intended to provide the permissions necessary for someone who is reviewing/approving content in a record.

###`USER`

Defines all of the permissions in `READ ONLY` but also provides access to `add new related record`.

###`ADMIN`

Defines all of the permissions in `DELETE`.  Intended to be used for system administrators.  However, in practice it is usually just easier to grant the admin `Dataface_PermissionsTool::ALL()`.

###`MANAGER`

Defines all of the permissions in `ADMIN` but adds access to management functions in the control panel.

~~~
manage=1
manage_output_cache=1
manage_migrate=1
manage_build_index=1
install = 1
~~~

<a name="custom-permission-sets"></a>
##Custom Permission-Sets

Although Xataface provides a large set of built-in permission-sets, you are encouraged to create your own permission-sets to suit the access required to users of your application.  Any time you find yourself building custom permission sets inside your `getPermissions()` method, you should probably consider creating a custom permission set in your app's permissions.ini file.


<a name="permissions-ini-file"></a>
###The permissions.ini file

In general, you should never modify any files inside the `xataface` directory, because that will make it more difficult for you to upgrade to newer versions later.  If you want to create a custom permission set, you should create a file named *permissions.ini* in the root directory of your application.  The format of this file should match the format of the Xataface *permissions.ini* file.  At the beginning this file will be empty.  

**There is no need to copy the contents of the Xataface permissions.ini file into your app's permissions.ini file.  Xataface will load both of them**

The `permissions.ini` file, just like most Xataface configuration files, can be defined in 3 places:

1. The core Xataface permissions.ini file.
2. Inside individual modules.
3. Inside the application root.

These files are loaded at the beginning of each request, in the following order:

1. `XATAFACE_ROOT/permissions.ini`
2. `modules/MODULE_NAME/permissions.ini` (for each active module)
3. `APP_ROOT/permissions.ini`

Permission sets that are loaded last take precedence, so you do have the ability to completely override core permission sets, by simply defining a permission set with the same name.

E.g. You could redefine the `READ ONLY` permission set to grant no permissions by adding the following to your app's `permissions.ini` file:

~~~
[READ ONLY]
~~~

This probably isn't a good idea though because it doesn't make sense for "READ ONLY" to take on the same meaning as "NO ACCESS".

<a name="permission-set-inheritance"></a>

###Permission-Set Inheritance

You can use the `extends` keyword to cause your permission set to inherit all of the permissions from an existing permission set.  In fact, if you look at the core Xataface [permissions.ini file](../permissions.ini), you'll see that many of the permission sets are defined as an extension of an existing one.  E.g. `EDIT` extends `READ ONLY`.  That way it only has to explicitly define those permissions that `READ ONLY` doesn't already define.

One place where this would be useful is in our previous example when we wanted our users to have `READ ONLY` permissions, but also to be able to insert new records.  Our `getPermissions()` method looked like:

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
~~~

Rather than explicitly add the 'new' permission inside this method, let's create a new permission set named *PRODUCTS USERS*.

In our app's `permissions.ini` file:

~~~
[PRODUCTS USERS extends READ ONLY]
   new=1
~~~

Then we can change the `getPermissions()` method to:

~~~
function getPermissions(Dataface_Record $record = null){
   $user = Dataface_AuthenticationTool::getInstance()->getLoggedInUser();
   if ( $user and $user->val('role') === 'USER' ){
      if ( $record and $user->val('username') === $record->val('owner') ){
          return Dataface_PermissionsTool::getRolePermissions('EDIT');
      } else {
          return Dataface_PermissionsTool::getRolePermissions('PRODUCTS USERS');
      }
   } else {
       return null;
   }
}
~~~

##Table-Level Permissions

As the examples at the beginning of this article show, table-level permissions are those permissions that pertain to a particular table.  They are provided by the `getPermissions()` method of a table delegate class.  The `getPermissions()` method is called every time Xataface wants to know if a particular action is granted in a given context.  If Xataface is querying for the purpose of interacting with the table, but not with a particular record, it may pass `null` as the `$record` parameter.  

Your `getPermissions($record)` method should answer the question:

*"What can the current user do with the provided `$record` object?"*

And it should answer with a permissions set (as an associative array).

##Field-Level Permissions

Xataface also allows you to limit access to the individual fields/columns in your table.  By default, your fields will inherit the permissions that you have assigned at the table level (if you have defined a `getPermissions()` method for the table), or at the application level (if you haven't defined any custom permissions at the table level).

To limit access on a particular field, you should implement a `fieldname__permissions()` method in the table delegate class (where *fieldname* is the name of the field to limit access on).

We had an example earlier in this article using this strategy to limit access to the `owner` field of a table so that users don't have edit access to it, even though they have been assigned `edit` access at the table/record-level:

~~~
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

In this case, all we have done is return a permission set with the single permission, `edit`, denied. 

 This highlights a key difference between Xataface's handling of *field* permissions and *record/table/app* permissions:
 
 **Field permissions are calculated by merging field permissions with table-level permissions**
 
 **Record/Table permissions are calculated by replacing the application-level permissions with the permissions defined at table level**
 
 This is an important difference, as it means that the following won't work:
 
 ~~~
function owner__permissions(Dataface_Record $record = null){
   $user = Dataface_AuthenticationTool::getInstance()->getLoggedInUser();
   if ( $user and $user->val('role') === 'USER' 
           and $record and $user->val('username') === $record->val('owner')
           ){
      return Dataface_PermissionsTool::READ_ONLY();
   } else {
      return null;
   }
}
~~~


This won't work because the `READ ONLY` permission set doesn't explicitly deny any permissions.  It just grants permissions, and omits other permissions.  That means that if it is merged with `Dataface_PermissionsTool::ALL()` or some other more permissive set of permissions, the result will effectively be all permissions!

Using `READ_ONLY()` at the application-level or table level is fine because:

1. The permissions at Application-level at the top level and thus aren't merged with anything.
2. The permissions at the table/record level will replace the application-level permissions - they won't be merged together.

###Default Field Permissions

Being able to define permissions on each field individually is powerful, but it may be cumbersome in cases where the majority of fields in a table require the same custom permissions.

For example, suppose, in our previous example with the product owner, we wanted a user to *only* be able to modify the `owner` field, but for all other fields to be *read only*.  A first instinct might be to assign `READ ONLY` permissions at the record level, and then grant the `edit` permission only on the `owner` field.  This won't work because, the user requires the `edit` permission to access the *Edit* form at all.  Therefore we need to express the following permissions:

* Record Level: EDIT
* All Fields : READ ONLY
* `owner` field : EDIT

Xataface allows you to implement a `__field__permissions()` method to define default permissions for all fields of a table.  So we could solve this problem as follows:

~~~
function getPermissions(Dataface_Record $record = null){
   $user = Dataface_AuthenticationTool::getInstance()->getLoggedInUser();
   if ( $user and $user->val('role') === 'USER' ){
      if ( $record and $user->val('username') === $record->val('owner') ){
          return Dataface_PermissionsTool::getRolePermissions('EDIT');
      } else {
          return Dataface_PermissionsTool::getRolePermissions('PRODUCTS USERS');
      }
   } else {
       return null;
   }
}

function __field__permissions(Dataface_Record $record = null){
   $user = Dataface_AuthenticationTool::getInstance()->getLoggedInUser();
   if ( $user and $user->val('role') === 'USER' 
           and $record and $user->val('username') === $record->val('owner')
           ){
      return array('edit' => 0);
   } else {
      return null;
   }
}

function owner__permissions(Dataface_Record $record = null){
   $user = Dataface_AuthenticationTool::getInstance()->getLoggedInUser();
   if ( $user and $user->val('role') === 'USER' 
           and $record and $user->val('username') === $record->val('owner')
           ){
      return array('edit' => 1);
   } else {
      return null;
   }
}
~~~

##Relationship Permissions



