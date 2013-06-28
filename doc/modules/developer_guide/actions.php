<?php
/**

@page module_developer_guide_actions Creating a Module With an Action

- Return to @ref module_developer_guide
- Previous: @ref module_developer_guide_first_module
- Next: @ref module_developer_guide_javascript

In the @ref module_developer_guide_first_module "last section" we developed a simple module that displays "Hello World" at the top of every page.  

In this tutorial we'll create our own custom action that can called from any application that includes our module.

@attention This tutorial assumes a familiarity with actions.  For more information about Xataface actions please refer to <a href="http://xataface.com/documentation/tutorial/getting_started/dataface_actions">Xataface Actions the Basics</a>.

@par Step 1 Create an @p actions directory

We start out by creating a directory for the actions of our module.  The directory will be located at:

@code
/var/www/myapp/modules/hello_world/actions
@endcode

@par Step 2 Create a PHP file for our Action

We'll call our action @ hello_world, so the PHP file will be located at @p actions/hello_world.php

Inside this file we'll create a basic action handler that simply displays "hello world":

@code
class actions_hello_world {
	function handle($params){
		echo "<h1>Hello World</h1>";
	}
}
@endcode

@see @ref ActionHandler for details about the structure of an action handler.

@par Step 3 Test Our Action

In your browser point to the URL @p path/to/myapp/index.php?-action=hello_world

You should see something like 

<img src="http://media.weblite.ca/files/photos/Screen_shot_2011-11-28_at_1.34.48_PM.png?max_width=640"/>


Notice that our action uses a blank screen and just displays "Hello World".  It doesn't use the Xataface look and feel for the rest of the app.  It also, noticeably, doesn't include the "Hello World" header defined in our @p block__before_header slot (from @ref module_developer_guide_first_module).  This is because we aren't using any templates to render the page.  We're just doing direct output with the @p echo command.

@section module_developer_guide_templates Using Templates

Right now our hello world action doesn't make use of any templates so it's quite plain.  We could provide a template in our application's @p templates directory and then reference it in our action using the df_display() function, but this wouldn't be good practice because it would make our module less portable (because not all applications would have our template).  What we would prefer to do is to package the templates with the module.

@par Packaging Templates With the Module

Packaging templates with a module is easy.  You just create a @p templates directory inside the module directory, then you just need to progammatically register this directory with Xataface's skin tool before you use it.

@par Step 1 Creating the @p templates directory

Create a directory inside the @p hello_world module directory named @p templates  i.e.
@code
modules/hello_world/templates
@endcode

@par Step 2 Create a template

We are going to create a template for our @p hello_world action that extends from the @p Dataface_Main_Template.html template (the main Xataface template).  So we create a template named @p hello_world_template.html inside the @p templates directory. i.e.
@code
modules/hello_world/templates/hello_world_template.html
@endcode

with the following contents:

@code
{use_macro file="Dataface_Main_Template.html"}
    {fill_slot name="main_section"}
    	
    	<h1>Hello world</h1>
    
    {/fill_slot}
{/use_macro}
@endcode


@par Step 3 Register the @p templates directory

Before we can use our template from our @p hello_world action, we need to register our custom @p templates directory with the @ref Dataface_SkinTool so that it knows the look for templates there.  We can do this with the df_register_skin() function:

Then we'll use the df_display() function to display our template.  After these changes, our hello_world.php action will look like:

@code
class actions_hello_world {

	function handle($params){
	
		df_register_skin('hello world skin', dirname(__FILE__).'/../templates');
		df_display(array(), 'hello_world_template.html');
	}
}
@endcode


@par Step 4 Trying Our Action Again

Point the browser to the application again at @p index.php?-action=hello_world
You should notice that the action is now rendered with the full Xataface look and feel.

<img src="http://media.weblite.ca/files/photos/Screen_shot_2011-11-28_at_1.51.34_PM.png?max_width=640"/>


@section module_developer_guide_best_practice Best Practice - Namespaces

Since all templates are loaded with the same namespace and are set up to override each other, it is a good practice to develop a unique directory structure under your @p templates directory so that you can uniquely identify your module's templates.  The naming convention that is recommended is to place all templates in a directory structure as folows:

@code
templates/xataface/modules/%modulename%/%templatename%.html
@endcode

Then when we are referring to the template with the df_display() method we would include the path starting from the @templates directory.  In accordance with this convention let's change the location of our hello_world_temlate.html to be located at:
@code 
templates/xataface/modules/hello_world/hello_world_template.html
@endcode

And change the action as follows:
@code
class actions_hello_world {

	function handle($params){
	
		df_register_skin('hello world skin', dirname(__FILE__).'/../templates');
		df_display(
		    array(),
		    'xataface/modules/hello_world/hello_world_template.html'
		);
	}
}
@endcode


- Return to @ref module_developer_guide
- Previous: @ref module_developer_guide_first_module
- Next: @ref module_developer_guide_javascript

*/
?>
