<?PHP
/**
 * example that shows how to create event bubbling
 *
 * This allows you to create several levels of event handling and you
 * may post a notification to any of these levels.
 *
 * After a notification has been posted on a lower level, it will bubble
 * up through all other levels.
 *
 * @package    Event_Dispatcher
 * @subpackage Examples
 * @author     Stephan Schmidt <schst@php.net>
 */
 
/**
 * load Event_Dispatcher package
 */
require_once 'Event/Dispatcher.php';

/**
 * example sender class
 */
class sender
{
    var $_dispatcher = null;
    
    function sender(&$dispatcher)
    {
        $this->_dispatcher = &$dispatcher;
    }
    
    function foo($bubble = true)
    {
        $this->_dispatcher->post($this, 'onFoo', 'Some Info...', true, $bubble);
    }
}

/**
 * example observer
 */
function receiver1(&$notification)
{
    echo "receiver 1 received notification<br />\n";
}

/**
 * example observer
 */
function receiver2(&$notification)
{
    echo "receiver 2 received notification<br />\n";
}

/**
 * example observer
 */
function receiver3(&$notification)
{
    echo "receiver 3 received notification<br />\n";
}

// get the different dispatchers
$dispatcher1 = &Event_Dispatcher::getInstance();
$dispatcher2 = &Event_Dispatcher::getInstance('child');
$dispatcher3 = &Event_Dispatcher::getInstance('grandchild');

// create senders in two different levels
$sender1 = new sender($dispatcher1);
$sender2 = new sender($dispatcher2);

// build three levels
$dispatcher1->addNestedDispatcher($dispatcher2);
$dispatcher2->addNestedDispatcher($dispatcher3);

// add observers in level one and two
$dispatcher1->addObserver('receiver1', 'onFoo');
$dispatcher2->addObserver('receiver2', 'onFoo');

// this will bubble up from 1 to 3
echo 'sender1->foo()<br />';
$sender1->foo();

// this will not bubble up
echo '<br />';
echo 'sender1->foo(), but disable bubbling<br />';
$sender1->foo(false);


// this will bubble up from 2 to 3
echo '<br />';
echo 'sender2->foo()<br />';
$sender2->foo();

// This observer will receive the two pending notifications on level 3
echo '<br />';
echo 'dispatcher3->addObserver()<br />';
$dispatcher3->addObserver('receiver3', 'onFoo');

// remove one level
$success = $dispatcher1->removeNestedDispatcher($dispatcher2);
if ($success === true) {
    echo '<br />';
	echo 'removed nested dispatcher2 from dispatcher1<br />';
}

// this will stay in level 1
echo 'sender1->foo()<br />';
$sender1->foo();

// this will bubble up from 2-3
echo '<br />';
echo 'sender2->foo()<br />';
$sender2->foo();
?>
