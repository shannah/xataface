<?PHP
/**
 * example that shows how to change the class used for notifications
 *
 * You may change the notification class per dispatcher or globally for all
 * newly created dispatchers.
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
 * example sender
 *
 * @package    Event_Dispatcher
 * @subpackage Examples
 * @author     Stephan Schmidt <schst@php.net>
 */
class sender
{
    var $_dispatcher = null;
    
    function sender(&$dispatcher)
    {
        $this->_dispatcher = &$dispatcher;
    }
    
    function foo()
    {
        $this->_dispatcher->post($this, 'onFoo', 'Some Info...');
    }
}

function receiver(&$notification)
{
    echo 'received notification: ';
    echo get_class($notification);
    echo '<br />';    
}

/**
 * custom notification class
 *
 * @package    Event_Dispatcher
 * @subpackage Examples
 * @author     Stephan Schmidt <schst@php.net>
 */
class MyNotification extends Event_Notification 
{
}

$dispatcher = &Event_Dispatcher::getInstance();
$dispatcher->setNotificationClass('MyNotification');
$sender = new sender($dispatcher);

$dispatcher->addObserver('receiver');

echo 'sender->foo()<br />';
$sender->foo();

Event_Dispatcher::setNotificationClass('MyNotification');

$dispatcher2 = &Event_Dispatcher::getInstance();
$sender2 = new sender($dispatcher2);

$dispatcher2->addObserver('receiver');

echo '<br />sender2->foo()<br />';
$sender2->foo();
?>
