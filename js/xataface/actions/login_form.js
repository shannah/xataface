//require <jquery.packed.js>
//require <duDialog.min.js>
(function() {
    var $ = jQuery;

    registerXatafaceDecorator(function() {
        loadLoginType();
        // Login with email button should trigger an email to the user with a login link.
        $('#Login-with-email-submit').click(function() {
            var self = this;
            $(this).attr('disabled', false);
            setTimeout(function() {
                $(self).removeAttr('disabled');
            }, 1000);
            var email = $('#Login-Username input[name="UserName"]').val();
            var redirect = $('input[name="-redirect"]').val();
            var rememberMe = $('input[name="--remember-me"]');
            var rememberMeChecked = rememberMe.is(':checked');
            
            if (email) {
                var params = {'-action' : 'xf_email_login', '--email' : email, '--redirectUrl' : redirect};
                if (rememberMeChecked) {
                    params['--remember-me'] = '1';
                }
                $.post(DATAFACE_SITE_HREF, params, function(res) {
                    if (!res) {
                        res = {code : 500, message: 'Email failed'};
                    }
                    if (res) {
                        console.log("About to show prompt");
                        var prompt = new duDialog(null, res.message, {
                          buttons: duDialog.OK_CANCEL,
                          okText: 'OK',
                            cancelText: null,
                          callbacks: {
                            okClick: function(){
                              // do something
                              
                              this.hide();
              
                            }
                          }
                        });
                    }
                    console.log('result:', res);
                });
            } else {
                var prompt = new duDialog(null, 'Please enter your email address', {
                  buttons: duDialog.OK_CANCEL,
                  okText: 'OK',
                    cancelText: null,
                  callbacks: {
                    okClick: function(){
                      // do something
                      
                      this.hide();
      
                    }
                  }
                });
            }
            
            return false; 
        });
        
        
        
        
        $('#Login-with-password-button').click(function() {
            toggleLoginType('password');
            //$('form.xataface-login-form').addClass('xf-password-login').removeClass('xf-email-login');
        });
        
        $('#Login-with-email-button').click(function() {
            toggleLoginType('email');
            //$('form.xataface-login-form').removeClass('xf-password-login').addClass('xf-email-login');
        });
        
        $('ul.xf-auto-register a.xf-login-action-register').click(function() {
            var prompt = new duDialog(null, 'Use the "Email Login Link" button above to create an account', {
              buttons: duDialog.OK_CANCEL,
              okText: 'OK',
                cancelText: null,
              callbacks: {
                okClick: function(){
                  // do something
                  
                  this.hide();
  
                }
              }
            });
            return false;
        });
    });
   
   
    function getOtherLoginType(type) {
        return (type == 'email') ? 'password' : 'email';
    }
   
    function toggleLoginType(type) {
        var form = $('form.xataface-login-form');
        if (form.hasClass('xf-allow-'+type+'-login')) {
            form.addClass('xf-'+type+'-login').removeClass('xf-'+getOtherLoginType(type)+'-login');
            setCookie("__xf_login_type__", type, 1);
        }
        
    }
    
    function loadLoginType() {
        var loginType = getCookie('__xf_login_type__');
        if (loginType) {
            toggleLoginType(loginType);
        }
    }
   
   
    function setCookie(key, value, expiry) {
        var expires = new Date();
        expires.setTime(expires.getTime() + (expiry * 24 * 60 * 60 * 1000));
        document.cookie = key + '=' + value + ';expires=' + expires.toUTCString();
    }

    function getCookie(key) {
        var keyValue = document.cookie.match('(^|;) ?' + key + '=([^;]*)(;|$)');
        return keyValue ? keyValue[2] : null;
    }

    function eraseCookie(key) {
        var keyValue = getCookie(key);
        setCookie(key, keyValue, '-1');
    }
   
})();