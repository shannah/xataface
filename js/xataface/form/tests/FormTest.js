//require <xataface/modules/testrunner/TestRunner.js>
//require <xataface/form/Form.js>
(function(){
	var $ = jQuery;
	
	var TestRunner = xataface.modules.testrunner.TestRunner;
	var Model = xataface.model.Model;
	var Form = xataface.form.Form;
	var assertEquals = TestRunner.assertEquals;
	TestRunner.addTest(testModel);
	
	
	function testModel(){
		var pre = 'xataface.form.tests.FormTest: ';
		var $form = $('<form>'+
			'<input type="text" name="firstname"/>'+
			'<input type-="text" name="lastname"/>'+
			'<input type="text" data-key="age" name="foo"/>'+
			'</form>'
		);
		
		$firstName = $('input[name="firstname"]', $form);
		$lastName = $('input[name="lastname"]', $form);
		$age = $('input[name="foo"]', $form);
		
		
		var model = new Model();
		Model.addProperties(model, ['firstname','lastname','age']);
		$.extend(model, {
			firstname : 'Steve',
			lastname : 'Hannah',
			age : 33
		});
		
		var model2 = new Model();
		Model.addProperties(model2, ['firstname','lastname','age']);
		$.extend(model2, {
			firstname : 'John',
			lastname : 'Smith',
			age : 12
		});
		
		
		var f1 = new Form({
			el : $form.get(0),
			model : model
		});
		
		assertEquals(pre+'Form status before build',
			Form.STATUS_IDLE,
			f1.formStatus
		);
		
		f1.decorate();
		assertEquals(pre+'Form status after build',
			Form.STATUS_READY,
			f1.formStatus
		);
		
		
		assertEquals(pre+'Empty firstname field','', $firstName.val());
		assertEquals(pre+'Empty lastname field', '', $lastName.val());
		assertEquals(pre+'Empty age field', '', $age.val());
		
		f1.pull();
		
		assertEquals(pre+'Firstname field after pull', 'Steve', $firstName.val());
		assertEquals(pre+'Lastname field after pull', 'Hannah', $lastName.val());
		assertEquals(pre+'age field after pull', '33', $age.val());
		
		f1.model = model2;
		f1.pull();
		assertEquals(pre+'Firstname field after pull 2', 'John', $firstName.val());
		assertEquals(pre+'Lastname field after pull 2', 'Smith', $lastName.val());
		assertEquals(pre+'age field after pull 2', '12', $age.val());
		
		function validateSteve(evt, data){
			data.start();
			if ( data.el.getValue() == 'Steve' ){
				data.pass();
			} else {
				data.fail('First name wasnt steve');
			}
		}
		
		// try validating with no validators
		var successes = 0;
		var failures = 0;
		
		var validateCallback = {
			onSuccess : function(){
				successes++;
			},
			
			onFail : function(){
				failures++;
			}
		};
		
		f1.validate(validateCallback);
		
		assertEquals(
			pre+'1 Success after validate',
			1,
			successes
		);
		
		assertEquals(
			pre+'0 Failures after validate',
			0,
			failures
		);
		
		assertEquals(
			pre+'No error after successful validation',
			false,
			f1.error
		);
		
		assertEquals(
			pre+'Null errorMessage after successful validation.',
			null,
			f1.errorMessage
		);
		
		$(f1.elements.firstname).bind('validators', validateSteve);
		
		f1.validate(validateCallback);
		
		assertEquals(
			pre+'1 failure after validate',
			1,
			failures
		);
		
		assertEquals(
			pre+'1 success still after validate',
			1,
			successes
		);
		
		assertEquals(
			pre+'Error flag after failed validation',
			true,
			f1.error
		);
		
		
		
		
		function validateForm1(evt, data){
			data.start();
			if ( data.el.elements.firstname.getValue() == 'Steve'
				&&
				data.el.elements.lastname.getValue() == 'Smith'
			){
				data.pass();
			} else {
				data.fail('Only accepts Steve Smith')
			}
		}
		
		$(f1).bind('validators', validateForm1);
		
		f1.validate(validateCallback);
		
		assertEquals(
			pre+'2 failures after failed validation',
			2,
			failures
		);
		
		assertEquals(
			pre+'Error flag still after another failed validation',
			true,
			f1.error
		);
		
		$(f1).unbind('validators', validateSteve);
		
		f1.validate(validateCallback);
		
		assertEquals(
			pre+'3 failures after failed validation',
			3,
			failures
		);
		
		assertEquals(
			pre+'Error flag still after a third failed validation',
			true,
			f1.error
		);
		
		f1.elements.firstname.setValue('Steve');
		f1.elements.lastname.setValue('Smith');
		
		f1.validate(validateCallback);
		
		assertEquals(
			pre+'3 failures still after successful validation',
			3,
			failures
		);
		
		assertEquals(
			pre+'2 successes after successful validation',
			2,
			successes
		);
		
		assertEquals(
			pre+'No error after successful form validation',
			false,
			f1.error
		);
		
		assertEquals(
			pre+'No error message after successful form validation',
			null,
			f1.errorMessage
		);
		
		
		
		
		
				
	
	}
})();