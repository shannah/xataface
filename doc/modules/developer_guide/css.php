<?php
/**

@page module_developer_guide_css Adding Custom CSS Files

- Return to @ref module_developer_guide
- Previous @ref module_developer_guide_javascript
- Next @ref module_developer_guide_widget

There are two ways to include custom CSS files with your module and its actions:

-# Using the Dataface_Application::addHeadContent() method
-# Using the Dataface_CSSTool class

@par Reasons to use Dataface_Application::addHeadContent()

Using the Dataface_Application::addHeadContent() has the benefit of loading your CSS file at the beginning of the page load rather than at the end of it.  Therefore your styles will be picked up immediately.  

@par Reasons to use Dataface_CSSTool

The Dataface_CSSTool is closely integrated with Dataface_JavascriptTool so if your CSS file is related to a javascript file and needs to be bundled with it, then it may be easier to reference the file using the @p require-css declaration directly from a javascript file (and this will use the Dataface_CSSTool class).

Another benefit of using the Dataface_CSSTool is that it allows you to bundle multiple small CSS files together at run-time in a network-friendly compressed format.  This might allow you to organize your CSS files more logically and make it easier to maintain them moving forward (because it decouples the production format from the development format).

@see Dataface_CSSTool for more discusson on the merits of the two approaches.


@section module_developer_guide_css_goal Goal of this Tutorial

For this tutorial we are going to use the Dataface_CSSTool to include a style-sheet with our @p hello_world action.  We are just going to create some simple styles to demonstrate the steps involved in loading a custom CSS file.

@par Step 1 Create a css directory

We begin by creating a @p css directory inside our module's directory.  It will be located at:
@code
myapp/modules/hello_world/css
@endcode

Further we're going to add a directory structure underneath this @p css directory as a namespacing mechanism (so that we know that our CSS paths as referenced by the CSS tool will be unique.  We'll use the same convention as we used for our javascript files and templates:

@code
xataface/modules/hello_world
@endcode

@par Step 2 Create A CSS File

Now that we have our directory structure let's create a CSS file at 
@code
xataface/modules/hello_world/hello_world.css
@endcode

With the following contents:
@code
#user-name {
	color: red;
}
@endcode

So all we're doing here is changing the color of the "world" part of "Hello World" so that it is red.

@par Step 3 Register the @p css Directory with the CSS Tool

In our @p hello_world action we now need to register our @p css directory.  We do this with the following code:
@code
$cssTool = Dataface_CSSTool::getInstance();
$cssTool->addPath(
	dirname(__FILE__).'/../css', 
	DATAFACE_SITE_URL.'/modules/hello_world/css'
);
@endcode

The Dataface_CSSTool::addPath() method takes 2 parameters:

- The path to the @p css directory
- The URL to the @p css directory


@par Step 4 Import the CSS File

Now that we have added the @p css directory to the paths in the CSS Tool, we just need to import the CSS File.  We will do this using the @p require-css javascript directive at the beginning of the @p hello_world.js javascript file:
@code
//require-css <xataface/modules/hello_world/hello_world.css>
@endcode


Our @p hello_world action now looks like:

@code
class actions_hello_world {

	function handle($params){
	
		$javascriptTool = Dataface_JavascriptTool::getInstance();
		$javascriptTool->addPath(
			dirname(__FILE__).'/../js',  // The Path to the js dir
			DATAFACE_SITE_URL.'/modules/hello_world/js'   // The URL to the js dir
		);
		$cssTool = Dataface_CSSTool::getInstance();
		$cssTool->addPath(
			dirname(__FILE__).'/../css', 
			DATAFACE_SITE_URL.'/modules/hello_world/css'
		);
		
		$javascriptTool->import('xataface/modules/hello_world/hello_world.js');
		
		
		
		
		// Rest of the file is unchanged
		df_register_skin('hello world skin', dirname(__FILE__).'/../templates');
		df_display(
		    array(),
		    'xataface/modules/hello_world/hello_world_template.html'
		);
	}
}
@endcode

And our @p hello_world.js script now looks like:
@code
//require <jquery.packed.js>
//require-css <xataface/modules/hello_world/hello_world.css>
(function(){
	
	var $ = jQuery;
	$(document).ready(function(){
	
		$('#update-user-name-btn').click(function(){
		    // On click handler for the button with id update-user-name-btn
		    
		    // Take the value of the user-name-field and place it in the 
		    // user-name span:
		    $('#user-name').text($('#user-name-field').val());
			
		});
	});

})();
@endcode

@par Step 5 Try our Application Again

Point your web browser to the hello_world action again and it should look like:
<img src="http://media.weblite.ca/files/photos/Screen_shot_2011-11-28_at_3.53.02_PM.png?max_width=640"/>

Notice that the word "world" is now in red.


- Return to @ref module_developer_guide
- Previous @ref module_developer_guide_javascript
- Next @ref module_developer_guide_widget

*/
