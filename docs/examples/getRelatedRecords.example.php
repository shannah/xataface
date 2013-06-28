<?php
require_once 'dataface-public-api.php';

// load student record with last name 'Hannah'
$student =& df_get_record('Students', array('LastName'=>'Hannah'));

// The $student variable now contains a Dataface_Record object.

// Get the first 30 courses that this student has enrolled in -- assumes that the 'Courses' relationship is defined appropriately in relationships.ini file.
$courses =& $student->getRelatedRecords('Courses')

// $courses now contains an array of associative arrays with Course information.

// Iterate through the courses and print them in a table.
echo '<table>
	<thead>
	 <tr>
	  <th>Course ID</th>
	  <th>Course Name</th>
	  <th>Course Description</th>
	 </tr>
	</thead>
	<tbody>
	';
foreach ( array_keys($courses) as $index){
	echo '
	 <tr>
	  <td>'.$courses[$index]['CourseID'].'</td>
	  <td>'.$courses[$index]['CourseName'].'</td>
	  <td>'.$courses[$index]['CourseDescription'].'</td>
	 </tr>
	';
}
echo '</tbody>
	</table>
	';
?>
