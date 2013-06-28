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

class TableTest extends BaseTest {

	var $db;
	var $table1;
	var $fieldnames_control;
	var $types_control;
	
	function TableTest($name = 'TableTest'){
		//$this->PHPUnit_TestCase($name);
		//parent::BaseTest();
		$this->BaseTest($name);
	}
	
	function test_enough_columns_loaded(){
		
		
		$fields =& $this->table1->fields();
		
		$fieldnames = array_keys($fields);
		foreach ($this->fieldnames_control as $name){
			$this->assertTrue( in_array($name, $fieldnames), "Column $name not found in table");
			
		}
	}
	
	
	function test_not_too_many_columns_loaded(){
		$fields =& $this->table1->fields();
		$fieldnames = array_keys($fields);
		foreach ($fieldnames as $name){
			$this->assertTrue( in_array($name, $this->fieldnames_control), "Table column $name should not be in the table");
			$field =& $fields[$name];
			
			unset($field);
		}
		
		
			
	
	}
	
	function test_column_types(){
		$fields =& $this->table1->fields();
		$fieldnames = array_keys($fields);
		foreach ($fieldnames as $name){
			$field =& $fields[$name];
			$this->assertEquals( strtolower($this->types_control[$name]),  strtolower($field['Type']) );
			
			unset($field);
		}
	
	}
	
	function test_keys(){
		$control = array("id");
		$keys =& $this->table1->keys();
		$keynames = array_keys($keys);
		foreach ($keynames as $keyname){
			$this->assertTrue( in_array($keyname, $control), "Key $keyname should not be a key");
		}
		
		foreach ($control as $keyname){
			$this->assertTrue( in_array($keyname, $keynames), "Key $keyname is not registering as a key in the table when it should.");
		}
	}
	
	
	
	
	function test_getType(){
		$this->assertEquals($this->table1->getType('id'), 'int');
		$this->assertEquals($this->table1->getType('fname'), 'varchar');
		$this->assertEquals($this->table1->getType('fax'), 'varchar');
		$this->assertEquals($this->table1->getType('dob'), 'date');
		$this->assertEquals($this->table1->getType('description'), 'text');
		$this->assertEquals($this->table1->getType('datecreated'), 'timestamp');
	
	}
	
	function test_isDate(){
	
		$this->assertTrue($this->table1->isDate('dob'));
		$this->assertTrue($this->table1->isDate('datecreated'));
		$this->assertTrue($this->table1->isDate('favtime'));
		$this->assertTrue($this->table1->isDate('lastlogin'));
		$this->assertTrue(!$this->table1->isDate('id'));
		$this->assertTrue(!$this->table1->isDate('description'));
		$this->assertTrue(!$this->table1->isDate('fname'));
	}
	
	function test_parse_date(){
		$date1 = "1978-12-27";
		$date2 = "June 13, 2005";
		
		$parsed1 = $this->table1->parse_date($date1);
		$parsed2 = $this->table1->parse_date($date2);
		
		$this->assertEquals($parsed1['year'], '1978');
		$this->assertEquals($parsed1['month'], '12');
		$this->assertEquals($parsed1['day'], '27');
		
		$this->assertEquals($parsed2['year'], '2005');
		$this->assertEquals($parsed2['month'], '06' );
		$this->assertEquals($parsed2['day'], '13');
	
	}
	
	function test_parse_time(){
		$time1 = "3:00pm";
		$time2 = "14:26:34";
		
		$parsed1 = $this->table1->parse_time($time1);
		$parsed2 = $this->table1->parse_time($time2);
		
		$this->assertEquals($parsed1['hours'], '15');
		$this->assertEquals($parsed1['minutes'], '00');
		$this->assertEquals($parsed1['seconds'], '00');
		
		$this->assertEquals($parsed2['hours'], '14');
		$this->assertEquals($parsed2['minutes'], '26');
		$this->assertEquals($parsed2['seconds'], '34');
		
	
	}
	
	
	function test_parse_datetime(){
	
		$dt1 = "1978-12-27 14:56:23";
		$dt2 = "February 15, 2003 3:00pm";
		
		$parsed1 = $this->table1->parse_datetime($dt1);
		$parsed2 = $this->table1->parse_datetime($dt2);
		
		$this->assertEquals( $parsed1['year'], '1978');
		$this->assertEquals( $parsed1['month'], '12');
		$this->assertEquals( $parsed1['day'], '27');
		$this->assertEquals( $parsed1['hours'], '14');
		$this->assertEquals( $parsed1['minutes'], '56');
		$this->assertEquals( $parsed1['seconds'], '23');
		
	
	}
	
	
	
	
	function test_hasKey(){
	
		$this->assertTrue($this->table1->hasKey('id'));
		$this->assertTrue(!$this->table1->hasKey('fname'));
		$this->assertTrue(!$this->table1->hasKey('fake'));
	}
	
		
	function test_fieldsIniFilePath(){
		$this->assertEquals(  realpath(dirname(__FILE__).'/tables/Profiles/fields.ini'), $this->table1->_fieldsIniFilePath());
	}
	
