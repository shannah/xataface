#Xataface Delegate Classes

##Contents

1. [Introduction](#introduction)
2. [What Can You Do With A Delegate Class?](#what-can-you-do-with-delegate)
3. [How to Add an Application Delegate Class to Your App](#application-delegate-example)
4. [How to Add a Table Delegate Class to Your App](#table-delegate-example)
5. [Application Delegate Troubleshooting](#application-delegate-troubleshooting)
6. [Table Delegate Troubleshooting](#table-delegate-troubleshooting)
7. [Supported Methods](#supported-methods)
   1. [Triggers](#triggers)
   2. [Permissions](#permissions)
   3. [Field Formatting](#field-formatting)
   4. [Record Metadata](#record-metadata)
   5. [Query Customization](#query-customization)
   6. [Feature-specific Methods](#feature-specific-methods)

<a name="introduction"></a>
Xataface applications provide quite a number of mechanisms for customizing application behaviour. One of the most fundamental of these is the *Delegate Class*.  A Delegate class is a class that can be implemented to provide customized functionality to your application by implementing methods that follow naming conventions.

There are two types of delegate classes:

1. **Table Delegate Classes** : Override behaviour pertaining to a particular table.
2. **Application Delegate Class** : Override behaviour pertaining to the entire application.

<a name="what-can-you-do-with-delegate"></a>

##What Can You Do With A Delegate Class?

1. Customize Permissions
2. Override portions of the UI with custom HTML content. (Slots)
3. Insert custom HTML content into parts of the UI. (Blocks and Sections)
4. Override the representation of field content in various formats. E.g. HTML, CSV, Text, etc..
5. Implement event handlers or "triggers" to execute custom PHP code during specified events.  E.g. *before insert*, *after insert*, *before update*, *after user registration*, *before handle request*, etc...
6. Add custom initialization to tables and fields.
7. Create custom fields.

There are many other things you can do with delegate classes also.  In fact most tutorials that involve customizing application behaviour will involve delegate classes in some form.

<a name="application-delegate-example"></a>

##How to Add an Application Delegate Class to Your App

1. Create a directory named *conf* in your application's root directory.
2. Create a file named *ApplicationDelegate.php* inside your *conf* directory with the following contents:
 
 ~~~
 <?php
 class conf_ApplicationDelegate {
 
 }
 ~~~

3. Verify that your application is picking it up, by implementing a supported method and checking to make sure that it is executed.  In this example, we'll implement the `beforeHandleRequest()` method, which is called before every HTTP request:

 ~~~
 <?php
 class conf_ApplicationDelegate {
   function beforeHandleRequest(){
      echo "In beforeHandleRequest";
      exit;
   }
 }
 ~~~
Now, try loading your app.  You should see:

 ~~~
 In beforeHandleRequest
 ~~~
in your browser if the delegate class is being picked up.  If you don't see this, then either it isn't being picked up at all, or it isn't finding the `beforeHandleRequest` method.  In this case, see the [troubleshooting](#application-delegate-troubleshooting) section for more details.

<a name="table-delegate-example"></a>

##How to Add a Table Delegate Class to Your App

1. Create a directory named *tables* in your application's root directory.
2. Create a directory with the same name as your table inside the *tables* directory.  If the table is named *people*, then this directory will be located at `APP_ROOT/tables/people`.
3. Create a PHP file named after the table inside the directory you just created.  If the table is named *people*, this file will be located at `APP_ROOT/tables/people/people.php`.  It should have the following contents:

 ~~~
 <?php
 class tables_people {
 
 }
 ~~~
4. Verify that your application is picking it up by implementing a supported method and checking to make sure that it is executed.  In this example, we'll implement the `init()` method which is called the first time the table is loaded in a request:

 ~~~
 <?php
 class tables_people {
   function init(Dataface_Table $table){
      echo "In people init()";
      exit;
   }
 }
 ~~~

Now try loading your app in the context of your table.  E.g. If your table name is *people*, the app URL to test would be `index.php?-table=people`.  You should see:

 ~~~
 In people init()
 ~~~
If you don't see this, see the [troubleshooting](#table-delegate-troubleshooting) section.

##Application Delegate Troubleshooting

If your application doesn't seem to be picking up your application delegate class after following the [instructions above](#application-delegate-example), this section describes how to debug the issue.  There are two possible scenarios:

1. There is an error.
2. There is no error, the app just runs normally as if there is no Application delegate defined.

###Resolving Errors

If, after adding an Application Delegate, you either receive an error or a blank white screen in your app, you likely have a syntax error in your delegate class.  If you have a blank white screen, your first order of business is to locate the error log.  **Now is the best time to find your PHP error log!!**  If you don't know where your error log is you need to find it.  Without it, you are flying blind and you will begin to hate life before long.

**Common Mistakes:**

1. **Your class has the wrong name**. The class name must be exactly `conf_ApplicationDelegate`.  If you name this wrong, but your PHP file is named correctly, you will receive errors about "Class Not Found", etc..
2. You simply have a syntax error.  Find out what the error is in your error log, and fix it.
3. Your `<?php` open tag is missing or malformed.

###Resolving "Nothing Happened"

If your application runs normally except that it didn't seem to execute your `echo` statement, then either your PHP file is named incorrectly, it is in the wrong location, or the `beforeHandleRequest` method has been named incorrectly.  Make sure that the PHP file is exactly at `APP_ROOT/conf/ApplicationDelegate.php` (where *APP_ROOT* is just the path to your application's directory).  Then make sure that your `beforeHandleRequest()` method is named exactly that.

If you are certain that the PHP file is named correctly, and located in the correct location, you may want to double-check the permissions on your `conf` directory to make sure that it is readable and navigable by the web server process.

##Table Delegate Troubleshooting

If your application doesn't seem to be picking up a table delegate class after following the [instructions above](#table-delegate-example), this section describes how to debug the issue.  There are two possible scenarios:

1. There is an error.
2. There is no error, the app just runs normally as if there is no table delegate.

###Resolving Errors

If, after adding a table delegate, you either receive an error or a blank white screen in your app, you likely have a syntax error in your delegate class.  If you have a blank white screen, your first order of business is to locate the error log.  **Now is the best time to find your PHP error log!!**  If you don't know where your error log is you need to find it.  Without it, you are flying blind and you will begin to hate life before long.

**Common Mistakes:**

1. **Your class has the wrong name**. The class name must be exactly `tables_TABLENAME` (where *TABLENAME* is the name of the table that the delegate class is for)  E.g. `tables_people` if the table is named "people".  If you name this wrong, but your PHP file is named correctly, you will receive errors about "Class Not Found", etc..
2. You simply have a syntax error.  Find out what the error is in your error log, and fix it.
3. Your `<?php` open tag is missing or malformed.

###Resolving "Nothing Happened"

If your application runs normally except that it didn't seem to execute your `echo` statement, then either your PHP file is named incorrectly, it is in the wrong location, or the `init()` method has been named incorrectly.  Make sure that the PHP file is exactly at `APP_ROOT/tables/TABLENAME/TABLENAME.php` (where *APP_ROOT* is just the path to your application's directory, and *TABLE_NAME* is the name of your table).  E.g. If the table is named *people* it would be located at `APP_ROOT/tables/people/people.php`. **This is case sensitive!**  If your table is named `People`, then the delegate class would be located at `APP_ROOT/tables/People/People.php`.

 Then make sure that your `init()` method is named exactly that.

If you are certain that the PHP file is named correctly, and located in the correct location, you may want to double-check the permissions on your `tables` directory to make sure that it is readable and navigable by the web server process.

##Supported Methods

Delegate classes support many different method interfaces, and they can be expanded to support an infinite number of methods.  E.g. Module developers can create their own protocols for delegate class methods for use in their modules.  In addition, many delegate classes simply follow naming conventions, giving you, as an app developer, to implement unlimited methods that affect the behaviour of your application.

The following is a list of some of the common methods that are supported by delegate classes, but this list is by no means exhaustive

###Triggers

| Name | Description | Table | App |
|---|---|---|---|
| `beforeInsert` | Fired before a record is inserted | Y |  |
| `afterInsert` | Fired after a record is inserted | Y |    |
| `beforeSave` | Fired before a record is saved (inserted or updated) | Y | |
| `afterSave` | Fired after a record is saved (inserted or updated) | Y | |
| `beforeUpdate` | Fired before a record is updated | Y |  |
| `afterUpdate ` | Fired after a record is updated | Y | |
| `beforeDelete` | Fired before a record is deleted | Y | |
| `afterDelete` | Fired after a record is deleted | Y | |
| `beforeCopy` | Fired before a record is copied | Y | |
| `afterCopy` | Fired after a record is copied | Y | |
| `beforeAddRelatedRecord` | Fired before a related record is added to a relationship. | Y | |
| `afterAddRelatedRecord` | Fired after a related record is added. | Y | |
| `beforeAddNewRelatedRecord` | Fired before a new record is added to a relationship. | Y | |
| `afterAddNewRelatedRecord` | Fired after a new record is added to a relationship. | Y | |
| `beforeAddExistingRelatedRecord` | Fired before an existing record is added to a relationship. | Y | |
| `beforeRemoveRelatedRecord` | Fired before a related record is removed from a relationship. | Y | |
| `afterRemoveRelatedRecord` | Fired after a related record is removed from a relationship. | Y |  |
| `beforeHandleRequest` | Fired at the beginning of each request.  This is the most common place to add custom request handling - like modifying the query, etc... |  | Y |
| `after_action_new` | Fired after the `new` action is completed successfully. | Y | Y |
| `after_action_edit` | Fired after the `edit` action is completed successfully. | Y | Y |
| `after_action_delete` | Fired after the `delete` action is completed successfully. | Y | Y |
| `after_action_login` | Fired after the login action successfully completes | | Y |
| `after_action_logout` | Fired after the logout action successfully completes |  | Y |
| `after_action_activate` | Trigger called after activation is complete. Activation occurs after a user registers and responds to the registration confirmation email. | | Y |
| `before_authenticate` | Trigger called just before authentication is carried out. This allows you to change the authentication type based on such things as SESSION variables etc... | | Y |
| `loginFailed` | Trigger called after a failed login attempt. Allows you to provide your own logging. | | Y |
| `startSession` | If implemented, this overrides how Xataface starts its sessions. If you implement this method, your custom method should at least include a call to session_start. | | Y |
| `init` | Fired after a table is loaded for the first time in a request. | Y | |

###Permissions

| Name | Description | Table | App |
|---|---|---|---|
| `getPermissions` | Returns the permissions available for a given record. | Y | Y |
| `__field__permissions` | Returns the default permissions for a field of a given record. | Y | |
| `fieldname__permissions` | Returns the permissions that are allowed for the field *fieldname* on a given record. | Y | |
| `rel_relationshipname__permissions` | Returns the permissions pertaining to the relationship relationshipname on a given record. | Y | |

###Field Formatting

| Name | Description | Table | App |
|---|---|---|---|
| `fieldname__htmlValue` | Returns the value of the field "fieldname" for a given record as HTML. | Y | |
| `fieldname__display` | Returns the value of the field "fieldname" appropriate for displaying. | Y | |
| `fieldname__toString` | Converts the value of the field fieldname to a string. This string representation is used as the basis for most higher level data retrieval methods (such as serialize and display). This could be treated as an inverse to the `fieldname__parse` method. | Y | |

###Record Metadata

| Name | Description | Table | App |
|---|---|---|---|
| `getTitle` | Returns the title for a given record. The title is used in various parts of the application to represent the record. | Y | |
| `titleColumn` | Returns a string SQL select expression that is used to describe the title of records.| Y | |
| `getURL` | Overrides the getURL() method for a record. Returns the URL that should be used to display the given record. | Y | |
| `getPublicLink` | Returns the public URL of this record (in case it is different than the standard URL). | Y | |
| `getDescription` | Returns a string description summary of this record. This is used for indexing, RSS feeds, and anywhere that a brief summary of a record is appropriate. | Y | |
| `getCreated` | Returns a unix timestamp marking the date that a record was created. | Y | |
| `getChildren` | Returns a list of Dataface_Record objects that are to be considered children of the given record. | Y | |
| `getBreadCrumbs` | Returns the bread crumbs (i.e. you are here) for a given record as an associative array of path parts. | Y | |

###Query Customization

| Name | Description | Table | App |
|---|---|---|---|
| `__sql__` | Defines the SQL query that can be used to fetch records of this table. This is identical to the fields.ini file `__sql__` directive, except that by defining it in the delegate class you have more flexibility. | Y | |

###Calculated Fields

| Name | Description | Table | App |
|---|---|---|---|
| field__fieldname | Implements a calculated field that can be accessed programmatically like any other field. | Y | |

Example implementation of a calculated field.  Suppose our table has `first_name` and `last_name` fields, but we find ourselves frequently needing to display the full name.  Then we could create a calculated field `full_name` as follows inside the table delegate class:

~~~
function field__full_name(Dataface_Record $record){
    return $record->val('first_name').' '.$record->val('last_name');
}
~~~

Then you can access this value directly from a `Dataface_Record` object using the normal `val()`, `strval()`, `display()`, and `htmlValue()` methods.  E.g.

~~~
$fullname = $record->display('full_name');
~~~

###Feature-Specific Methods

This page only lists some of the core methods supported by delegate classes.  However there are many more methods that pertain to specific features.  These methods will be documented on pages that cover those specific features.

