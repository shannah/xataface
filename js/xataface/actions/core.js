//require <xataface/components/Sheet.js>
(function() {
    window.xataface = window.xataface || {};
    var Sheet = xataface.Sheet;
    var $ = jQuery;
    
    /**
     * Converts all links with data-xf-sheet-position attributes so that
     * they open inside a sheet instead of navigating away.
     */
    function activateSheetLinks(root) {
        var links = root.querySelectorAll('a[data-xf-sheet-position]');
        links.forEach(function(link) {
            link.addEventListener('click', function(e) {
                var href = link.getAttribute('href');
                if (!href) {
                    return;
                }
                var sheet = new Sheet({
                    url : href,
                    position: link.getAttribute('data-xf-sheet-position')
                });
                sheet.show();
                e.preventDefault();
                return false;
                
            });
        });

    }
    
    
    registerXatafaceDecorator(activateSheetLinks);
   
    
})();