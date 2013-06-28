<?PHP
/**
 * example that shows how to cancel an event
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

/**
 * example observer
 */
function receiver1(&$notification)
{
    echo "receiver 1 received notification<br />\n";
    // the notification will be cancelled and no other observers
    // will be notified
    $notification->cancelNotification();
}

/**
 * example observer
 */
function receiver2(&$notification)
{
    echo "receiver 2 received notification<br />\n";
}


$dispatcher = &Event_Dispatcher::getInstance();
$sender = new sender($dispatcher);

$dispatcher->addObserver('receiver1', 'onFoo');
$dispatcher->addObserver('receiver2', 'onFoo');

$sender->foo();
?>
