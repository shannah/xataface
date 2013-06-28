//require <xataface/modules/testrunner/TestRunner.js>
//require <xataface/store/ResultSet.js>
(function(){
	var $ = jQuery;
	
	var TestRunner = xataface.modules.testrunner.TestRunner;
	var Model = xataface.model.Model;
	var ListModel = xataface.model.ListModel;
	var store = xataface.store;
	var ResultSet = store.ResultSet;
	var assertEquals = TestRunner.assertEquals;
	TestRunner.addTest(testModel);
	
	
	function testModel(){
		var pre = 'xataface.store.tests.ResultSetTest: ';
		var r1 = new ResultSet();
		var l1 = new ListModel();
		r1.setModel(l1);
		
		r1.query = {
			'-action' : 'test_DocumentTest',
			'-resultSet' : '1',
			'result_id' : '2'
		}; 
		
		r1.load(function(res){
			
			assertEquals(
				pre+'Response code for load 200',
				200,
				res.code
			);
			
			assertEquals(
				pre+'Number of results',
				3,
				res.rows.length
			);
			
			assertEquals(
				pre+'Number of rows in list model',
				3,
				l1.rows.length
			);
			
			assertEquals(
				pre+'First Row first Name',
				'Joe',
				l1.rows[0].firstName
			);
			
			assertEquals(
				pre+'Second row first Name',
				'Steve',
				l1.rows[1].firstName
			);
				
			
		});
		
		
		
		
	
	}
})();