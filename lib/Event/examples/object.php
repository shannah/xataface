<?PHP
/**
 * example that show how to use objects as observers without
 * loosing references
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
        $notification = &$this->_dispatcher->post($this, 'onFoo', 'Some Info...');
        echo "notification::foo is {$notification->foo}<br />";
    }
}

/**
 * example observer
 */
class receiver
{
    var $foo;
    
    function notify(&$notification)
    {
        echo "received notification<br />";
        echo "receiver::foo is {$this->foo}<br />";
        $notification->foo = 'bar';
    }
}

$dispatcher = &Event_Dispatcher::getInstance();

$sender = new sender($dispatcher);
$receiver = new receiver();
$receiver->foo = 42;

// make sure you are using an ampersand here!
$dispatcher->addObserver(array(&$receiver, 'notify'));

$receiver->foo = 'bar';

echo 'sender->foo()<br />';
$sender->foo();
?>