	function test_hasFieldsIniFile(){
		$this->assertTrue( $this->table1->_hasFieldsIniFile() );
	}
	
	function test_init_permissions(){
		$fields =& $this->table1->fields();
		$id =& $fields['id'];
		$perms =& $id['permissions'];
		$this->assertEquals($perms['view'], 1);
		$this->assertEquals($perms['edit'], 1);
		
		$perms1 =& $fields['description']['permissions'];
		$this->assertTrue($perms1['view']);
		$this->assertTrue($perms1['edit']);
		
		
	
	}
	
	function test_init_widget(){
	
		$fields =& $this->table1->fields();
		$id =& $fields['id'];
		$widget =& $id['widget'];
		$this->assertEquals($widget['label'], 'Id');
		$this->assertEquals($widget['type'], 'hidden');
		$this->assertEquals($widget['description'], '');
		
		$widget2 =& $fields['description']['widget'];
		$this->assertEquals($widget2['label'], 'Description');
		$this->assertEquals($widget2['type'], 'htmlarea');
		$this->assertEquals($widget2['description'], 'Please enter a description of yourself.');
		
		$blob =& $fields['thumbnail']['widget'];
		$this->assertEquals($blob['type'], 'file');
		
		$blob2 =& $fields['photo']['widget'];
		$this->assertEquals($blob2['type'], 'file');
		
		$datetime =& $fields['lastlogin']['widget'];
		$this->assertEquals($datetime['type'], 'calendar');
		
		$this->assertEquals($fields['photo_mimetype']['widget']['type'], 'hidden');
		$this->assertEquals($fields['photo']['mimetype'], 'photo_mimetype');
	}
	
	function test_validators(){
		$fields =& $this->table1->fields();
		$id =& $fields['id'];
		$validators = $id['validators'];
		//$this->assertEquals($validators['maxlength']['arg'], 11);
		//$this->assertTrue(!isset($validators['required']));
		
		
		$fname =& $fields['fname'];
		$validators = $fname['validators'];
		//$this->assertEquals($validators['maxlength']['arg'], 32);
		//$this->assertTrue(isset($validators['required']));
		
		
		
	
	}
	
	
	
	
	function test_valuelists(){
		
		$colors =& $this->table1->getValuelist('Colors');
		$this->assertEquals(count($colors), 4);
		$this->assertEquals($colors['red'], 'Red');
		$this->assertEquals($colors['blue'], 'Blue');
		$this->assertEquals($colors['brown'], 'Brown');
	
	}
	
	
	function test_parseString(){
	
		$this->assertEquals( $this->table1->parseString('My id number is $id', array('id'=>10, 'fname'=>'John')), 'My id number is 10');
		$this->assertEquals( $this->table1->parseString('$id is my id number',array('id'=>10, 'fname'=>'John')), '10 is my id number');
		$this->assertEquals( $this->table1->parseString('Id number: ($id)',array('id'=>10, 'fname'=>'John')), 'Id number: (10)');
		
		// try two fields in same attempt
		$this->assertEquals( $this->table1->parseString('First name: $fname, Id: $id',array('id'=>10, 'fname'=>'John')), 'First name: John, Id: 10');
		// try a non-existent column
		$this->assertEquals( $this->table1->parseString('First name: $firstname',array('id'=>10, 'fname'=>'John')), 'First name: ');
		
		// try to escape the sign
		$this->assertEquals( $this->table1->parseString('We have \$50.00 for id: $id',array('id'=>10, 'fname'=>'John')), 'We have \$50.00 for id: 10');
		
		// trysuccessive
		$this->assertEquals( $this->table1->parseString('$fname$id',array('id'=>10, 'fname'=>'John')), 'John10');
		
		
		
	
	}
	
	
	
	
		
	
	
	
	
	

	
	function test_is_metafield(){
	
		$s =& $this->table1;
		$this->assertTrue( !$s->isMetaField('fname') );
		$this->assertTrue( !$s->isMetaField('id') );
		$this->assertTrue( !$s->isMetaField('addresses.line1') );
		$this->assertTrue( !$s->isMetaField('photo') );
		$this->assertTrue( $s->isMetaField('photo_mimetype') );
	
	}
	
	
	
	
	function test_load_delegate(){
	
		$profiles =& Dataface_Table::loadTable('Profiles');
		$addresses =& Dataface_Table::loadTable('Addresses');
		$this->assertTrue( $profiles->_hasDelegateFile() );
		$this->assertTrue( !$addresses->_hasDelegateFile() );
		$this->assertEquals(strtolower(get_class($profiles->getDelegate())), 'tables_profiles');
		$this->assertEquals($addresses->getDelegate(),null);
	}
	
