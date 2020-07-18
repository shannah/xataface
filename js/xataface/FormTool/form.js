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
    
    window.addEventListener('DOMContentLoaded', installMobileSubmitButton);
})();