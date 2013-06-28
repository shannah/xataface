<?php
import('PHPUnit.php');

class xataface_Dataface_RecordTest extends PHPUnit_TestCase {

	private static $TABLES = array('fragrances','formulas','ingredients','formula_ingredients', 'units', 'test683', 'test841','test841_join','test_versions');
	
	function xataface_Dataface_RecordTest( $name = 'xataface_Dataface_RecordTest'){
		$this->PHPUnit_TestCase($name);
		
	}

	function setUp(){
		$this->tearDown();
		$s = DIRECTORY_SEPARATOR;
		$tables = self::$TABLES;
		foreach ($tables as $t){
			Dataface_Table::setBasePath($t, dirname(__FILE__));
			self::q(file_get_contents(Dataface_Table::getBasePath($t).$s.'tables'.$s.basename($t).$s.'create.sql'));
			self::q(file_get_contents(Dataface_Table::getBasePath($t).$s.'tables'.$s.basename($t).$s.'init.sql'));
			
		
		}		
	}
	
	
	
	
	
	
	
	function tearDown(){
		$s = DIRECTORY_SEPARATOR;
		$tables = self::$TABLES;
		foreach ($tables as $t){
			self::q('DROP TABLE if exists `'.$t.'`');
		}
		

	}
	
	function testVersions(){
	
		$table = Dataface_Table::loadTable('test_versions');
		$this->assertEquals('version', $table->getVersionField(), 'The version field of test_versions should be version');
		$this->assertEquals(true, $table->isVersioned(), 'test_versioned should be versioned');
		
		$row1 = df_get_record('test_versions', array('test_versions_id'=>'=1'));
		$this->assertEquals(3, $row1->getVersion(), 'Version for row 1 of test_versions (non null)');
		
		$row2 = df_get_record('test_versions', array('test_versions_id'=>'=2'));
		$this->assertEquals(0, $row2->getVersion(), 'Version for row 2 of test_versions (null value)' );
		
		$row3 = df_get_record('test_versions', array('test_versions_id'=>'=3'));
		$this->assertEquals(0, $row3->getVersion(), 'Version for row 3 of test_versions (0 value)');
		
		
		// Now let's try to make some changes
		$row1->setValue('varchar_field', 'new value');
		$res = $row1->save();
		$this->assertTrue(!PEAR::isError($res), 'Saving row 1 of test_versions with new varchar value');
		$this->assertEquals(4, $row1->getVersion(), 'Version for row 1 should be incremented after save');
		
		$row1Copy = df_get_record('test_versions', array('test_versions_id'=>'=1'));
		$this->assertTrue(4, $row1Copy->getVersion(), 'Version of row 1 copy should be incremented after save');
		
		$res = mysql_query("select version from test_versions where test_versions_id=1", df_db());
		$row = mysql_fetch_row($res);
		$this->assertEquals(4, $row[0], 'Version of row 1 straight out of db with mysql_query');
		@mysql_free_result($res);
		
		
		$row2->setValue('varchar_field', 'new value for row 2');
		$res = $row2->save();
		$this->assertTrue(!PEAR::isError($res), 'Saving row 2 of test_versions with new varchar value');
		$this->assertEquals(1, $row2->getVersion(), 'Version of row 2 should  be incremented to 1 after save');
		
		$row2Copy = df_get_record('test_versions', array('test_versions_id', '=2'));
		$this->assertTrue(1, $row2Copy->getVersion(), 'Version of row 2 copy should be 1 after save');
		
		// Now we'll try making changes to a record, and then try making more changes from the 
		// copy... the changes to the copy should fail because it will be working with
		// an old version of the record.
		$this->assertEquals(4, $row1->getVersion(), 'Row 1 version should still be 4');
		$row1->setValue('varchar_field', 'new value number 2 for row 1');
		$res = $row1->save();
		$this->assertTrue(!PEAR::isError($res), 'Row 1 second save');
		if ( PEAR::isError($res) ){
			error_log('Error updating row1: '.$res->getMessage());
		}
		$this->assertEquals(5, $row1->getVersion(), 'Row 1 version after second save');
		
		$row1Copy->setValue('varchar_field', 'copy of row 1 changed');
		$res = $row1Copy->save();
		$this->assertTrue(PEAR::isError($res), 'Saving out of date version of row 1');
		if ( PEAR::isError($res) ){
			$this->assertEquals(DATAFACE_E_VERSION_MISMATCH, $res->getCode(), 'Error code for version check failure should be DATAFACE_E_VERSION_MISMATCH');
		}
		
		
		
	}
	
