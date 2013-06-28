<?php
/**

@page module_developer_guide_javascript Adding Custom Javascripts

- Return to @ref module_developer_guide
- Previous: @ref module_developer_guide_actions
- Next: @ref module_developer_guide_css

As of Xataface 2.0, there is a much greater emphasis on Web 2.0 and Javascript.  The @ref Dataface_JavascriptTool now allows you to build complex javascript applications with dependencies and have them compiled (and cached) at run-time.   You can develop your own Javascript libraries and include them with your module easily.  This tutorial describes how to bundle your javascripts, and how to make them available to your actions (and other actions too if you so desire).

@section module_developer_guide_dataface_javascripttool Dataface_JavascriptTool Primer

The @ref Dataface_JavascriptTool class provides all of the functionality necessary to build javascript applications.  We are going to make use of 3 main methods of this class for this tutorial:

-# Dataface_JavascriptTool::getInstance() - To obtain a reference to the request's JavascriptTool object.
-# Dataface_JavascriptTool::addPath() - To register our module's @p js directory as a location where Javascript files can be found.
-# Dataface_JavascriptTool::import() - To load a particular javascript file with the functionality that we want to use for our action.


@section module_developer_guide_javascript_goal Goal for This Tutorial

In this tutorial we are going to use Javascript to turn our "Hello World" action into a more interactive action where the user can type in their own name and have the text changed to "Hello &lt;Their Name&gt;" depending on what they enter into the text field.

@par Step 1 Modifying the Template

First let's modify our template to prepare for interactivity.  We need to do two things:

-# Add a @p span tag to the heading so that we can reference and change the "Hello World" text more easily.
-# Add a text field for the user to enter their name

The resulting template now looks like:
@code
{use_macro file="Dataface_Main_Template.html"}
    {fill_slot name="main_section"}
    	
    	<h1>Hello <span id="user-name">world</a></h1>
    	
    	<p>
    	    Please enter your name 
    	    <input type="text" id="user-name-field"/>
    	    <button id="update-user-name-btn">Update</button>
    	</p>
    
    {/fill_slot}
{/use_macro}
@endcode


Notice that we referenced the @p span, @p input, and @p button tags with @p id attributes so that they are easy to reference from Javascript.

@par Step 2 Create a Directory For Javascripts

We're going to store all of our javascript files inside a directory named @p js inside our module's directory.  I.e.
@code
modules/hello_world/js
@endcode

Further, we're going to follow the best practice of creating a sort of namespace for our scripts (just like we did with our templates from the previous section) so that we don't conflict with the script locations in other modules or Xataface itself.  Hence underneath our @p js directory we're going to create a directory: @p xataface/modules/hello_world and place our javascript file inside that.  I.e. the full path within our module's directory to our javascript file will be:
@code
js/xataface/modules/hello_world/hello_world.js
@endcode

@par Step 3 Create the Javascript File

Inside the hello_world.js file add the following:
@code
//require <jquery.packed.js>
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


Now there are a few things to notice about this Javascript segment:

-# The first line is a declaration that it requires the file @p jquery.packed.js .  This declaration is handled by the @ref Dataface_JavascriptTool at runtime to ensure that the jquery.packed.js file has been loaded.  This ensures that jQuery is available to use in this file.  The jquery.packed.js is located in the xataface/js directory which is automatically and always included in the Javascript Tool's list of javascript paths so that you can always reference files from it.
-# The enter contents of this file (except the @p require statement) is wrapped in a @p function structure.  This is a javascript technique for makeing namespaces so that variables that we create inside this file do not pollute the global namespace.  See <a href="http://stackoverflow.com/questions/1841916/how-to-avoid-global-variables-in-javascript">This thread</a> for more information about this technique.  It is best practice to always use this technique in your own javascript files to make the source base more manageable.
-# We listen for the @p click() event on the @p update-user-name-btn @p button and then change the "world" text to whatever text is entered into the text field.  

@par Step 4 Load the Javascript File

Now that you have created your javascript file, let's modify the @p hello_world action to use this javascript file.

@code
class actions_hello_world {

	function handle($params){
	
		$javascriptTool = Dataface_JavascriptTool::getInstance();
		$javascriptTool->addPath(
			dirname(__FILE__).'/../js',  // The Path to the js dir
			DATAFACE_SITE_URL.'/modules/hello_world/js'   // The URL to the js dir
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


The only change that we made was to register our @p js directory with the Javascript Tool so that it knows to look there for Javascript files.  Next we just import our hello_world.js file so that it will be executed on page load.

@par Step 5 Try the Action Again

At this point we're ready to try out our action.  Just point the browser to your hello_world action again (e.g. @p index.php?-action=hello_world

<img src="http://media.weblite.ca/files/photos/Screen_shot_2011-11-28_at_3.02.30_PM.png?max_width=640"/>


@section module_developer_guide_javascript_troubleshooting Troubleshooting

Shifting from a PHP-centric application to a javascript-centric application can present a bit of a learning curve while you get used to the new environment.  Sometimes debugging Javascript can get a little tricky.  The trick is to use the right tools and know where to look.  If you are debugging your application or developing it, the first thing you should do is turn on debugging in the Dataface_Javscript tool.  You can do this by adding the following section to your application's conf.ini file:

@code
[Dataface_JavascriptTool]
	debug=1
@endcode

This will prevent the Javascript Tool from minimizing the code.  It will also turn off the cache.  Once you have finished debugging you should remove this directive again (or comment it out) as it will cause performance to be much slower.

Once you have enabled debugging, the next step is to use your preferred Javascript debugging tool to debug your application.  I generally just use <a href="http://www.apple.com/safari/features.html#developer">Safari with the Develop menu enabled</a>, but most of the modern browsers now include some mechanism to debug javascript.


- Return to @ref module_developer_guide
- Previous: @ref module_developer_guide_actions
- Next: @ref module_developer_guide_css



*/
