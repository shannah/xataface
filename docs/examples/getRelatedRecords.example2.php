<?php
require_once 'dataface-public-api.php';

// get all related records (well at least the first 30)
$courses =& $student->getRelatedRecords('Courses');

// get ALL related records  - if there are 500 of them, this will return all 500
$courses =& $student->getRelatedRecords('Courses', 'all');

// get all courses from Spring-05 semester
$courses =& $student->getRelatedRecords('Courses', 'all', "Semester='Spring-05'");

// get all courses from Spring-05 semester, sorted on registration date
$courses =& $student->getRelatedRecords('Courses', 'all', "Semester='Spring-05'", "RegistrationDate");

// get only the first 5 courses based on letter grade.
$courses =& $student->getRelatedRecords('Courses', 
					0 /*start*/, 
					5 /*limit*/,
					0 /*where*/,
					"LetterGrade");

// get the first 5 courses of the Spring-05 semester based on letter grade
$courses =& $student->getRelatedRecords('Courses',
					0 /*start*/,
					5 /*limit*/,
					"Semester='Spring-05'" /*where*/,
					"LetterGrade" /*Order by*/);
?>
