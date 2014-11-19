#Xataface Portlet Component

The Portlet component allows you to include a data-table with the results of an SQL query inside an existing page or action.  It will optionally include "Add", "Edit", and "Delete" buttons that will allow you to modify the data set.  The "Add" and "Edit" actions will open in a jQuery dialog using the [RecordDialog](RecordDialog.md) component.

##Requirements

Xataface 2.1.3 or higher (or latest in GitHub).

##Source

If you are working in PHP, you will access it through the [xf\components\Portlet](../../xf/components/Portlet.php) class.  It wraps the [Porlet](../../js/xataface/components/Portlet.js) Javascript component.

You can also use the [Porlet Javascript component](../../js/xataface/components/Portlet.js) directly from Javascript

##Code Example

The following example shows a section implemented inside a table [delegate class](../DelegateClasses.md) to display a portlet inside the *view* tab of a record.

    // Includes at beginning of file
    require_once 'xf/components/Portlet.php';
    use xf\components\Portlet;
    
    
    // sections_xxx method
    function section__events(Dataface_Record $record){
        $portlet = Portlet::createPortletWithQuery(
            array(
                '-table' => 'career_events',
                'student_number' => '='.$record->val('student_number'),
                'career_type' => '='.$record->val('career_type'),
                'career_number' => '='.$record->val('career_number')
            ),
            array(
                'milestone_type',
                'scheduled_date',
                'event_type',
                'status',
                'date_created',
                'last_modified',
                'comments'
            ),
            array(
                'cssClass' => '',
                'canEdit' => true,
                'canAdd' => true,
                'canDelete' => true,
                'rowActions' => 'test_portlet_actions',
                'params' => array(
                    'student_number' => $record->val('student_number'),
                    'career_type' => $record->val('career_type'),
                    'career_number' => $record->val('career_number'),
                    '-xf-hide-fields' => 'student_number career_type career_number'
                ),
                'addButtonLabel' => 'Add Event'
            )
        );
        $content = $portlet->toHtml();
        return array(
            'label' => 'Events',
            'class' => 'main',
            'content' => $content,
            'order' => 5
        );
    }
    
The resulting portlet will look like:

![Portlet Screenshot](images/events-portlet.png)

##Usage

1. Imports
 ~~~
 require_once 'xf/components/Portlet.php';
 use xf\components\Portlet;
 ~~~
2. Create Portlet:
 ~~~
 $portlet = Portlet::createPortletWithQuery(
    $query, // A Xataface query associative array
    $columns, // An array of column names to display - null for all
    $options, // An associative array of configuration options passed to Portlet
 ~~~
3. Display portlet:
 ~~~
 $content = $portlet->toHtml();
 ~~~
    
TODO:  MORE DOCUMENTATION ON THE OPTIONS

For now, check out the [xf\components\Portlet](../../xf/components/Portlet.php) source code for information about available options.
    