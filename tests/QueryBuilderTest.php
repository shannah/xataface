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
require_once 'Dataface/QueryBuilder.php';
require_once 'Dataface/Record.php';
require_once 'Dataface/RelatedRecord.php';

class QueryBuilderTest extends BaseTest {
	function QueryBuilderTest($name='QueryBuilderTest'){
		$this->BaseTest($name);
	}
	
	
	function test_query_builder_select(){
		$builder = new Dataface_QueryBuilder('Profiles');
		$this->assertEquals($builder->select(), 'SELECT `Profiles`.`id`,`Profiles`.`fname`,`Profiles`.`lname`,`Profiles`.`title`,`Profiles`.`description`,`Profiles`.`dob`,`Profiles`.`phone1`,`Profiles`.`phone2`,`Profiles`.`fax`,`Profiles`.`email`,`Profiles`.`datecreated`,`Profiles`.`lastmodified`,`Profiles`.`favtime`,`Profiles`.`lastlogin`,`Profiles`.`photo_mimetype`,`Profiles`.`tablefield` FROM `Profiles`');
		$this->assertEquals($builder->select(array('id')), 'SELECT `Profiles`.`id` FROM `Profiles`');
		$this->assertEquals($builder->select(array('id','fname')), 'SELECT `Profiles`.`id`,`Profiles`.`fname` FROM `Profiles`');
		$this->assertEquals($builder->select(array('id'),array('-limit'=>1)), 'SELECT `Profiles`.`id` FROM `Profiles` LIMIT 1');
		$this->assertEquals($builder->select(array('id'),array('-limit'=>1,'-skip'=>5)), 'SELECT `Profiles`.`id` FROM `Profiles` LIMIT 5,1');
		$this->assertEquals($builder->select(array('id'),array('-limit'=>1,'-skip'=>5,'fname'=>'John')),
							'SELECT `Profiles`.`id` FROM `Profiles` WHERE `Profiles`.`fname` LIKE \'%John%\' LIMIT 5,1');
							
		$this->assertEquals($builder->select(array('id'),array('-limit'=>1,'-skip'=>5,'fname'=>'John','lname'=>'Smith','id'=>10)),
							'SELECT `Profiles`.`id` FROM `Profiles` WHERE `Profiles`.`fname` LIKE \'%John%\' AND `Profiles`.`lname` LIKE \'%Smith%\' AND `Profiles`.`id` = \'10\' LIMIT 5,1');
							
		$this->assertEquals($builder->select(array('id'),array('-limit'=>1,'-skip'=>5,'fname'=>'John','lname'=>'=Smith','id'=>10)),
							'SELECT `Profiles`.`id` FROM `Profiles` WHERE `Profiles`.`fname` LIKE \'%John%\' AND `Profiles`.`lname` = \'Smith\' AND `Profiles`.`id` = \'10\' LIMIT 5,1');
							
		$this->assertEquals($builder->select(array('id'),array('-limit'=>1,'-skip'=>5,'fname'=>'John','lname'=>'=Smith','id'=>'10..20')),
							'SELECT `Profiles`.`id` FROM `Profiles` WHERE `Profiles`.`fname` LIKE \'%John%\' AND `Profiles`.`lname` = \'Smith\' AND `Profiles`.`id` > \'10\' AND `Profiles`.`id` < \'20\' LIMIT 5,1');
		
		$this->assertEquals($builder->select(array('id'),array('-limit'=>1,'-skip'=>5,'fname'=>'John','lname'=>'=Smith','id'=>'>10')),
							'SELECT `Profiles`.`id` FROM `Profiles` WHERE `Profiles`.`fname` LIKE \'%John%\' AND `Profiles`.`lname` = \'Smith\' AND `Profiles`.`id` > \'10\' LIMIT 5,1');
							
		$this->assertEquals($builder->select(array('id'),array('-limit'=>1,'-skip'=>5,'fname'=>'John','lname'=>'=Smith','id'=>'<10')),
							'SELECT `Profiles`.`id` FROM `Profiles` WHERE `Profiles`.`fname` LIKE \'%John%\' AND `Profiles`.`lname` = \'Smith\' AND `Profiles`.`id` < \'10\' LIMIT 5,1');
							
		$this->assertEquals($builder->select(array('id'),array('-limit'=>1,'-skip'=>5,'fname'=>'John','lname'=>'=Smith','id'=>'!=10')),
							'SELECT `Profiles`.`id` FROM `Profiles` WHERE `Profiles`.`fname` LIKE \'%John%\' AND `Profiles`.`lname` = \'Smith\' AND `Profiles`.`id` <> \'10\' LIMIT 5,1');
		
		$query = $builder->select(array('id'), array('-search'=>'foo'));
		//echo $query;
							
		
		$builder = new Dataface_QueryBuilder('Profiles',array('-skip'=>1, '-limit'=>1));
		$this->assertEquals($builder->select(array('id')), 'SELECT `Profiles`.`id` FROM `Profiles` LIMIT 1,1');
		
		$builder = new Dataface_QueryBuilder('Profiles', array('-limit'=>20) );
		$this->assertEquals($builder->select(array('id')), 'SELECT `Profiles`.`id` FROM `Profiles` LIMIT 20');
		
		
		$builder = new Dataface_QueryBuilder('Profiles',array('-cursor'=>0));
		$res = $builder->select(array('photo'));
		$this->assertTrue( PEAR::isError($res), 'Trying to select blob column should return error');
		
		$builder = new Dataface_QueryBuilder('Profiles',array('-cursor'=>0));
		$builder->includeBlobs();
		$res = $builder->select(array('photo'));
		$this->assertTrue( !PEAR::isError($res), 'Trying to select blob column should return error');
		
		
		// Now test security features
		
		$builder->addSecurityConstraint('fname', '=John');
		$this->assertEquals($builder->select(array('id'),array('-limit'=>1,'-skip'=>5,'fname'=>'John','lname'=>'=Smith','id'=>'!=10')),
							'SELECT `Profiles`.`id` FROM `Profiles` WHERE `Profiles`.`fname` LIKE \'%John%\' AND `Profiles`.`lname` = \'Smith\' AND `Profiles`.`id` <> \'10\' AND `Profiles`.`fname` = \'John\' LIMIT 5,1');
		
		
		
		
	}
	
