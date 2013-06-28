<?php
/********************************************************************************
 *
 *  Xataface Web Application Framework for PHP and MySQL
 *  Copyright (C) 2005  Steve Hannah <shannah@sfu.ca>
 *  
 *  This library is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU Lesser General Public
 *  License as published by the Free Software Foundation; either
 *  version 2.1 of the License, or (at your option) any later version.
 *  
 *  This library is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 *  Lesser General Public License for more details.
 *  
 *  You should have received a copy of the GNU Lesser General Public
 *  License along with this library; if not, write to the Free Software
 *  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 *
 *===============================================================================
 */

$_SERVER['PHP_SELF'] = __FILE__;
require_once 'BaseTest.php';
require_once 'Dataface/Relationship.php';
require_once 'Dataface/Record.php';
require_once 'Dataface/IO.php';


class RelationshipTest extends BaseTest {

	
	
	function RelationshipTest($name = 'RelationshipTest'){
		$this->BaseTest($name);
		//parent::BaseTest();
	}
	
	
	function test_relationships(){
		$record = new Dataface_Record('Profiles', array());
		$record->setValue('id',10);
		$record->setValue('fname','John');
		$table =& $this->table1;
		$this->table1->relationships(); // loads the relationships
		
		// make sure that the relationships were loaded properly
		$rels = array('appointments', 'degrees','addresses');
		foreach ($rels as $rel){
			$this->assertTrue(is_a($table->_relationships[$rel], 'Dataface_Relationship'), "Relationship $rel not loaded");
		}
		
		//make sure the sql was loaded properly
		$appointments =& $table->getRelationship('appointments');
		
		
		$this->assertEquals($appointments->_schema['sql'], "select * from Appointments where Appointments.profileid='\$id'");
		$this->assertEquals($record->parseString($appointments->_schema['sql']),
							"select * from Appointments where Appointments.profileid='10'");
							
		$this->assertEquals(count($appointments->_schema['tables']), 1);
		$this->assertEquals($appointments->_schema['tables'][0], 'Appointments');
		$this->assertEquals(count($appointments->_schema['selected_tables']), 1);
		$this->assertEquals($appointments->_schema['selected_tables'][0], 'Appointments');
		$this->assertEquals(count($appointments->_schema['columns']), 6);
		//(profileid, position, startdate, enddate, salary)
		$this->assertEquals($appointments->_schema['columns'][0], 'Appointments.id');
		$this->assertEquals($appointments->_schema['columns'][1], 'Appointments.profileid');
		$this->assertEquals($appointments->_schema['columns'][2], 'Appointments.position');
		$this->assertEquals($appointments->_schema['columns'][3], 'Appointments.startdate');
		$this->assertEquals($appointments->_schema['columns'][4], 'Appointments.enddate');
		$this->assertEquals($appointments->_schema['columns'][5], 'Appointments.salary');
				
		$degrees =& $table->getRelationship('degrees');
		$this->assertEquals($degrees->_schema['sql'], "select name, institution from Degrees where Degrees.profileid='\$id'");
		$this->assertEquals($record->parseString($degrees->_schema['sql']), "select name, institution from Degrees where Degrees.profileid='10'");
		$this->assertEquals(count($degrees->_schema['tables']),1);
		$this->assertEquals($degrees->_schema['tables'][0], 'Degrees');
		$this->assertEquals(count($degrees->_schema['columns']), 2);
		$this->assertEquals($degrees->_schema['columns'][0], 'Degrees.name');
		$this->assertEquals($degrees->_schema['columns'][1], 'Degrees.institution');
		
		$courses =& $table->getRelationship('courses');
		
		$this->assertEquals($courses->_schema['selected_tables'][0], 'Courses');
		$this->assertEquals(count($courses->_schema['columns']), 4);
		$this->assertEquals($courses->_schema['sql'], "select Courses.* from Courses, Student_Courses where Courses.id=Student_Courses.courseid and Student_Courses.studentid='\$id'");
		
		
		// try default get related... should only return 3 records
		unset($degrees);
		$degrees = $record->getRelatedRecords('degrees');
		$this->assertEquals($degrees[0]['name'],'Master of Technology');
		$this->assertEquals(count($degrees), 3, 'Degrees relationship returned wrong number of records.  Should be 3.');
		foreach ($degrees as $degree){
			$this->assertTrue(isset($degree['name']), 'name is not set for '.$degree);
			$this->assertTrue(isset($degree['institution']), 'institution is not set for'.$degree);
		}
		
		// Explicitly request multiple records
		$degrees = $record->getRelatedRecords('degrees', true);
		$this->assertEquals($degrees[0]['name'],'Master of Technology');
		$this->assertEquals(count($degrees), 3, 'Degrees relationship returned wrong number of records.  Should be 3.');
		foreach ($degrees as $degree){
			$this->assertTrue(isset($degree['name']), 'name is not set for '.$degree);
			$this->assertTrue(isset($degree['institution']), 'institution is not set for'.$degree);
		}
		
		// Explicitly request single record
		$degrees = $record->getRelatedRecords('degrees', false);
		$this->assertEquals($degrees['name'],'Master of Technology');
		$this->assertTrue(isset($degrees['name']), 'name is not set for '.$degree);
		$this->assertTrue(isset($degrees['institution']), 'institution is not set for'.$degree);
		
		// Try using getValue()
		
		$this->assertEquals($record->getValue('degrees.name'), 'Master of Technology');
		
		// Try multiple values 
		//$names = $record->getValue('degrees.name', true);
		//$this->assertEquals(3, count($names), 'Wrong number of names returned for getValue(degrees.name)');
		//$this->assertEquals($names[0], 'Master of Technology');
		//$this->assertEquals($names[1], 'PH.D of Technology');
		//$this->assertEquals($names[2], 'Bachelor of Science');
		
		//
		//  Now we use a relationship that was defined directly with SQL
		//(profileid,line1,line2,line3,city,state,country,postalcode) 
		$addresses =& $this->table1->getRelationship('addresses');
		$this->assertEquals($addresses->_schema['sql'], "select * from Addresses where profileid='\$id'");
		$this->assertEquals($record->parseString($addresses->_schema['sql']),
							"select * from Addresses where profileid='10'");
		
		$this->assertEquals(count($addresses->_schema['tables']), 1);
		$this->assertEquals(count($addresses->_schema['columns']), 9);
		$this->assertEquals($addresses->_schema['tables'][0], 'Addresses');
		$this->assertEquals($addresses->_schema['columns'][0], 'Addresses.id');
		$this->assertEquals($addresses->_schema['columns'][1], 'Addresses.profileid');
		$this->assertEquals($addresses->_schema['columns'][2], 'Addresses.line1');
		$this->assertEquals($addresses->_schema['columns'][3], 'Addresses.line2');
		
		$line1 = $record->getValue('addresses.line1');
		$this->assertEquals($line1, '555 Elm St');
	
		
	}
	
	
	function test_has_field(){
		$table =& $this->table1;
		$relationship =& $table->getRelationship('courses');
		$this->assertTrue($relationship->hasField('id'));
		$this->assertTrue( !$relationship->hasField('mid'));
		$this->assertTrue($relationship->hasField('Courses.id'));
		$this->assertTrue( !$relationship->hasField('Courses.mid'));
		$this->assertTrue( !$relationship->hasField('Henchmen.id'));
	
	
	}
	
	
	
	
	function test_get_relationship_domain(){
		$courses =& $this->table1->getRelationship('courses');
		$sql = $courses->getDomainSQL();
		$this->assertEquals( "select Courses.* from Courses", $sql);
	
	
	}
	
	
	function test_make_equivalence_labels(){
		$labels = array();
		$values = array();
		require_once 'SQL/Parser.php';
		$sql = "select * 
					from Profiles p 
					inner join Student_Courses sc 
						on p.id = sc.studentid
					inner join Courses c
						on c.id = sc.courseid
				where
					p.id = '\$id'";
		$parser = new SQL_Parser();
		$parsed = $parser->parse($sql);
		$courses =& $this->table1->getRelationship('courses');
		$courses->_makeEquivalenceLabels( $labels,$values,$parsed);
		
		$this->assertEquals(
			array(	"Profiles.id"=>"Profiles.id",
					"Student_Courses.studentid" => "Profiles.id",
					"Courses.id" => "Courses.id",
					"Student_Courses.courseid" => "Courses.id"),
			$labels
		);
		
		$this->assertEquals(
			array( "Profiles.id" => '$id'),
			$values
		);
	}
	
	
	function test_destination_tables(){
		
		$record = new Dataface_Record('Profiles', array());
		$io = new Dataface_IO('Profiles');
		$io->read(array('id'=>10), $record);
		$relationship =& $record->_table->getRelationship('appointments');
		$destinationTables =& $relationship->getDestinationTables();
		
		$this->assertEquals(1, count($destinationTables));
		$this->assertEquals('Appointments', $destinationTables[0]->tablename);
		//$this->assertEquals(array('Appointments'), $destinationTables);
		
		require_once 'dataface-public-api.php';
		$registration =& df_get_record('Registrations', array('RegistrationID'=>1));
		$products =& $registration->getRelationshipIterator('Products');
		$product =& $products->next();
		$destTables =& $product->_relationship->getDestinationTables();
		$this->assertEquals(2, count($destTables));
		
	
	
	}
	
