//require <xataface/modules/testrunner/TestRunner.js>
//require <xataface/store/MasterDetailStore.js>
(function(){
	var $ = jQuery;
	
	var TestRunner = xataface.modules.testrunner.TestRunner;
	var model = xataface.model;
	var Model = model.Model;
	var ListModel = model.ListModel;
	var MasterDetailModel = model.MasterDetailModel;
	var store = xataface.store;
	var ResultSet = store.ResultSet;
	var Document = store.Document;
	var MasterDetailStore = store.MasterDetailStore;

	var assertEquals = TestRunner.assertEquals;
	TestRunner.addTest(testModel);
	
	
	function testModel(){
		var pre = 'xataface.store.tests.MasterDetailStoreTest: ';
		var mdm1 = new MasterDetailModel({
			listModel : new ListModel()
		});
		
		var mds1 = new MasterDetailStore({
			model : mdm1,
			resultSet : new ResultSet({
				model : mdm1.listModel
			}),
			document : new Document(),
			getQueryFor : function(row){
				return {
					'-action' : 'test_DocumentTest',
					'row_id' : row.row_id
				};
			}
		});
		
		
		mds1.resultSet.query = {
			'-action' : 'test_DocumentTest',
			'-resultSet' : '1',
			'result_id' : '2'
		}; 
		
		mds1.resultSet.load(function(res){
			
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
				this.model.rows.length
			);
			
			assertEquals(
				pre+'First Row first Name',
				'Joe',
				this.model.rows[0].firstName
			);
			
			assertEquals(
				pre+'Second row first Name',
				'Steve',
				this.model.rows[1].firstName
			);
			
			$(mdm1).bind('detailModelChanged', function(){
				
			});
			
			mds1.selectRow(this.model.rows[1], function(res){
				
				assertEquals(
					pre+'First name after row selected',
					this.resultSet.model.rows[1].firstName,
					mds1.document.model.firstName
				);
				
				assertEquals(
					pre+'Comments after row selected',
					'Steve Comments',
					mds1.document.model.comments
				);
				
				mds1.selectRow(this.resultSet.model.rows[2], function(res){
					assertEquals(
						pre+'First name after row 3 selected',
						this.resultSet.model.rows[2].firstName,
						mds1.document.model.firstName
					);
					
					assertEquals(
						pre+'Comments after row 3 selected',
						'Barry comments',
						mds1.document.model.comments
					);
				});
				
			});
				
			
		});
		
		
		
		
	
	}
})();