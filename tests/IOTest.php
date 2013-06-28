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

require_once 'BaseTest.php';
require_once 'Dataface/IO.php';
require_once 'Dataface/Record.php';

class IOTest extends BaseTest {



	function IOTest($name = "IOTest"){
		$this->BaseTest($name);
	}
	
	
	function test_read(){
		$io = new Dataface_IO('Profiles', $this->db);
		$record = new Dataface_Record('Profiles', array());
		$res = $io->read( array('id'=>10), $record );
		
		
		$s =& $this->table1;
		
		//(id,fname,lname,title,description,dob,phone1,phone2,fax,email,datecreated,lastmodified,favtime,lastlogin,photo,thumbnail,photo_mimetype) VALUES
		//(10,'John','Smith','Researcher','Head of the household','1978-12-27','555-555-5555','555-555-5556','555-555-5557','shannah@sfu.ca','20051222135634','20051222135634','14:56:23','2005-06-30','000010101','00010101','text/binary'),
		
		$this->assertEquals($record->getValue('id'), '10');
		$this->assertEquals($record->getValue('fname'), 'John');
		$this->assertEquals($record->getValue('lname'), 'Smith');
		$this->assertEquals($record->getValue('title'), 'Researcher');
		
		// make sure that the flags were set properly
		$this->assertTrue( !$record->valueChanged('id') );
		$this->assertTrue( !$record->valueChanged('fname'));
		
	}
	
	function test_write(){
	
		$io = new Dataface_IO('Profiles', $this->db);
		$record = new Dataface_Record('Profiles', array());
		$res = $io->read( array('id'=>10), $record );
		if ( PEAR::isError($res) ){
		
			echo $res->toString();
		}
		
		$s =& $this->table1;
		$record->setValue('fname', 'Toby');
		$record->setValue('lname', 'McGwire');
		ob_start();
		$io->write($record);
		$buffer = ob_get_contents();
		ob_end_clean();
		$this->assertEquals(" beforeSave beforeUpdate afterUpdate afterSave", $buffer);
		
		$record2 = new Dataface_Record('Profiles', array());
		$io->read(array('id'=>10), $record2);
		
		$this->assertEquals( $record2->getValue('fname'), 'Toby');
		$this->assertEquals( $record2->getValue('lname'), 'McGwire');
	
	}
	
	function test_insert(){
	
		$io = new Dataface_IO('Profiles', $this->db);
		$s = new Dataface_Record('Profiles', array());
		
		$s->setValues( array(
			"fname"=>"Thomas",
			"lname"=>"Hutchinson",
			"title"=>"Mr."
			)
		);
		ob_start();
		$res = $io->write($s);
		$buffer = ob_get_contents();
		ob_end_clean();
		$this->assertEquals(" beforeSave beforeInsert afterInsert afterSave", $buffer);
		if ( PEAR::isError($res) ){
			echo $res->toString();
		}
		$s2 = new Dataface_Record('Profiles', array());
		
		
		$io->read( array("fname"=>"Thomas"), $s2);
		$this->assertEquals($s2->getValue("lname"), "Hutchinson");
		
	
	
	}
	
	function test_add_related_record(){
	
		$io = new Dataface_IO('Profiles', $this->db);
		$record = new Dataface_Record('Profiles', array());
		
		$io->read(array('id'=>10), $record);
		
		$relatedRecord = new Dataface_RelatedRecord($record, 'courses', array('dept'=>'MATH', 'coursenumber'=>268));
		ob_start();
		$res = $io->addRelatedRecord($relatedRecord);
		$buffer = ob_get_contents();
		ob_end_clean();
		$this->assertEquals(' beforeAddRelatedRecord beforeAddNewRelatedRecord afterAddNewRelatedRecord afterAddRelatedRecord', $buffer);
		$this->assertTrue(!PEAR::isError($res) );
		
		$cio = new Dataface_IO('Courses', $this->db);
		$course = new Dataface_Record('Courses', array());
		$cio->read(array('dept'=>'MATH', 'coursenumber'=>268), $course);
		$this->assertTrue($course->val('id')>0, "Course ID inserted must have id > 0");
		$this->assertTrue('MATH', $course->val('dept'));
		$this->assertTrue('268', $course->val('coursenumber'));
		
		unset($record);
		$record = new Dataface_Record('Profiles', array());
		$io->read(array('id'=>10), $record);
		$it =& $record->getRelationshipIterator('courses');
		$found = false;
		while ($it->hasNext() ){
			$nex =& $it->next();
			if ( $nex->val('dept') == 'MATH' and $nex->val('coursenumber') == 268){
				$found = true;
			}
			unset($nex);
		}
		$this->assertTrue($found, "Added course did not show up in list of related records.");
		
	
	}
	
