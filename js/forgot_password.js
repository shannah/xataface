
//require <jquery.packed.js>
jQuery(document).ready(function($){
	
	var formDiv = $('.forgot-password-form');
	
	function getUsername(){
		if ( $('#reset-password-by', formDiv).val() != 'username' ) return null;
		else return $('#email-or-username', formDiv).val();
	}
	
	function getEmail(){
		if ( $('#reset-password-by', formDiv).val() != 'email' ) return null;
		else return $('#email-or-username', formDiv).val();
	}
	
	function submitFormByEmail(callback){
		var email = getEmail();
		if ( !email ) throw "Please enter a valid email address.";
		
		var p= {
			'--email': email,
			'-action': 'forgot_password',
			'--format': 'json'
		};
		
		$.post(DATAFACE_SITE_HREF, p, callback);
	}
	
	function submitFormByUsername(callback){
		var username = getUsername();
		if ( !username) throw "Please enter a valid username";
		
		var p = {
			'--username': username,
			'-action': 'forgot_password',
			'--format': 'json'
		};
		
		$.post(DATAFACE_SITE_HREF, p, callback);
	}
	
	function setStatus(msg){
		$('.status-message', formDiv).text(msg);
		$('.status-message').css('display','');
	}
	
	function serverCallback(response){
		try {
			if ( typeof(response) == 'string' ){
				eval('response = '+response+';');
			}
			
			if ( !response.code ) throw "Unknown server error.";
			if ( response.code == 200 ){
				setStatus(response.message);
			} else {
				throw response.message;
			}
		} catch (e){
			setStatus(e);
		}
	}
	
	
	$('#submit-button', formDiv).click(function(){
		if ( getUsername() ){
			submitFormByUsername(serverCallback);
		} else if ( getEmail() ){
			submitFormByEmail(serverCallback);
		} else {
			alert('Please enter a username or email address.');
		}
	});

});