	function testCreation(){
	
		$formula = new Dataface_Record('formulas', array('formula_name'=>'Test'));
		$this->assertEquals('Test', $formula->val('formula_name'), 'Initialization of Dataface_Record didn\'t retain value.');
		
		$formula = new Dataface_Record('formulas', 
				array('ingredients'=>array ( 
					0 => array ( 
						'ingredient_id' => 3,
						'concentration' => 10,
						'concentration_units' => 1,
						'amount' => 10,
						'amount_units' => 2,
						'__id__' => 'new',
						'__order__' => 0,
					),
					'__loaded__' => 1 
				) 
			)
		);
		
		$this->assertEquals(array ( 
				0 => array ( 
					'ingredient_id' => 3,
					'concentration' => 10,
					'concentration_units' => 1,
					'amount' => 10,
					'amount_units' => 2,
					'__id__' => 'new',
					'__order__' => 0,
				),
				'__loaded__' => 1 
			) ,
			$formula->val('ingredients'),
			'Initialization of Dataface_Record did not retain transient field value.'
		);
	}
	
	
		
	function testSnapshots(){
		
		$formula = df_get_record('formulas', array('formula_id'=>'=9'));
		$this->assertTrue($formula instanceof Dataface_Record, 'Loaded record should be a Dataface_Record object.');
		$this->assertEquals('hello world', $formula->val('formula_name'));
		$this->assertTrue(!$formula->valueChanged('formula_name'), 'Field should not be dirty after load.');
		$this->assertTrue(!$formula->recordChanged(), 'Record should not be dirty after loading.');
		$formula->setValue('formula_name', 'hello langley');
		$this->assertTrue($formula->valueChanged('formula_name'), 'Field should be dirty after setValue.');
		$this->assertTrue($formula->recordChanged(), 'Record should be dirty after setValue');
		$res = $formula->save();
		//$this->assertTrue($res, 'Return value of save should be true on success.');
		$this->assertTrue(!$formula->valueChanged('formula_name'), 'Field should not be dirty after changes are saved.');
		$this->assertTrue(!$formula->recordChanged(), 'Record should not be dirty after saving.');
		$this->assertEquals('hello langley', $formula->val('formula_name'), 'New value for formula should be present after save.');
		
		
	
	}
	
	/**
 	 * Test for http://bugs.weblite.ca/view.php?id=683
 	 */
	function testMultifind(){
		$records = df_get_records_array('test683', array('multifield'=>'es'));
		$this->assertEquals(2, count($records), 'Both rows contain spanish so we should have found both of them.');
		
		$records = df_get_records_array('test683', array('multifield'=>'fr'));
		$this->assertEquals(1, count($records), 'Only one record contains french.');
		
	}
	
	
	/**
	 * Unit test for http://bugs.weblite.ca/view.php?id=685
	 */
	function testHasValue(){
		$formula = new Dataface_Record('formulas', array('formula_id'=>10));
		$this->assertTrue($formula->hasValue('formula_id'), 'hasValue() failed to identify that the formulas table has a formula_id field');
		$this->assertTrue($formula->hasValue('formula_name'), 'hasValue() actually only checks that a field exists in the table.  It doesnt say anything about that fields value.');
		$this->assertTrue(!$formula->hasValue('nonexistent_field'), 'hasValue() should return false if asked about a field that doesnt exist.');
	}