	function test_query_builder_select_with_meta_data(){
	
		$builder = new Dataface_QueryBuilder('Profiles');
		$builder->selectMetaData = true;
		
		$this->assertEquals('SELECT length(`Profiles`.`id`) as `__id_length`,`Profiles`.`id`,length(`Profiles`.`fname`) as `__fname_length`,`Profiles`.`fname`,length(`Profiles`.`lname`) as `__lname_length`,`Profiles`.`lname`,length(`Profiles`.`title`) as `__title_length`,`Profiles`.`title`,length(`Profiles`.`description`) as `__description_length`,`Profiles`.`description`,length(`Profiles`.`dob`) as `__dob_length`,`Profiles`.`dob`,length(`Profiles`.`phone1`) as `__phone1_length`,`Profiles`.`phone1`,length(`Profiles`.`phone2`) as `__phone2_length`,`Profiles`.`phone2`,length(`Profiles`.`fax`) as `__fax_length`,`Profiles`.`fax`,length(`Profiles`.`email`) as `__email_length`,`Profiles`.`email`,length(`Profiles`.`datecreated`) as `__datecreated_length`,`Profiles`.`datecreated`,length(`Profiles`.`lastmodified`) as `__lastmodified_length`,`Profiles`.`lastmodified`,length(`Profiles`.`favtime`) as `__favtime_length`,`Profiles`.`favtime`,length(`Profiles`.`lastlogin`) as `__lastlogin_length`,`Profiles`.`lastlogin`,length(`Profiles`.`photo`) as `__photo_length`,length(`Profiles`.`thumbnail`) as `__thumbnail_length`,length(`Profiles`.`photo_mimetype`) as `__photo_mimetype_length`,`Profiles`.`photo_mimetype`,length(`Profiles`.`tablefield`) as `__tablefield_length`,`Profiles`.`tablefield` FROM `Profiles`',
		                     $builder->select() );
	
	}
	
	
	function test_query_builder_select_num_rows(){
	
		$builder = new Dataface_QueryBuilder('Profiles');
		$this->assertEquals($builder->select_num_rows(), 'SELECT COUNT(*) FROM `Profiles`');
		$this->assertEquals($builder->select_num_rows( array('phone1'=>'555-555-5555') ), 'SELECT COUNT(*) FROM `Profiles` WHERE `Profiles`.`phone1` LIKE \'%555-555-5555%\'');
		$this->assertEquals($builder->select_num_rows( array('phone1'=>'555-555-5555', '-skip'=>'2') ),'SELECT COUNT(*) FROM `Profiles` WHERE `Profiles`.`phone1` LIKE \'%555-555-5555%\'');
		$this->assertEquals($builder->select_num_rows( array('phone1'=>'555-555-5555', '-skip'=>'2','-limit'=>10) ),'SELECT COUNT(*) FROM `Profiles` WHERE `Profiles`.`phone1` LIKE \'%555-555-5555%\'');
		
		
	}
	
	
	function test_query_builder_update(){
	
		$builder = new Dataface_QueryBuilder('Profiles', array("-limit"=>20));
		
		// test default update functionality.
		
		$s= new Dataface_Record('Profiles', array());
		$s->clearValues();
		$s->setValues( array(
			'id'=>10,
			'fname'=>'John',
			'lname'=>'Smith',
			'title'=>'President Financial Accounting',
			'phone1'=>'555-555-5555',
			'description'=>'This is a description',
			'favtime'=>'14:23:56',
			'dob'=>'1978-12-27',
			'datecreated'=>'19991224060708',
			'lastlogin'=>'1978-12-27 14:45:23') );
		$this->assertEquals($builder->update($s), "UPDATE `Profiles` SET `id` = '10', `fname` = 'John', `lname` = 'Smith', `title` = 'President Financial Accounting', `description` = 'This is a description', `dob` = '1978-12-27', `phone1` = '555-555-5555', `datecreated` = '19991224060708', `favtime` = '14:23:56', `lastlogin` = '1978-12-27 14:45:23' WHERE `Profiles`.`id` = '10' LIMIT 1");
		
		
	}
	