	function test_get_sql(){
		$table =& Dataface_Table::loadTable('Profiles');
		$courses =& $table->getRelationship('courses');
		$sql = $courses->getSQL();
		$this->assertEquals("select `Courses`.`id`, `Courses`.`dept`, `Courses`.`coursenumber`, length(`Courses`.`id`) as `__id_length`, length(`Courses`.`dept`) as `__dept_length`, length(`Courses`.`coursenumber`) as `__coursenumber_length`, length(`Courses`.`pdf_outline`) as `__pdf_outline_length` from `Courses`, `Student_Courses` where `Courses`.`id` = `Student_Courses`.`courseid` and `Student_Courses`.`studentid` = '\$id'", $sql);
		
		$sql_with_blobs = $courses->getSQL(true);
		$this->assertEquals("select `Courses`.`id`, `Courses`.`dept`, `Courses`.`coursenumber`, `Courses`.`pdf_outline`, length(`Courses`.`id`) as `__id_length`, length(`Courses`.`dept`) as `__dept_length`, length(`Courses`.`coursenumber`) as `__coursenumber_length`, length(`Courses`.`pdf_outline`) as `__pdf_outline_length` from `Courses`, `Student_Courses` where `Courses`.`id` = `Student_Courses`.`courseid` and `Student_Courses`.`studentid` = '\$id'", $sql_with_blobs);
		
		
		$sql_with_where = $courses->getSQL(false, "Courses.coursenumber = 'MATH343'");
		$this->assertEquals("select `Courses`.`id`, `Courses`.`dept`, `Courses`.`coursenumber`, length(`Courses`.`id`) as `__id_length`, length(`Courses`.`dept`) as `__dept_length`, length(`Courses`.`coursenumber`) as `__coursenumber_length`, length(`Courses`.`pdf_outline`) as `__pdf_outline_length` from `Courses`, `Student_Courses` where (`Courses`.`id` = `Student_Courses`.`courseid` and `Student_Courses`.`studentid` = '\$id') and `Courses`.`coursenumber` = 'MATH343'", $sql_with_where);
		
		
	
	}
	
	
	
	
	
