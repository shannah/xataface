//require <jquery.packed.js>
//require <jquery.noty.js>
//require <jquery-ui.min.js>
//require-css <jquery-ui/jquery-ui.css>
(function() {
    var $ = jQuery;
    
    window.xataface = window.xataface || {};
    window.xataface.post = post;
    
    /**
     * Posts to an AJAX action.  Displays a progress dialog while it waits.  Then optionally
     * displays a dialog on completion, depending on the action's "silent" property value returned
     * in JSON.
     * 
     * @param actionName : string The name of the server-side action to post to.
     * @param arg : mixed If a string, it is interpreted as a record ID.  If an object, it is interpreted
     *      as a query.  If an HTMLElement, it will look for the xf-record-id attribute for the record ID.
     *
     * @returns void
     */
    function post(actionName, arg) {
        return postAction(actionName, arg).then(displayActionResult).catch(displayActionResult);
    }
    
    function displayActionResult(data) {
        console.log("xataface#displayActionResult", data);
        if (!data || !data.code) {
            console.log("Action failed.  No data received.  Check the server log.");
            showError('Action failed.  No response from server', 4500);
            return data;
        }
        if ((data.code < 200 || data.code >= 300) && data.message) {
            if (data.silent !== false) {
                showError(data.message, 4500);
            } 
            return data;
        }
        if (!data.silent && data.message) {
            showInfo(data.message, 4500);
        }
        return data;
    }
    
    function postAction(actionName, arg) {

        if (isElement(arg)) {
            // Case 1. This is an element.  We'll look for the xf-record-id attribute
            // If it exists we'll call ourself with that ID.
            // If it doesn't exist, we'll navigate up the tree until we 
            // find an xf-record-id attribute.
            // If we get to the top and none is found, then we just return an empty promise that 
            // rejects.
            //console.log("addToPlaylist", recordId);
            var el = arg;
            
            if ($(el).attr('xf-record-id')) {
                if ($(el).hasClass('disabled')) {
                    return new Promise(function(resolve, reject) {
                        return reject({code: 299, message:'Button disabled.  Action may already be in progress'});
                    });
                }
                $(el).addClass('disabled');
                
                return postAction(actionName, $(el).attr('xf-record-id')).catch(function(error) {
                    $(el).removeClass('disabled');
                    return error;
                }).then(function(data) {
                    $(el).removeClass('disabled');
                    return data;
                });
            } else {
                var matchingParent = $(el).parents('[xf-record-id]').first();
                if (matchingParent.length == 0) {
                    return new Promise(function(resolve, reject) {
                        return reject({code: 500, message:'No elements found with xf-record-id attribute'});
                    });
                }
                return postAction(actionName, matchingParent.get(0));
            }
        }
        
        var query = {'-action' : actionName, '-response' : 'json'};
        if (typeof arg === 'string') {
            query['-recordid'] = arg;
        } else if (typeof arg === 'object'){
            for (var k in arg) query[k] = arg[k];
        }

        return new Promise(function(resolve, reject) {
            var progressDialog = showProgress('Action in progress...')
            $.post(DATAFACE_SITE_HREF, query)
            .done(function(data) {
                console.log("Completed HTTP request with ", data);
                progressDialog.close();
                if (!data || !data.code) {
                    return reject(
                        {
                            code:500,
                            message: 'Unexpected HTTP response data.', 
                            detail:data
                        }
                    );
                }
                if (data.code == 200 || data.code == 201 || data.code == 202) {
                    return resolve(data);
                }  else {
                    return reject(data);
                }
            })
            .fail(function(data) {
                reject({code:500, message:'Action Failed', detail: data});
            });
        });
    }
    
    
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
    
    function showProgress(msg) {
        msg = msg + '<progress/>';
        var n = noty({text:msg, type:'alert', maxVisible:1});
        n.show();
        return n;
    }
    
    
    
    
    function showMessage(msg, type, timeout) {
        var n = noty({text:msg, type:type, maxVisible:1});
        if (timeout > 0) {
            setTimeout(function(){n.close()}, timeout);
        }
        n.show();
    }
    
    function showInfo(msg, timeout) {
        showMessage(msg, 'alert', timeout);
    }
    
    function showError(msg, timeout) {
        showMessage(msg, 'error', timeout);
    }
    
})();