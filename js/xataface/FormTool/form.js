(function() {
    var $ = jQuery;
    function isMobile() {
        var body = document.querySelector('body');
        return (body && body.classList.contains('small'));
    }
    
    function installMobileSubmitButton() {
        if (!isMobile()) {
            return;
        }
        var saveBtn = document.querySelector('input[name="--session:save"]');
        if (saveBtn) {
            var label = $(saveBtn).parents('[xf-submit-label]').attr('xf-submit-label');
            if (!label) {
                return;
            }
            saveBtn.style.display = 'none';
            
            
            
            var newBtn = $('<button class="submit-btn btn featured">'+label+'</button>');
            newBtn.on('click', function(e) {
                e.preventDefault();
                
                saveBtn.click();
                return false;
                
            });
            newBtn.insertBefore(saveBtn);
            
            
        }
        
        
    }
    
    function installFieldgroupMenu() {

        $('ul.xf-fieldgroup-menu > li > a').click(function() {
            if ($(this).hasClass('disabled')) {
                return false;
            }
            var groupName = $(this).parent().attr('data-xf-fieldgroup-name');
            console.log(groupName);
            if (groupName) {
                $('[data-form-group="' + groupName +'"]').removeClass('hidden');
                $(this).prop('disabled', true).addClass('disabled');
            }
            return false;
            
        });
        
    }
    window.addEventListener('DOMContentLoaded', installFieldgroupMenu);
    window.addEventListener('DOMContentLoaded', installMobileSubmitButton);
})();