#Advanced Find Form Date Widget

The advanced find form provides users the ability to filter the result set based on any field in the table, or in any related table.  Like all widgets on the advanced find form, these widgets are pure javascript widgets and they are loaded lazily at the time that the advanced find form is expanded.

All non-vocabulary fields (including most date fields) are represented on the advanced find form by an underlying text field (i.e. `<input type="text"/>`.  Date fields are decorated by Javascript to add a button to show a date range calendar.  

TODO: Still need to add a *lot* more documentation for advanced find form and associated widgets.

##Source Code

* [The date.js widget](../../../modules/g2/js/xataface/findwidgets/date.js)

##Events

###`afterShowDateRangeWidget`

The `afterShowDateRangeWidget` event is fired after the date range panel is shown on a date widget. 

**Bind to**: `window.xataface`

**Parameters:**

* `widget` - The `date` object that encapsulates the entire field and its widgets

The structure of the `date` object is as follows:

~~~
{
		el : The <input text> field for the field.
		name : The name of the field
		from : The hidden <input text> field for the *from* date in the range panel.
		to : The hidden <input text> field for the *to* date in the range panel.
		rangePanel : The HTML element that wraps the range panel.
		btn : The button to toggle the range panel display
	}
~~~

Check out the [The date.js widget](../../../modules/g2/js/xataface/findwidgets/date.js) for a full picture of the methods and properties on the `date` object that is passed as `widget` argument.

Some helpful tips to know:

1. `widget.to` Has a jQuery [datepicker](http://jqueryui.com/datepicker/) installed on it.
2. `widget.from` has also has a jQuery [datepicker](http://jqueryui.com/datepicker/) installed on it.

####Example

Add a javascript file to your app.  For example, create a file named 'advanced-find-extensions.js' inside your app's js directory with the following contents:

~~~
//require <jquery.packed.js>
//require <xatajax.core.js>
(function(){
    var $ = jQuery;
    var xataface = XataJax.load('xataface');
    
    registerXatafaceDecorator(function(node){
      $(xataface).bind('afterShowDateRangeWidget', function(event, data){
          var toField = data.widget['to'];
          var fromField = data.widget.['from'];
          $(fromField).datepicker( "option", "minDate", new Date(2007, 1 - 1, 1) );
          // ....
          
      });
    });
})();
~~~

Then include this javascript file in your app using the `Dataface_JavascriptTool`.  E.g. You could add it to the beforeHandleRequest() method of your application delegate class:

~~~
function beforeHandleRequest(){
    import('Dataface/JavascriptTool.php');
    Dataface_JavascriptTool::getInstance()->import('advanced-find-extensions.js');
}
~~~
