<?php
$_SERVER['PHP_SELF'] = __FILE__;
require_once 'BaseTest.php';
require_once 'Dataface/Record.php';
require_once 'dataface-public-api.php';


class RecordTest extends BaseTest {

	
	
	function RecordTest($name = 'RecordTest'){
		$this->BaseTest($name);
		//parent::BaseTest();
	}
	
	
	function test_init(){
		$record = new Dataface_Record('Profiles', array('id'=>3));
		$this->assertEquals(array('id'=>3), $record->vals(array('id')));
		$this->assertEquals(array('id'=>3), $record->values(array('id')) );
		$this->assertEquals(array('id'=>3), $record->getValues(array('id')));
		$table =& $record->table();
		$keys = array_keys($table->fields());
		$expected = array();
		foreach ( $keys as $key ) $expected[$key] = null;
		$expected['id'] = 3;
		$this->assertEquals($expected, $record->vals() );
		
	}
	
	
	function test_getters_and_setters(){
		$record = new Dataface_Record('Profiles', array('id'=>3));
		$record->setValue('id',5);
		$this->assertEquals($record->getValue('id'), 5);
		$this->assertEquals($record->value('id'), 5);
		$this->assertEquals($record->val('id'), 5);
		
		$record = new Dataface_Record('Test', array('id'=>3));
		$record->setValue('varcharfield_checkboxes', array('1','3'));
		$this->assertEquals(array('1','3'), $record->getValue('varcharfield_checkboxes'));
		$record->setValue('varcharfield_checkboxes', "1\n3");
		$this->assertEquals(array('1','3'), $record->getValue('varcharfield_checkboxes'));
		
	
	}
	
	
	function test_get_values(){
		$record = new Dataface_Record('Profiles', array('id'=>3, 'fname'=>'John', 'lname'=>'Smith'));
		$this->assertEquals(array('id'=>3, 'lname'=>'Smith'), $record->getValues(array('id','lname')));

	
	}	
	
	function test_get_length(){
		$record = new Dataface_Record('Profiles', array('id'=>3, 'fname'=>'John', 'lname'=>'Smith'));
		$this->assertEquals(1, $record->getLength('id'));
		$this->assertEquals(4, $record->getLength('fname'));
		
		$record->setValue('id', 10);
		$this->assertEquals(7, $record->getLength('appointments.position',1));
		$this->assertEquals(8, $record->getLength('appointments.position',0));
	
	}
	
	function test_set_date(){
	
		$record = new Dataface_Record('Profiles', array('id'=>3));
		$record->setValue('lastlogin', "February 4 2005 12:36:15");
		$val = $record->val('lastlogin');
		$this->assertEquals(2005, $val['year']);
		$this->assertEquals(2, $val['month']);
		$this->assertEquals(4, $val['day']);
		$this->assertEquals(12, $val['hours']);
		$this->assertEquals(36, $val['minutes']);
		$this->assertEquals(15, $val['seconds']);
		
		
		$record->setValue('lastlogin', '2004-12-27 9:23:24');
		$val = $record->val('lastlogin');
		$this->assertEquals(2004, $val['year']);
		$this->assertEquals(12, $val['month']);
		$this->assertEquals(27, $val['day']);
		$this->assertEquals(9, $val['hours']);
		$this->assertEquals(23, $val['minutes']);
		$this->assertEquals(24, $val['seconds']);
		
	
	}
	
	function test_string_values(){
		$record = new Dataface_Record('Profiles', array());
		$record->setValue('id',6);
		$this->assertEquals($record->strval('id'), "6");
		$this->assertEquals($record->stringValue('id'), "6");
		$this->assertEquals($record->getValueAsString('id'), "6");
		$record2 = new Dataface_Record('Test', array('id'=>2));
		$record2->setValue('timestampfield_date', "February 4 2005 12:36:15");
		$this->assertEquals('20050204123615', $record2->strval('timestampfield_date'));
	}
	
