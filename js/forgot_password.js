
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
                $('#reset-password-submit-button').get(0).disabled = false;
			} else {
				throw response.message;
			}
		} catch (e){
			setStatus(e);
            $('#reset-password-submit-button').get(0).disabled = false;
		}
	}
	
	
	$('#reset-password-submit-button', formDiv).click(function(){
        // FOr some reason we're getting this event twice per click, so we need to disable the button 
        // to prevent the second time around.
        // Can't seem to find the source of this for a proper fix.
        if (this.disabled) return;
        this.disabled = true;

		if ( getUsername() ){
			submitFormByUsername(serverCallback);
		} else if ( getEmail() ){
			submitFormByEmail(serverCallback);
		} else {
			alert('Please enter a username or email address.');
		}
	});
    
    $('#reset-password-by').on('change', function() {
        $("#email-or-username").focus();
    });

    
    (function() {
        var sideMenu = document.querySelector('.sidemenu.header');
        if (sideMenu) {
            sideMenu.style.display = 'none';
        }
        var toggle = document.querySelector('.sidebarIconToggle');
        if (toggle) {
            toggle.style.display = 'none';
        }
    })();

});