	function testFormulasRelationship(){
	
		$fragrancesTable = Dataface_Table::loadTable('fragrances');
		$formulasRelationship = $fragrancesTable->getRelationship('formulas');
		$this->assertTrue($formulasRelationship instanceof Dataface_Relationship, 'Could not load formulas relationship.');
		
		$fields = $formulasRelationship->fields();
		$this->assertEquals(array (
			  0 => 'formulas.formula_id',
			  1 => 'formulas.parent_id',
			  2 => 'formulas.formula_name',
			  3 => 'formulas.fragrance_id',
			  4 => 'formulas.date_created',
			  5 => 'formulas.last_modified',
			  6 => 'formulas.formula_description',
			),
			$fields,
			'Field list from relationship is incorrect.'
		);
		
		
		$fields = $formulasRelationship->fields(true);
		$this->assertEquals(array (
			  0 => 'formulas.formula_id',
			  1 => 'formulas.parent_id',
			  2 => 'formulas.formula_name',
			  3 => 'formulas.fragrance_id',
			  4 => 'formulas.date_created',
			  5 => 'formulas.last_modified',
			  6 => 'formulas.formula_description',
			),
			$fields,
			'Field list with grafted from relationship is incorrect.'
		);
		// In this case we have no grafted fields so it should be the same.
		$fields = $formulasRelationship->fields(true, true);
		$this->assertEquals(array (
			  0 => 'formulas.formula_id',
			  1 => 'formulas.parent_id',
			  2 => 'formulas.formula_name',
			  3 => 'formulas.fragrance_id',
			  4 => 'formulas.date_created',
			  5 => 'formulas.last_modified',
			  6 => 'formulas.formula_description',
			  7 => 'formulas.ingredients'
			),
			$fields,
			'Field list with grafted and transient from relationship is incorrect.'
		);
		
		
		$this->assertTrue($formulasRelationship->hasField('formulas.formula_id'), 'hasField() failed with absolute field path.');
		$this->assertTrue($formulasRelationship->hasField('formula_id'), 'hasField() failed with relative field path.');
		$this->assertTrue(!$formulasRelationship->hasField('ingredients'), 'hasField() should not look at transient fields unless asked.');
		$this->assertTrue(!$formulasRelationship->hasField('formulas.ingredients'), 'hasField() should not look at transient fields unless asked.');
		$this->assertTrue($formulasRelationship->hasField('ingredients', true, true), 'hasField() not picking up transient fields when asked.');
		$this->assertTrue($formulasRelationship->hasField('formulas.ingredients', true, true), 'hasField() not picking up transient fields given absolute path - and specifying transient.');
	
		$this->assertEquals('formulas', $formulasRelationship->getName(), 'getName() does not match.');
		
		$this->assertTrue($formulasRelationship->getSourceTable() instanceof Dataface_Table, 'getSourceTable() should be an instance of Dataface_Table.');
		$this->assertEquals('fragrances', $formulasRelationship->getSourceTable()->tablename, 'getSourceTable() returning wrong table name.');
		
		$destinationTables = $formulasRelationship->getDestinationTables();
		$this->assertEquals(1, count($destinationTables), 'getDestinationTables() returning the wrong number of tables.');
		
		$found = array();
		foreach ($destinationTables as $t){
			$found[$t->tablename] = true;
		}
		$this->assertTrue($found['formulas'], 'getDestinationTables() missing one of the tables in the relationship.');
		
		$table = $formulasRelationship->getTable();
		$this->assertTrue($table instanceof Dataface_Table, 'getTable() should return a Dataface_Table object.');
		$this->assertEquals('fragrances', $table->tablename, 'getTable() should return the source table if no field is specified.');
		
		$table = $formulasRelationship->getTable('doesntexist');
		$this->assertEquals(null, $table, 'getTable() should return null if the specified field could not be found.');
		
		$table = $formulasRelationship->getTable('formula_name');
		$this->assertEquals('formulas', $table->tablename, 'getTable() returned the wrong table for the specified column.');
		
		$table = $formulasRelationship->getTable('ingredients');
		$this->assertEquals('formulas', $table->tablename, 'getTable() returned the wrong table when given the relative path to a transient field.');
	
		$table = $formulasRelationship->getTable('formulas.formula_name');
		$this->assertEquals('formulas', $table->tablename, 'getTable() returned the wrong table for the specified column given an absolute path.');
	
		$table = $formulasRelationship->getTable('formulas.ingredients');
		$this->assertEquals('formulas', $table->tablename, 'getTable() returned the wrong table for the specified transient column given an absolute path.');
	
		$this->assertEquals('formulas', $formulasRelationship->getDomainTable(), 'getDomainTable() returned the wrong table name.');
	
		
		$fkvals = $formulasRelationship->getForeignKeyValues();
		
		$this->assertEquals(array (
			  'formulas' => 
				  array (
					'fragrance_id' => '$fragrance_id',
				  ),
			),
			$fkvals,
			'getForeignKeyValues() returned wrong structure.'
		);
		
		$fkvals = $formulasRelationship->getForeignKeyValues(array('formulas.formula_name'=>'Test'));
		$this->assertEquals(array (
			  'formulas' => 
				  array (
					'fragrance_id' => '$fragrance_id',
					'formula_name' => 'Test'
				  ),
			),
			$fkvals,
			'getForeignKeyValues() returned wrong structure when passed some values.'
		);
		$fragrance = df_get_record('fragrances', array('fragrance_id'=>'=1'));
		$fkvals = $formulasRelationship->getForeignKeyValues(array('formulas.formula_name'=>'Test'), null, $fragrance);
		$this->assertEquals(array (
			  'formulas' => 
				  array (
					'fragrance_id' => '1',
					'formula_name' => 'Test'
				  ),
			),
			$fkvals,
			'getForeignKeyValues() returned wrong structure when passed a source record.'
		);
		
	
	}
	
	
	function testRelatedRecord(){
		$fragrance = df_get_record('fragrances', array('fragrance_id'=>'=1'));
		$relatedRecord = new Dataface_RelatedRecord($fragrance, 'formulas');
		
		$this->assertEquals($fragrance, $relatedRecord->getParent(), 'getParent() returned wrong record.');
		
		$relatedRecord->setValue('formula_name', 'Test');
		$fkvals = $relatedRecord->getForeignKeyValues();
		$this->assertEquals(array (
			  'formulas' => 
				  array (
					'fragrance_id' => '1',
					'formula_name' => 'Test'
				  ),
			),
			$fkvals,
			'getForeignKeyValues() returned wrong structure.'
		);
		
		$relatedRecord->setValue('ingredients', array ( 
				0 => array ( 
					'ingredient_id' => 3,
					'concentration' => 10,
					'concentration_units' => 1,
					'amount' => 10,
					'amount_units' => 2,
					'__id__' => 'new',
					'__order__' => 0,
				),
				'__loaded__' => 1 
			) 
		);
		
		$fkvals = $relatedRecord->getForeignKeyValues();
		$this->assertEquals(array (
			  'formulas' => 
				  array (
					'fragrance_id' => '1',
					'formula_name' => 'Test'
				  ),
			),
			$fkvals,
			'getForeignKeyValues() returned wrong structure.'
		);
		
		$formulaRecord = $relatedRecord->toRecord('formulas');
		$this->assertTrue($formulaRecord instanceof Dataface_Record, 'toRecord() is expected to return a Dataface_Record object.');
		$this->assertEquals('Test', $formulaRecord->val('formula_name'), 'toRecord() failed to transfer basic value to resulting Dataface_Record object.');
		$this->assertEquals(array ( 
				0 => array ( 
					'ingredient_id' => 3,
					'concentration' => 10,
					'concentration_units' => 1,
					'amount' => 10,
					'amount_units' => 2,
					'__id__' => 'new',
					'__order__' => 0,
				),
				'__loaded__' => 1 
			) ,
			$formulaRecord->val('ingredients'),
			'toRecord() failed to transfer value of transient field to resulting Dataface_Record object.'
		);
		
		
		$records = $relatedRecord->toRecords();
		$this->assertEquals(1, count($records), 'toRecords() returned wrong number of records.');
		$this->assertTrue($records[0] instanceof Dataface_Record, 'toRecords should return an array of Dataface_Record objects.');
		
		
		
	
	}
	
	
	function testAddRelatedRecord(){
		$fragrance = df_get_record('fragrances', array('fragrance_id'=>'=1'));

		$this->assertTrue($fragrance instanceof Dataface_Record, 'Loaded fragrance should be a Dataface_Record object.');
		
		$formulasRelationship = $fragrance->table()->getRelationship('formulas');
		$this->assertTrue($formulasRelationship instanceof Dataface_Relationship, 'The formulas relationship does not exist or could not be loaded.');
		
		$relatedRecord = new Dataface_RelatedRecord($fragrance, 'formulas');
		$this->assertTrue(!$relatedRecord->isDirty('formula_name'), 'Record should not be dirty when it is first created.');
		
		$formula = $relatedRecord->toRecord('formulas');
		$this->assertTrue($formula instanceof Dataface_Record, 'Formula should be a Dataface_Record');
		
		
		
		$records = $relatedRecord->toRecords();
		$this->assertTrue($records[0] instanceof Dataface_Record, 'Formulas record from toRecords() should be of type Dataface_Record.');
		
		
		
		$relatedRecord->setValues(array(
			'formula_name'=>'Test formula',
			'formula_description'=>'This is just a test formula',
			'ingredients'=>array ( 
				0 => array ( 
					'ingredient_id' => 3,
					'concentration' => 10,
					'concentration_units' => 1,
					'amount' => 10,
					'amount_units' => 2,
					'__id__' => 'new',
					'__order__' => 0,
				),
				'__loaded__' => 1 
			) 
		));
		
		$this->assertTrue($relatedRecord->isDirty('formula_name'), 'The formula name should be dirty before it is saved.');
		$this->assertTrue($relatedRecord->isDirty('ingredients'), 'The ingredients should be dirty befor it is saved.');
		$io = new Dataface_IO('fragrances');
		$res = $io->addRelatedRecord($relatedRecord);
		$this->assertTrue(!PEAR::isError($res), 'The result of saving a related formula should not be an error.');
	
		
	
	}
	
	
	function testPermissions(){
	
		$formulasTable = Dataface_Table::loadTable('formulas');
		$formulasDel = $formulasTable->getDelegate();
		$formulasDel->testPermissions = true;
		
		$formula = new Dataface_Record('formulas', array('formula_name'=>'test formula'));
		
		// Test the standard permissions on tables and fields
		$this->assertTrue($formula->checkPermission('view'), 'View permission should be set by default.');
		$this->assertTrue(!$formula->checkPermission('list'), 'List permission should be denied by default.');
		$this->assertTrue($formula->checkPermission('new'), 'New permission should be permitted by default.');
		$this->assertTrue(!$formula->checkPermission('new', array('field'=>'formula_name')), 'New permission should be denied on the formula_name field.');
		$this->assertTrue($formula->checkPermission('view', array('field'=>'formula_name')), 'The view permission should be allowed on the formula_name field.');
		$this->assertTrue(!$formula->checkPermission('view', array('field'=>'formula_id')), 'The view permission should be denied on the formula_id field.');
	
	
		// Test the nobubble parameter on getPermissions
	
		$this->assertTrue(!$formula->checkPermission('delete', array('field'=>'formula_name', 'nobubble'=>1)), 'Since we are not bubbling up to record, we should not have permission for the delete permission as it is not enabled at field level explicitly - only at record level.');
		$this->assertTrue($formula->checkPermission('delete', array('field'=>'formula_name')), 'Now that we are allowing bubbling, we should return true for delete on teh formula_name field.');
		$this->assertTrue($formula->checkPermission('copy', array('field'=>'formula_name', 'nobubble'=>1)), 'Even though there is no bubbling, we should still return true for the copy permission on the formula_name field since it is defined in the __field__permssions() method.');
		$this->assertTrue($formula->checkPermission('view', array('field'=>'amount', 'relationship'=>'ingredients')), 'view permission of the amount field in the ingredients relationship should be allowed because it is granted in the rel_ingredients__amount__permissions() method of the formulas delegate class.');
		$this->assertTrue($formula->checkPermission('view', array('field'=>'amount', 'relationship'=>'ingredients', 'nobubble'=>1)), 'view permission for amount field in ingredients relationship should be allowed even with nobubble=1 because it is permistted in the rel_ingredients__amount__permissions().');
		$this->assertTrue($formula->checkPermission('link', array('field'=>'amount', 'relationship'=>'ingredients')), 'link permission on amount field of the ingredients relationship should be allowed because it is granted in the rel_ingredients__permissions() method of the formulas delegate class.');
		$this->assertTrue(!$formula->checkPermission('link', array('field'=>'amount', 'relationship'=>'ingredients', 'nobubble'=>1)), 'link permission on the amount field of the ingredients relationship should not be allowed when nobubble=1 because although it is granted in the rel_ingredients__permissions() method of the formulas delegate class - this method shouldnt be consulted if nobubble=1.  It should just check the specific field permissions of the relationship and then break.');
		$this->assertTrue($formula->checkPermission('link', array('relationship'=>'ingredients')), 'link  permission should be allowed on the ingredients relationship because it is granted in the rel_ingredients__permissions() method of the formulas delegate class.');
		$this->assertTrue($formula->checkPermission('link', array('relationship'=>'ingredients', 'nobubble'=>1)), 'link permission should be allowed in the ingredients relationship even with nobubble=1 because it is granted in the rel_ingredients__permissions() method of the formulas delegate class.  nobubble should just prevent it from looking past the relationship permissions.');
		
		
		// Test related record permissions
		$formulaIngredientsTable = Dataface_Table::loadTable('formula_ingredients');
		$formulaIngredientsDel = $formulaIngredientsTable->getDelegate();
		$formulaIngredientsDel->testPermissions = true;
		
		$relatedRecord = new Dataface_RelatedRecord($formula, 'ingredients', array('ingredient_id'=>1, 'concentration'=>3, 'amount'=>4));
		
		// Test the standard related permission
		$this->assertTrue(!$relatedRecord->checkPermission('view', array('field'=>'concentration')), 'There shouldn\'t be permission to view the concentration field as it is denied in the getPermissions() method and is not overridden in any of the function methods.');
		$this->assertTrue($relatedRecord->checkPermission('view', array('field'=>'ingredient_id')), 'There should be permission to view the ingredient_id field since it is overridden in the ingredient_id__permissions() method of the formula_ingredients delegate class.');
		$this->assertTrue($relatedRecord->checkPermission('view', array('field'=>'amount')), 'There should be permission to view the amount field since the rel_ingredients__amount__permissions() method is defined in the parent table delegate class and grants the permission..  This should table precedence.');
		
		$ingredientRecord = new Dataface_Record('formula_ingredients',  array('ingredient_id'=>1, 'concentration'=>3, 'amount'=>4));
		$this->assertTrue(!$ingredientRecord->checkPermission('view', array('field'=>'amount')), 'There should be no permission for view of the amount field directly because it hasnt been granted in the formula_ingredients delegate class.');
		
		
		// Test the display now.
		$this->assertEquals('NO ACCESS', $relatedRecord->display('concentration'), 'Concentration should be no access via the related record because we havent granted access yet.');
		$this->assertEquals('4', $relatedRecord->display('amount'), 'Amount should display the proper value because view has been granted via the relationship.');
		$this->assertEquals('NO ACCESS', $ingredientRecord->display('amount'), 'Amount should display "NO ACCESS" when accessing the record directly, but instead received the actual value.');
		
		
	
		$formulasDel->testPermissions = false;
	}
		
		
	
