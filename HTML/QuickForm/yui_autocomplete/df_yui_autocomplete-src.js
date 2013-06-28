if (typeof Array.indexOf != 'function') {
    Array.prototype.indexOf = function(f, s) {
        if (typeof s == 'undefined') s = 0;
        for (var i = s; i < this.length; i++) {
            if (f === this[i]) return i;
        }
        return -1;
    }
}

function buildYUIAutocomplete(field){
	//alert("in onfocus handler");
	var ds;
	if ( field.getAttribute('df:datasource') ){
		var dsurl = field.getAttribute('df:datasource') || '';
		var dsquery = '';
		if ( dsurl.indexOf('?') >= 0 ){
			var parts = dsurl.split('?');
			dsurl = parts[0];
			dsquery = parts[1];
		}
		var schema = [(field.getAttribute('df:resultNode') || 'Result'), (field.getAttribute('df:queryKeyNode') || 'df:title')];
		var additionalNodes = (field.getAttribute('df:additionalNodes') || '').split(',');
		for ( var i=0; i<additionalNodes.length;i++){
			schema[schema.length] = additionalNodes[i];
		}
		ds = new YAHOO.widget.DS_XHR(dsurl, schema);
		ds.scriptQueryAppend = dsquery; 
		ds.responseType = YAHOO.widget.DS_XHR.TYPE_XML;
		ds.scriptQueryParam = field.getAttribute('df:scriptQueryParam') || '';
	}
	else if ( field.getAttribute('df:vocabulary') ){
		//alert("Using valuelist");
		var values = window.DATAFACE.VALUELISTS[field.getAttribute('df:vocabulary')];
		
		ds = new YAHOO.widget.DS_JSArray(values);
		ds.queryMatchContains = true;
	}
	
	var wrapper = document.createElement('div');
	wrapper.className = 'yui-autocomplete-wrapper';
	wrapper.style.width = '100%';
	wrapper.setAttribute('df:noClone', '1');
	
	var containerId = field.getAttribute('id')+'-autocomplete-container';
	
	var container = document.createElement('div');
	container.className = 'yui-autocomplete-container';
	container.style.width='300px';
	container.setAttribute('id', containerId);
	wrapper.appendChild(container);
	
	if ( field.nextSibling ){
		field.parentNode.insertBefore(wrapper,field.nextSibling);
	} else {
		field.parentNode.appendChild(wrapper);
	}
	
	var ac =  new YAHOO.widget.AutoComplete(field.getAttribute('id'),container.getAttribute('id'), ds);
	if ( field.getAttribute('data-xf-max-results-displayed') ){
	    ac.maxResultsDisplayed = parseInt(field.getAttribute('data-xf-max-results-displayed'));
	}
}


function updateYUIVocabulary(field){
	var values = window.DATAFACE.VALUELISTS[field.getAttribute('df:vocabulary')];
	if ( values.indexOf(field.value) == -1 ){
		values[values.length] = field.value;
		values.sort();
	}
}