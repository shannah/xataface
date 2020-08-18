//require <xataface/components/Sheet.js>
(function() {
    var $ = jQuery;
    window.xataface = window.xataface || {};
    window.xataface.list = window.xataface.list || {};
    window.xataface.list.openSortDialog = openSortDialog;
    window.xataface.list.openFilterDialog = openFilterDialog;
    
    var settingsWrapper = document.querySelector('.mobile-list-settings-wrapper');
    function init() {
        setTimeout(function() {
            
            $('body').on('scroll', showListSettings);
        }, 1000);
        
    }
    
    function updateSettingsButtonPosition() {
        var viewport = xataface.viewport;
        if (viewport) {
            settingsWrapper.style.bottom = (viewport.bottom + 10) + 'px';
        }
    }
    
    function showListSettings() {
        $(settingsWrapper).addClass('active');
        
    }
    
    
    function openSortDialog() {
        var qStr = window.location.search;
        if (qStr.indexOf('-action=') !== -1) {
            qStr = qStr.replace(/-action=[^&]*/, '-action=mobile_sort_dialog');
        } else {
            qStr += '&-action=mobile_sort_dialog';
        }
        var sheet = new xataface.Sheet({
            url : qStr
        });
        sheet.show();
    }
    
    function openFilterDialog() {
        var qStr = window.location.search;
        if (qStr.indexOf('-action=') !== -1) {
            qStr = qStr.replace(/-action=[^&]*/, '-action=mobile_sort_dialog');
        } else {
            qStr += '&-action=mobile_filter_dialog';
        }
        var sheet = new xataface.Sheet({
            url : qStr
        });
        sheet.show();
    }
    
    $(window).on('xf-viewport-changed', updateSettingsButtonPosition);
    $(document).ready(init);
})();