	/**
	 * This test is to verify a fix to the following bug report:
	 * http://bugs.weblite.ca/view.php?id=841
	 *
	 * When editing a join record but the join record doesn't exist yet, it would
	 * report an error.  This was traced back to the behavior of Dataface_Record::getJoinRecord()
	 * so that it returns a record even if that record hasn't been created yet.  
	 * Dataface_FormTool::createRecordForm() depended on this null value to signal when
	 * it should create a new record form rather than an edit record form for the tab.
	 */
	function test841(){
		
		$rec = df_get_record('test841', array('test841_id'=>'=1'));
		$this->assertTrue($rec instanceof Dataface_Record, 'Could not find record in test841 table but it should exist');

		$join = $rec->getJoinRecord('test841_join');
		$this->assertTrue($join instanceof Dataface_Record, 'Failed to load join record but it should be there.');
		$this->assertEquals(1, $join->val('test841_id'), 'Join record primary key does not match parent record.');
		$this->assertEquals('join value', $join->val('join_varchar_field'), 'Join record did not load its field values correctly.');
		
		import('Dataface/QuickForm.php');
		import('Dataface/FormTool.php');
		
		$ft = Dataface_FormTool::getInstance();
		$form = $ft->createRecordForm($rec, false, 'test841_join');
		$this->assertTrue($form instanceof Dataface_QuickForm, 'Join form should be an instance of Dataface_QuickForm.');
		
		$formRec = $form->_record;
		$this->assertTrue($formRec instanceof Dataface_Record, 'Join form should be based on a Dataface_Record object.');
		$this->assertEquals('test841_join', $formRec->table()->tablename, 'Join form record has wrong table name');
		$this->assertEquals(false, $form->_new, 'Join form for id 1 exists so the form should be an edit record form.');
		
		
		
		// Now for the one where no join yet exists.
		
		$rec = df_get_record('test841', array('test841_id'=>'=2'));
		$this->assertTrue($rec instanceof Dataface_Record, 'Could not find record but it should exist.');
		$join = $rec->getJoinRecord('test841_join');
		$this->assertTrue($join instanceof Dataface_Record, 'Failed to load join record.');
		$this->assertEquals(2, $join->val('test841_id'), 'Join record value primary key does not match.');
		$this->assertEquals(null, $join->val('join_varchar_field'), 'Join record shouldnt have a value yet because it doesnt exist in the database.');
		
		// Now to request a null value if the record doesn't exist yet
		$join2 =$rec->getJoinRecord('test841_join', true);
		$this->assertEquals(null, $join2, 'getJoinRecord() should have returned null when record doesnt exist - and the 2nd parameter was set.');
		
		$form = $ft->createRecordForm($rec, false, 'test841_join');
		$this->assertTrue($form instanceof Dataface_QuickForm, 'Join form should be a Dataface_QuickForm record.');
		$this->assertEquals('test841_join', $form->_record->table()->tablename, 'Join table edit form is working on the wrong record.');
		$this->assertEquals(true, $form->_new, 'Join form for id 2 should be a new record form because the record doesnt exist yet.');
		
		
		
	
	}
		
		
	function test_RecordReader(){
		import('Dataface/RecordReader.php');
		$q = array('-table'=>'formulas');
		
		$reader = new Dataface_RecordReader($q, 3, true);
		$result = array();
		foreach ($reader as $key=>$record){
			$result[$key] = $record;
		}
		
		$this->assertEquals(9, count($result));
		$this->assertEquals('Test formula', $result[0]->val('formula_name'));
		$this->assertEquals('hello world', $result[8]->val('formula_name'));
		
		
		$q = array('-table'=>'formulas', '-limit' => 4);
		
		$reader = new Dataface_RecordReader($q, 3, true);
		$result = array();
		foreach ($reader as $key=>$record){
			$result[$key] = $record;
		}
		
		$this->assertEquals(4, count($result));
		$this->assertEquals('Test formula', $result[0]->val('formula_name'));
		$this->assertEquals('Test formula Copy', $result[3]->val('formula_name'));
		
		$q = array('-table'=>'formulas', '-limit' => 4, '-skip' => 1);
		
		$reader = new Dataface_RecordReader($q, 3, true);
		$result = array();
		foreach ($reader as $key=>$record){
			$result[$key] = $record;
		}
		
		$this->assertEquals(4, count($result));
		$this->assertEquals('Test formula Copy', $result[1]->val('formula_name'));
		$this->assertEquals('Test formula Copy Again', $result[4]->val('formula_name'));
	}
	
