#Xataface Widgets

Xataface includes many common widget types built-in, and there are modules that add support for many other widget types.  In addition, it includes a pluggable API that will allow you to develop your own widgets for use in Xataface forms.

##Core Widget Types

| Type | Description |
|---|---|
| text | A text field |
| calendar | A javascript calendar widget.  Used for date and datetime fields |
| select | Generates a `<select>` list. |
| grid | A compound widget that allows you to manage mutliple related records in a single table on the add/edit record form.  Includes functionality to add, remove, and reorder rows. |
| lookup | A lookup to select a record from another table. |
| table | A compound widget for editing tabular data.  Data is stored in an XML format inside the field. |
| textarea | A `<textarea>` widget |
| time | A drop-down list that allows you to select times.  Used as the default for `time` fields |
| yui_autocomplete | An autocomplete widget (uses the YUI Autocomplete widget as a basis). |
| password | A password field.  Default for any field that includes the word `password` in the field name |
| file | Used for file uploads.  You should almost never set this explicitly.  Instead specify that the field is `Type=container` or a BLOB field. |
| htmlarea | **Deprecated** Uses a WYSIWYG HTML editor.  Default is FCKeditor, but you can also select TinyMCE.  This has been deprecated in favour of the CKeditor module |


##Available Widgets

In addition to the core widgets, there are a number of add-on modules that you can install that include other widgets.

| Type | Description |
| --- | --- | --- |
| depselect | Creates a select list whose contents are automatically updated/filtered based on the selections of other widgets in the form | 
| ckeditor | A WYSIWYG HTML editor | 
| datepicker | An alternative date chooser/calendar widget that uses the jQuery UI datepicker plugin. |
| durationselector | Allows you to select a time duration.  Compatible with underlying datetime and time widgets so that you can select a start time and duration instead of start time and end time. |
| tagger | A compound widget that operates on related records.  Add related records as tags.  |
| ajax_upload | Alternative upload widget that uses AJAX for uploading in the background instead of including the file as part of the POST request. |
| map | Provides a Google Map to select points on a map.  Points are stored as a JSON structure |

##Create Your Own Widget

TODO
