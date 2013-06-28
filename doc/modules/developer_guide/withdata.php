<?php
/**

@page module_developer_guide_withdata Module-Associated Data

- Return to @ref module_developer_guide
- Previous:  @ref module_developer_guide_widget

@par Contents:
-# @ref module_developer_guide_withdata_step1
-# @ref module_developer_guide_withdata_step2
-# @ref module_developer_guide_withdata_step3
-# @ref module_developer_guide_withdata_step4
-# @ref module_developer_guide_withdata_step5
-# @ref module_developer_guide_withdata_step6
-# @ref module_developer_guide_withdata_step7
-# @ref module_developer_guide_withdata_step8
-# @ref module_developer_guide_withdata_step9 


So far we have created a module that has included:
- @ref module_developer_guide_first_module "A slot"
- @ref module_developer_guide_actions "An action"
- @ref module_developer_guide_javascripttool "Custom Javascript"
- @ref module_developer_guide_css "Custom Stylesheets"
- @ref module_developer_guide_widget "A Custom Widget"

None of these examples included any module-associated data.  In this section we will extend the "hello_world" module to include a blog component.  This will require us to introduce some database tables that are associated with the module.

@section module_developer_guide_withdata_req Module Requirements

-# Should add an action to the "Table Links" to access the blog.
-# All config files and delegate classes for the blog should be included inside the module.
-# Should define slots that can be included in templates to show the 5 most recent blog posts.

@section module_developer_guide_withdata_step1 Step 1: Create Database Structure

The data structure for this blog will be very basic: a single table.  We'll call this table: @p xf_blog_posts

It's definition is: @code
create table xf_blog_posts (
    blog_post_id int(11) not null auto_increment primary key,
    post_title varchar(255) not null,
    post_content longtext,
    date_posted datetime,
    last_modified datetime,
    posted_by varchar(100)
)
@endcode

Create this table in your application's database.

@note In a later section we'll see how to have this table automatically created when the module is activated.  For now (testing purposes) we'll just create the table in our application's database so that we can work with it.

@section module_developer_guide_withdata_step2 Step 2: Add Link to UI

In order for users to access the blog we need them to add a link to the xf_blog_posts table in their application.  There are two ways to achieve this:

-# Instruct module users to add the link themselves in the conf.ini file
-# Have the module automatically add an action in the appropriate category to appear in the UI.

We're going to take the second approach here.  We want our module to appear along with the links to all of the tables at the top of the page.

@note This example requires the new Xataface 2.0 look and feel to be enabled (i.e. the @p g2 module).  This is because that module allows us to add links to the top left by way of the actions.ini files.  The 1.0 look doesn't allow this for that part of the UI.

@par Create actions.ini file
First create an actions.ini file for the module with the following contents: @code
[blog]
   label="Blog"
   url="{$site_href}?-table=xf_blog_posts"
   category="top_left_menu_bar"
   order=10
   selected_condition="$query['-table'] == 'xf_blog_posts'"
@endcode

@note For more information about the actions.ini file and action syntax, see <a href="http://xataface.com/wiki/actions.ini_file">actions.ini file</a>.

@par Make sure that the g2 module is installed and enabled
In the conf.ini file, make sure the g2 module is enabled as this is required for the top_left_menu_bar category to have any meaning: @code
[_modules]
    modules_g2=modules/g2/g2.php
@endcode

@par Try it out:
<img src="http://media.weblite.ca/files/photos/Screen_shot_2011-11-30_at_10.05.34_AM.png?max_width=640"/>

@par New Post
<img src="http://media.weblite.ca/files/photos/Screen_shot_2011-11-30_at_10.05.42_AM.png?max_width=640"/>

@par Summary So Far
We have now created a module that uses a table and we have successfully linked our table into the application's menu so that it is accessible by users of the system.  This is not very useful yet, however, as we haven't yet introduced a way to package this database structure into the module to that it can be ported to other applications.  In addition we need to add some configuration for our blog so that, for example, the @p date_posted, @p last_modified, and @p posted_by fields get populated automatically.

@section module_developer_guide_withdata_step3 Step 3: The fields.ini file

In order to configure our table, we need to define a fields.ini file.  The normal location of a fields.ini file for a table would be at @p %APPLICATION_PATH%/tables/xf_blog_posts/fields.ini but we want this configuration to be packaged with our module, so instead we'll place it at: @code
%APPLICATION_PATH%/modules/hello_world/tables/xf_blog_posts/fields.ini
@endcode

Our basic fields.ini file will contain: @code
[date_posted]
	widget:type=hidden
	timestamp=insert
	
[last_modified]
	widget:type=hidden
	timestamp=update
	
[posted_by]
	widget:type=hidden
@endcode

@par Tell Xataface Where The fields.ini File Is
We need to do one more thing in order to tell Xataface that it should look in our module directory for the fields.ini file instead of the usual location.  Add the following to the constructor of our module class (i.e. @p hello_world.php): @code
Dataface_Table::setBasePath('xf_blog_posts', dirname(__FILE__));
@endcode
What this does is tell Xataface that the base path for the @p xf_blog_posts table is our hello world module so it will look for the "tables" directory with the fields.ini file etc.. inside our module's directory - at least when it is looking for config for the @p xf_blog_posts table.

Now the new record form for the xf_blog_posts table should look like:
<img src="http://media.weblite.ca/files/photos/Screen_shot_2011-11-30_at_10.28.38_AM.png?max_width=640"/>

Notice that the @p date_posted, @p last_modified, and @p posted_by fields are now hidden.  We've specified the @p date_posted and @p last_modified fields to be automatically populated with the appropriate timestamps (using the @p timestamp directive in the fields.ini file), but we still need to add some logic to populate the @p posted_by field.  We will do this by implementing the DelegateClass::beforeInsert() method.

@section module_developer_guide_withdata_step4 Step 4: The Table Delegate Class

Our table delegate class will be located at:
@code
%APPLICATION_PATH%/modules/hello_world/tables/xf_blog_posts/xf_blog_posts.php
@endcode

The contents will start with: @code
<?php
class tables_xf_blog_posts {
    function beforeInsert($record){
        if ( class_exists('Dataface_AuthenticationTool') ){
            $record->setValue(
                'posted_by', Dataface_AuthenticationTool::getInstance()->getLoggedInUserName()
            );
        }
    }
}
@endcode

@note We check that the Dataface_AuthenticationTool class exists before trying to call any methods on it.  This is because if the application doesn't implement authentication, then it may not have loaded the Authentication Tool - and we want this module to be portable.

Now if you insert a new post then look at the post in the database you should see the username of the currently logged in user reflected accurately.

@section module_developer_guide_withdata_step5 Step 5: Permissions

So far we have completely ignored permissions.  I.e. How do we decide which users can post blog posts or which blog posts they can view, edit, or delete.  This can be a somewhat complex problem because when developing a module you often don't have any knowlege of how the user accounts and permissions will be managed in the application in which the module will be eventually installed. 

If we were developing a blog application we would implement permissions simply by implementing a DelegateClass::getPermissions() or DelegateClass::getRoles() method where we would grant permissions based on factors such as the user's role, or who owns the post.  In our case we have no knowledge of how the end application may define its roles so we can't reference them directly from a DelegateClass::getPermissions() method inside our module.

There are many strategies that we can employ for infusing our blog module with permissions.  Some of these include:

-# Define a simple, rigid permissions system that doesn't depend on anything outside the module (e.g. we could allow all users to view blog posts, all logged-in users to post new blog posts, and allow only the post owner to edit or delete a post (we know this information from the @p posted_by field).
-# Defer permissions wholely to the application allowing them to implement their own getPermissions() method for the table.
-# Add some permissions (via the permissions.ini file) that are strictly for use with the blog module and allow the application to grant these permissions in its ApplicationDelegateClass::getPermissions() method... then our module could check those permissions in our own getPermissions() method where we would translate them into standard permissions for the xf_blog_posts table.  (This sounds complex, but really its not).
-# Implement a hybrid of the above approaches.

For this tutorial we are going to implement a hybrid of option #1 and option #2 above.  We will implement #1 as the default functionality, and we will allow the application developer to define its own permissions method (option #2) if they want a more complex structure.

@subsection module_developer_guide_withdata_step5p1 Default Permissions

For our default permissions system, we'll just have 3 levels of users, and we'll create corresponding roles for them in the module's permissions.ini file.

-# BLOG PUBLIC USER - A read only role
-# BLOG REGISTERED USER - Allows reading and posting new posts.
-# BLOG POST OWNER - Allows reading and posting, and editing.  This role is assigned based on the @p posted_by field.

@par Create the permissions.ini file
We create a permissions.ini file in our module's directory with the following contents: @code
[BLOG PUBLIC USER extends READ ONLY]
[BLOG REGISTERED USER extends BLOG PUBLIC USER]
    new=1
[BLOG POST OWNER extends BLOG REGISTERED USER]
    edit=1
    delete=1
@endcode


@par create the getRoles() method:
Now in our @p xf_blog_posts delegate class we add the following DelegateClass::getRoles() implementation: @code
function getRoles($record){
    $username = Dataface_AuthenticationTool::getInstance()->getLoggedInUserName();
    if ( $record and $username and $record->val('posted_by') == $username ){
        return 'BLOG POST OWNER';
    } else if ( $username ){
        return 'BLOG REGISTERED USER';
    } else {
        return 'BLOG PUBLIC USER';
    }
}
@endcode


Now if you try out your application with different user accounts, logged in or no, you should notice that:
- If not logged in, you can view all of the blog posts but can't edit them, or post new posts.
- If logged in, you can post new blog posts but can only edit posts that were posted by you.


@subsection module_developer_guide_withdata_step5p2 Allowing Application to Override Permissions

The default permissions that we set up seem logical, but one inescapable fact of life is that there is no such thing as one-size-fits-all.  In order for this module to be truly useful to application developers, they need to be able to override the permissions to fit their own needs.  We will allow them to do this by leveraging the DelegateClass::getDelegate() method to return an alternate delegate class at our discretion.

Our DelegateClass::getDelegate() method will check the application's tables/xf_blog_posts directory to see if they have defined their own delegate class.  If so we'll return an instance of that object.  Otherwise it just returns null to indicate that our delegate will remain in the authority.

@note If we allow the application to implment their own delegate class it is a good idea to recommend that their class extends from our class so that they can still take advantage of the other customizations that have been defined in our delegate class (e.g. the DelegateClass::beforeInsert() trigger).

@attention Watch out for name conflicts in the delegate class.  The application's delegate class cannot have the same name as our delegate class so it is best to direct users to employ a slightly different name for their application delegate class.

@par Our getDelegate() method: 
@code
function getDelegate(){
	$altpath = DATAFACE_SITE_PATH.'/tables/xf_blog_posts/xf_blog_posts.php';
	if ( is_readable($altpath) ){
		include $altpath;
	}
	if ( class_exists('tables_xf_blog_posts_override') ){
		return new xb_blog_posts_override;
	}
	return null;
}
@endcode

@note In this example we are requiring application developers to name their optional delegate class @p xb_blog_posts_override.  Make sure that you include this in your documentation so that application developers know how to extend your module.

An example overriding delegate class might look like: @code
<?php
class tables_xf_blog_posts_override extends tables_xf_blog_posts {
    
    public function getRoles($record){
    	$user = Dataface_AuthenticationTool::getInstance()->getLoggedInUser();
    	if ( $user and $user->val('role') == 'ADMIN' ){
    		// Admin users should just use the permissions defined in
    		// the applicaiton delegate class (all perms usually)
    		return null;
    	} else {
    		return parent::getRoles($record);
    	}
    }

}

@endcode

@attention It is important that the override delegate class attempt to use the default behavior of the module's parent delegate class whenever possible to ensure that it isn't overriding essential behavior.  E.g. the beforeInsert() method of the module's delegate class performs important functions that need to be run regardless of whether the application wants to override it.  It is best practice in these cases to check if the parent method exists and try to call it where appropriate inside the child.  E.g.: @code
function beforeInsert($record){
    if ( method_exists('tables_xf_blog_posts', 'beforeInsert') ){
        $res = parent::beforeInsert($record);
        if ( PEAR::isError($res) ) return $res;
    }
    
    // Now do custom stuff
}
@endcode


@section module_developer_guide_withdata_step6 Step 6: Details

Before moving onto the final steps of packaging our module, there is one nagging thing that we should really polish up:  The label for our table.  The "New Record" button says "New xf_blog_post".  This is really ugly.  Let's change it to "Blog Posts" using the global @p label directive of the fields.ini file: @code
label="Blog Posts"
@endcode

<img src="http://media.weblite.ca/files/photos/Screen_shot_2011-11-30_at_11.38.45_AM.png?max_width=640"/>

Ahh.. much better.


@section module_developer_guide_withdata_step7 Step 7: Packaging and Versioning

If we were to distribute our module at this point, we would need to include directions on how to build the xf_blog_posts MySQL table manually.  What if we improve our module later on and want to distribute the changes?  We would need to again direct our users to modify their xf_blog_posts table to correspond with our changes.  This is inconvenient and error-prone.  Thankfully, Xataface offers a better way with its packaging and versioning features.

@par Create the version.txt File:
All Xataface modules should include a plain text file named @p version.txt that marks the version number of the module.  This file follows the same format as the Xataface @p version.txt file: @code
x.y.z w
@endcode
Where @p x.y.z is the human-readable version number (e.g. 1.3.1) and @p w is the ever-incrementing build number (e.g. 1256).  Every time you release a new version you should increment this number so that Xataface can tell that the file system version of your module is newer than the version currently in the database.

We'll start our module at version @p 0.1 (human-readable) and with a build number @p 1. So our version.txt file will look like: @code
0.1 1
@endcode


@subsection module_developer_guide_withdata_step8_installer The installer.php file

Now that we have a means to marking the file system version of our module, let's create the @p installer.php file to tell Xataface what it has to do to bring the database inline with the current file system version.  Our initial installer.php file will be located in the root of the module's directory (i.e. @p %APPLICATION_PATH%/modules/hello_world/installer.php, and it will contain the following: @code
<?php
class modules_hello_world_installer {
    function update_1(){
    
    	$sql[] = "create table if not exists xf_blog_posts (
					blog_post_id int(11) not null auto_increment primary key,
					post_title varchar(255) not null,
					post_content longtext,
					date_posted datetime,
					last_modified datetime,
					posted_by varchar(100)
				)";
				
		df_q($sql);
    }
}
@endcode

Things to notice:

-# The class name follows the naming convention @p modules_%modulename%_installer.
-# The method @p update_1() will be executed by Xataface in the event that the database version of the module is lower than 1 and the file system version is greater than or equal to 1.  This conveniently serves as an install script for your module since the first time your module is activated, the database version is effectively 0, so the update_1() method would be called to create the xf_blog_posts table.

@note Xataface stores the database module version numbers in a table called @p dataface__modules.  If you need to clear the database version number or play with it for development purposes, you can modify the record corresponding to your module in this table.

@see <a href="http://www.sjhannah.com/blog/?p=144">Application Versioning and Synchronization with Xataface</a> for a full discussion of Xataface's versioning and synchronization features.


@par Starting Fresh
Let's try out our packaging and versioning by deleting the xf_blog_posts table from our database, and also deleting the corresponding entry in the @p dataface__modules table.  Then load the application in your browser again.

If everything went as planned you should see no difference in your application.  If you check the database, your @p xf_blog_posts table has been recreated and the dataface__modules table now has an entry for your module marking it with version 1:
<img src="http://media.weblite.ca/files/photos/Screen_shot_2011-11-30_at_12.00.50_PM.png?max_width=640"/>

@section module_developer_guide_withdata_step8 Step 8: Distributing Your Module

Now that the module has a self-contained installer you should be able to package it up and distribute it.  Installing the module in a different application is as simple as adding and entry to the @p [_modules] section of the conf.ini file.  If you do distribute your module, make sure to include documentation so that people know how to use it.  Also be sure to let the rest of us know about your module on the <a href="http://xataface.com/forum">Xataface forum</a>.

@section module_developer_guide_withdata_step9 Download The Module Source

You can download the source for this module <a href="http://xataface.com/dox/core/examples/module_developer_guide/hello_world.with-data.tar.gz">here</a>.

- Return to @ref module_developer_guide
- Previous:  @ref module_developer_guide_widget




*/
?>