	function test_display_values(){
		$record = new Dataface_Record('Profiles', array());
		$record->setValue('id',6);
		$this->assertEquals($record->printval('id'), "6");
		$this->assertEquals($record->q('id'), "6");
		$this->assertEquals($record->display('id'), "6");
		
		
		$record = new Dataface_Record('Test',array('id'=>2));
		$record->setValue('intfield_vocab_select', 2);
		$this->assertEquals("Blue", $record->display('intfield_vocab_select'));
		
		$record->setValue('varcharfield_select', "Not in vocab");
		$this->assertEquals("Not in vocab", $record->display('varcharfield_select'));
		/*
		$record->setValue('varcharfield_checkboxes', array('1','3'));
		$this->assertEquals('NO ACCESS',  $record->display('varcharfield_checkboxes'));
			// This should be no access because we have added a permissions:view = 0 to the fields.ini file for this field.
		
		$record->setValue('varcharfield_checkboxes', "1\n3\n");
		$this->assertEquals('NO ACCESS',  $record->display('varcharfield_checkboxes'));
		*/
		$record->setValue('datetimefield_date', "February 4 2005 12:36:15");
		$this->assertEquals("2005-02-04 12:36:15", $record->display('datetimefield_date'));
		
		$record->setValue('timestampfield_date', "February 4 2005 12:36:15");
		$this->assertEquals("20050204123615", $record->display('timestampfield_date'));
		
		$record->setValue('datefield_date', "February 4 2005 12:36:15");
		$this->assertEquals("2005-02-04", $record->display('datefield_date'));
		
		$record->setValue('timefield_date', "February 4 2005 12:36:15");
		$this->assertEquals("12:36:15", $record->display('timefield_date'));
		
		$this->assertEquals(__FILE__."?-action=getBlob&-table=Test&-field=blobfield&-index=0&id=2", $record->display('blobfield'));

		
	
		
	
	}
	
	function test_value_changed(){
		$s = new Dataface_Record('Profiles', array());
		$s->clearValues();
		$this->assertTrue( !$s->valueChanged('id') );
		$s->setValue('id',10);
		//$this->assertTrue( !$s->valueChanged('addresses.city',1) );
		//$s->setValue('addresses.city', 'Houston', 1);
		//$this->assertTrue( $s->valueChanged('addresses.city',1) );
		
		$s->setValue('id',50);
		$this->assertTrue( $s->valueChanged('id'));
	
	}
	
	
	function test_snapshots(){
		$s = new Dataface_Record('Profiles', array());
		$snapshot =& $s->getSnapshot();
		//$this->assertTrue(is_array($snapshot));
		
		/*$temp = array();
		
		foreach ( array_keys($snapshot) as $key){
			if ( strlen($snapshot[$key]) > 0 ) $temp[$key] = $snapshot[$key];
		}
		
		$this->assertEquals(array(), $temp);
		*/
		$this->assertEquals(null, $snapshot);
		$s->setValue('id', 10);
		$s->setSnapshot();
		$this->assertTrue( $s->snapshotExists());
		$expected = array();
		foreach ( array_keys($s->_table->fields()) as $field){
			$expected[$field] = null;
		}
		$expected['id'] = 10;
		$this->assertEquals($expected, $s->getSnapshot());
		$this->assertTrue( !$s->valueChanged('id') );
		$this->assertTrue( !$s->valueChanged('fname') );
		
		$s->setValue('id',50);
		$this->assertEquals($expected, $s->getSnapshot());
		$this->assertTrue( $s->valueChanged('id') );
		$this->assertTrue( !$s->valueChanged('fname') );
		
		$this->assertEquals(array('id'=>10), $s->snapshotKeys());
	
	}
	
	
	
	
	
	function test_iterator(){
		$records = array();
		for ($i=0; $i<10; $i++){
			$records[] = array('id'=>$i);
		}
		
		$it = new Dataface_RecordIterator('Profiles',$records);
		$index = 0;
		while ( $it->hasNext() ){
			$record = $it->next();
			$this->assertEquals($index++, $record->getValue('id') );
		}
	
	}
	
	
	
