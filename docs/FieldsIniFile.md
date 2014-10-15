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