	function test_query_builder_update_snapshot(){
	
		$builder = new Dataface_QueryBuilder('Profiles');
		
		// test default update functionality.
		
		$s = new Dataface_Record('Profiles', array());
		$s->clearValues();
		$s->setValues( array(
			'id'=>10,
			'fname'=>'John',
			'lname'=>'Smith',
			'title'=>'President Financial Accounting',
			'phone1'=>'555-555-5555',
			'description'=>'This is a description',
			'favtime'=>'14:23:56',
			'dob'=>'1978-12-27',
			'datecreated'=>'19991224060708',
			'lastlogin'=>'1978-12-27 14:45:23') );
		$s->setSnapshot();
	
		$s->setValues( array(
			'id'=>50,
			'fname'=>'Susan',
			'lname'=>'Moore',
			'phone1'=>'555-555-5556',
			'description' => 'This is another description',
			'favtime' => '14:23:57',
			'dob'=>'1978-12-28',
			'datecreated'=>'19991224060709',
			'lastlogin'=>'1978-12-28 14:45:24') );
		$this->assertEquals($builder->update($s), "UPDATE `Profiles` SET `id` = '50', `fname` = 'Susan', `lname` = 'Moore', `description` = 'This is another description', `dob` = '1978-12-28', `phone1` = '555-555-5556', `datecreated` = '19991224060709', `favtime` = '14:23:57', `lastlogin` = '1978-12-28 14:45:24' WHERE `Profiles`.`id` = '10' LIMIT 1");
	
	
	}
	
	
	
