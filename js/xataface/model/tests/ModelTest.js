//require <xataface/modules/testrunner/TestRunner.js>
//require <xataface/model/Model.js>
(function(){
	var $ = jQuery;
	
	var TestRunner = xataface.modules.testrunner.TestRunner;
	var Model = xataface.model.Model;
	
	TestRunner.addTest(testModel);
	
	
	function testModel(){
		var _ = 'xataface.model.tests.ModelTest: ';
		var m1 = new Model();
		var data = {
			firstName : 'Steve',
			lastName : 'Hannah',
			age : 34
		};
		
		var changes = [];
		function changeListener(evt, data){
			changes.push(1);
		}
		$(m1).bind('changed', changeListener);
		TestRunner.assertEquals(_+'Num Change Events Before', 0, changes.length);
		TestRunner.assertEquals(_+'Dirty before?', false, m1.dirty);
		// Set the data... should fire a change event
		m1.set(data);
		
		TestRunner.assertEquals(_+'Dirty after?', true, m1.dirty);
		TestRunner.assertEquals(_+'Num Change Events After', 1, changes.length);
		TestRunner.assertEquals(_+'Get firstName', 'Steve', m1.get('firstName'));
		TestRunner.assertEquals(_+'Get lastName', 'Hannah', m1.get('lastName'));
		TestRunner.assertEquals(_+'Get age', 34, m1.get('age'));
		
		// Try getting all of the data as an object.
		var d2 = m1.get();
		TestRunner.assertEquals(_+'Get firstName', 'Steve', d2.firstName);
		TestRunner.assertEquals(_+'Get lastName', 'Hannah', d2.lastName);
		TestRunner.assertEquals(_+'Get age', 34, d2.age);
		
		changes = [];
		
		m1.setDirty(false);
		TestRunner.assertEquals(_+'Changes after setting dirty', 1, changes.length);
		TestRunner.assertEquals(_+'Dirty after setting dirty', false, m1.dirty);
		m1.set('firstName', 'John');
		TestRunner.assertEquals(_+'First name after change', 'John', m1.get('firstName'));
		TestRunner.assertEquals(_+'Changes after name change', 2, changes.length);
		
		changes = [];
		
		// Now let's test the startUpdate
		m1.startUpdate();
		m1.set('firstName', 'Joe');
		TestRunner.assertEquals(_+'Changes in transaction 1',0, changes.length);
		m1.set('lastName', 'Walker');
		TestRunner.assertEquals(_+'Changes in transaction 2',0, changes.length);
		m1.set({
			firstName : 'Jim',
			age : 12
		});
		TestRunner.assertEquals(_+'Changes in transaction 3',0, changes.length);
		m1.endUpdate();
		TestRunner.assertEquals(_+'Changes after closing transaction', 1, changes.length);
		
	
	}
})();