<?php
/**
@addtogroup installation Installation Instructions


The Xataface framework is simply a collection of scripts that reside in the Xataface directory.  You don't really need to install Xataface per-se, you just need to make sure the xataface directory is uploaded on your web server somewhere in the document root (i.e. it is accessible via a web browser).


@section firstapp Creating your First Application

There are several different ways for you to set up a Xataface application, including using the automated installer, or the command-line makesite script, but to get your feet wet we'll just start with a basic manual installtion where we create the application from scratch by hand.


@subsection createdirectory Step 1: Create a Directory for your App

Just like any other web application, you need to set up a directory to store your files.  Make sure that this directory is accessible from a web server.

For this example, let's assume that we have created our directory under the path:
@code
/home/shannah/pub_html
@endcode
And we name it "hello_world", so that our application will be located at:
@code
/home/shannah/pub_html/hello_world
@endcode
and will be accessible over the web at http://example.com/hello_world


@subsection copyxataface Step 2: Copy the xataface directory inside your application directory

In this example we going to place the Xataface directory inside our application directory.  You could also place Xataface somewhere else on your web server if you wanted to.

So the xataface directory is now located at:
@code
/home/shannah/pub_html/hello_world/xataface
@endcode

@subsection confinifile Step 3: Create conf.ini file

Create a configuration file named conf.ini in your application directory with your database settings:

@code
[_database]
    host=localhost
    name=mydb
    user=me
    password=mypass

[_tables]
    ; A list of tables to include in your application's menu
    ; These tables must already exist in your database
    people=Profiles
    news=News Articles
@endcode

This file would be located at
@code
/home/shannah/pub_html/hello_world/conf.ini
@endcode

This file will store all of your database connection information.

@see @ref installation_sql_dump for an SQL dump of an example database upon which these settings are based.

@attention For this step we assumed that you already have a database with tables and content.

@subsection secureapp Step 4: Secure the .ini files

Since all of Xataface's config files are stored in .ini files, we want to make sure that the web server won't serve these up to the public so we create an .htaccess file in our application directory to prevent access to your conf.ini file:

@code
<FilesMatch "\.ini$">
Deny from all
</FilesMatch>
@endcode

This file would be located at 
@code
/home/shannah/pub_html/hello_world/.htaccess
@endcode

@attention Before moving on you should test to make sure that the conf.ini file is not writable.  Point your web browser to the URL of your conf.ini file (e.g. http://example.com/hello_world/conf.ini) and ensure that you receive a "Forbidden" error message.  If instead you can see the contents of the conf.ini file, it means that your .htaccess file is being ignored.

@see @ref troubleshooting if you can see the contents of the conf.ini file through your web browser.

@subsection phpscript Step 5: Create an Entry Point

Create a PHP script in your application directory as an access point for your app. We'll call it index.php:
@code
<?php
require_once 'xataface/dataface-public-api.php';
df_init(__FILE__, 'xataface')->display();
@endcode

This script would be located at:
@code
/home/shannah/pub_html/hello_world/index.php
@endcode


@subsection templates_cdir Step 6: Create templates_c directory

As of Xataface 1.3 applications need to have their own templates_c directory to store compiled templates.  This directory needs to be writable by the web server (e.g. chmod 777).   At this point, our application should contain the following files:

@code
$ ls -la
total 32
drwxr-xr-x   7 shannah  admin  238 24 May 14:24 .
drwxr-xr-x@ 22 shannah  admin  748 24 May 14:15 ..
-rw-r--r--   1 shannah  admin   49 24 May 14:15 .htaccess
-rw-r--r--@  1 shannah  admin  139 24 May 14:24 conf.ini
-rw-r--r--@  1 shannah  admin   96 24 May 14:16 index.php
drwxrwxrwx   2 shannah  admin   68 24 May 14:24 templates_c
drwxr-xr-x   1 shannah  admin   16 24 May 14:15 xataface
@endcode


@subsection tryitout Try out Our Application

At this point you should have a fully functional web application that allows you to manage the content in your database.  Point your browser to the application (e.g. http://example.com/hello_world) to take it for a spin.

@see @ref troubleshooting if you get a blank white screen or an error when you try to access your application.

@section screenshots  Screenshots of Our application

@subsection firstload The first Page Load

When we first open our app with no records we just see the list view with no records found:

<img src="http://media.weblite.ca/files/photos/Screen%20shot%202011-05-24%20at%202.30.30%20PM.png?max_width=640"/>

A little boring.

@subsection newrecordform New Record Form

We can add new records by clicking the "New Record" link.  It provides us with a form to insert records into the current table.  Below is a screenshot of the new record form for the "people" table:

<img src="http://media.weblite.ca/files/photos/Screen%20shot%202011-05-24%20at%202.31.05%20PM.png?max_width=640"/>

@subsection viewtab The View Tab


The details veiw (i.e. the view tab) for a record shows all of the properties for the current record.  The screenshot below shows the "View" tab for the record that was just added using the new record form:

<img src="http://media.weblite.ca/files/photos/Screen%20shot%202011-05-24%20at%202.31.47%20PM.png?max_width=640"/>

@subsection listtab The List Tab

The list view shows the current found set in a table.  Below is a screenshot of the list tab after adding a single record to the people table.

<img src="http://media.weblite.ca/files/photos/Screen%20shot%202011-05-24%20at%202.32.00%20PM.png?max_width=640"/>

@subsection findtab The Find Tab

Xataface provides an advanced find form to easily find records in your application.  Below is a screenshot of the find tab of the people table in our sample application.

<img src="http://media.weblite.ca/files/photos/Screen%20shot%202011-05-24%20at%202.32.11%20PM.png?max_width=640"/>

*/
?>
