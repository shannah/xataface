//require <xataface/modules/testrunner/TestRunner.js>
//require <xataface/model/ListModel.js>
(function(){
	var $ = jQuery;
	
	var TestRunner = xataface.modules.testrunner.TestRunner;
	var ListModel = xataface.model.ListModel;
	
	TestRunner.addTest(testModel);
	
	
	function testModel(){
		var _ = 'xataface.model.tests.ListModelTest: ';
		
		var m1 = new ListModel();
		var row1 = {
			firstName : 'Steve',
			lastName : 'Hannah',
			age : 34
		};
		var row2 = {
			firstName : 'John',
			lastName : 'Smith',
			age : 12
		};
		
		var rowChanges = [];
		function rowChangeListener(evt, data){
			rowChanges.push(data);
		}
		$(m1).bind('rowsChanged', rowChangeListener);
		TestRunner.assertEquals(_+'Num Change Events Before', 0, rowChanges.length);
		
		// Set the data... should fire a change event
		m1.add(row1);
		
		TestRunner.assertEquals(_+'Num Change Events After Add', 1, rowChanges.length);
		TestRunner.assertEquals(_+'Num changes in row change event',
			1,
			rowChanges[0].changes.length
		);
		var theChange = rowChanges[0].changes[0];
		TestRunner.assertEquals(_+'Change type', 
			ListModel.ROW_ADD_CHANGE, 
			theChange.changeType
		);
		TestRunner.assertEquals(_+'Changed row', row1, theChange.row);
		TestRunner.assertEquals(_+'Changed row index', 0, theChange.index);
		
		TestRunner.assertEquals(_+'Row 0', row1, m1.rows[0]);
		TestRunner.assertEquals(_+'Num rows after add', 1, m1.rows.length);
		
		// Let's try to undo this change and see that everything works out
		// as it should
		
		rowChanges[0].undo();
		
		
		TestRunner.assertEquals(_+'Num Change Events After Undo', 2, rowChanges.length);
		////console.log(rowChanges);
		TestRunner.assertEquals(_+'Num changes in row change event',
			1,
			rowChanges[1].changes.length
		);
		
		var undoChange = rowChanges[1].changes[0];
		TestRunner.assertEquals(_+'Undo change type',
			ListModel.ROW_REMOVE_CHANGE,
			undoChange.changeType
		);
		TestRunner.assertEquals(_+'Undo changed row', row1, undoChange.row);
		TestRunner.assertEquals(_+'Undo changed index', 0, undoChange.index);
		TestRunner.assertEquals(_+'List size after undo add', 0, m1.rows.length);
		
		// Now let's test out the list selection functions
		
		var selectionChanges = [];
		function selectionChangeListener(evt, data){
			selectionChanges.push(data);
		}
		$(m1).bind('selectionChanged', selectionChangeListener);
		m1.add([row1, row2]);
		TestRunner.assertEquals(_+'Num rows in selected record test', 2, m1.rows.length);
		TestRunner.assertEquals(_+'Row 1 not selected', false, m1.isSelected(row1));
		TestRunner.assertEquals(_+'Null for selected', null, m1.getSelectedRecord());
		TestRunner.assertEquals(_+'0 Selected records', 0, m1.selected.length);
		
		m1.select(row1);
		TestRunner.assertEquals(
			_+'Selection changes after selecting row', 
			1,
			m1.selected.length
		);
		
		TestRunner.assertEquals(
			_+'Num selection change events after select',
			1,
			selectionChanges.length
		);
		
		////console.log(selectionChanges);
		var selChangeEvent = selectionChanges[0];
		
		TestRunner.assertEquals(
			_+'Num previous selected',
			0,
			selChangeEvent.oldValue.length
		);
		
		TestRunner.assertEquals(
			_+'Num now selected',
			1,
			selChangeEvent.newValue.length
		);
		
		TestRunner.assertEquals(
			_+'Record now selected (from event)',
			row1,
			selChangeEvent.newValue[0]
		);
		
		TestRunner.assertEquals(_+'Row 1 selected now', true, m1.isSelected(row1));
		TestRunner.assertEquals(
			_+'Row 1 is the selected record', 
			row1,
			m1.getSelectedRecord()
		);
		
		
		
		
		
	
	}
})();