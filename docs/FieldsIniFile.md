#Xataface *fields.ini* File

In Xataface, the *fields.ini* files serves as the foundation for table configuration.  Each table may have a single *fields.ini* file and, if present, this file can customize many aspects of the table including:

1. The widget labels, descriptions, and types that are used for the table on Xataface's forms, lists, and details views.
2. The field ordering, grouping, and visibility in various parts of your application.
3. Customizing the SQL query that is used to fetch records of the table (i.e. the `__sql__` directive).
4. Other miscellaneous table configuration details.

##Creating a *fields.ini* file

Suppose our application has a table named *people* with the following SQL structure:

~~~
CREATE TABLE `people` (
   person_id INT(11) NOT NULL auto_increment PRIMARY KEY,
   name VARCHAR(100),
   bio TEXT,
   photo LONGBLOB,
   photo_mimetype VARCHAR(50),
   marital_status ENUM('Single','Married', 'Complicated')
   birth_date DATE,
   employer_id INT(11)
)
~~~

We create a *fields.ini* file for this table as follows:

1. Create a *tables* directory inside the app root folder if it doesn't already exist.  I.e. `APP_ROOT/tables`, where *APP_ROOT* is the path to the application.
2. Create directory named *people* inside the *tables* directory.  I.e. `APP_ROOT/tables/people`.  Note that this directory is called "people" because the table is named "people".  If it were named "foo", then the directory would be named "foo" as well.  **Note: This is case sensitive**
3. Create a file named `fields.ini` inside the `people` directory.  I.e. `APP_ROOT/tables/people/fields.ini`.
4. Verify that the fields.ini file is being picked up by trying to modify the label of one of the fields in our table.  E.g. Add the following content to the `fields.ini` file:

 ~~~
 [name]
   widget:label="Name Test"
 ~~~
Try loading the *new record* form for the *people* table, and verify that the label for the *name* field is now "Name Test".  If it is not, see the [troubleshooting](#troubleshooting) section.

