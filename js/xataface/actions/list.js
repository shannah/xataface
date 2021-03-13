//require <xataface/components/Sheet.js>
//require <xataface/components/InfiniteScroll.js>
(function() {
    var $ = jQuery;
    window.xataface = window.xataface || {};
    window.xataface.list = window.xataface.list || {};
    window.xataface.list.openSortDialog = openSortDialog;
    window.xataface.list.openFilterDialog = openFilterDialog;
    
    // Settings wrapper is container for actions like "sort" and "filter"
    var settingsWrapper = document.querySelector('.mobile-list-settings-wrapper');
    

    
    /**
     * Initializes list on dom ready.
     */
    function init() {
        // We will add a scroll listener to the body to show the list settings.
        setTimeout(function() {
            document.addEventListener('scroll', function(event) {
                showListSettings();
            }, true);
        }, 1000);
        
        if (window.innerWidth < 768 || $('.mobile-listing').hasClass('list-style-mobile')) {
            // For mobile we enable infinite scrolling
            new xataface.InfiniteScroll({
               scrollEl : $('body').get(0),
               parentEl : $('.mobile-listing').get(0) 
            });
        }
            
       
        
    }
    
    function decoratePreviews(root) {
        $('.external-link-preview[data-href]', root).click(function() {
            window.open($(this).attr('data-href'));
        });
    }
    
    /**
     * Update the settings wrapper position to 10 px above the mobile footer.
     */
    function updateSettingsButtonPosition() {
        var viewport = xataface.viewport;
        if (viewport) {
            settingsWrapper.style.bottom = (viewport.bottom + 10) + 'px';
        }
    }
    
    /**
     * Marks the settings wrapper as "active" which causes it to float.
    */
    function showListSettings() {
        $(settingsWrapper).addClass('active');
        
    }
    
    /**
     * Opens the sort dialog.
     */
    function openSortDialog() {
        var qStr = window.location.search;
        if (!qStr) {
            qStr = '?';
        }
        qStr = qStr.replace(/\?-action=[^&]*/, '?').replace(/&-action=[^&]*/, '');
        qStr += '&-action=mobile_sort_dialog';
        
        var position = $('body').hasClass('small') ? 'bottom' : 'right';
        var sheet = new xataface.Sheet({
            url : qStr,
            position: position
        });
        sheet.show();
    }
    
    /**
     * Opens the filter dialog.
     */
    function openFilterDialog() {
        var qStr = window.location.search;
        if (!qStr) {
            qStr = '?';
        }
        qStr = qStr.replace(/\?-action=[^&]*/, '?').replace(/&-action=[^&]*/, '');
        qStr += '&-action=mobile_filter_dialog';
        
        var position = $('body').hasClass('small') ? 'bottom' : 'right';
        var sheet = new xataface.Sheet({
            url : qStr,
            position : position
        });
        sheet.show();
    }
    
    // When the viewport changes, we may need to update settings buttons position.
    $(window).on('xf-viewport-changed', updateSettingsButtonPosition);
    $(document).ready(init);
    registerXatafaceDecorator(decoratePreviews);
})();