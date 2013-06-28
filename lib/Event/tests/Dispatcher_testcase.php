<?php

// $Id: Dispatcher_testcase.php,v 1.1.1.1 2005/11/29 19:21:53 sjhannah Exp $

/**
 * Unit tests for Event_Dispatcher package.
 *
 * @author     Bertrand Mansion <bmansion@mamasam.com>
 * @package    Event_Dispatcher
 * @subpackage Tests
 */

class Notified
{
    var $notif;

    function notifReceived(&$notif)
    {
        $this->notif =& $notif;
    }

    function description()
    {
        $notObj =& $this->notif->getNotificationObject();
        $name = $this->notif->getNotificationName();
        $info = $this->notif->getNotificationInfo();
        $desc = $name.':'.implode(':', $info).':'.$notObj->id;
        return $desc;
    }
}

class Dummy
{
    var $id;
    function Dummy($id = 'default')
    {
        $this->id = $id;
    }
}

class Notifier
{
    var $id = 'notifier';
    function Notifier($id)
    {
        $this->id = $id;
        $ed =& Event_Dispatcher::getInstance();
        $ed->post($this, 'NotifierInstanciated', array('info'));
    }
}

function notified(&$notif)
{
    $obj = $notif->getNotificationObject();
    $obj->id = $notif->getNotificationName().':'.implode(':', $notif->getNotificationInfo());
}

class Dispatcher_testCase extends PHPUnit_TestCase
{

    function Dispatcher_testCase($name)
    {
        $this->PHPUnit_TestCase($name);
    }

    // Get the default dispatch center
    function test1()
    {
        $nf = new Notified();
        $dm = new Dummy();
        $ed =& Event_Dispatcher::getInstance();

        // Generic notification, global observer
        $ed->addObserver(array(&$nf, 'notifReceived'));
        $not =& $ed->post($dm, 'test', array('A', 'B'));
        $this->assertEquals('test:A:B:default', $nf->description(), "Error");
        $this->assertEquals(1, $not->getNotificationCount(), "Wrong notification count");

        // Object references
        $dm->id = 'dummy';
        $this->assertEquals('test:A:B:dummy', $nf->description(), "Wrong notification description");

        // Named notifications
        $ed->addObserver('notified', 'NotifierInstanciated');
        $nt = new Notifier('notifier');
        $this->assertEquals('NotifierInstanciated:info', $nt->id, "Wrong notification id");

        // Pending notifications
        $not =& $ed->post($nt, 'PendingNotification');
        $ed->addObserver(array(&$nf, 'notifReceived'), 'PendingNotification');
        $this->assertEquals('PendingNotification::NotifierInstanciated:info', $nf->description(), "Error");
        $this->assertEquals(2, $not->getNotificationCount(), "Error");

        // Class filter 1
        $ed->addObserver(array(&$nf, 'notifReceived'), 'ClassFilterNotification', 'Dummy');
        $not =& $ed->post($nt, 'ClassFilterNotification', array('isGlobal'));
        $this->assertEquals('ClassFilterNotification:isGlobal:NotifierInstanciated:info', $nf->description(), "Error");
        $this->assertEquals(1, $not->getNotificationCount(), "Error");

        // Remove observer
        $ed->removeObserver(array(&$nf, 'notifReceived'));
        $nt->id = 'reset';
        $not =& $ed->post($nt, 'ClassFilterNotification', array('test'));
        $this->assertEquals('ClassFilterNotification:isGlobal:reset', $nf->description(), "Error");
        $this->assertEquals(0, $not->getNotificationCount(), "Error");

        // Class filter 2
        $not =& $ed->post($dm, 'ClassFilterNotification');
        $this->assertEquals('ClassFilterNotification::dummy', $nf->description(), "Error");
        $this->assertEquals(1, $not->getNotificationCount(), "Error");

        // Re-add the global observer
        $ed->addObserver(array(&$nf, 'notifReceived'));
        $not =& $ed->post($dm, 'ClassFilterNotification');
        $this->assertEquals('ClassFilterNotification::dummy', $nf->description(), "Error");
        $this->assertEquals(2, $not->getNotificationCount(), "Error");

    }

    // Tests with 2 dispatchers
    function test2()
    {
        $nf = new Notified();
        $dm = new Dummy();

        $ed2 =& Event_Dispatcher::getInstance('another');
        $ed1 =& Event_Dispatcher::getInstance();

        $ed2->addObserver(array(&$nf, 'notifReceived'));
        $not =& $ed2->post($dm, 'test', array('A', 'B'));
        $this->assertEquals('test:A:B:default', $nf->description(), "Error");
        $this->assertEquals(1, $not->getNotificationCount(), "Error");

        $not =& $ed1->post($dm, 'test', array('A2', 'B2'));
        $this->assertEquals(1, $not->getNotificationCount(), "Error");
        
        $not =& $ed1->post($dm, 'test', array('A2', 'B2'));
        $this->assertEquals(1, $not->getNotificationCount(), "Error");

        $ed2->addObserver(array(&$nf, 'notifReceived'), 'ClassFilterNotification', 'Notifier');
        $not =& $ed2->post($dm, 'ClassFilterNotification');
        $this->assertEquals('ClassFilterNotification::default', $nf->description(), "Error");
        $this->assertEquals(1, $not->getNotificationCount(), "Error");

        $ed2->addObserver(array(&$nf, 'notifReceived'), 'ClassFilterNotification', 'Dummy');
        $not =& $ed2->post($dm, 'ClassFilterNotification');
        $this->assertEquals(2, $not->getNotificationCount(), "Error");

    }
}
?>
