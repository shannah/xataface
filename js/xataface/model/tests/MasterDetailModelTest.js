//require <xataface/modules/testrunner/TestRunner.js>
//require <xataface/model/MasterDetailModel.js>
(function(){
	var $ = jQuery;
	
	var TestRunner = xataface.modules.testrunner.TestRunner;
	var model = xataface.model;
	var MasterDetailModel = model.MasterDetailModel;
	var Model = model.Model;
	var ListModel = model.ListModel;
	
	TestRunner.addTest(testModel);
	
	
	function testModel(){
		var _ = 'xataface.model.tests.MasterDetailModelTest: ';
		var lm1 = new ListModel();
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
		
		lm1.add([row1, row2]);
		
		var mdm1 = new MasterDetailModel();
		mdm1.setListModel(lm1);
		
		TestRunner.assertEquals(
			_+'Null detail model at beginning',
			null,
			mdm1.detailModel
		);
		
		lm1.select(row1);
		
		TestRunner.assertEquals(
			_+'Detail model after selecting row 1',
			row1,
			mdm1.detailModel
		);
		 
		var selectionChanges = [];
		function selectionChangeListener(evt, data){
			selectionChanges.push(data);
		}
		$(mdm1).bind('selectionChanged', selectionChangeListener);
		
		lm1.select(row2);
		
		
		TestRunner.assertEquals(
			_+'Detail model after selecting row 2',
			row2,
			mdm1.detailModel
		);
		
		TestRunner.assertEquals(
			_+'Selection change events thrown',
			1,
			selectionChanges.length
		);
		
		var selChangeEvent = selectionChanges[0];
		TestRunner.assertEquals(
			_+'Old detail model row1', 
			row1,
			selChangeEvent.oldDetailModel
		);
		
		TestRunner.assertEquals(
			_+'New detail model row2',
			row2,
			selChangeEvent.newDetailModel
		);
		
		
		// Now try undoing the change
		selChangeEvent.undo();
		
		TestRunner.assertEquals(
			_+'Another selection change after undo',
			2, 
			selectionChanges.length
		);
		
		TestRunner.assertEquals(
			_+'Detail model after undo row 1 again',
			row1,
			mdm1.detailModel
		);
		//console.log(selectionChanges);
		
		
		
		
		
		
	
	}
})();