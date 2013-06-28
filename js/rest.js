(function(){

	if ( typeof(jQuery) == 'undefined' ){
		document.writeln('<'+'script src="'+DATAFACE_URL+'/js/jquery.packed.js"><'+'/script>');
	}
	
	if ( typeof(window.Xataface) != 'object' ) window.Xataface = {};
	window.Xataface.insert = insertRecord;
	window.Xataface.update = updateRecord;
	window.Xataface.deleteRecord = deleteRecord;
	window.Xataface.load = loadRecord;
	window.Xataface.submit = submitForm;
	window.Xataface.form = decorateForm;
	
	
	function decorateForm(form, successCallback, errorCallback, suppressAlerts){
	
		var $ = jQuery;
		$(form).submit(function(){
			submitForm(form, successCallback, errorCallback, suppressAlerts);
			return false;
		});
		
		// Now get extra data about this form.
		
		var fields = [];
		$('.xf-field', form).each(function(){
			fields.push($(this).attr('name'));
		});
		
		var q = {
			'-action': 'rest_form',
			'-table': getFormTable(form),
			'--fields': fields.join(',')
		};
		$.get(DATAFACE_SITE_HREF, q, function(result){
			
			try {
				if ( typeof(result) == 'string' ){
					eval('result='+result+';');
				}
				
				if ( result.code == 200 ){
					$.each(result.form, function(key,val){
						if ( typeof(val.validators) == 'object' && val.validators.required ){
							
							$('.xf-field[name="'+key+'"]', form).each(function(){
								var dot = document.createElement('img');
								$(dot).attr('src', DATAFACE_URL+'/images/required.gif');
								$(dot).attr('title', 'This field is required');
								$(this).after(dot);
							});
						
						}
					});
				} else {
					throw result.message;
				}
			} catch (e){
			
				alert(e);
			}
			
		});
	}
	
	
	function insertRecord(table, values, callback, errorCallback){
		
		if ( typeof(callback) != 'function' ) callback = function(){};
		if ( typeof(errorCallback) != 'function' ) errorCallback = function(result){
			throw result.message;
		};
		
		var q = jQuery.extend({
			'-action': 'rest_insert',
			'-table': table
		}, values);
		
		//alert(q['-action']);
		
		jQuery.post(DATAFACE_SITE_HREF, q, function(result){
			try {
				if ( typeof(result) == 'string' ){
					eval('result='+result+';');
				}
				
				if ( result.code == 200 ){
					callback(result);
				} else {
					errorCallback(result);
				}
				
			} catch (e){
				alert(e);
			}
		
		});
	
	}
	
	
	function getFormTable(form){
		var table;
		if ( $(form).attr('data-xf-table') ){
			table = $(form).attr('data-table');
		} else {
			var tableInput = $('input[name="-table"]', form);
			if ( tableInput.size() > 0 ){
				table = tableInput.val();
			} else {
				throw "No table found for form";
			}
		}
		return table;
	
	}
	
	
	function submitForm(form, successCallback, errorCallback, suppressAlerts){
		if ( typeof(suppressAlerts) == 'undefined' ) suppressAlerts = false;
		clearFormErrors(form);
		var $ = jQuery;
		var values = {};
		var table = getFormTable(form);
		
		// Now for the values
		$('.xf-field', form).each(function(){
			values[$(this).attr('name')] = $(this).val();
		});
		
		
		var submitButton = $('input[type="submit"]', form);
		var progressImage = document.createElement('img');
		$(progressImage).attr('src', DATAFACE_URL+'/images/progress.gif');
		$(submitButton).after(progressImage);
		$(submitButton).attr('disabled',true);
		
		
		function mySuccessCallback(result){
			$(progressImage).remove();
			$(submitButton).attr('disabled', false);
			clearFormErrors(form);
			
			if ( typeof(result.record) == 'object' ){
				for ( var i in result.record ){
					$('.xf-field[name="'+i+'"]', form).val(result.record[i]);
				}
			}
			if ( typeof(successCallback) == 'function' ) successCallback(result);
			
		
		}
		
		function myErrorCallback(result){
			$(progressImage).remove();
			$(submitButton).attr('disabled', false);
			if ( typeof(result.errors) != 'undefined' ){
				$.each(result.errors, function(i, val){
					if ( typeof(i) == 'string' ){
						
						// try to find the field
						$('.xf-field[name="'+i+'"]', form).each(function(){
						
							$(this).addClass('submit-error');
							var infoIcon = document.createElement('a');
							$(infoIcon)
								.attr('href','#')
								.attr('title', result.errors[i])
								.click(function(){ alert(result.errors[i]); return false;})
								.addClass('xf-field-error-icon')
								.html('<img src="'+DATAFACE_URL+'/images/error.png"/>');
								
							$(this).after(infoIcon);
								
						});
						
					}
				});
			}
			if ( !suppressAlerts ){
				
				if ( result.code == 501 ){
					alert('Validation Error.  Please check the marked fields and try again.');
				} else {
					alert(result.message);
				}
			}
			if ( typeof(errorCallback) == 'function' ) errorCallback(result);
		}
		
		if ( $(form).attr('data-recordID') ){
			Xataface.update($(form).attr('data-recordID'), values, mySuccessCallback, myErrorCallback);
		} else {
			Xataface.insert(table, values, mySuccessCallback, myErrorCallback);
		}
		
	}
	
	
	function clearFormErrors(form){
		var $ = jQuery;
		$('.xf-field-error-icon', form).remove();
		$('.xf-field', form).removeClass('submit-error');
		
	}
	
	
	function updateRecord(recordID, values, callback){
		throw "Not implemented yet";
	}
	
	function deleteRecord(recordID, callback){
		var $ = jQuery;
		
		var q = {
			'--record_id': recordID,
			'-action': 'rest_delete'
		};
		$.post(DATAFACE_SITE_HREF, q, function(res){
			try {
				if ( res.code == 200 ){
					if ( typeof(callback) == 'function' ){
						callback(res);
					}
				} else {
					if ( res.message ){
						throw res.message;
					} else {
						throw 'An Unspecified Server Error Has Occurred.  Failed to delete record.';
					}
				}
			} catch (e){
				alert(e);
			}
		});
		
	}
	
	function loadRecord(recordID, callback){
		throw "Not implemented yet";
	}
	

})();