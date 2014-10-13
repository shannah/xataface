#Xataface Actions

Actions are the entry points to a Xataface application.  They are essentially PHP scripts the run in the context of a Xataface HTTP request/response cycle.  Xataface includes core actions to perform all of the common tasks of a database application (e.g. List, View, Edit Record, New Record, Add Related Record, Export CSV, etc...), and it also provides an API for you to define you to define your own actions.  You can define custom actions at the application level (i.e. that are for use only by a single application) and at the module level (i.e. that can be reused by multiple applications).

##Routing

Use the `-action` HTTP request parameter to specify which action should be used to handle a given HTTP request.  E.g.

`index.php?-action=list`

will be routed to the *list* action (i.e. shows the current result set as a list), and 

`index.php?-action=edit`

will be routed to the *edit* action (i.e. shows a form to edit the current record).

##Core Xataface Actions

| Action Name | Description |
|---|---|
|list | Shows the current result set in a table.  This is the default action if one isn't explicitly declared in the URL |
| view | Shows the detail view for the singe, currently-selected record |
| new | Shows the *new record* form. |
| edit | Shows a form to edit the currently-selected record |
| import | Shows form to import records into the current table or relationship |
| forgot_password | Shows *forgot password* form |
| history | Shows history log for current record |
| register | Registration form for user to register for an account on the system |
| new_related_record | Shows form to create a new record to a given relationship. |
| related_records_list | Shows a table with related records for a given parent record/relationship|
| translate | Shows a translation form to translate the currently selected record.  Requires multilingual support to be enabled.|
| export_csv | Exports the current result set as a CSV file |
| export_json | Exports the current result set as a JSON file |
| export_xml | Exports the current result set as XML |
| feed | Exports the current result set as an RSS feed |
| delete | Shows a form to delete the current record. |

This is just a small sample of the actions that are included with Xataface.  For a full list of actions see [here](../actions).

Many of the actions defined in the [actions](../actions) folder are not intended to be directly accessed, but rather, provide REST APIs that are used by internal javascript libraries.

