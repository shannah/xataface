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
 * example observers
 */
function receiver1(&$notification)
{
    echo "receiver 1 received notification<br />\n";
}

function receiver2(&$notification)
{
    echo "receiver 2 received notification<br />\n";
}

$dispatcher = &Event_Dispatcher::getInstance();
$dispatcher->addObserver('receiver1', 'onFoo', 'TestClass');
$dispatcher->addObserver('receiver2', 'onFoo', 'AnotherTestClass');
$dispatcher->addObserver('receiver2', 'onBar');

// Test, whether an observer has been registered
$registered = $dispatcher->observerRegistered('receiver1', 'onFoo');
if ($registered === true) {
	echo "Observer successfully registered";
}

$observers = $dispatcher->getObservers('onFoo');
echo '<pre>';
print_r($observers);
echo '</pre>';

// Filter using a class name
$observers = $dispatcher->getObservers('onFoo', 'TestClass');
echo '<pre>';
print_r($observers);
echo '</pre>';

?>
