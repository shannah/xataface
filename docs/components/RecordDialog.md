#Record Dialog

The Record Dialog component allows you to open a *new* or *edit* record form inside a jQuery dialog.  This dialog is opened inside the current page so that the user does not have to leave the current page.  

You can specify the title and size of the dialog, in addition to providing a call back to be executed on save.

##Usage

The get the most our of the RecordDialog component, you should be using the Dataface_JavascriptTool class to deploy your javascript.  Then you can just use the `<require>` statement to include all necessary scripts and styles for the RecordDialog.

Add the following to the beginning of your .js file:

~~~
//require <RecordDialog/RecordDialog.js>
~~~

To open a *new* record form:

~~~

// Open a new record dialog for some_table 
var dialog = new xataface.RecordDialog({
   table : 'some_table'
});
dialog.display();

~~~

To open an *edit* record form:

~~~
// Open an edit record dialog for the specified record ID
var dialog = new xataface.RecordDialog({
   table : 'some_table',
   recordid : 'some_table?id=25'
});
dialog.display();
~~~

A Fuller Example

~~~
var dialog = new xataface.RecordDialog({
   table : 'some_table',
   recordid : 'some_table?id=25',
   title : 'Edit Julie\'s Profile',
   width : 600,
   height : 250,
   params : {
      '-fields' : 'first_name last_name' // Only show first and last name fields
   },
   callback : function(data){
      // Data contains all of the record data.  Use console.log to see what
      // is here.  Also includes special __title__ and __id__ properties.
      alert('You saved her profile.  Her name is now \''+data.first_name+'\'');
   }
});
dialog.display();

~~~

##Options

| Option | Type | Description | Required | Since |
|--------|------|-------------|----------|-------|
| table | String | The name of the table for the form | Yes | 1.0|
| recordid | String | The Xataface record ID for the record to edit.  If omitted, it will be a new record form.| No | 1.0 |
| callback | Function | A Callback function that will be called when the dialog is closed upon saving. | No | 1.0 |
| title | String | Title for the dialog | No | 2.0.4 |
| width | Number | The width of the dialog in pixels | No | 2.0.4|
| height | Number | The height of the dialog in pixels | No | 2.0.4|
| marginW | Number | Number of pixels between edge of window and the right/left edges of the dialog. | No | 2.0.4 |
| marginH | Number | Number of pixels between edge of window and the top/bottom edges of the dialog. | No | 2.0.4 |
| params | Object | Additional GET parameters that will be passed to the new/edit record forms. | No | 1.0 |

##How RecordDialog is Used in Xataface

The RecordDialog component is used throughout Xataface in different contexts.  The RecordBrowser component creates a RecordDialog instance when the user clicks the "New Record" button.  The lookup widget uses the RecordBrowser to allow users to find records, and, therefore, transitively uses the RecordDialog if the user adds a new record.  It also uses the RecordDialog directly if the user clicks the "Edit" icon to edit the currently-selected record.

The `depselect` and `select` widgets also use the RecordDialog component when the user clicks the "Add New" icon to add a new record to the list.

##How it Works

The RecordDialog uses an iframe to embed the standard new and edit record forms within a jQuery dialog.  It also provides some additional decoration of the contents to hide the parts of the UI that are not inside the form itself.  Because of this, you may notice a small flicker when the dialog first opens, showing the header and footer of the page.  A fade-in effect is used to minimize this flicker, and with good internet connections, it probably isn't noticeable at all.

In order to allow you to create styles that target the record forms specifically inside the RecordDialog dialog, it also adds the *RecordDialogBody* CSS class to the `<body>` tag of the iframe content.  This should allow you to target items inside the RecordDialog without affecting the standard new and edit record forms.

##Overriding Response Messages

By default response messages (e.g. "Record Successfully Saved") are displayed in a regular javascript alert dialog.  You can override this by overriding the `showResponseMessage` method on the `RecordDialog` object.  E.g.

~~~
var dialog = new xataface.RecordDialog({
   ...
});
dialog.showResponseMessage = function(msg){
   // Show the message msg using your own custom dialog -- or don't show the message at all.
};
dialog.display();
~~~
