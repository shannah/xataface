#Xataface Valuelists

##Contents

1. [Introduction](#intro)
2. [Syntax](#syntax)
3. [Dynamic Valuelists](#dynamic-valuelists)
4. [Defining Valuelists in a Delegate Class](#defining-valuelists-in-a-delegate-class)
5. [Performance Concerns](#performance-concerns)

<a name="intro"></a>

The *valuelists.ini* file stores value lists that can be used as vocabularies for select lists, checkbox groups, and other widgets the provide the user with options to choose from.

Each table can have an associated *valuelists.ini* file located in its table configuration directory. E.g. for a table named "people" its *valuelists.ini* file will be stored in `tables/people/valuelists.ini`.

In addition you can define an application-wide *valuelists.ini* file in the root of your application's directory, whose valuelists can be used by any table.

##Syntax

The valuelists.ini file uses [INI file syntax](http://en.wikipedia.org/wiki/INI_file), where a valuelist is defined by a single section of the INI file.  E.g.

~~~
[colors]
    r=Red
    b=Blue
    g=Green
~~~

This example would define a single valuelist named "colors" with 3 values: `r`,`g`, and `b` (with corresponding labels "Red", "Green", and "Blue).  The values (the left of the equals sign) are stored in the database, while the labels are rendered on screen for the user's convenience.

##Dynamic Valuelists

It is often advantageous to load valuelists from the database rather than store them directly in the valuelists.ini file.  The `__sql__` directive allows you to specify an SQL query which selects up to 2 columns (the first is the id and the second, the label).

E.g.

~~~
[colors]
    __sql__ = "select colorCode, colorName from colors"
~~~


##Defining Valuelists in a Delegate Class

If you require more flexibility with the definition of your valuelists than can be gained from the valuelists.ini file, you can define your valuelist using PHP inside a delegate class.  Essentially you just create a method that returns an associative array, where the keys are the IDs that are stored in the database, and the values are the values that are visible in the select list.

e.g.  In either the application delegate class or a table delegate class:

~~~
function valuelist__colors(){
    return array(
        'r'=>'Red',
        'g'=>'Green',
        'b'=>'Blue'
    );
}
~~~

This method is called each time the valuelist is about to be used, so if your method performs any sort of intensive processing, it is a good idea to use a caching scheme so that it only runs the critical code once per request.  For example, you could use a static variable as follows:

~~~
function valuelist__colors(){
    static $colors = -1;
    if ( !is_array($colors) ){
        $colors = array();
        $res = mysql_query("select colorCode, colorName from colors", df_db());
        if ( !$res ) throw new Exception(mysql_error(df_db()));
        while ($row = mysql_fetch_row($res) ) $colors[$row[0]] = $row[1];
    }
    return $colors;
}
~~~

In this example the database query is only executed once per request to load the $colors variable.  The rest of the time it simply loads the cached value from $colors.


##Performance Concerns

Vocabularies (valuelists) are expensive because they need to be loaded completely into memory on every request, hence they should be used judiciously. It is best not to use them if there will be a large number of records in your table, i.e. more than a few hundred max.

For example, the `lookup` widget doesn't create a vocabulary on its field.  This can be an annoyance but it is best for efficiency.  Often times a lookup widget is used on a field for which there could be a lot of options (thousands or millions).

In cases like this, hide the field in both the list and details view, and instead add a grafted field (via fields.ini file `__sql__` directive) that contains the name of the item instead of the ID, e.g.:


~~~
__sql__ = "select b.*, a.author_name from books b left join authors a on b.author_id=a.author_id"

[author_name]
   ; author_name field settings
   ; won't show up on edit form but will be treated as normal field in most other ways

[author_id]
    widget:type = lookup
    widget:table = authors
    visibility:browse = hidden
    visibility:list = hidden
~~~