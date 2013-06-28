//require <xataface/modules/testrunner/TestRunner.js>
//require <xataface/store/Document.js>
(function(){
	var $ = jQuery;
	
	var TestRunner = xataface.modules.testrunner.TestRunner;
	var Model = xataface.model.Model;
	var store = xataface.store;
	var Document = store.Document;
	var assertEquals = TestRunner.assertEquals;
	TestRunner.addTest(testModel);
	
	
	function testModel(){
		var pre = 'xataface.store.tests.DocumentTest: ';
		var d1 = new Document();
		
		
		var m1 = new Model();
		var data = {
			firstName : 'Steve',
			lastName : 'Hannah',
			age : 34
		};
		
		d1.setModel(m1);
		assertEquals(pre+'Document model', m1, d1.model);
		d1.query = {
			'-action' : 'test_DocumentTest',
			'row_id' : 1
		};
		
		d1.load(function(res){
			assertEquals(pre+'Response code for load', 200, res.code);
			assertEquals(pre+'firstName after load', 'Joe', m1.get('firstName'));
			assertEquals(pre+'lastName after load', 'Montana', m1.get('lastName'));
			assertEquals(pre+'age after load', 56, m1.get('age'));
			assertEquals(pre+'Dirty flag after load', false, m1.dirty);
			assertEquals(
				pre+'Dirty status bit after load', 
				0x0, 
				d1.getStatus() & Document.DIRTY
			);
			m1.set('firstName', 'Bob');
			assertEquals(
				pre+'Dirty status bit after change', 
				Document.DIRTY, 
				d1.getStatus() & Document.DIRTY
			);
			
			assertEquals(
				pre+'Document open flag', 
				Document.OPEN, 
				d1.getStatus() & Document.OPEN
			);
			
			assertEquals(
				pre+'Document closed flag',
				0x0,
				d1.getStatus() & Document.CLOSED
			);
			
			// Try closing the document
			
			var closeLog = [];
			
			// Create a dummy saveRequest that purports to work.
			d1.saveRequest = function(success, error, complete){
				success.call(d1, {
					code : 200,
					data : d1.data
				});
				complete.call(d1, {
					code : 200,
					data : d1.data
				});
				return d1;
			}
			
			d1.close(function(){
				closeLog.push('1');
			});
			console.log("After close");
			// The callback should have been called exactly once
			assertEquals(
				pre+'Close log size',
				1,
				closeLog.length
			);
			
			// Verify that the document is now closed
			assertEquals(
				pre+'Document is closed after closing',
				Document.CLOSED,
				d1.getStatus() & Document.CLOSED
			);
			
			assertEquals(
				pre+'Document is not open after closing',
				0x0,
				d1.getStatus() & Document.OPEN
			);
			
			
			
		});
		
		var d2 = new Document({
		
			// Custom open prompt that provides a specific query
			// to load
			openPrompt : function(o){
				if ( o && o.open && o.open.call ){
					o.open.call(this, {
						'-action' : 'test_DocumentTest',
						'row_id' : 1
					});	
				}
				return this;

			}
		});
		var m2 = new Model();
		d2.setModel(m2);
		
		d2.open(function(){
			assertEquals(
				pre+'Document open after opening',
				Document.OPEN,
				d2.getStatus() & Document.OPEN
			);
			
			assertEquals(
				pre+'Document not dirty after opening',
				0x0,
				d2.getStatus() & Document.DIRTY
			);
			
			assertEquals(
				pre+'Document not closed after opening',
				0x0,
				d2.getStatus() & Document.CLOSED
			);
			
			assertEquals(
				pre+'Model 2 firstName set after opening',
				'Joe',
				m2.get('firstName')
			);
			
			assertEquals(
				pre+'Model 2 lastName set after opening',
				'Montana',
				m2.get('lastName')
			);
		});
		
		
		
	
	}
})();