	function test_set_scalar_values(){
	
		$control = array(
			"id" => 5,
			"fname" => "John",
			"lname" => "Smith",
			"title" => "President Financial Accounting"
		);
		
		$record = new Dataface_Record('Profiles', $control);
		
		
		$fields =& $this->table1->fields();
		
		$this->assertEquals($record->getValue('id'), 5);
		$this->assertEquals( "John", $record->getValue('fname'));
		$this->assertEquals("Smith", $record->getValue('lname'));
		$this->assertEquals("President Financial Accounting", $record->getValue('title'));
	
	}
	
	function test_set_date_value_as_string(){
		$date1 = 'February 21, 2004';
		$record = new Dataface_Record('Profiles', array());
		
		$record->setValues(array('dob'=>$date1));
		
		$parsed = $record->getValue('dob');
		
		$this->assertEquals($parsed['year'], '2004');
		$this->assertEquals($parsed['month'], '02');
		$this->assertEquals($parsed['day'], '21');
	
	}
	
	function test_set_date_value_as_array(){
		$date = array("Y"=>"2004", "m"=>2, "d"=>"21");
		$record = new Dataface_Record('Profiles', array());
		$record->setValues(array('dob'=> $date) );
		
		$parsed = $record->getValue('dob');
		
		$this->assertEquals($parsed['year'], '2004');
		$this->assertEquals($parsed['month'], '02');
		$this->assertEquals($parsed['day'], '21');
		
	}
	
	function test_set_datetime_value_as_string(){
	
		$dt1 = "1978-12-27 14:56:23";
		$record = new Dataface_Record('Profiles', array());
		
		$record->setValue('dob', $dt1);
		
		$parsed1 = $record->getValue('dob');
		
		$this->assertEquals( $parsed1['year'], '1978');
		$this->assertEquals( $parsed1['month'], '12');
		$this->assertEquals( $parsed1['day'], '27');
		$this->assertEquals( $parsed1['hours'], '14');
		$this->assertEquals( $parsed1['minutes'], '56');
		$this->assertEquals( $parsed1['seconds'], '23');
	
	}
	
	function test_get_value_as_string(){
	
		$s = new Dataface_Record('Profiles', array());
		$s->setValue('id',10);
		$this->assertEquals($s->getValueAsString('id'), '10');
	}
	
	
	function test_serializedDate(){
	
		$dt1 = "1978-12-27 14:56:23";
		$record = new Dataface_Record('Profiles', array());
		$record->setValue('dob',$dt1);
		
		$sv = $record->getSerializedValue('dob');
		
		$this->assertEquals($sv, '1978-12-27');
		
	}
	
	
	function test_serializeDatetime(){
	
		$dt1 = "1978-12-27 14:56:23";
		$record = new Dataface_Record('Profiles', array());
		$record->setValue('lastlogin',$dt1);
		
		$sv = $record->getSerializedValue('lastlogin');
		
		$this->assertEquals($sv, '1978-12-27 14:56:23');
	}
	
	
	function test_serializeTime(){
	
		$dt1 = "1978-12-27 14:56:23";
		$record = new Dataface_Record('Profiles', array());
		$record->setValue('favtime',$dt1);
		
		$sv = $record->getSerializedValue('favtime');
		
		$this->assertEquals($sv, '14:56:23');
	
	}
	
	function test_serializeTimestamp(){
	
		$dt1 = "1978-12-27 14:56:23";
		$record = new Dataface_Record('Profiles', array());
		$record->setValue('datecreated',$dt1);
		
		$sv = $record->getSerializedValue('datecreated');
		
		$this->assertEquals($sv, '19781227145623');
	
	}
	
	function test_serializeVarchar(){
	
		$vc =  "Stanley";
		$record = new Dataface_Record('Profiles', array());
		$record->setValue('fname', $vc);
		$sv = $record->getSerializedValue('fname');
		
		$this->assertEquals($sv, $vc);
	
	
	}
	
	
	