	function test_foreign_key_values(){
		$s = new Dataface_Record('Profiles', array());
		$courses =& $this->table1->getRelationship('courses');
		$s->setValue('id', 15);
		$vals = $courses->getForeignKeyValues(null, null, $s);
		$this->assertEquals( 
			array(
				"Courses" => array(
					"id" => "__Courses__auto_increment__"
					),
				"Student_Courses" => array(
					"courseid" => "__Courses__auto_increment__",
					"studentid" => "15"
					)
				),
			$vals
		);
		
		
		$vals = $courses->getForeignKeyValues(null,null);
		$this->assertEquals( 
			array(
				"Courses" => array(
					"id" => "__Courses__auto_increment__"
					),
				"Student_Courses" => array(
					"courseid" => "__Courses__auto_increment__",
					"studentid" => "\$id"
					)
				),
			$vals
		);
		
		
		$vals = $courses->getForeignKeyValues(array('Courses.id'=>2),null, $s);
		$this->assertEquals( 
			array(
				"Courses" => array(
					"id" => 2
					),
				"Student_Courses" => array(
					"courseid" => 2,
					"studentid" => "15"
					)
				),
			$vals
		);
		
	
	}
	
	/**
	 * This test is to test the case where we are mapping a record to other
	 * records in the same table.  (i.e. a sibling relationship).
	 */
	function test_sibling_relationship(){
		import('dataface-public-api.php');
		$person = df_get_record('People2', array('PersonID'=>1));
		$people2 =& Dataface_Table::loadTable('People2');
		$friends =& $people2->getRelationship('Friends');
		$vals = $friends->getForeignKeyValues(null,null,$person);
		print_r($vals);
	
	}
	
	function test_parent_relationship(){
		import('dataface-public-api.php');
		$person = df_get_record('People2', array('PersonName'=>'John'));
		$people2 =& Dataface_Table::loadTable('People2');
		$parents =& $people2->getRelationship('Parents');
		$vals = $parents->getForeignKeyValues(null,null,$person);
		print_r($vals);
	}
	
	
	function test_isOneToMany(){
		$courses = $this->table1->getRelationship('courses');
		$this->assertTrue($courses->isManyToMany());
		$this->assertTrue(!$courses->isOneToMany());
	}
}
	
?>