	function test_ResultReader(){
		import('Dataface/ResultReader.php');
		$q = "select * from formulas";
		
		$reader = new Dataface_ResultReader($q, df_db(), 3);
		$result = array();
		foreach ($reader as $key=>$record){
			$result[$key] = $record;
		}
		
		$this->assertEquals(9, count($result));
		$this->assertEquals('Test formula', $result[0]->formula_name);
		$this->assertEquals('hello world', $result[8]->formula_name);
		
		
		$q = "select * from formulas limit 4";
		
		$reader = new Dataface_ResultReader($q, df_db(), 3);
		$result = array();
		foreach ($reader as $key=>$record){
			$result[$key] = $record;
		}
		
		$this->assertEquals(4, count($result));
		$this->assertEquals('Test formula', $result[0]->formula_name);
		$this->assertEquals('Test formula Copy', $result[3]->formula_name);
		
		$q = "select * from formulas limit 1,4";
		
		$reader = new Dataface_ResultReader($q, df_db(), 3);
		$result = array();
		foreach ($reader as $key=>$record){
			$result[$key] = $record;
		}
		
		$this->assertEquals(4, count($result));
		$this->assertEquals('Test formula Copy', $result[1]->formula_name);
		$this->assertEquals('Test formula Copy Again', $result[4]->formula_name);
	}
		
		
		

		
		
		
		
			
	
	static function q($sql){
		$res = mysql_query($sql, df_db());
		if ( !$res ) throw new Exception(mysql_error(df_db()));
		return $res;
	}
	
		


}



// Add this test to the suite of tests to be run by the testrunner
Dataface_ModuleTool::getInstance()->loadModule('modules_testrunner')
		->addTest('xataface_Dataface_RecordTest');