	function test_hasValue(){
		$record = new Dataface_Record('Profiles', array());
		$record->setValue('id', 5);
		$record->setValue('fname','tom');
		
		$this->assertTrue($record->hasValue('id'));
		$this->assertTrue($record->hasValue('fname'));
		$this->assertTrue(!$record->hasValue('fake'));
		
		$record->setValue('id', null);
		$this->assertTrue($record->hasValue('id'));
		
	}
	
	
	
	function test_getValues(){
		$record = new Dataface_Record('Profiles', array());
		$vals = array(
			'id' => 5,
			'fname' => 'John',
			'lname' => 'Smith',
			'description' => 'This is a description',
			'dob' => 'December 27, 1978',
			'phone1' => '555-555-5555'
			);
			
		$record->setValues($vals);
		
		$ret = $record->getValues();
		
		
		foreach ($vals as $key=>$value){
			$this->assertTrue( isset( $ret[$key]) );
		}
		
		
		$ret = $record->getValues( array('id', 'fname') );
		$this->assertTrue( count($ret) == 2 );
		$this->assertTrue( isset( $ret['id'] ) );
		$this->assertTrue( isset( $ret['fname'] ) );
	
	}
	
	function test_get_value(){
	
		$vals = array(
			'id' => 5,
			'fname' => 'John',
			'lname' => 'Smith',
			'description' => 'This is a description',
			'dob' => 'December 27, 1978',
			'phone1' => '555-555-5555'
			);
		$s = new Dataface_Record('Profiles', array());
		$s->setValues($vals);
		
		$this->assertEquals( $s->getValue('id'), '5');
		$this->assertEquals( $s->getValue('fname'), 'John');
		$this->assertEquals( $s->getValue('lname'), 'Smith');
		
		$s->setValue('id', 10);
		
		$this->assertEquals('Gotham', $s->getValue('addresses.city',0) );
		$this->assertEquals('Gotham',  $s->getValue('addresses.city', false, "id='1'") );
		$this->assertEquals('Springfield',  $s->getValue('addresses.city', 1) );
		$this->assertEquals('Springfield', $s->getValue('addresses.city', false, "id='2'") );
		
	
	}
	
	
	function test_get_indexed_values(){
	
		$s = new Dataface_Record('Profiles', array());
		//$s->setValue('addresses.line1', '888 Elm');
		//$this->assertEquals($s->getValue('addresses.line1'), '888 Elm');
		//$this->assertEquals($s->getValue('addresses.line1', 0), '888 Elm');
		
		
		
	}
	
	
	
	function test_flags(){
		$s = new Dataface_Record('Profiles', array());
		$s->clearValue('id');
		$this->assertTrue(!$s->valueChanged('id'), 'ID has not been changed, yet it says it has.');
		$s->setValue('id',112);
		$this->assertTrue($s->valueChanged('id'), 'ID has changed, yet it says it hasn\'t');
		$s->clearFlag('id');
		$this->assertTrue(!$s->valueChanged('id'), 'Clear flag did not work');
		$s->setValue('id', 112);
		$this->assertTrue(!$s->valueChanged('id'), 'Setting value to same value should not set off dirty flag.');
		$s->setValue('id', 111);
		$this->assertTrue($s->valueChanged('id'), 'Setting value to different value should set off dirty flag.');
		$s->clearFlags();
		$this->assertTrue(!$s->valueChanged('id'), 'Clearing all flags did not clear flag for id.');
		$s->setValue('id',10);
		$this->assertTrue($s->valueChanged('id'));
		$s->clearValues();
		$this->assertTrue(!$s->valueChanged('id'));
		
	
	
	}
	
	function test_get_related_records(){
		$s = new Dataface_Record('Profiles', array());
		$s->clearValues();
		$s->setValue('id', 10);
		$records = $s->getRelatedRecords('addresses');
		$this->assertEquals( count($records), 2);
		
		$row1 =& $records[0];
		$this->assertEquals($row1['line1'], '555 Elm St');
		$this->assertEquals($row1['line2'], 'Box 123');
		$row2 =& $records[1];
		$this->assertEquals('123 Perl Drive', $row2['line1']);
		
		$records = $s->getRelatedRecords('addresses', "line1='555 Elm St'");
		$this->assertEquals( count($records), 1);
		$this->assertEquals('555 Elm St', $records[0]['line1']);
	
	
	}
	
