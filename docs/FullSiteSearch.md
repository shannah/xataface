#Xataface Full-Site Search

Xataface supports an optional full-site, full-text search that allows the user to search multiple tables at once in the top search box.

##Activating Full-Text Search

1. Add a `[_index]` section to your app's conf.ini file.
2. For each table that you wish to be searchable in the full-site search, add a line to the `[_index]` section as follows:
 
 ~~~
 table_name=1
 ~~~
 E.g. If you wanted the `conferences`, and `products` tables to be searchable, your `[_index]` section would look like:
 
 ~~~
 [_index]
    products=1
    conferences=1
 ~~~
3. When you first activate full-site search, you need to manually build the index.  Log into your app as a user who has the "manage" permission.  Under the "Control Panel" menu in the upper right, you should have an option to *build search index*.  Select this option.
4. Select the tables that you want to index, then click the "Index" button.

At this point, you should now have a select list beside the top search box with "Site" selected by default.  If you perform a search you will see results from multiple tables, and they will be sorted by relevance.

