<?php
/**

@page module_developer_guide_widget Creating Custom Widgets

- return to @ref module_developer_guide
- Back to @ref module_developer_guide_css
- Next @ref module_developer_guide_withdata

@par Contents of this Section:
 -# @ref module_developer_guide_widget_step1
 -# @ref module_developer_guide_widget_step2
 -# @ref module_developer_guide_widget_step3
 -# @ref module_developer_guide_widget_step4
 -# @ref module_developer_guide_widget_step5
 -# @ref module_developer_guide_widget_step6
 -# @ref module_developer_guide_widget_step7

The previous sections have been laying the foundation for this section, in what will be our first "useful" module.  Xataface comes with a set of built-in widgets that are quite useful, but once in a while, you will likely find yourself in need of a widget that doesn't yet exist.  The internet is full of jQuery plugins that can easily be converted into Xataface widgets with very little effort.

Some Javascript plugins that have already been adapted into Xataface widgets include:

- <a href="http://xataface.com/dox/modules/ckeditor/latest/">CKeditor</a>
- <a href="http://xataface.com/dox/modules/datepicker/latest/">Datepicker</a>
- <a href="http://xataface.com/dox/modules/depselect/latest/">Depselect</a>
- <a href="http://xataface.com/dox/modules/tagger/latest/">Tagger</a>

@section module_developer_guide_widget_strategy The Strategy

The general strategy for creating a widget is:

-# Add CSS classes and HTML attributes to a simple text input so that we can access all necessary information from javascript
-# Transform text inputs matching the given attributes or CSS classes into more complex widgets using Javascript.

For this tutorial we will be creating a Color Picker widget based on the <a href="http://www.eyecon.ro/colorpicker/">eyecon colorpicker</a>.


@section module_developer_guide_widget_step1 Step 1: Create a Widget Handler

Xataface forms are based on <a hef="http://pear.php.net/package/HTML_QuickForm/">HTML_QuickForm</a> for its base set of widgets, but it uses a Widget Handler as a wrapper around these widgets as a means of configuring them.   These wrappers are known as Widget handlers and they are managed by the @ref Dataface_FormTool class.

A widget handler has to be able to do at least three things:

-# Build a widget (returning an <a href="http://pear.php.net/package/HTML_QuickForm/docs/3.2.13/HTML_QuickForm/HTML_QuickForm_element.html">HTML_QuickForm_element</a> object.)
-# Retrieve a value stored in the widget.
-# Store a value in the widget.

In most cases you can get by with only defining a single method (buildWidget()) in the widget handler and let Xataface just use its default handler to load and store the values in the widget.


@par Creating the Widget Handler Script

There is no set place where the widget handler needs to go.  Let's just add it to the root directory of our module, and call it @p colorpicker_widget.php.  Add the following content to this file:

@code
class Dataface_FormTool_colorpicker {
	
	// Builds a widget for the given record
	function buildWidget($record, &$field, $form, $formFieldName, $new=false){
		
	    // Obtain an HTML_QuickForm factory to creat the basic elements
	    $factory = Dataface_FormTool::factory();
	    
	    // Create a basic text field
	    $el = $factory->addElement('text', $formFieldName, $widget['label']);
	    
	    return $el;
	}
}
@endcode

@see WidgetHandler::buildWidget() for details of the parameters of this method and how they should be used.

The above widget handler doesn't do anything fancy yet.  It simply creates a plain text field.  After we have our widget working as a plain text field, we'll go back and transform it into a color picker.


@attention I chose to base this widget on the 'text' widget because text widgets are easy to work with.  If your widget will be storing multiple lines of data, you should use a textarea widget as a base instead to prevent data from being clipped inadvertently.  In our case we are only storing colors though so a text widget makes sense as a base to build upon.

@section module_developer_guide_widget_step2 Step 2: Register the Widget Handler

Before we can use our new widget, we need to register the widget handler with the form tool.  We'll make use of the @p Dataface_FormTool::init event that is fired when the form tool is first created.  This allows us to avoid loading the FormTool into memory unnecessarily.  We'll do all of this in our module class's constructor as this is guraranteed to be executed at the beginning of every request that the module is enabled for.

@code
class modules_hello_world {

    // The constructor for the module class.  Executed at the beginning
    // of each request
    public function __construct(){
        
        $app = Dataface_Application::getInstance();
        
        
        if ( !class_exists('Dataface_FormTool') ){
        	// If the formtool is not loaded then we don't 
        	// want to load it here... we'll just register
        	// the _registerWidget() method to run  as soon
        	// as the FormTool is loaded.
			$app->registerEventListener(
				'Dataface_FormTool::init', 
				array($this, '_registerWidget')
			);
		} else {
			// If the formTool is already loaded, then we'll
			// register the widget directly
			$this->_registerWidget(Dataface_FormTool::getInstance());
		}
    }
    
    // Function to register our widget with the form tool.
    public function _registerWidget(Dataface_FormTool $formTool){
        $formTool->registerWidgetHandler(
            'colorpicker', 
            dirname(__FILE__).'/colorpicker_widget.php',
            'Dataface_FormTool_colorpicker'
        );
    }
}
@endcode


@section module_developer_guide_widget_step3 Step 3: Use The Widget For a Field

Now that we have registered our widget, we should be able to use the widget.  Let's set up a simple table in the database for this purpose:
@code
create table color_profile (
    profile_id int(11) not null auto_increment primary key,
    profile_name varchar(32),
    foreground_color varchar(32),
    background_color varchar(32)
)
@endcode

And we'll create a fields.ini file for this table in the @p myapp directory:
@code
myapp/tables/color_profile/fields.ini
@endcode

We will use our new colorpicker widget as the widget for both the @p foreground_color and @p background_color fields: 
@code
[foreground_color]
    widget:type=colorpicker
    
[background_color]
    widget:type=colorpicker
@endcode


@par Try Out Our Widgets

Now that all of the pieces are in place, let's load up a new record form for the @p color_profile table and see what it looks like.

@note You can load the new record form for the @p color_profile table by pointing your web browser to your application's index.php page with the GET parameters @p -table=color_profile and @p -action=new

<img src="http://media.weblite.ca/files/photos/Screen_shot_2011-11-29_at_11.57.54_AM.png?max_width=640"/>


If everything is working correctly, you should see regular text fields for the @p foreground_color and @p background_color fields.  This may be slightly disappointing, but this is what we want.  We'll convert these into more interactive color-picker widgets in the next few steps.  At this point we just want to be sure that our custom widget handler is working.

@attention If you do not see a form like the one above but instead see an error message, you'll need to go back through your code to make sure that there are no typos...   Get this working before moving onto the next steps.


@section module_developer_guide_widget_step4 Step 4: Add Javascripts and CSS Files

Before we can convert our text fields into color pickers, we need to make sure that the javascripts, CSS files, and images for the colorpicker widget that we are planning to use are accessible to our project.  Specifically we need to add these files to our project in such a way that the Dataface_JavascriptTool and Dataface_CSSTool can work with them.

@par Downloading The Color Picker Files

We start by downloading the Color Picker source files from the <a href="http://www.eyecon.ro/colorpicker/#download">EyeCon</a> website.
After unzipping the ZIP file we should have the following directory structure:

<img src="http://media.weblite.ca/files/photos/Screen_shot_2011-11-29_at_12.14.44_PM.png?max_width=640"/>

So we have 3 types of content to contend with:

-# CSS Files
-# Images
-# Javascript Files

@attention If possible it is important to keep all images in the same place relative to the CSS files or some of the path dependencies may be broken at runtime.  Alternatively you can go through the CSS files to alter the paths to all referenced images to mark the new location.

@par Adding the Javascript Files To Our Module

The first thing we'll do is to add the Javascript files to our module.  In order to keep things separated, we'll place them inside our namespace inside the js directory.  A good location might be
@code
js/xataface/modules/hello_world/colorpicker
@endcode

@attention We will copy all of the javascript files except @p jquery.js because we have our own version of jQuery with the Xataface distribution that we'll be using instead.

Once we have copied all of the javascript files we should have the following directory structure inside our module:

<img src="http://media.weblite.ca/files/photos/Screen_shot_2011-11-29_at_12.24.08_PM.png?max_width=640"/>


@par Adding the CSS Files and Images To Our Module

Similarly, we'll add the CSS files in a namespaced directory structure so that we can reference them uniquely.  We'll place both the @p css and @p images directories inside the 
@code
css/xataface/modules/hello_world/colorpicker
@endcode
directory.

When we're done copying these files, the directory structure of our module will look like:

<img src="http://media.weblite.ca/files/photos/Screen_shot_2011-11-29_at_12.27.53_PM.png?max_width=640"/>


@section module_developer_guide_widget_step5 Step 5: Creating a Javascript Bootstrap For the Widget

Now that we've added all of the necessary javascript and CSS files for the colorpicker widget, we need to create our own thin bootstrap code that converts our text fields into color pickers.

Before we can do this, however, we need to add a CSS class or attribute to our text fields so that we are able to identify the colorpicker widgets in Javascript.  We modify our Widget handler (@p colorpicker_widget.php) in order to accommodate this:

@code
class Dataface_FormTool_colorpicker {
	
	// Builds a widget for the given record
	function buildWidget($record, &$field, $form, $formFieldName, $new=false){
		
	    // Obtain an HTML_QuickForm factory to creat the basic elements
	    $factory = Dataface_FormTool::factory();
	    
	    $atts = array(
	    	'class' => 'xf-colorpicker'
	    );
	    
	    // Create a basic text field
	    $el = $factory->addElement('text', $formFieldName, $widget['label'], $atts);
	    
	    return $el;
	}
}
@endcode


All we've done here is create an @p $atts array where we specify a @p class attribute that is to be added to all color picker fields.  We then add this as the 4th parameter of the @p $factory->addElement() call (a call to HTML_QuickForm::addElement).

In short, all this does is add the css class @p xf-colorpicker to all instances of our colorpicker widget.  This will allow our Javascript bootstrap code that we're about to write to identify which fields are colorpicker fields, and hence which ones should be "decorated".

We'll place our Javascript bootstrap code into a new file.  It's location doesn't matter as long as it is located somewhere inside the module's @p js directory.  We'll place it at
@code
js/xataface/modules/hello_world/colorpicker_widget.js
@endcode

Place the following code inside this file:
@code
//require <jquery.packed.js>
//require <xataface/modules/hello_world/colorpicker/colorpicker.js>
//require-css <xataface/modules/hello_world/colorpicker/css/colorpicker.css>
(function(){
    var $ = jQuery;
    
    // We wrap our code in registerXatafaceDecorator so that it is executed
    // whenever new content is added to the page.  This is important if you
    // want your widget to work in compound widgets like the grid widget.
    registerXatafaceDecorator(function(node){
    	
    	// Find all elements with the xf-colorpicker CSS class
    	// and convert them into a ColorPicker()
    	$('input.xf-colorpicker', node).ColorPicker({
    	    onSubmit: function(hsb, hex, rgb, el) {
		        $(el).val(hex);
		        $(el).ColorPickerHide();
	        },
	        onBeforeShow: function () {
		        $(this).ColorPickerSetColor(this.value);
	        }
	    });
    	
    });

})();
@endcode

There isn't much in ths bootstrap file, but there's a lot going on.

-# The first three lines declare the other javascript and CSS files that should be included.  This is where we include jQuery and the ColorPicker javascript and CSS files that we just installed in the last step.
-# We wrap the whole thing inside an anonymous Javascript function to ensure that we don't pollute the global namespace with our code.
-# We wrap our "decoration" code inside the @p registerXatafaceDecorator function so that it will be executed for any part of the page that is added even after page load.  This is important if you want your widget to work properly inside compound widgets like the grid widget.
-# We apply the @p ColorPicker() jQuery plugin to all elements with the @p xf-colorpicker CSS class.

@section module_developer_guide_widget_step6 Step 6: Registering the Javascript Bootstrap


Now that we have created the Javascript bootstrap we need a way to ensure that it will be run whenever a form is rendered.  Essentially, this just requires us to import the @p colorpickier_widget.js using Dataface_JavascriptTool::import(), but we would prefer to do this in the most efficient way possible, such that the file is only imported if the colorpicker is going to be rendered.  And taking an additional step back, we also need to ensure that our module's @p js and @p css directories are registered with the application when necessary.  Otherwise our import won't work.

In the @ref module_developer_guide_dataface_javascripttool "previous section" we used Dataface_JavascriptTool::addPath() method to register our @p js directory.  However we only did this inside the @p hello_world action.  This code is only executed for that one action and won't be executed for the @p edit form or other Xataface actions that may require the colorpicker widget to be rendered.  A simple solution would be to perform this registration inside our module's constructor, but then we may be adding unnecessary overhead to our application since most of the application has no need for our scripts.

To succinctly rephrase, our problem is:

-# We want our Javascript files to be available to the Dataface_JavascriptTool when they are needed and only when they are needed.  (I.e. we don't want to load them if they are not needed as it adds to the overhead of the application).

Properties of the ideal solution to this problem are outlined in the wording of the problem itself.  Namely, the ideal solution should:

-# Make the CSS and Javascript files available to any part of the application that requires them.
-# Not load the CSS or Javascript files nor register the @p css or @p Javascript directories with Dataface_JavascriptTool unless they are needed.


To achieve this goal we will encapsulate the code that registers these files into our module's class as a method, and use a flag to ensure that they are loaded at most once.

@p hello_world.php:
@code
<?php
class modules_hello_world {
	
	// ... Other contents of this class hidden here ......
	
	private $jsLoaded = false;
	
	// Function to register the module's javascript and css
	// files
	function loadJs(){
	    if ( !$this->jsLoaded ){
	        $this->jsLoaded = true;
	        
	        $jsTool = Dataface_JavascriptTool::getInstance();
	        $jsTool->addPath(
	            dirname(__FILE__).'/js', 
	            DATAFACE_SITE_URL.'/modules/hello_world/js'
	        );
	        
	        $cssTool = Dataface_CSSTool::getInstance();
	        $cssTool->addPath(
	            dirname(__FILE__).'/css',
	            DATAFACE_SITE_URL.'/modules/hello_world/css'
	        );
	    }
	}
}
@endcode


Having this functionality encapsulated in a single method of our module makes it a simple matter to register the module's javascript and css assets on demand.  

@note You can always access the module file using Dataface_ModuleTool::loadModule() e.g. @code
$mod = Dataface_ModuleTool::getInstance()->loadModule('modules_hello_world');
$mod->loadJs();
@endcode


Now to complete our solution, let's add load our bootstrap javascript code from our widget handler (hello_world_widget.js):
@code
class Dataface_FormTool_colorpicker {
	
	// Builds a widget for the given record
	function buildWidget($record, &$field, $form, $formFieldName, $new=false){
		
	    // Obtain an HTML_QuickForm factory to creat the basic elements
	    $factory = Dataface_FormTool::factory();
	    
	    $atts = array(
	    	'class' => 'xf-colorpicker'
	    );
	    
	    // Create a basic text field
	    $el = $factory->addElement('text', $formFieldName, $widget['label'], $atts);
	    
	    // Load our bootstrap colorpicker javascript code so that it runs
	    // on page load.
	    $mod = Dataface_ModuleTool::getInstance()->loadModule('modules_hello_world');
	    $mod->loadJs();
	    Dataface_JavascriptTool::getInstance()->import(
	        'xataface/modules/hello_world/colorpicker_widget.js'
	    );
	    
	    
	    return $el;
	}
}
@endcode


All we've done here is to call our loadJs() method on the hello_world module class to load our javascript and css directories.  Then we imported the bootstrap code so that it will run on page load.  Notice that we do all this inside the buildWidget() method of our widget handler.  This ensures that it will only run the bootstrap code if a colorpicker widget has been built (and thus likely will exist on the final page output).


@section module_developer_guide_widget_step7 Step 7: Trying Out Our Widget

Now if you reload the new record form for the @p color_profile table and click on one of the colorpicker fields, you should see something like:

<img src="http://media.weblite.ca/files/photos/Screen_shot_2011-11-29_at_1.39.12_PM.png?max_width=640"/>


If you don't see anything like this, there see @ref module_developer_guide_widget_troublueshooting


@section module_developer_guide_widget_troubleshooting Troubleshooting

If you run into problems along the way in this tutorial it really helps to have a good Javascript development tool so that you can easily check things like attributes and classes on HTML elements on the page.  In addition you will want to keep an eye on the Javascript error log to see if any errors are reported.  Especially in a tutorial such as this, with multiple parts, there are many places where you can go wrong.  If a file is miss-named you may not see an error message - but the file will just not be picked up at all.  

If you get really stuck, the best thing to do is seek help in the <a href="http://xataface.com/forum">forum</a>.


@section module_developer_guide_widget_download Download This Module

You can download the source for this module <a href="http://xataface.com/dox/core/examples/module_developer_guide/hello_world.create-custom-widget.tar.gz">here</a>.

- Return to @ref module_developer_guide
- Back to @ref module_developer_guide_css
- Next: @ref module_developer_guide_withdata


*/
?>
