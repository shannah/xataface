<?php
/**

@page module_developer_guide_first_module Creating Your First Module

- Return to @ref module_developer_guide
- Next: @ref module_developer_guide_actions

In this tutorial we will create a simple module that merely prints "Hello World" at the top
of all pages in the site.

@par Step 1 Create An Application

Creating a module is boring if you don't first have an application to test it out in.  So before moving onto the rest of this tutorial, make sure that you have an application available to enable your module in.

If you don't know how to create an application, please refer to the <a href="http://xataface.com/documentation/tutorial/getting_started">Xataface Getting Started</a> tutorial.

@attention For the remainder of this tutorial we will assume that we have created an application and it is located at 
@code
/var/www/myapp/
@endcode

@par Step 2 Create A "modules" Directory

If your application doesn't already have a @p modules directory, create one now. i.e. Create the directory:
@code
/var/www/myapp/modules/
@endcode

@attention This assumes that your application is located at /var/www/myapp/ .  Change the path accordingly for your application's installation location.

@par Step 3 Create a Directory For Your Module

We're going to call our module "Hello World" so we'll name the directory @p hello_world.  The directory is located at:
@code
/var/www/myapp/modules/hello_world
@endcode

@par Step 4 Create Module Class

All modules need to have at least a base class that serves as an entry point for the module.  This class should be located in a file named @p %modulename%.php and the class name should be @p modules_%modulename% .  E.g. The file should be located at @p /var/www/myapp/modules/hello_world/hello_world.php and the class file should contain the following contents:
@code
class modules_hello_world {

}
@endcode

@par Step 5 Implement the @p before_header Block

We want to display "Hello World" at the head of every page in the site so we'll implement the @p before_header block in our template.  Modules allow you to implement blocks and slots the same way as you can implement them in table Delegate classes and Application delegate classes.

@code
class modules_hello_world {
    function block__before_header(){
        echo "<h1>Hello World</h1>";
    }
}
@endcode

@see ModuleClass::block__blockname()
@see DelegateClass::block__blockname()
@see ApplicationDelegateClass::block__blockname()


@par Step 6 Activate the Module

At this point, the module is inactive because we haven't told our application to use the module.  To activate the module all we need to do is add a @p [_modules] section to the application's conf.ini file (if it doesn't yet exist), and add the following line:

@code
modules_hello_world=modules/hello_world/hello_world.php
@endcode

(The full modules section would look like:
@code
[_modules]
    modules_hello_world=modules/hello_world/hello_world.php
@endcode
)

@par Step 7 Load the Application in the Browser

Now point your web browser to your application and notice that "Hello world" is displayed in large print at the beginning of each page.

<img src="http://media.weblite.ca/files/photos/Screen_shot_2011-11-28_at_1.01.49_PM.png?max_width=640"/>


@section module_developer_guide_first_module_finding_slots Help with Slots

This tutorial leverages Xataface's built-in block/slot system that allows use to embed content into various locations of the templates.  You can always get a full list of the blocks and slots in a given template by turning on @p debug in your application.  The interface will then be rendered with the available blocks and slots rendered.

For more information about blocks and slots see <a href="http://xataface.com/wiki/block__blockname">The wiki page for block__blockname</a> or one of the following additional tutorial links dealing with Xataface user interface customization:

- <a href="http://xataface.com/documentation/tutorial/getting_started/changing-look-and-feel">Changing the Xataface Look and Feel</a>
- <a href="http://www.xataface.com/documentation/tutorial/customizing-the-dataface-look-and-feel">Customizing the Xataface Look and Feel</a>


@section module_developer_guide_first_module_troubleshooting Troubleshooting

@par I Don't See Hello World

If you don't see "Hello World" at the top of each page and there are no error messages, there are a few possible problems:

-# Your block function is not named correctly.  Ensure that your function is named exactly @p block__before_header .  Note the two underscores between @p block and @p before_header.
-# Your @p [_modules] section is named incorrectly.  
-# You have two @p [_modules] sections defined in your conf.ini file and one is overriding the other.  You can have at most one @p [_modules] section.

@section module_developer_guide_first_module_examples Some Example Module Classes

Since every Xataface module must have a module class, there are plenty of examples of the structure of such classes from existing modules.  The following are links to documentation pages for some module classes already in existence.  Some use more features than others.  Later tutorials will cover all of the possibilities of this class in more detail:

- <a href="http://xataface.com/dox/modules/ckeditor/latest/classmodules__ckeditor.html">The CKeditor Module</a>
- <a href="http://xataface.com/dox/modules/htmlreports/latest/htmlreports_8php_source.html">html_reports module</a>.
- <a href="http://xataface.com/dox/modules/switch_user/latest/classmodules__switch__user.html">Switch User Module</a>


- Return to @ref module_developer_guide
- Next: @ref module_developer_guide_actions

*/

?>
