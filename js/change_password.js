//require <jquery.packed.js>
(function(){


	jQuery(document).ready(function($){
	
		var form = $('#change-password-form');
		var successPage = $('#change-password-complete');
		
		form.show();
		successPage.hide();
		
		var oldPasswordField = $('#--current-password');
		var newPasswordField1 = $('#--password1');
		var newPasswordField2 = $('#--password2');
		var submitBtn = $('#change-password-submit');
		submitBtn.click(function(){
			return handleSubmitClicked(this);
		});
		
		function handleSubmitClicked(btn){
			
			if ( !newPasswordField1.val() ){
				alert('You cannot enter a blank password.');
				return false;
			}
			
			if ( newPasswordField1.val() != newPasswordField2.val() ){
				alert('Your passwords do not match.  Please ensure that you type your new password the same in both fields.');
				return false;
			}
			
			var query = {
				'-action': 'change_password',
				'--current-password': oldPasswordField.val(),
				'--password1': newPasswordField1.val(),
				'--password2': newPasswordField2.val()
			};
			
			submitBtn.attr('disabled',1);
			submitBtn.after('<img class="progress-image" src="'+DATAFACE_URL+'/images/progress.gif" alt="please wait ..."/>');
			$.post(DATAFACE_SITE_HREF, query, function(response){
				
				handleChangePasswordResponse(response);
				submitBtn.attr('disabled',0);
				$('.progress-image').remove();
			
			});
			return false;
		
		}
		
		
		function handleChangePasswordResponse(response){
		
			try {
				if ( typeof(response) == 'string' ){
					eval('response='+response+';');
				}	
				
				if ( response.code == 200 ){
					successPage.show();
					form.hide();
				} else if ( response.message ){
					throw response.message;
				} else {
					throw "Password was not changed due to a server error.  Please check the server logs for more details.";
				}
			
			} catch (e){
				alert(e);
			}
			
		}
		
	});


})();