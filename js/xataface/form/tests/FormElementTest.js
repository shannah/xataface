//require <xataface/modules/testrunner/TestRunner.js>
//require <xataface/form/FormElement.js>
(function(){
	var $ = jQuery;
	
	var TestRunner = xataface.modules.testrunner.TestRunner;
	var Model = xataface.model.Model;
	var FormElement = xataface.form.FormElement;
	var assertEquals = TestRunner.assertEquals;
	TestRunner.addTest(testModel);
	
	
	function testModel(){
		var pre = 'xataface.form.tests.FormElementTest: ';
		var $form = $('<form><input type="text" name="firstname"/></form>');
		
		
		
		var model = new Model();
		Model.addProperties(model, ['firstname','lastname','age']);
		$.extend(model, {
			firstname : 'Steve',
			lastname : 'Hannah',
			age : 33
		});
		
		
		var e1 = new FormElement({
			el : $('input[name="firstname"]', $form).get(0),
			model : model
		});
		assertEquals(pre+"Model value after init", 'Steve', model.firstname);
		assertEquals(pre+"Model value through e1", 'Steve', e1.model.firstname);
		assertEquals(pre+'Initial Value', '', e1.getValue());
		e1.pull();
		assertEquals(pre+'Value after pull', 'Steve', e1.getValue());
		$(e1.el).val('Barry');
		assertEquals(pre+'Value in model unchanged before push', 'Steve', model.firstname);
		e1.push();
		assertEquals(pre+'Model value changed after push', 'Barry', model.firstname);
		
		function steveValidator(evt, data){
			data.start();
			if ( data.el.getValue() == 'Steve' ){
				data.pass();
			} else {
				data.fail('Only accepts input Steve');
			}
		}
		
		$(e1).bind('validators', steveValidator);
		var successes = 0;
		var failures = 0;
		e1.validate({
			onSuccess : function(data){
				successes++;
			},
			onFail : function(data){
				failures++;
			}
		});
		
		assertEquals(pre+"Failed validation count", 1, failures);
		assertEquals(pre+"Succeeded validation count", 0, successes);
		assertEquals(
			pre+'Validation error msg', 
			'Only accepts input Steve', 
			e1.errorMessage
		);
		
		assertEquals(
			pre+'Validation error flag',
			true,
			e1.error
		);
		
		
		e1.setValue('Steve');
		e1.validate({
			onSuccess : function(data){ successes++},
			onFail : function(data){ failures++}
		});
		assertEquals(
			pre+'Failed validation count 2',
			1,
			failures
		);
		assertEquals(
			pre+'Succeeded validation count 2',
			1,
			successes
		);
		
		assertEquals(
			pre+'Validation error message 2',
			null,
			e1.errorMessage
		);
		assertEquals(
			pre+'Validation error flag 2',
			false,
			e1.error
		);
				
	
	}
})();