	function test_add_existing_related_record(){
		$io = new Dataface_IO('Profiles', $this->db);
		$record = new Dataface_Record('Profiles', array());
		$io->read(array('id'=>10), $record);
		$cio = new Dataface_IO('Courses');
		$course = new Dataface_Record('Courses', array('dept','PSYC', 'coursenumber'=>400, 'id'=>1001));
		$cio->write($course);
		
		unset($course);
		$course = new Dataface_Record('Courses', array());
		$cio->read( array('id'=>'1001') , $course);
		$this->assertTrue('PSYC', $course->val('dept'));
		
		$relatedCourse = new Dataface_RelatedRecord($record, 'courses', array('id'=>1001));
		ob_start();
		
		$res = $io->addExistingRelatedRecord($relatedCourse);
		$buffer = ob_get_contents();
		ob_end_clean();
		$this->assertEquals(' beforeAddRelatedRecord beforeAddExistingRelatedRecord afterAddExistingRelatedRecord afterAddRelatedRecord', $buffer);
		$this->assertTrue($res, "Failed to add existing course");
		$this->assertTrue(!PEAR::isError($res), "Error occurred while adding existing course");
		
		// Now check to make sure that the record is in the list of related records
		
		unset($record);
		$record = & new Dataface_Record('Profiles', array());
		$io->read(array('id'=>10), $record);
		$it =& $record->getRelationshipIterator('courses');
		$found = false;
		while ($it->hasNext() ){
			$nex =& $it->next();
			if ( $nex->val('id') == 1001){
				$found = true;
			}
			unset($nex);
		}
		$this->assertTrue($found, "Added course did not show up in list of related records.");
		
		
	}
	
	function test_delete(){
		$record = new Dataface_Record('Profiles', array());
		$io = new Dataface_IO('Profiles');
		$io->read(array('id'=>10), $record);
		$res = $io->delete($record);
		$this->assertTrue($res);
		$this->assertTrue( !PEAR::isError($res) );
		
		$this->assertTrue( PEAR::isError($io->read(array('id'=>10), $record)));
	}
	
	
	function test_record_exists(){
	
		$record = new Dataface_Record('Profiles', array('id'=>10));
		$io = new Dataface_IO($record->_table->tablename);
		$this->assertTrue( $io->recordExists($record));
	
	}
	