	function test_get_related_records_sort(){
		$s = new Dataface_Record('Profiles', array());
		$s->clearValues();
		$s->setValue('id', 10);
		$records = $s->getRelatedRecords('addresses',true, null,null,null,'line1');
		$this->assertEquals( count($records), 2);
		$row1 =& $records[1];
		$this->assertEquals($row1['line1'], '555 Elm St');
		$this->assertEquals($row1['line2'], 'Box 123');
		$row2 =& $records[0];
		$this->assertEquals('123 Perl Drive', $row2['line1']);
		
		$records = $s->getRelatedRecords('addresses',true, null,null,null,'line1 desc');
		$this->assertEquals( count($records), 2);
		$row1 =& $records[0];
		$this->assertEquals($row1['line1'], '555 Elm St');
		$this->assertEquals($row1['line2'], 'Box 123');
		$row2 =& $records[1];
		$this->assertEquals('123 Perl Drive', $row2['line1']);
		
		$records = $s->getRelatedRecords('addresses', "line1='555 Elm St'");
		$this->assertEquals( count($records), 1);
		$this->assertEquals('555 Elm St', $records[0]['line1']);
	
	}
	
	
	
	

	
	function test_set_indexed_record(){
	
		$s = new Dataface_Record('Profiles', array());
		$s->clearValues();
		$s->setValue('id', 10);
		$this->assertEquals( $s->getValue('addresses.city', 1), 'Springfield');
		
		//$s->setValue('addresses.city', 'Houston', 1);
		//$this->assertEquals( $s->getValue('addresses.city', 1), 'Houston');
		//$s->setValue('addresses.city', 'LA', array('id'=>2) );
		//$this->assertEquals( $s->getValue('addresses.city', 1), 'LA');
		//$this->assertEquals( $s->getValue('addresses.city', array('id'=>2)), 'LA');
		
		
	
	
	}
	

	
	function test_record_changed(){
		$s =  new Dataface_Record('Profiles', array());
		$s->clearValues();
		$s->setValue('id',10);
		//$this->assertEquals($s->getValue('addresses.city', 1), 'Springfield');
		//$this->assertTrue( !$s->recordChanged('addresses', 1) );
		//$res = $s->setValue( 'addresses.city', 'Houston', 1);
		//if ( PEAR::isError($res) ){
		//	echo $res->toString();
		//}
		//$this->assertEquals( $s->getValue('addresses.city', 1), 'Houston');
		//$this->assertTrue( $s->valueChanged('addresses.city', 1) );
		//$this->assertTrue( $s->recordChanged('addresses', 1));
	
	}
	
	
	