	function test_query_builder_insert(){
	
		$builder = new Dataface_QueryBuilder('Profiles');
		
		// test default update functionality.
		
		$s = new Dataface_Record('Profiles', array());
		$s->setValues( array(
			'id'=>10,
			'fname'=>'John',
			'lname'=>'Smith',
			'title'=>'President Financial Accounting',
			'phone1'=>'555-555-5555',
			'description'=>'This is a description',
			'favtime'=>'14:23:56',
			'dob'=>'1978-12-27',
			'datecreated'=>'19991224060708',
			'lastlogin'=>'1978-12-27 14:45:23') );
		$this->assertEquals($builder->insert($s), "INSERT INTO `Profiles` (`id`,`fname`,`lname`,`title`,`description`,`dob`,`phone1`,`datecreated`,`favtime`,`lastlogin`) VALUES ('10','John','Smith','President Financial Accounting','This is a description','1978-12-27','555-555-5555','19991224060708','14:23:56','1978-12-27 14:45:23')");
	
	
	
	}
	
	function test_query_builder_delete(){
	
		$builder = new Dataface_QueryBuilder('Profiles');
		$s =& $this->table1;
		$this->assertEquals($builder->delete(), "DELETE FROM `Profiles`");
		$this->assertEquals($builder->delete(array('-skip'=>5,'-limit'=>2)), "DELETE FROM `Profiles` LIMIT 5,2");
		$this->assertEquals($builder->delete(array('-skip'=>5,'-limit'=>2,'fname'=>'John')), "DELETE FROM `Profiles` WHERE `Profiles`.`fname` LIKE '%John%' LIMIT 5,2");
	
	}
	
	
	
	
	
	function test_add_related_record_sql(){
		$s = new Dataface_Record('Profiles',array());
		$builder = new Dataface_QueryBuilder('Profiles');
		$s->setValue('id', 15);
		
		$newRecord = new Dataface_RelatedRecord($s, "addresses");
		//print_r($s->relationships());
		$res = $builder->addRelatedRecord($newRecord);
		if ( PEAR::isError($res) ) echo $res->toString();
		$this->assertEquals(
			array('Addresses'=>"INSERT INTO `Addresses` (`profileid`) VALUES ('15')"),
			$res
			
		);
			
		$newCourse = new Dataface_RelatedRecord($s, "courses");
		$res = $builder->addRelatedRecord($newCourse);
		if ( PEAR::isError($res) ) echo $res->toString();
		$this->assertEquals(
			array('Courses'=>"INSERT INTO `Courses` () VALUES ()",
				  'Student_Courses'=> "INSERT INTO `Student_Courses` (`studentid`,`courseid`) VALUES ('15','__Courses__auto_increment__')"),
			$res
		);
		
		$newAppointment = new Dataface_RelatedRecord($s, "appointments", array('profileid'=>2));
		$res = $builder->addRelatedRecord($newAppointment);
		if ( PEAR::isError($res) ) echo $res->toString();
		$this->assertEquals(
			array('Appointments'=>"INSERT INTO `Appointments` (`profileid`) VALUES ('15')"),
			$res
		);
	}
	
	function test_add_existing_related_record_sql(){
		$s = new Dataface_Record('Profiles');
		$builder= new Dataface_QueryBuilder('Profiles');
		$existingRecord = new Dataface_RelatedRecord($s, 'addresses');
		$s->setValue('id', 15);
		
		$res = $builder->addExistingRelatedRecord($existingRecord);
		if ( PEAR::isError($res) ) echo $res->toString();
		else {
			
			//print_r($res);
		}
		$this->assertEquals(
			array('Addresses'=>"INSERT INTO `Addresses` (`profileid`) VALUES ('15')"),
			$res
		);
		$existingCourse = new Dataface_RelatedRecord($s, 'courses', array('id'=>3));	
		$res = $builder->addExistingRelatedRecord($existingCourse);
		if ( PEAR::isError($res) ) echo $res->toString();
		else {
			//print_r($res);
		}
		$this->assertEquals(
			array('Student_Courses'=>"INSERT INTO `Student_Courses` (`studentid`,`courseid`) VALUES ('15','3')"),
			$res
		);
	
	}
	