	function test_import_data(){
		$t =& $this->_table1;
		
		/*
		 * For our first test we will try to import data directly into a table.
		 * We do not worry about relationships here.
		 */
		$data = '<?xml version="1.0"?>
			<dataface>
				<Profiles>
					<fname>John</fname>
					<lname>Smith</lname>
					<title>Professor</title>
				</Profiles>
				<Profiles>
					<fname>Julia</fname>
					<lname>Vaughn</lname>
					<title>Assistant</title>
				</Profiles>
			
			
			</dataface>';
		$io = new Dataface_IO('Profiles', $this->db);
		$record = null;
		
		
		// First we try to import the data into a temporary import table.
		
		$importTablename = $io->importData($record, $data);
		$res = mysql_query("SELECT * FROM `$importTablename`", $this->db);
		$rows = array();
		while ( $row = mysql_fetch_array($res) ){
			$rows[] = $row;
		}
		$this->assertEquals(2, count($rows), "Incorrect number of rows in import table: '$importTablename'");
		$this->assertEquals( 'John', $rows[0]['fname']);
		$this->assertEquals('Smith', $rows[0]['lname']);
		$this->assertEquals('Professor', $rows[0]['title']);
		
		
		// now we try to commit the records
		$records = $io->importData($record, $importTablename, null,null,true);
		$this->assertEquals(2, count($records));
		$this->assertEquals('John Smith', $records[0]->val('fname').' '.$records[0]->val('lname'));
		$this->assertEquals('Julia Vaughn', $records[1]->val('fname').' '.$records[1]->val('lname'));
		$this->assertTrue( $records[0]->val('id') > 0 );
		$this->assertTrue( $records[1]->val('id') > 0 );
		
		//Now let's try to imort some records into a relationship
		/*
		 * Now we attempt to import data into a one-to-many relationship
		 */
		$data = '
			<dataface>
				<Appointments>
					<position>Trucker</position>
					<startdate>2003-11-12</startdate>
					<enddate>2004-05-06</enddate>
					<salary>1234.56</salary>
				</Appointments>
				<Appointments>
					<position>Director</position>
					<startdate>2002-01-02</startdate>
					<enddate>2005-02-03</enddate>
					<salary>5678.57</salary>
				</Appointments>
			</dataface>';
		
		
		$record = new Dataface_Record('Profiles', array());
		$io->read(array('id'=>10), $record);
		$importTablename = $io->importData($record, $data, 'xml', 'appointments');
		
		$res = mysql_query("SELECT * FROM `$importTablename`", $this->db);
		if ( !$res ){
			trigger_error("Error selecting records from import table '$importTablename'.  A mysql error occurred: ".mysql_error($this->db)."\n".Dataface_Error::printStackTrace(), E_USER_ERROR);
		}
		$this->assertEquals(2, mysql_num_rows($res) );
		$rows = array();
		while ( $row = mysql_fetch_array($res) ){
			$rows[] = $row;
		}
		
		$this->assertEquals('Trucker', $rows[0]['position']);
		$this->assertEquals('Director', $rows[1]['position']);
		
		// now to commit this import
		$records = $io->importData($record,$importTablename,'xml','appointments',true);
		if ( PEAR::isError($records) ){
			trigger_error($records->toString().Dataface_Error::printStackTrace(), E_USER_ERROR);
		}
		
		$this->assertEquals(2, count($records));
		$this->assertEquals('dataface_relatedrecord', strtolower(get_class($records[0])));
		$this->assertEquals(10, $records[0]->val('profileid')); 
		$this->assertEquals('Trucker', $records[0]->val('Appointments.position'));
		//print_r($records[0]->getValues());
		
		$res = mysql_query("select * from `Appointments`", $this->db);
		$rows = array();
		while ( $row = mysql_fetch_array($res) ){
			$rows[] = $row;
		}
		
		$this->assertEquals(10, $rows[3]['profileid']);
		$this->assertEquals('Trucker', $rows[3]['position']);
		$this->assertEquals('Director', $rows[4]['position']);
		
		
		/*
		 *
		 * Finally we try to import data into a many-to-many relationship.
		 *
		 */
		$data = '
			<dataface>
				<Courses>
					<dept>Math</dept>
					<coursenumber>332</coursenumber>
				</Courses>
				<Courses>
					<dept>CMPT</dept>
					<coursenumber>475</coursenumber>
				</Courses>
			</dataface>';
		$importTablename = $io->importData($record, $data, 'xml', 'courses');
		if ( PEAR::isError($importTablename) ){
			trigger_error( $importTablename->toString().Dataface_Error::printStackTrace(), E_USER_ERROR);
		}
		$res = mysql_query("SELECT * FROM `$importTablename`", $this->db);
		if ( !$res ){
			trigger_error("Error selecting records from import table '$importTablename'.  A mysql error occurred: ".mysql_error($this->db)."\n".Dataface_Error::printStackTrace(), E_USER_ERROR);
		}
		$this->assertEquals(2, mysql_num_rows($res) );
		$rows = array();
		while ( $row = mysql_fetch_array($res) ){
			$rows[] = $row;
		}
		
		$this->assertEquals('Math', $rows[0]['dept']);
		$this->assertEquals('CMPT', $rows[1]['dept']);
		
		$records = $io->importData($record, $importTablename, 'xml', 'courses', true);
		if ( PEAR::isError($records) ){
			trigger_error($records->toString().Dataface_Error::printStackTrace(), E_USER_ERROR);
		}
		$this->assertEquals(2, count($records) );
		foreach ( $records as $rec ){
			$this->assertEquals('dataface_relatedrecord', strtolower(get_class($rec)));
		}
		//echo "Records: $records";
		$this->assertEquals('Math', $records[0]->val('dept'));
		$this->assertEquals('CMPT', $records[1]->val('dept'));
		
		$res = mysql_query("SELECT * FROM Courses c inner join Student_Courses sc on c.id=sc.courseid inner join Profiles p on p.id=sc.studentid where p.id='10'", $this->db);
		if ( !$res ){
			trigger_error(mysql_error($this->db).Dataface_Error::printStackTrace(), E_USER_ERROR);
		}
		$this->assertEquals(2, mysql_num_rows($res));
		$course1 = mysql_fetch_array($res);
		$course2 = mysql_fetch_array($res);
		$this->assertEquals(10, $course1['studentid']);
		$this->assertTrue( $course1['courseid'] > 0 );
		$this->assertEquals( 10, $course2['studentid']);
		$this->assertTrue( $course2['courseid'] > 0 );
		$this->assertEquals( 'Math', $course1['dept']);
		$this->assertEquals( 'CMPT', $course2['dept']);
		$this->assertEquals( 'John Smith', $course1['fname'].' '.$course1['lname']);
		
		
		
				
	
	}
	
	
	function old_test_perform_sql(){
		$s =& $this->table1;
		$s->setValue('id', 15);
		$res = $s->_addRelatedRecordSQL("courses");
		if ( PEAR::isError($res) ){
			echo "Printing error";
			echo $res->toString();
		
			exit;
		}
		
		//echo "What the fuck";
		//echo "REs: ";print_r($res);
		$s->_performSQL($res);
		
		
		$res = $s->_addRelatedRecordSQL("courses", array("Courses.dept"=>"Computing", "Courses.coursenumber"=>"CMPT118"));
		print_r($res);
		$this->assertEquals( 1, mysql_num_rows(mysql_query("SELECT * FROM Courses", $s->db)));
		$s->_performSQL($res);
		$this->assertEquals(2, mysql_num_rows(mysql_query("SELECT * FROM Courses", $s->db)));
		
		$res = mysql_query("SELECT * FROM Courses", $s->db);
		$found = false;
		while ( $row = mysql_fetch_array($res) ){
			if ( $row['id'] == 2 ){
				$found = true;
				$this->assertEquals("Computing", $row['dept']);
				$this->assertEquals("CMPT118", $row['coursenumber']);
			}
		}
		$this->assertTrue($found, "Failed to insert related record into Courses.");
	
	}
	
