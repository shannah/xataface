//require <jquery.packed.js>
//require <jquery.noty.js>
//require <jquery-ui.min.js>
//require-css <jquery-ui/jquery-ui.css>
//require <xataface/actions/core.js>
(function() {
    var $ = jQuery;
    
    window.xataface = window.xataface || {};
    var isElement = xataface.isElement;
    var isNode = xataface.isNode;
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
    function post(actionName, arg, removeClass, addClass) {
        return postAction(actionName, arg, removeClass, addClass).then(displayActionResult).catch(displayActionResult);
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
    
    
    
    function postAction(actionName, arg, removeClass, addClass) {
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
                var classRemoved = false;
                if (removeClass) {
                    if ($(el).hasClass(removeClass)) {
                        removeClassGlobal(el, removeClass);
                        classRemoved = true;
                    }
                    
                }
                var classAdded = false;
                if (addClass) {
                    if (!$(el).hasClass(addClass)) {
                        addClassGlobal(el, addClass);
                        classAdded = true;
                    }
                    
                }
                
                return postAction(actionName, $(el).attr('xf-record-id')).catch(function(error) {
                    $(el).removeClass('disabled');
                    if (addClass && classAdded) {
                        removeClassGlobal(el, addClass);
                    }
                    if (removeClass && classRemoved) {
                        addClassGlobal(el, removeClass);
                    }
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
                return postAction(actionName, matchingParent.get(0), removeClass, addClass);
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
	
	function removeClassGlobal(el, className) {
		$(el).removeClass(className);
		var recId = $(el).attr('xf-record-id');
		if (recId) {
			$('[xf-record-id="'+recId+'"]').removeClass(className);
		}
	}
	
	function addClassGlobal(el, className) {
		$(el).addClass(className);
		var recId = $(el).attr('xf-record-id');
		if (recId) {
			$('[xf-record-id="'+recId+'"]').addClass(className);
		}
	}
    
})();