	function test_match(){
		$builder = new Dataface_QueryBuilder('Profiles');
		$query = $builder->_match('foo');
		$this->assertEquals(
			"MATCH (`Profiles`.`title`,`Profiles`.`description`) AGAINST ('foo' IN BOOLEAN MODE)",
			$query);
		
		$query = $builder->_match('foo', true);
		$this->assertEquals(
			"MATCH (`Profiles`.`title`,`Profiles`.`description`) AGAINST ('foo' IN BOOLEAN MODE)",
			$query);
		
		$query = $builder->_match('"foo bar"', true);
		$this->assertEquals(
			"MATCH (`Profiles`.`title`,`Profiles`.`description`) AGAINST ('\"foo bar\"' IN BOOLEAN MODE)",
			$query);
		
	
	}
	
	function test_search(){
		$builder = new Dataface_QueryBuilder('Profiles');
		$query = $builder->search('foo');
		$this->assertEquals(
			"SELECT `Profiles`.`id`,`Profiles`.`fname`,`Profiles`.`lname`,`Profiles`.`title`,`Profiles`.`description`,`Profiles`.`dob`,`Profiles`.`phone1`,`Profiles`.`phone2`,`Profiles`.`fax`,`Profiles`.`email`,`Profiles`.`datecreated`,`Profiles`.`lastmodified`,`Profiles`.`favtime`,`Profiles`.`lastlogin`,`Profiles`.`photo_mimetype`,`Profiles`.`tablefield` FROM `Profiles` WHERE MATCH (`Profiles`.`title`,`Profiles`.`description`) AGAINST ('foo' IN BOOLEAN MODE)",
			$query);
		$query = $builder->search('foo', array('title','description'));
		$this->assertEquals(
			"SELECT `Profiles`.`title`,`Profiles`.`description` FROM `Profiles` WHERE MATCH (`Profiles`.`title`,`Profiles`.`description`) AGAINST ('foo' IN BOOLEAN MODE)",
			$query);
		$query = $builder->search('foo', array('title','description'), array('-skip'=>5,'-limit'=>10));
		$this->assertEquals(
			"SELECT `Profiles`.`title`,`Profiles`.`description` FROM `Profiles` WHERE MATCH (`Profiles`.`title`,`Profiles`.`description`) AGAINST ('foo' IN BOOLEAN MODE) LIMIT 5,10",
			$query);
		
	
	
	}
	
	function test_or(){
		$builder = new Dataface_QueryBuilder('Profiles');
		$query = $builder->_where(array('fname'=>'Steve OR John'));
		$this->assertEquals(
				"WHERE (`Profiles`.`fname` LIKE '%Steve%' OR `Profiles`.`fname` LIKE '%John%')",
				$query
		
			);
			
		$query = $builder->_where(array('fname'=>'Steve OR John', 'lname'=>'Smith OR MacLean'));
		$this->assertEquals(
				"WHERE (`Profiles`.`fname` LIKE '%Steve%' OR `Profiles`.`fname` LIKE '%John%') AND (`Profiles`.`lname` LIKE '%Smith%' OR `Profiles`.`lname` LIKE '%MacLean%')",
				$query
		
			);
	}
	
	function test_repeat_field(){
		$builder = new Dataface_QueryBuilder('People');
		$query = $builder->_where(array('Interests'=>'1 OR 2'));
			// Interests is a repeating field (checkbox with vocabulary) where values are stored one per line.
			// The query must take this into account and use regular expressions.
		$this->assertEquals(
			"WHERE (`People`.`Interests` RLIKE '[[:<:]]1[[:>:]]' OR `People`.`Interests` RLIKE '[[:<:]]2[[:>:]]')",
			$query
			);
	}
	
	function test_metadata_from(){
		$app =& Dataface_Application::getInstance();
		$original_value = @$app->_conf['metadata_enabled'];
		$app->_conf['metadata_enabled'] = 1;
		import('Dataface/MetadataTool.php');
		$mt = new Dataface_MetadataTool('Profiles');
		$mt->createMetadataTable();
		$builder = new Dataface_QueryBuilder('Profiles');
		echo ($from = $builder->_from());
		$sql = "select * {$from} where id='10'";
		//$res = mysql_query($sql, $app->db());
		//if ( !$res ) trigger_error(mysql_error($app->db()), E_USER_ERROR);
		
		
		
	}
	
	


}



?>