	function test_add_existing_related_record_trigger(){
		import('dataface-public-api.php');
		$registration =& df_get_record('Registrations', array('RegistrationID'=>1, 'RegistrantID'=>1));
		$product = new Dataface_RelatedRecord($registration, 'Products', array('ProductID'=>3));
		$io = new Dataface_IO('Registrations');
		$res = $io->addExistingRelatedRecord($product);
		$this->assertTrue( PEAR::isError($res) );
		$this->assertEquals(
			"We don't really like this combination of product and registration",
			$res->getMessage()
			);
		
	}
	
	function test_recordid2query(){
		
		$recordid = "Personal/Publications?FirstName=Steve&LastName=Hannah&".urlencode('Publications::Title')."=".urlencode("Welcome to the world");
		$query = Dataface_IO::recordid2query($recordid);
		$this->assertEquals(
			array(
				'-relationship'=>'Publications',
				'-table'=>'Personal',
				'FirstName'=>'=Steve',
				'LastName'=>'=Hannah',
				'Publications::Title'=>'=Welcome to the world'
				),
			$query
			);
		
	
	}
	
	
	function test_loadRecordById(){
		$record =& Dataface_IO::loadRecordById('Profiles?id=10');
		$this->assertEquals(
			'John',
			$record->val('fname')
			);
		$this->assertEquals(
			'Smith',
			$record->val('lname')
			);
			
		$record2 =& Dataface_IO::loadRecordById('Profiles/appointments?id=10&appointments::id=2');
		$this->assertEquals(
			'Teacher',
			$record2->val('position')
			);
	
	}
	
	
	function test_removeRelatedRecord(){
		$this->assertTrue(mysql_num_rows(mysql_query("SELECT * FROM `Appointments` where `id`=2"))==1);
		$record =& Dataface_IO::loadRecordById('Profiles/appointments?id=10&appointments::id=2');
		$res = Dataface_IO::removeRelatedRecord($record);
			// This should fail to remove the record because it is a one-to-many relationship,
			// and you can only remove the record if you add the 'delete' flag to allow it 
			// to delete the domain record.
		$this->assertTrue(!$res);
		$this->assertTrue(mysql_num_rows(mysql_query("SELECT * FROM `Appointments` where `id`=2"))==1);
		$res = Dataface_IO::removeRelatedRecord($record,true);
		$this->assertTrue($res);
		$this->assertTrue(mysql_num_rows(mysql_query("SELECT * FROM `Appointments` where `id`=2"))==0);
	
	}
	
