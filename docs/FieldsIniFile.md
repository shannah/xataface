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

###Troubleshooting

If you create a *fields.ini* file and the your configuration doesn't seem to be affecting your application, then it is possible that the application is not picking up the *fields.ini* file at all.  If that is the case, check the following:

1. *File Location*. Make sure the *fields.ini file is in the correct location. It should be located at `APP_ROOT/tables/TABLE_NAME/fields.ini` where *APP_ROOT* is the path to your app's root directory, and *TABLE_NAME* is the name of the table to which the *fields.ini* file applies.  **NOTE: Everything is Case sensitive**
2. *Field Name*. Make sure that the section name in your *fields.ini* file exactly matches the field name to which it applies.  **THIS IS CASE SENSITIVE**.  E.g. If you want to change the label of a field named "name", then the following will not work:

 ~~~
 [Name]
   widget:label="My Name"
 ~~~
The `[Name]` should be `[name]`, because the field name is `name` (all lower case).  I.e. the correct way is:

 ~~~
 [name]
   widget:label="My Name"
 ~~~
3. **Duplicate Field Definitions**. Make sure that you don't have multiple sections in your *fields.ini* file with the same name.  If there are multiple sections, the *last* one will override all previous sections of the same name.  E.g.:
 
 ~~~
 [name]
   widget:label="My Name"
   
 [age]
   widget:label="My Age"
 
 [name]
   
 ~~~
 In the above example, the `name` field will have label "name" because the last occurring section (which is blank) overrides the one that appears at the top - effectively overriding the `widget:label` directive.
 
##Structure and Syntax

The *fields.ini* file follows the standard [INI file format](http://en.wikipedia.org/wiki/INI_file).  Each field of the table may have a corresponding section, and all properties (key-value pairs) inside a section are assigned to the the section's corresponding field.  E.g. We can customize the `name`, `birth_date`, and `bio` fields in the table by adding three sections to the *fields.ini file as follows:

~~~
[name]
   widget:label="Person Name"
   widget:description="Please enter the person's name"

[birth_date]
   widget:description="Please select the birth date"
   widget:type = "date"
   
[bio]
   widget:label="Biographical Info"
~~~

###Table-Level Directives

Key-value pairs that occur before the first declared section of the *fields.ini* file are applied to the table as a whole, rather than a specific field.  Some table level directives include `label`, `__sql__`, and `description`, but there are others.  E.g.

~~~
label="The People Table"

[name]
   widget:label="Person Name"
   ;... etc...
   
~~~

In the above example we set the table label to "The People Table".

###Comments

You can add comments to your fields.ini file using a `;`.  Any text occurring after a `;` on a line are considered a comment. E.g.

~~
[name] ; This is a comment
~~~

##Supported Field Directives


| Name | Description |
|---|---|
| `widget:label` | The label for the field.  This is used on forms, lists (as column headers), and on other details views in Xataface.|
| `column:label` | Overrides the label for this field as it is displayed in column headers in the list view. |
|`column:legend` | Optional description/legend text to be displayed in column headers in list view just beneath the column label. |
| `widget:description` | The description for the field.  This is used for help text on forms, and possibly tool-tip text in other parts of the UI. |
| `widget:type` | The type of widget that should be used on forms to edit this field.  See [Widgets](Widgets.md) for more information about the different supported widget types.|
| `visibility:list` | Whether this field should be visible in *list* view.  *Possible values*: `visible` or `hidden`.  *Default value*: `visible` for most fields, but some fields, like *password* fields and *metadata* fields are hidden by default. |
| `visibility:browse` | Whether this field should be visible in *browse* (details) view.  *Possible values*: `visible` or `hidden`.  *Default value*: `visible` for most fields, but some fields, like *password* fields and *metadata* fields are hidden by default. 
| `visibility:find` | Whether this field should be visible in the advanced find form. *Possible values*: `visible` or `hidden` |

