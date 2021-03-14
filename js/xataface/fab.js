//require <xataface/components/ActionSheet.js>
(function() {
    var ActionSheet = window.xataface.ActionSheet;
    function installFab() {
        var $ = jQuery;
        
        $('#zoomBtn').click(function() {

            var pageActionsUl = $('div.page-actions > nav > ul').first();
            if (pageActionsUl.length > 0) {
                var menu = new ActionSheet(pageActionsUl.get(0));
                menu.show();
            }
            
        });
        var pageActionsUl = $('div.page-actions > nav > ul').first();
        if ($('>li', pageActionsUl).length > 0) {
            $('.zoom').css('display', '');
        }
        
    }
    
    function updatePosition() {
        var zoom = document.querySelector('.zoom');
        
        if (zoom) {
            var footer = document.querySelector('.mobile-footer');
            if (footer) {
                zoom.style.bottom = (footer.offsetHeight + 10) + "px";
            }    
        }
    }
    window.addEventListener('xf-viewport-changed', updatePosition);
    window.addEventListener('DOMContentLoaded', installFab);
})();