#Xataface Actions

##Contents

1. [Synopsis](#synopsis)
2. [Routing](#routing)
3. [Core Xataface Actions](#core-xataface-actions)
4. [Configuration](#action-configuration)
5. [Menus](#action-menus)
  1. [Core Action Categories](#core-action-categories)
6. [Custom Actions](#custom-actions)
  1. [Hello World](#hello-world-action)
  2. [actions.ini directives](#directives)
  3. [Inheritance](#action-inheritance)
  4. [Overriding Existing Actions](#overriding-existing-actions)
  5. [Hiding Actions](#hiding-actions)
7. [Action Permissions](#action-permissions)
8. [PHP Expressions in Action Directives](#expressions)
  1. [Expression Context](#expression-context)
  2. [Debugging Action Expressions](#debugging-action-expressions)

<a name="synopsis"></a>

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

##Action Configuration

Actions have two components:

1. The PHP script that handles the HTTP request.
2. The action configuration defined in the [actions.ini file](../actions.ini).

The configuration options for an action may include such directives as:

1. **permission** : The name of the permission required to access this action.
2. **condition** : A PHP expression that, if evaluated to true at runtime, allows the action to be displayed in a menu.
3. **category** : Allows actions to be grouped together into different menus.
4. **label** : A label for the action when it is displayed in a menu.
5. **description** : A description for the action when displayed in a menu.  Generally this will result in tool-tip text, but some menus may render this in different ways.
6. **url** : The URL for the action when it is displayed in a menu.

Notice than many of these options pertain to the way that actions are rendered as menus.

See the [Xataface actions.ini file](../actions.ini), and the [g2 module actions.ini file](../modules/g2/actions.ini) for a full list of core actions and their associated configuration directives.

##Action Menus

Most Xataface templates/pages include one or more menus.  E.g. Most pages include the following menus:

1. **Top Left Menu** : Includes links to navigate between tables in the app.
2. **Top Right Menu** : Includes user account preferences and control panel links.
3. **Table Menu** : In the left column, includes "New Record" and "Import Records" buttons.
4. **Result List Actions** : When in *list* view, buttons above and below the result list such as *Export*, *Show/Hide Columns*, etc..

These menus are generated based on the *category* directive of actions defined in the *actions.ini* file.

###Core Action Categories

There are many action categories, and module and application developers can create their own categories, so there can be an unlimited number of categories.  However, the following are some of the common categories in Xataface:

* `table_tabs` : In the old theme (from Xataface 2.0.x and earlier), these are the top level tabs in each table. (e.g. *list*, *details*, and *find*).  With the *g2* theme, which is default in 2.1.x, this category is no longer used.
* `find_actions` : Alternative "find" actions.  In the old theme these are incorporated in a drop-down list beside the search field.  In the g2 theme, these manifest as additional buttons next to the search field.
* `table_actions` : Actions related to a particular table. E.g. Add new record, import records etc..  In the old theme these are shown in the toolbar just below the table tabs.   In the new theme they are shown as buttons vertically in the left column.
* `result_list_actions` : Actions pertaining to the result list.  This includes actions like *export csv*, *export xml*, etc...  In the old theme, these were shown in the upper right corner of the result list as icons only.  In the g2 theme, these are shown horizontally in  a toolbar just above and below the result list as buttons.
* `record_actions` : Actions pertaining to the currently selected record.  These are shown as buttons along the top of the record details panel.
* `related_list_actions` : Actions pertaining to a related list.  In the old theme these are shown in the upper right of the result list as icons only.  In the g2 theme these are shown as buttons horizontally in toolbars just above and below the related records list.
* `selected_result_actions` : Actions that operate on the currently *checked* records in the result list.  This is used only in the old theme.  The g2 theme uses a different mechanism for operating on selected records based on the *class* directive.
* `selected_related_result_actions` : Actions operating on checked rows in related lists.  Used only in the old theme.  The g2 theme uses a different mechanism  for operating on selected records based on the *class* directive.
* `summary_actions` : Actions shown in a summary list.
* `record_tabs` : Subtabs in record detail view.  E.g. *history*, and the sundry relationship tabs.
* `login_actions` : Actions displayed on the login form.  E.g. *forgot password*.
* `history_record_actions` : Displayed next to each entry of history in the *history* tab.
* `personal_tools` : Actions pertaining to user reflexive tools.  E.g. Preferences, personal account, and logout.
* `management_actions` : Displayed as part of the control panel.
* `event_actions` : Actions shown in the event details of the calendar view.
* `view_related_record_footer_actions` : Actions shown in the footer of the details view for a related record.
* `view_related_record_actions` : Actions shown in the details view for a related record.
* `edit_related_record_actions` : Actions show in the edit form for a related record.
* `top_right_menu_bar` : Actions shown in the upper right of the g2 theme interface on the tool bar.  This includes things like the control panel, and the drop-down menu named for the currently logged-in user.
* `list_export_actions` : Actions appearing in the *Export* drop-down button-list in the list view of the g2 theme.
* `record_export_actions` : Actions appearing in the *Export* drop-down button-list in the details view of the g2 theme.
* `related_export_actions` : Actions appearing in the *Export* drop-down button-list in the related list of the g2 theme.
* `add_new_related_record_actions` : Actions appear on the toolbar of the *new related record* form in the g2 theme.
* `edit_record_form_actions` : Actions appear on the toolbar of the *edit record* form in the g2 theme.
* `advanced_search_actions` : Actions appearing inside the *advanced search* window.

##Custom Actions

Xataface allows you to create your own custom actions for your application by creating an `actions.ini` file inside the root directory of your application.  The general format of the `actions.ini` file is:

~~~
[action_name]
    label="Action Label"
    description="Some information about the action"
    category=some_category
    url="{$this->url('-action=action_name')}"
    permission=some_permission
    condition="some PHP boolean expression"
    
[action2_name]
    label="Action 2 Label"
    etc...

etc...
~~~

The format is the same as the [Xataface actions.ini file](../actions.ini) so the best way to learn is to take a look at the [source](../actions.ini) of that file.

In addition to an *actions.ini file* entry, a custom action generally needs to have a corresponding PHP class located inside the application's `actions` directory with the same file name (not including the `.php` suffix) as the action itself, and the class name in the form `actions_ACTIONNAME` (where *ACTIONNAME* is the name of the action.

###Hello World Action

A Simple *Hello World* action might look like the following:

**actions.ini**:

~~~
[hello]
   label="Hello World"
   url="?-action=hello"
   category=top_left_menu_bar
~~~

**`actions/hello.php`** :

~~~
class actions_hello {
    function handle($params=array()){
        echo "Hello World";
    }
}
~~~

Key points to notice here:

1. The action name is "hello" and this is manifested in 3 places:
 1. The section name for the action in the `actions.ini` file.  e.g. `[hello]`
 2. The file name of the PHP script inside the `actions` directory. e.g. `actions/hello.php`
 3. The name of the class inside the PHP file.  E.g. `actions_hello`.
2. The `category` directive causes the action to be listed in the top left menu in the g2 theme (this category is not present in other themes, so the action would not be shown if you are not using the g2 theme).
3. The `url` directive links the *hello* menu item to the *hello* action (i.e. `actions/hello.php`. 

This action would simply display:

~~~
Hello World
~~~

<a name="directives"></a>

###actions.ini Directives

Some of the key directives in the actions.ini file :

| Name | Description |
|----|-----|
| `label` | The label that is displayed for the action when it is shown in a menu of the UI. |
| `description` | The description that is displayed as a tool-tip when the action is shown in a menu. |
| `category` | Identifies which menu the action should be displayed in. |
| `url` | The URL that the action's associated menu item should link to.  This may include PHP expressions embedded in curly braces.  E.g. `category="{$this->url('-action=foo')}` |
| `permission` | The name of the permission required to access this action. ** If this is omitted, then the action will be open to the public.** |
| `condition` | A PHP boolean expression that is executed just prior to the menuitem being rendered.  If the expression resolves to `false`, then the menu item will not be rendered.|
| `icon` | The path to an icon that can be displayed for an action.  These are used more in the old theme than in g2.|
| `selected_condition` | A boolean expression that, if evaluated to true, will cause the action to be marked as *selected* in the UI.  Generally the UI marks the `<li>` tag with the *selected* CSS class when rendered in a menu, but different implementations may do it differently.  This is the mechanism used for tabs to show which one is currently selected. |
| `class` | Optional CSS classes that can be added to the `<li>` tag when the action's menu item is rendered |
| `accessKey` | Optional access key that may be used to trigger the action. |
| `confirm` | Optional confirmation message to be shown when the user clicks on the action.|
| `onclick` | Optional Javascript expression to be executed when the action's menuitem is clicked. |
| `subcategory` | If this action is intended to be a parent menu with sub-items, you can specify the *category* from which its *children* are selected. |
| `atts:xxx` | Additional HTML attributes that should be added to the the `<li>` tag in the actions menu. |

To see how actions are rendered, you might find it helpful to look at the [Dataface_ActionsMenu.html template](../Dataface/templates/Dataface_ActionsMenu.html) which is used to render many of the action menus in Xataface.  Although it is important to note that the actions infrastructure is foundational to Xataface and can be used to generate menus in many different ways.


###Action Inheritance

Xataface supports inheritance with actions using the following syntax:

~~~
[action_name > parent_action_name]
~~~

The above creates an action named `action_name` with the same properties as the previously declared action `parent_action_name`.

E.g. Suppose you wanted to create an action "filtered_list" that is the same as the "list" action, except that it filters the results to only show records with `approved=1`.  You would define something like the following:

~~~
[approved_list > list]
    url="{$this->url('-action=list&approved=1')}"
    condition="$query['-table'] == 'mytable'"
    label="Approved List"
~~~

###Overriding Existing Actions

Actions, in Xataface, are loaded from various actions.ini files in the following order:

1. `XATAFACE_PATH/actions.ini`
2. `modules/MODULE_NAME/actions.ini`  (for each activated module *MODULE_NAME*)
3. `APP_PATH/actions.ini`

The *last* action loaded takes precedence in the case of a name conflict.  That means that you can override an action by simply defining an action with the same name in your app's *actions.ini* file.

**WARNING: Overriding an action will replace ALL configuration properties of the action, including permissions.  Simply overriding a private action will cause it to be publicly accessible if you don't set the configuration directive.**

E.g. You *could* override the *list* action by adding the following to your `actions.ini` file:

~~~
[list]
~~~

This would have the following consequences:

1. The *list* action would now be effectively a blank action with no configuration properties.
2. If there is a *list* action handler (e.g. in `actions/list.php` or `xataface/actions/list.php`, it would be open to the public because no *permission* directive was set).
3. The *list* action would no longer be listed in any menus because it has no *category* directive.


A better way to override the *list* action is to use inheritance.  E.g:

~~~
[list > list]
~~~

What this does is creates a new action named *list* that inherits all of the configuration directives from the existing action named *list*.  Overriding *list* in this manner would have no effect on program execution because it effectively replaces *list* with an exact duplicate of itself. Now we can override individual properties selectively.  E.g. If we wanted to change the label of the *list* action to "My List", we could do:

~~~
[list > list]
   label="My List"
~~~


###Hiding Actions

The vanilla Xataface install includes lots of menus with useful functions - but some applications don't require all of these functions.  For example, the list view includes an *Export XML* action.  If you want to hide this so that it doesn't show up, you can easily do this by overriding the *export_xml* action, and changing the category to something that isn't used, like an empty string:

~~~
[export_xml > export_xml]
   category=""
~~~

**NOTE: Before using this approach to hide actions from your UI, take a moment to consider whether you want to prevent users from accessing this functionality or if you just want the button hidden.  Hiding the menu item won't actually block users from accessing this functionality because they can still access it with a well-crafted URL.  If you actually want to block access, then you should use permissions instead to deny access to the action.**

##Action Permissions

By default all actions are publicly accessible.  There are two ways to limit access to an action:

1. Using the `permission` directive of the actions.ini file.
2. Using PHP logic inside the action handler to limit access to itself.

The `permission` directive specifies the name of a permission that must be granted to a user in order to access the action.  If a request is made by a logged-in user for an action and the current user lacks the permission specified, then they will receive an error message. If the user isn't yet logged in, and they request an action for which they don't have permission, they will be redirected to the login page.

You can see many examples of the `permission` directive inside Xataface's [actions.ini file](../actions.ini).  E.g. the *new* action:

~~~
[new]
	label = New Record
	description = Create a new record
	url = "{$this->url('-action=new', false)}"
	icon = "{$dataface_url}/images/add_icon.gif"
	category = table_actions
	accessKey = n
	mode = browse
	permission = new
	order=1
~~~

The `permission` directive here specifies that users require the *new* permission in order to access the new record form.  This means that the "New Record" button won't appear in any menus for unauthorized users, and that cleverly crafted URLs for the *new* action will be blocked except for authorized users.

If you require more precision in determination of whether the current user has authorization to perform an action, you may use logic inside the action itself.  E.g. The following snippet is from the [delete_file action](../actions/delete_file.php):

~~~
if ( !$record->checkPermission('edit', array('field'=>$fieldDef['Field'])) ){
	return Dataface_Error::permissionDenied('You don\'t have permission to edit this field.');
}
~~~

This uses the Xataface API (particularly the `Dataface_Record::checkPermission()` method) to check whether the current record has the edit permission on a particular field.  If not, it returns a *permission denied* error, which Xataface knows how to handle.

<a name="expressions"></a>

##PHP Expressions in actions.ini Directives

There are three types of directives that can be included in an action definition:

1. **Static**.  These cannot contain any variables.  Examples of static directives include `category`, `table`, `relationship`, `name`, `id`, and `permission`.
2. **Boolean Expressions**. These are evaluated as PHP expressions that resolve to a boolean value.  Any directive whose name ends with "condition" is treated as a *Boolean Expression*.
3. **String**.  These are evaluated as PHP strings, so they can contain PHP expressions inside curly braces `{}`, just like double-quoted PHP strings can.  All non-static directives other than "condition" properties are treated as strings.

###Expression Context

PHP expressions run inside boolean expressions and String expressions are executed in a limited context with only a small handful of special variables accessible:

| Variable Name | Description | Example |
|---|---|---|
| `$site_url` | The URL to the app directory.  Not including `index.php` | `url="{$site_url}/pages/mypage.html"`|
| `site_href` | The URL to the app, including `index.php` | `url="{$site_href}?-action=foo"`|
| `$dataface_url` | The URL to the Xataface directory | `icon="{$dataface_url}/images/myimg.png"`|
| `$table` | The name of the current table (i.e. the value of the `-table` param for this request). | `condition="$table=='my_table'"` |
| `$tableObj` | The `Dataface_Table` object for the current table. | `condition="$tableObj->hasField('some_field')"`|
| `$query` | Associative array of the current request vars. | `condition="$query['-table'] == 'some_table'"`|
| `$app` | Reference to the `Dataface_Application` object. | `url="{$app->url('-action=foo')"`|
| `$this` | Alias for `$app` | `url="{$this->url('-action=foo')}"`|
| `$record` | `Dataface_Record` object of the record passed to the current context.  This is used when building menus for a particular record.  May be null. | `url="$record->getURL('-action=some_action')"`|
| `$relationship` | `Dataface_Relationship` object of the relationship passed to the current context.  This is used when building menus for a particular relationship.  May be null. | `condition="$relationship->hasField('some_field')"`|

**WARNING: When using `$record` and `$relationship`, please be aware that these may be null in any given context.  You NEED to guard against this situation.**

**Guarding against null `$record`**:

If you call methods on `$record` or `$relationship` inside a `condition` directive (i.e. a Boolean expression), you should first check to see if they are null.  E.g.

**WRONG**: `condition="$record->hasField('some_field')"`

**CORRECT**: `condition="$record and $record->hasField('some_field')"`

If you call methods on `$record` or `relationship` inside a string directive, you should add an associated `xxx_condition` directive to the action that only returns true if it is save to execute the string directive.  E.g.

**WRONG:** 

~~~
url="{$record->getURL('-action=some_action')}"
~~~

**CORRECT**:

~~~
url="{$record->getURL('-action=some_action')}"
url_condition="$record"
~~~

Xataface will always execute the `url_condition` directive before trying to parse the `url` directive.  If `$record` is null, it will not execute the `url` directive, and by doing so, avoid a fatal error.

###Debugging Action Expressions

In order to avoid PHP notices when executing string and boolean expressions, Xataface suppresses errors during their execution.  Unfortunately, this makes it difficult to debug fatal errors that may occur as a result of executing an action expression.  The common symptom is *the blank white screen of death*.  If you are getting a blank white screen and you have no viable clues in your PHP error log, there is a good chance that there is an error happening during the execution of your of your action expressions.

You can debug such errors by enabling debugging in Xataface.  Simply add the following to the beginning of your `conf.ini` file:

~~~
debug=1
~~~

The refresh.  You'll see lots of debug messages.  Hopefully the last message will be your fatal error.