	function test_getByID(){
		$record =& Dataface_IO::getById('Profiles?id=10');
		
		// Test a simple record
		$this->assertEquals(
			'John',
			$record->val('fname')
			);
		$this->assertEquals(
			'Smith',
			$record->val('lname')
			);
			
		// Test a related record
		$record2 =& Dataface_IO::getById('Profiles/appointments?id=10&appointments::id=2');
		$this->assertEquals(
			'Teacher',
			$record2->val('position')
			);
			
		// Test a simple record's field
		$this->assertEquals(
			'John',
			Dataface_IO::getByID('Profiles?id=10#fname')
			);
		
		$this->assertEquals(
			'Smith',
			Dataface_IO::getByID('Profiles?id=10#lname')
			);
			
		// Test a related record's field
		$this->assertEquals(
			'Teacher',
			Dataface_IO::getById('Profiles/appointments?id=10&appointments::id=2#position')
			);
		
		
		
			
		
	}
	
	function test_setByID(){
		
		$this->assertEquals(
			'John',
			Dataface_IO::getByID('Profiles?id=10#fname')
			);
		
		Dataface_IO::setByID('Profiles?id=10#fname', 'Jimmy');
		$this->assertEquals(
			'Jimmy',
			Dataface_IO::getByID('Profiles?id=10#fname')
			);
			
		
		$this->assertEquals(
			'Teacher',
			Dataface_IO::getById('Profiles/appointments?id=10&appointments::id=2#position')
			);
			
		Dataface_IO::setByID('Profiles/appointments?id=10&appointments::id=2#position', 'firefighter');
		
		$this->assertEquals(
			'firefighter',
			Dataface_IO::getById('Profiles/appointments?id=10&appointments::id=2#position')
			);
			
		Dataface_IO::setByID('Profiles?id=10', array('fname'=>'Bobby','lname'=>'Brown'));
		$r =& Dataface_IO::getByID('Profiles?id=10');
		$this->assertEquals(
			array('fname'=>'Bobby','lname'=>'Brown'),
			$r->vals(array('fname','lname'))
			);
	
	}
	


}


?>