	function test_get_relationship(){
	
		$t =& $this->table1;
		$r =& $t->getRelationship('addresses');
		$this->assertTrue(is_a($r, 'Dataface_Relationship'));
	}
	
	
	function test_load_table_for_table_field(){
		$profiles =& Dataface_Table::loadTable('Profiles');
		
		$s1 = $profiles->getTableTableForField('id');
		$this->assertEquals(  'Profiles', $s1->tablename);
		$s2 = $profiles->getTableTableForField('addresses.line1');
		$this->assertEquals('Addresses', $s2->tablename );
	}
	
	
	
	
	function test_absolute_field_name(){
		$s =& $this->table1;
		$expected = array();
		$actual = array();
		
		$actual[] = $s->absoluteFieldName('id', array('Profiles'));
		$expected[] = 'Profiles.id';
		
		$actual[] = $s->absoluteFieldName('Profiles.id', array('Profiles'));
		$expected[] = 'Profiles.id';
		
		$actual[] = $s->absoluteFieldName('Profiles.id', array('Profiles', 'Addresses') );
		$expected[] = 'Profiles.id';
		
		$actual[] = $s->absoluteFieldName('phone1', array('Profiles', 'Addresses'));
		$expected[] = 'Profiles.phone1';
		
		$actual[] = $s->absoluteFieldName('city', array('Profiles', 'Addresses'));
		$expected[] = 'Addresses.city';
		
		for ($i=0; $i<sizeof($expected); $i++){
			$this->assertEquals($actual[$i], $expected[$i]);
		}
	
	}
	
	function test_field_exists(){
		$s =& $this->table1;
		
		$this->assertTrue( $s->fieldExists('Profiles.id') );
		$this->assertTrue( !$s->fieldExists('id') );
		$this->assertTrue( $s->fieldExists('Addresses.city') );
	
	}
	
	function test_relative_field_name(){
		$s =& $this->table1;
		
		$this->assertEquals( 'id', $s->relativeFieldname('Profiles.id') );
		$this->assertEquals( 'city', $s->relativeFieldname('Addresses.city') );
		
	
	
	}
	
	
	function test_get_table_field(){
		$s =& $this->table1;
		
		$this->assertEquals( $s->getField('id'), $s->getTableField('Profiles.id'));
		$this->assertEquals( $s->getField('addresses.city'), $s->getTableField('Addresses.city'));
	}
	
	function test_get_table_for_field(){
	
		$s =& $this->table1;
		$this->assertEquals( Dataface_Table::loadTable('Addresses'), $s->getTableTableForField('addresses.city') );
	}
	
	
	function test_table_field(){
		$tf =& $this->table1->getField('tablefield');
		$this->assertEquals('table', $tf['widget']['type']);
		$this->assertEquals('int(11)', $tf['fields']['column1']['Type']);
		$this->assertEquals('select', $tf['fields']['column1']['widget']['type']);
		$this->assertEquals('Profiles',$tf['fields']['column1']['tablename']);
		
	
	}
	
	function test_import_tables(){
		$t =& $this->table1;
		
		$importTableName = $t->createImportTable();
		echo $importTableName;
		$this->assertTrue(preg_match('/^Profiles__import_(\d+)_(\d+)$/', $importTableName));
		$this->assertEquals( array($importTableName), $t->getImportTables());
		
		
	
	
	}
	
	
	function test_get_import_filters(){
		$t =& $this->table1;
		$filters =& $t->getImportFilters();
		$this->assertEquals(
			array('xml','test2'),
			array_keys($filters)
		);
		
		$this->assertEquals('xml', $filters['xml']->name);
		$this->assertEquals('Xml', $filters['xml']->label);
		$this->assertEquals('test2', $filters['test2']->name);
		$this->assertEquals('Test2', $filters['test2']->label);
	
	}
	
	function test_register_import_filter(){
		$t =& $this->table1;
		$filters =& $t->getImportFilters();
		$filter = new Dataface_ImportFilter($t->tablename, 'registered', 'Registered');
		$t->registerImportFilter($filter);
		
		$this->assertEquals(
			array('xml','test2','registered'),
			array_keys($filters)
		);
		
		$this->assertEquals('registered', $filters['registered']->name);
		$this->assertEquals('Registered', $filters['registered']->label);
	
	}
	
	
	function test_add_relationship(){
	
		$t =& $this->table1;
		$rel = array("__sql__"=>"SELECT * from Addresses WHERE city = 'Springfield'");
		$t->addRelationship("summer_homes", $rel);
		$this->assertTrue(is_a($t->_relationships['summer_homes'], 'Dataface_Relationship'));
	
	}
	
	
		
		
		
	
	


}


class Benchmarker extends PHPUnit_TestListener {
	
	function addError(&$test, &$t){}
	function addFailure( &$test, &$t){}
	function startTest(&$test){ echo "Starting test $test";}
	function endTest(&$test){ echo "Ending test $test"; print_r($test);}
}


/*
 * class MyListener extends PHPUnit_TestListener {
 *     function addError(&$test, &$t) {
 *         print "MyListener::addError() called.\n";
 *     }
 *
 *     function addFailure(&$test, &$t) {
 *         print "MyListener::addFailure() called.\n";
 *     }
 *
 *     function endTest(&$test) {
 *         print "MyListener::endTest() called.\n";
 *     }
 *
 *     function startTest(&$test) {
 *         print "MyListener::startTest() called.\n";
 *     }
 * }
 */



?>
