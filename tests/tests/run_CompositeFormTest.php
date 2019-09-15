<?php
require_once '../dataface-public-api.php';
df_init(__FILE__,'../dataface-public-api.php');
$app =& Dataface_Application::getInstance();

import('Dataface/CompositeForm.php');

echo "<h1>Form 1: All of the Profiles Table</h1>";
$form = new Dataface_CompositeForm(array(
	'Profiles?id=10'));
$form->build();
echo $form->display();

echo "<h1>Form 2: Only the first name and lastname fields</h1>";
$form = new Dataface_CompositeForm(array('Profiles?id=10#fname','Profiles?id=10#lname'));
$form->build();
echo $form->display();

echo "<h1>Form 3: First name, last name, and position fields</h1>";
$form = new Dataface_CompositeForm(array('Profiles?id=10#fname','Profiles?id=10#lname','Appointments?id=2#position'));
$form->build();
echo $form->display();
?>
