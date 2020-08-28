//require <xataface/components/Sheet.js>
(function() {
    window.xataface = window.xataface || {};
    xataface.isNode = isNode;
    xataface.isElement = isElement;
    
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
    
    
    //Returns true if it is a DOM node
    function isNode(o){
      return (
        typeof Node === "object" ? o instanceof Node : 
        o && typeof o === "object" && typeof o.nodeType === "number" && typeof o.nodeName==="string"
      );
    }

    //Returns true if it is a DOM element    
    function isElement(o){
      return (
        typeof HTMLElement === "object" ? o instanceof HTMLElement : //DOM2
        o && typeof o === "object" && o !== null && o.nodeType === 1 && typeof o.nodeName==="string"
    );
    }
   
    
})();