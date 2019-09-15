<?php

require_once 'BaseTest.php';
$test = new BaseTest();
$test->setUp();

require_once 'Dataface/ValuelistEditorForm.php';
$form = new Dataface_ValuelistEditorForm('Profiles','People');
$form->display();


?>