	function test_getSerializedValue(){
		$s =  new Dataface_Record('Profiles', array());
		$s->setValue('id', 10);
		$s->setValue('datecreated', '19991224000000');
		
		$this->assertEquals($s->getSerializedValue('id'), '10');
		$this->assertEquals($s->getSerializedValue('datecreated'),'19991224000000');
	}
	
	
	
	
	/**
	 * Links (ie: URLs) can be associated with fields of a table by defining a "link"
	 * property for a field in the fields.ini file or by defining a fieldname__link()
	 * method in the delegate class.  These links are accessible via the Table::getLink()
	 * method which resolves any variables in the link provided and returns the link
	 * in the same format as it is defined in its place of origin (either the fields.ini file
	 * or the delegate class.
	 *
	 * Links can be in the form of a query array, or in the form of a string (which should
	 * be a url).
	 * In this test, `fname`, `lname`, and `description` fields have links defined in the 
	 * Profiles delegate class.  
	 */
	function test_links() {
		$profiles =  new Dataface_Record('Profiles', array());
		
		// Part I:  Testing links produced by the delegate *__link() methods.
		//--------------------------------------------------------------------
		
		// First test a link with explicit values where the delegate method __link() is defined
		// and the delegate method explicitly returns an array.
		$profiles->setValues(array("fname"=>"John", "lname"=>"Thomas"));
		$link = $profiles->getLink("fname" );
		$this->assertEquals( $link["fname"], "John");
		$this->assertEquals( $link["lname"], "Thomas");
		$this->assertEquals( $link["description"], "My name is John");
		
		// Try the same thing where no link is defined
		$link = $profiles->getLink("id");
		$this->assertEquals($link, null);
		
		// Now test a link with implicit values where the delegate method __link() is defined
		// and the delegate method returns an array.
		$table = new Dataface_Record('Profiles', array());
		$table->clearValues();
		$table->setValues(array("fname"=>"John", "lname"=>"Thomas"));
		
		$link = $profiles->getLink("fname");
		$this->assertEquals( $link["fname"], "John");
		$this->assertEquals( $link["lname"], "Thomas");
		$this->assertEquals( $link["description"], "My name is John");
		
		
		// Try same thing when no link is defined
		$link = $profiles->getLink("id");
		$this->assertEquals($link, null);
		
		//Now test the link returned by a field that returns the link as a string with no 
		// variables that need parsing.
		$link = $profiles->getLink("lname");
		$this->assertEquals( "http://www.google.ca?fname=John&lname=Thomas", $link );
		
		// Now test the link returned by a field that returns a link that contains 
		// variables (which should automatically be resolved by the getLink() method.
		// This tests the delegate still.
		$link = $profiles->getLink("description");
		$this->assertEquals("http://www.google.ca?fname=John&lname=Thomas", $link );
		
		
		// Part II: Testing links defined in the fields.ini file
		//-------------------------------------------------------------------------
		
		// Test a link with no variables requiring resolving
		$link = $profiles->getLink("dob");
		$this->assertEquals("http://www.google.ca",$link);
		
		// Test a link with variables that need to be resolved.
		$link = $profiles->getLink("phone1");
		$this->assertEquals("http://www.google.ca?fname=John", $link );
		
		$profiles->clearValues();
		$profiles->setValue("fname","Thomas");
		$link = $profiles->getLink("phone1");
		$this->assertEquals("http://www.google.ca?fname=Thomas", $link );
		
		 
		
	}
	
	
	function test_num_related_records(){
		$record = new Dataface_Record('Profiles', array('id'=>10));
		$this->assertEquals(2, $record->numRelatedRecords('addresses'));
	
	}
	
	function test_getActions(){
		$record =& df_get_record('People', array('PersonID'=>1));
		$actions = $record->getActions();
		$this->assertEquals('Test Action', $actions['TestAction']['label']);
		$this->assertEquals('This action does something or another', $actions['TestAction']['description']);
		//$this->assertEquals(3, count($actions));
		$this->assertTrue(isset($actions['TestAction']));
		$this->assertTrue(isset($actions['TestAction2']));
		$this->assertTrue(isset($actions['TestAction3']));
		//print_r($actions);
		$actions = $record->getActions(array('category'=>'table_actions'));
		//$this->assertEquals(1, count($actions));
		$this->assertTrue(isset($actions['TestAction3']));
		
		$record2 =& df_get_record('Profiles', array('ProfileID'=>1));
		$actions = $record2->getActions();
		//$this->assertEquals(0, count($actions));
	}
	
	function test_get_url(){
	
		$record =& df_get_record('People', array('PersonID'=>1));
		$url1 = $record->getURL();
		$this->assertEquals(
			'?-table=People&-action=browse&PersonID=%3D1',
			substr($url1, strpos($url1, '?'))
			);
		
		$url2 = $record->getURL(array('-action'=>'edit'));
		$this->assertEquals(
			'?-action=edit&-table=People&PersonID=%3D1',
			substr($url2, strpos($url2, '?'))
			);
			
		$url3 = $record->getURL(array('-relationship'=>'foo'));
		$this->assertEquals(
			'?-relationship=foo&-table=People&-action=browse&PersonID=%3D1',
			substr($url3, strpos($url3, '?'))
			);
		
	}
	
	
	
	
	
	

	
	
	
	
	
	
	
}

?>
