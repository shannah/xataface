//require <xatajax.form.core.js>
//require <jsonPath.js>
(function() {
    var $ = jQuery;

    var jsonPath = XataJax.jsonPath;

    /**
     * Finds a field by name relative to a starting point.  It will search only within
     * the startNode's form group (i.e. class xf-form-group).
     *
     * @param {HTMLElement} startNode The starting point of our search (we search for siblings).
     * @param {String} fieldName The name of the field we are searching for.
     *
     * @return {HTMLElement} The found field or null if it cannot find it.
     */
    function findField(startNode, fieldName) {
        return XataJax.form.findField(startNode, fieldName);
    }

    function extractVars(str) {
        var out = [];
        var len = str.length;
        for (var i=0; i<len; i++) {
            var c = str.charAt(i);
            if (c === '{') {
                var varName = '';
                for (var j=i+1; j<len; j++) {
                    var d = str.charAt(j);
                    if (d === '}') {
                        break;
                    }
                    varName += d;
                }
                i = j;
                out.push(varName);
            }
        }
        return out;
    }

    function replaceVars(sourceField, str) {
        var vars = extractVars(str);
        var len = vars.length;
        for (var i=0; i<len; i++) {
            var fld = findField(sourceField, vars[i]);
            str = str.replace('{'+vars[i]+'}', encodeURIComponent($(fld).val()));
        }
        return str;
    }

    function update(field) {
        var urlTemplate = $(field).attr('data-xf-update-url');
        var contentType = 'json';
        function val() {
            var tagName = $(field).prop("tagName").toLowerCase();
            if (tagName == 'input' || tagName == 'select' || tagName == 'textarea') {
                return $(field).val();
            } else if (contentType == 'html'){
                return $(field).html().trim();
            } else {
                return $(field).text().trim();
            }
        }
        
        function setVal(newval) {
            var tagName = $(field).prop("tagName").toLowerCase();
            if (tagName == 'input' || tagName == 'select' || tagName == 'textarea') {
                return $(field).val(newval);
            } else if (contentType == 'html'){
                return $(field).html(newval);
            } else {
                return $(field).text(newval);
            }
        }
        if (urlTemplate.indexOf('#') < 0) {
            urlTemplate += '#';
            contentType = 'html';
            var tagName = $(field).prop("tagName").toLowerCase();
            if (tagName == 'input' || tagName == 'select' || tagName == 'textarea') {
                contentType = 'text';
            }
            if ($(field).attr('data-xf-update-content-type')) {
                contentType = $(field).attr('data-xf-update-content-type');
            }
        }

        var updateCondition = $(field).attr('data-xf-update-condition');
        if (updateCondition) {
            if (updateCondition == 'empty' && val()) {
                // It is only set to update with the field is currently empty
                return;
            }
            
        }

        var query = urlTemplate.substr(urlTemplate.indexOf('#')+1);
        urlTemplate = urlTemplate.substr(0, urlTemplate.indexOf('#'));

        if (urlTemplate) {
            var url = replaceVars(field, urlTemplate);
            $.get(url, function(res) {
                if (contentType == 'json') {
                    //console.log(res);
                    var results = jsonPath(res, query);
                    //console.log(results);

                    if (results && results.length > 0) {
                        var oldVal = val();
                        if (oldVal != results[0]) {
                            setVal(results[0]);
                            $(field).trigger('change');
                        }
                    }
                } else {
                    var oldVal = val();
                    if (oldVal != res) {
                        setVal(res);
                        $(field).trigger('change');
                    }
                }
                
            });
        } else {
            var newVal = replaceVars(field, query);
            var oldVal = val();
            if (oldVal != newVal) {
                setVal(newVal);
                $(field).trigger('change');
            }
        }
    }

    registerXatafaceDecorator(function (node) {
        $('[data-xf-update-url]').each(function() {
            //console.log('found');
            var depField = this;
            var varNames = extractVars($(depField).attr('data-xf-update-url'));
            var event = $(depField).attr('data-xf-update-event') || 'change';
            var len = varNames.length;
            for (var i=0; i<len; i++) {
                var varName = varNames[i];
                var fld = findField(this, varName);
                if (fld) {
                    $(fld).on(event, function() {
                        update(depField);
                    });
                }
            }
            update(depField);
            

        });
    });
})();
