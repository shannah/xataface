//require <jquery.packed.js>
//require <duDialog.min.js>
(function() {
    var $ = jQuery;

    registerXatafaceDecorator(function() {

        // Login with email button should trigger an email to the user with a login link.
        $('#Login-with-email-submit').click(function() {
            var self = this;
            $(this).attr('disabled', false);
            setTimeout(function() {
                $(self).removeAttr('disabled');
            }, 1000);
            var email = $('#Login-Username input[name="UserName"]').val();
            var redirect = $('input[name="-redirect"]').val();
            
            if (email) {
                $.post(DATAFACE_SITE_HREF, {'-action' : 'xf_email_login', '--email' : email, '--redirectUrl' : redirect}, function(res) {
                    if (!res) {
                        res = {code : 500, message: 'Email failed'};
                    }
                    if (res) {
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
            $('form.xataface-login-form').addClass('xf-password-login').removeClass('xf-email-login');
        });
        
        $('#Login-with-email-button').click(function() {
            $('form.xataface-login-form').removeClass('xf-password-login').addClass('xf-email-login');
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
   
})();