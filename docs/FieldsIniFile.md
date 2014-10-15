#Xataface *fields.ini* File

##Contents

1. [Introduction](#introduction)
2. [Creating a Fields.ini File](#creating-fields-ini-file)
   1. [Troubleshooting](#troubleshooting)
3. [Structure and Syntax](#structure-and-syntax)
   1. [Table-level Directives](#table-level-directives)
   2. [Comments](#comments)
4. [Supported Field Directives](#supported-field-directives)
   1. [Form-Related Directives](#form-related-directives)
   2. [Field Content Management](#field-content-management)
   3. [List Customization](#list-customization)
   4. [Field Visibility](#field-visibility)
   5. [Details-View Customization](#details-view-customization)
5. [Supported Table Directives](#supported-table-directives)
6. [Field Groups](#field-groups)
   1. [Field-Group Directives](#field-group-directives)

<a name="introduction"></a>
In Xataface, the *fields.ini* files serves as the foundation for table configuration.  Each table may have a single *fields.ini* file and, if present, this file can customize many aspects of the table including:

1. The widget labels, descriptions, and types that are used for the table on Xataface's forms, lists, and details views.
2. The field ordering, grouping, and visibility in various parts of your application.
3. Customizing the SQL query that is used to fetch records of the table (i.e. the `__sql__` directive).
4. Other miscellaneous table configuration details.

<a name="creating-fields-ini-file"></a>

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

~~~
[name] ; This is a comment
~~~

##Supported Field Directives

###Form-Related Directives

| Name | Description |
|---|---|
| `widget:label` | The label for the field.  This is used on forms, lists (as column headers), and on other details views in Xataface.|
| `widget:description` | The description for the field.  This is used for help text on forms, and possibly tool-tip text in other parts of the UI. |
| `widget:type` | The type of widget that should be used on forms to edit this field.  See [Widgets](Widgets.md) for more information about the different supported widget types.|
| `widget:question` | Text displayed just before the widget. This is almost the same as widget:description? except that this text is guaranteed to be displayed before the widget, whereas widget:description? may be displayed below or beside the widget. |
| `widget:focus` | Sets default focus. 0 or 1. (Javascript focus in a form) |
| `widget:atts:xxx` | A namespace for attributes that should be added to the HTML widget. This allows you to specify things like javascript events, styles, widget size, etc.. |
|`frozen_description` | The field description shown when the widget is frozen (i.e. uneditable). If this is not specified, no field description is shown in this case.|
| `group` | The name of the field group that this field belongs to. Fields with the same "group" value will be rendered in the same field group on the form. |
| `label_link` | An optional URL for the field label to link to. This would usually be some "help" page that explains what the field is for. The link will be a link in both the view and edit tabs. |
| `tab` | If tabbed forms are enabled, then this specifies the name of the tab that this field belongs to on the edit form. |
| `display` | Specifies the layout of the field on the edit form. Most fields have an implicit value of "inline" meaning the widget and its label appear on the same line. Textareas and htmlareas have an implicit value of "block" meaning that the label and widget appear in separate rows (label above the widget). You can set this value explicitly also to override the layout of a field. |
| `order` | The order of the field when laid out on forms and lists. Can contain any floating point number or integer (e.g. 0, 10, -10, 235.4) |
| `validators:VALIDATOR_NAME` | A prefix for a validation type on the current field. (Replace "VALIDATOR_NAME" with the name of the validator to be used. e.g. required). There are many validators available to be used. |
| `validators:VALIDATOR_NAME:message | The message the should be displayed if the form fails to validator due to the "VALIDATION_NAME" validation rule. |
| `vocabulary` | The valuelist? that should be used as the options to select. This is only applicable for fields that have options to select like a select list or a checkbox group. |

###Field Content Management

| Name | Description |
|---|---|
| `timestamp` | Indicates when a timestamp should be set in the field (only applicable for date and time fields). Possible values are "insert" and "update" |
| `date_format` | Specifies how the field should be formatted when displayed. Takes same parameters as PHP strftime function. |
| `display_format` | A pattern that can be used to define the display format of the field. This takes the same parameters as the PHP sprintf function. |
| `encryption` | Primarily used with password fields, indicates the type of encryption that should be used to save the field. Supports "md5", "sha1", "encrypt", and "password". |
| `money_format` | For fields containing monetary amounts, this specifies the format. Takes same parameters as PHP money_format function. |
| `number_format` | A flag to indicate that this field can not be used as part of a query. This is helpful if you want a field to remain completely confidential to prevent people from finding records based on the value of this field. This flag is even necessary if the permissions for the field don't permit viewing the value of the field. |
| `ignore` | Boolean value (0 or 1) indicating whether this field should be ignored on the edit form. This is handy if the field is going to be constantly updated in the background (via a cron job perhaps) and you don't want the edit form to interfere.|
| `logo` | Boolean value (0 or 1) to indicate if this field should be treated as a logo field. Logo fields are displayed in the upper left of the view tab? for a record, and are assumed to contain an image. If no logo field is explicitly specified, Xataface will make a best guess as to which field should be used. |
| `struct` | A boolean (0 or 1) value indicating whether this field is considered a structure. A value of 1 indicates that this field is a structure and should not be truncated under any circumstances. Normally fields are truncated at 255 chars in list view. This is useful if the field contains XML or other structured data so that attempts to truncate it would destroy integrity. |
| `title` | Boolean value (0 or 1) indicating whether this field should be treated as a title field. |
| `transient` | Boolean value (0 or 1) indicating whether this field is a transient field or not. A transient field is a field that is defined in the fields.ini file but not in the database. Hence the values that are input into this field on the edit form are not saved to the database. |
| `Type` | The data type of the field (note the capital "T" as Xataface is case sensitive). This value is only overridden for container? fields, however its value can be accessed programmatically for any field. |

###List-Customization

| Name | Description |
|---|---|
| `column:label` | Overrides the label for this field as it is displayed in column headers in the list view. |
|`column:legend` | Optional description/legend text to be displayed in column headers in list view just beneath the column label. |
| `filter` | Boolean value (0 or 1) indicating whether this field should be filterable? in list view?. |
| `noLinkFromListView` | Boolean value (0 or 1) to indicate if this field should be linked when in list view (or in a related list). Default value is 0 to indicate that the field IS linked. It is common to use this directive when using a custom xxx__renderCell() method that contains its own links.|


###Field Visibility

| Name | Description |
|---|---|
| `visibility:list` | Whether this field should be visible in *list* view.  *Possible values*: `visible` or `hidden`.  *Default value*: `visible` for most fields, but some fields, like *password* fields and *metadata* fields are hidden by default. |
| `visibility:browse` | Whether this field should be visible in *browse* (details) view.  *Possible values*: `visible` or `hidden`.  *Default value*: `visible` for most fields, but some fields, like *password* fields and *metadata* fields are hidden by default. 
| `visibility:find` | Whether this field should be visible in the advanced find form. *Possible values*: `visible` or `hidden` |
| `visibility:update` | Indicates whether the field should be included in update and copy/replace forms. Possible values are "visible" and "hidden".|
| `visibility:csv` | ndicates whether the field should be included in CSV exports. Possible values are "visible" and "hidden". (1.0 beta 4) |
| `not_findable` | A flag to indicate that this field can not be used as part of a query. This is helpful if you want a field to remain completely confidential to prevent people from finding records based on the value of this field. This flag is even necessary if the permissions for the field don't permit viewing the value of the field.|
| `xml` | A flag for use with calculated fields (i.e. fields defined in the delegate class? via the field__fieldname method) that will include the field in XML output produced by the export xml action?. Default is 0, but setting this value to 1 wil cause the field to be included. |

###Details View Customization

| Name | Description |
|---|---|
| `viewgroup` | The name of the field grouping that this field will belong to in the view tab. If this is not present, then it will be grouped according to the group directive. |


##Supported Table Directives

| Name | Description |
|---|---|
| `label` | The label for the table as it will be rendered on tabs and buttons. |
| `__sql__` | Defines a custom select query to override the default select query for the current table. (The default select query is generally "select * from tablename"). |
| `__dependencies__` | A comma-delimited list of tables that this table is dependent upon for caching purposes. E.g. if any table in this list is modified, then the query cache is cleared for queries on this table. See this blog article for more information about query caching. |
| `__isa__` | The name of the parent table of the current table. This directive allows you to have a heirarchical structure amongst the tables in your application. |
| `__source_tables__` | A comma-delimited list of tables that this table/view is derived from. This is used with the query caching feature and is necessary to use this directive if the table is actually a view. If this directive is not set, then any queries involving this view will not use the query cache because Xataface would have no way to discern the update time of the view. See [this blog article](http://xataface.blogspot.ca/2009/06/using-query-caching-in-xataface.html) for more information about query caching. |

##Field Groups

The group directive allows you to group multiple fields together so that they will be rendered in the same field group on forms. You can also configure these groups as a whole by defining a section named `[fieldgroup:GROUPNAME]` (where *GROUPNAME* is the name of the field group, corresponding to the group directive values for the fields) in the fields.ini file. This section provides a few basic directives to customize some aspects of the field group:

* label
* order
* description
* template
* more...

The most common use of these sections is to customize the label or order of groups, especially when there are multiple field groups in the table. For example, suppose we have a table *people* with fields `first_name`, `last_name`, `phone`, `fax`, `email`, `address`, `city`, and `country`. Suppose these fields are grouped as follows:

* `first_name` and `last_name`
* `phone`, `fax`, and `email`
* `address`, `city`, and `country`

so that the *fields.ini* file looks like:

~~~
[first_name]
    group=name
    
[last_name]
    group=name
    
[phone]
    group=contact
    
[fax]
    group=contact
[email]
    group=contact
    
[address]
    group=address
    
[city]
    group=address
    
[country]
    group=address
~~~

By default, the `name` group will appear first in the form, followed by `contact` and `address`. If we want to place "address" first we could add the following section to our *fields.ini* file:

~~~
[fieldgroup:address]
    order=-1
~~~

Since the default order value is 0 on other groups, setting the `order` parameter to `-1` will place the "address" group before all others.

###Field-Group Directives

| Name | Description |
|---|---|
|`order`|	Specifies the order of the group with respect to other groups on the form. Accepts any numerical value (e.g. 0, 1, -1, 25.43), with lower values appearing first. Default value is 0 |
|`label` |	Specifies the label that should be used for the field group.|
| `label_link` |	Specifies a URL that the field group label should link to.	|
| `template` |	The path to a custom template that should be used to render the fields of the field group.|
| `collapsed` |	Boolean value (0 or 1) indicating whether the field group should be collapsed by default (user can expand it).|

