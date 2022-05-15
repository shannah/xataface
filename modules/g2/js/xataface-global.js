

// Heads up! August 2003  - Geir B�kholt
// This file now requires the javascript variable portal_url to be set 
// in the plone_javascript_variables.js file. Any other variables from Plone
// that you want to pass into these scripts should be placed there.

/* <dtml-var "enableHTTPCompression(request=REQUEST, debug=1, js=1)"> (this is for http compression) */

function registerPloneFunction(func){
    // registers a function to fire onload. 
	// Turned out we kept doing this all the time
	// Use this for initilaizing any javascript that should fire once the page has been loaded. 
	// 
    if (window.addEventListener) window.addEventListener("load",func,false);
    else if (window.attachEvent) window.attachEvent("onload",func);   
  }

function unRegisterPloneFunction(func){
    // uregisters a previous function to fire onload. 
    if (window.removeEventListener) window.removeEventListener("load",func,false);
    else if (window.detachEvent) window.detachEvent("onload",func);   
  }



var registerXatafaceDecorator = null;
var decorateXatafaceNode = null;
(function(){
	
	var decorators = [];
	registerXatafaceDecorator = function(decorator){
		decorators.push(decorator);
	};
	
	decorateXatafaceNode = function(node){
		
		var replaceCallbacks = [];
		removeNoDecorateSections(node, replaceCallbacks);
		
		for ( var i=0; i<decorators.length; i++){
			decorators[i](node);
		}
		for ( var i=0; i<replaceCallbacks.length; i++){
			replaceCallbacks[i]();
		}
	}

	
	function removeNoDecorateSections(node, callbacks){
		
		if ( typeof(jQuery) != 'undefined' ){
			jQuery('.xf-disable-decorate', node).each(function(){
				var replace = document.createTextNode('');
				var parent = jQuery(this).parent();
				jQuery(this).replaceWith(replace);
				
				var self = this;
				callbacks.push(function(){
					jQuery(replace).replaceWith(self);
				});
			});
		}
	
	}
	
	
})();
registerPloneFunction(function(){decorateXatafaceNode(document.documentElement)});
  





function createCookie(name,value,days) {
  if (days) {
    var date = new Date();
    date.setTime(date.getTime()+(days*24*60*60*1000));
    var expires = "; expires="+date.toGMTString();
  }
  else expires = "";
  document.cookie = name+"="+escape(value)+expires+"; path=/;";
}

function readCookie(name) {
  var nameEQ = name + "=";
  var ca = document.cookie.split(';');
  for(var i=0;i < ca.length;i++) {
    var c = ca[i];
    while (c.charAt(0)==' ') c = c.substring(1,c.length);
    if (c.indexOf(nameEQ) == 0) return unescape(c.substring(nameEQ.length,c.length));
  }
  return null;
}



function invalidateTranslations(url){

	var res = confirm('Are you sure you want to invalidate the translations for this record?  This will mark the record for re-translation.');
	if ( !res ) return;
	var div = document.getElementsByTagName('body')[0].appendChild(document.createElement('div'));
	var html = '<form id="invalidate_translation_form" method="POST" action="'+url+'">';
	html += '<input type="hidden" name="--confirm_invalidate" value="1">';
	div.innerHTML = html;
	var form = document.getElementById('invalidate_translation_form');
	form.submit();
}


function require(path){
	if ( !window._javascripts_loaded ) window._javascripts_loaded = {};
	if ( window._javascripts_loaded[path] ) return true;
	else window._javascripts_loaded[path] = true;
	var e = document.createElement("script");
	e.src = path;
	e.type="text/javascript";
	document.getElementsByTagName("head")[0].appendChild(e);

}

function loadScripts(e){
	var scriptTags = e.getElementsByTagName('script');
	for ( var i=0; i< scriptTags.length; i++){
		if ( scriptTags[i].getAttribute('src') ) require(scriptTags[i].getAttribute('src'));
	}
}

function registerRecord(id, vals){
	if ( !document.recordIndex ) document.recordIndex = {};
	document.recordIndex[id] = vals;
}

function getRecord(id){
	if (!document.recordIndex ) document.recordIndex = {};
	return document.recordIndex[id];
}

function addToValuelist(table, valuelist, element){
	var value = prompt('Enter the value you wish to add to this value list.  Use the notation key=value if you need to add both a key and a value for the option.');
	if ( !value ) return;
	if ( value.indexOf('=') >= 0 ){
		var vals = value.split('=');
		var key = vals[0];
		value = vals[1];
	} else {
		key = null;
	}

	var http = getHTTPObject();
	http.open('POST', window.location, true);

	//request.onreadystatechange = this.handleUpdateResponse;
	//Send the proper header information along with the request
	//alert("here");
	var params = "-action=ajax_valuelist_append&-table="+escape(table)+"&-valuelist="+escape(valuelist)+"&-value="+escape(value)+"&-key="+escape(key);
	
    //alert(params);
	http.setRequestHeader("Content-type", "application/x-www-form-urlencoded");

	http.setRequestHeader("Content-length", params.length);
	http.setRequestHeader("Connection", "close");
	http.element = element;
	http.onreadystatechange = function() {//Call a function when the state changes.
		if(http.readyState == 4 /*&& http.status == 200*/) {

			    //alert(http.responseText);
				eval('var retval = '+http.responseText+';');
				if ( retval['success'] ){
					element.options[element.options.length] = element.options[element.options.length-1];
					element.options[element.options.length-2] = new Option(retval['value']['value'], retval['value']['key']);
					element.selectedIndex = element.options.length-2;
				} else {
					alert(retval['msg']);
					element.selectedIndex = 0;
				}
			
		}
	}
	http.send(params);
}

function makeSelectEditable(table, valuelist, select){
	if (select.onchange){
		select.onchange_old = select.onchange;
	}
	select.onchange = function(){
		if ( this.options[this.selectedIndex].value == '-1' ){
			addToValuelist(table, valuelist, this);
		}
		if ( this.onchange_old )
			return this.onchange_old();

	};
	select.options[select.options.length] = new Option('Edit values...', '-1');
	
}


var testPushPop = new Array();
if (testPushPop.push){
}else{
        Array.prototype.push = hackPush
        Array.prototype.pop = hackPop
        Array.prototype.shift =hackShift;
}

function registerOnloadHandler(func){
	if ( !document._onload ) document._onload = [];
	document._onload[document._onload.length] = func;
}

function bodyOnload(){
	if ( document._onload ){
		for (var i=0; i<document._onload.length; i++){
			document._onload[i]();
		}
	}
}

function getElementsByClassName(oElm, strTagName, strClassName){
    var arrElements = (strTagName == "*" && oElm.all)? oElm.all : oElm.getElementsByTagName(strTagName);
    var arrReturnElements = new Array();
    strClassName = strClassName.replace(/\-/g, "\\-");
    var oRegExp = new RegExp("(^|\\s)" + strClassName + "(\\s|$)");
    var oElement;
    for(var i=0; i<arrElements.length; i++){
        oElement = arrElements[i];      
        if(oRegExp.test(oElement.className)){
            arrReturnElements.push(oElement);
        }   
    }
    return (arrReturnElements)
}


function toggleSelectedRows(checkbox,tableid){
	var table = document.getElementById(tableid);
	var checkboxes = getElementsByClassName(table, 'input', 'rowSelectorCheckbox');
	for (var i=0; i<checkboxes.length; i++){
		checkboxes[i].checked = checkbox.checked;
	}
}

function getSelectedIds(tableid){
	var table = document.getElementById(tableid);
	var checkboxes = getElementsByClassName(table, 'input', 'rowSelectorCheckbox');
	var ids = [];
	for (var i=0; i<checkboxes.length; i++){
		if ( checkboxes[i].checked ){
			var id = checkboxes[i].getAttribute('id');
			id = id.substring(id.indexOf(':')+1);
			ids.push(id);
		}
	}
	return ids;
}

function actOnSelected(tableid, action, beforeHook, vals){
	var ids = getSelectedIds(tableid);
	if ( ids.length == 0 ){
		alert("First you must select the rows you wish to modify.");
		return false;
	}
	
	if ( typeof(beforeHook) != 'undefined' ){
		if ( !beforeHook() ) return false;
	}
	
	var form = document.getElementById("result_list_selected_items_form");
	form.elements['--selected-ids'].value = ids.join("\n");
	form.elements['-action'].value = action;
	form.submit();
	return false;

}

function copySelected(tableid){
	var ids = getSelectedIds(tableid);
	if ( ids.length == 0 ){
		alert("Please first check boxes beside the records you wish to copy, and then press 'Copy'.");
		return;
	}
	var form = document.getElementById("result_list_selected_items_form");
	form.elements['--selected-ids'].value = ids.join("\n");
	form.elements['-action'].value = 'copy_replace';
	var fld = document.createElement('input');
	fld.name = '--copy';
	fld.type = 'hidden';
	fld.value = '1';
	form.appendChild(fld);
	form.submit();
}

function updateSelected(tableid){
	var ids = getSelectedIds(tableid);
	if ( ids.length == 0 ){
		alert("Please first check boxes beside the records you wish to copy, and then press 'Copy'.");
		return;
	}
	var form = document.getElementById("result_list_selected_items_form");
	form.elements['--selected-ids'].value = ids.join("\n");
	form.elements['-action'].value = 'copy_replace';

	form.submit();
}


function removeSelectedRelated(tableid){
	var ids = getSelectedIds(tableid);
	if ( ids.length == 0 ){
		alert("Please first check boxes beside the records you wish to copy, and then press 'Copy'.");
		return;
	}
	var form = document.getElementById("result_list_selected_items_form");
	form.elements['--selected-ids'].value = ids.join("\n");
	form.elements['-action'].value = 'remove_related_record';
	form.submit();
}

(function() {
    
    // Mobile stuff now
    var mobileActivated = false;
    function xfHandleResize() {
        var body = document.querySelector('body');
        if (!body) return;
        if (window.innerWidth < 768) {
            if (body.classList.contains('large') || !body.classList.contains('small')) {
                body.classList.add('small');
                body.classList.remove('large');
                window.dispatchEvent(new Event('xf-mobileenter'));
            }
            
            mobileActivated = true;
        } else {
            if (body.classList.contains('small') || !body.classList.contains('large')) {
                body.classList.remove('small');
                body.classList.add('large');
                window.dispatchEvent(new Event('xf-mobileexit'));
            }
            
        }
    }
    xfHandleResize();
    window.addEventListener('DOMContentLoaded', xfHandleResize);
    window.addEventListener('resize', xfHandleResize);

    var paddingTopExplicit = false;
    var paddingBottomExplicit = false;
    var viewport = {
        top : 0,
        left : 0,
        right : 0,
        bottom : 0,
        width : window.innerWidth,
        height : window.innerHeight
         
    };
    var firedInitialViewportChangedEvent = false;
    function updateBodyPadding() {
        var localFiredInitialViewportChangedEvent = firedInitialViewportChangedEvent;
        firedInitialViewportChangedEvent = true;
        if (!mobileActivated) {
            if (viewport.width != window.innerWidth || viewport.height != window.innerHeight) {
                viewport.width = window.innerWidth;
                viewport.height = window.innerHeight;
                var event = new Event('xf-viewport-changed');
                window.dispatchEvent(event);
            } else {
                if (!localFiredInitialViewportChangedEvent) {
                    var event = new Event('xf-viewport-changed');
                    window.dispatchEvent(event);
                }
            }
            
            return;
        }
        var body = document.querySelector('body');
        var changed = false;
        if (!body.classList.contains('small')) {
            changed = paddingTopExplicit || paddingBottomExplicit;
            body.style.paddingBottom = null;
            body.style.paddingTop = null;
            paddingTopExplicit = paddingBottomExplicit = false;
            viewport.top = viewport.bottom = viewport.left = viewport.right = 0;
            viewport.width = window.innerWidth;
            viewport.height = window.innerHeight;
        } else {

            var footer = document.querySelector('.mobile-footer');
            var buttons = document.querySelector('.button-section');
            if (footer || buttons) {
                var offsetHeight = 0;
                if (footer) offsetHeight = footer.offsetHeight;
                if (buttons) offsetHeight = Math.max(offsetHeight, buttons.offsetHeight);
                if (body.style.paddingBottom != offsetHeight+'px') {
                    changed = true;
                }
                body.style.paddingBottom = offsetHeight + 'px';
                viewport.bottom = offsetHeight;
                viewport.height = window.innerHeight - viewport.bottom - viewport.top;
                viewport.width = window.innerWidth;
                explicitBottomPadding = true;
            }
            
            var header = document.querySelector('.mobile-header');
            if (header) {
                if (body.style.paddingTop != header.offsetHeight+'px') {
                    changed = true;
                }
                body.style.paddingTop = header.offsetHeight + 'px';
                viewport.top = header.offsetHeight;
                viewport.height = window.innerHeight - viewport.top - viewport.bottom;
                viewport.width = window.innerWidth;
                explicitBottomPadding = true;
            }
            
        }
        
        if (changed) {
            var event = new Event('xf-viewport-changed');
            window.dispatchEvent(event);
        }
        
    }
    
    function getViewport() {
        return Object.assign({}, viewport);
    }
    window.xataface = window.xataface || {};
    Object.defineProperty(window.xataface, 'viewport', {
        get: getViewport,
        configurable: false,
        enumerable: false
    });
    window.addEventListener('DOMContentLoaded', updateBodyPadding);
    window.addEventListener('load', updateBodyPadding);
    setInterval(updateBodyPadding, 1000);
    
})();

(function() {
    // browser detection
    function iOS() {
            
      return [
        'iPad Simulator',
        'iPhone Simulator',
        'iPod Simulator',
        'iPad',
        'iPhone',
        'iPod'
      ].includes(navigator.platform)
      // iPad on iOS 13 detection
      || (navigator.userAgent.includes("Mac") && "ontouchend" in document)
    }
    
    function addUserAgentCSSClass() {
        if (iOS()) {
            document.body.classList.add('iphone');
        }
    }
    window.addEventListener('DOMContentLoaded', addUserAgentCSSClass);
    
})();

(function() {
    var isScrolled = false;
    function onReady() {
        var body = document.querySelector('body');
        var runOnScroll = function(evt) {
            //console.log("scrolling ", body.scrollTop);
            
            if (body.scrollTop < 100) {
                if (isScrolled) {
                    body.classList.remove('xf-viewport-scrolled');
                    isScrolled = false;
                }
                
            } else {
                if (!isScrolled) {
                    body.classList.add('xf-viewport-scrolled');
                    isScrolled = true;
                }
                
            }
            
        };
    
        body.addEventListener('scroll', runOnScroll, {passive:true});
    }
    window.addEventListener('DOMContentLoaded', onReady);
    
})();
(function() {
    
    if (!window.localStorage) {
        return;
    }
    var history = window.localStorage.getItem('xf-history');
    if (!history) {
        history = {startPos : 0, endPos: 0, urls : {}};
        localStorage.setItem('xf-history', JSON.stringify(history));
    } else {
        history = JSON.parse(history);
    }
    history.urls[history.endPos++] = window.location.href;
    var currPos = history.endPos-1;
    while (history.endPos - history.startPos > 100) {
        delete history.urls[history.startPos++];
    }
    localStorage.setItem('xf-history', JSON.stringify(history));
    
    
    
    
    
    function addBackButton() {
        
        if (!window.jQuery) {
        
            
            return;
        }
        var referrerIndex = getReferrer();
        
        
        var $ = jQuery;
        var backButton = $('<button class="back-btn"><i class="material-icons">navigate_before</i> <span>Back</span></button>');
        var backUrl = history.urls[referrerIndex];
        if (!backUrl) {
            return;
        }
        backButton.click(function() {
            window.location.href  = backUrl;
        });
        backButton.insertBefore($('.site_logo'));
        $('body').addClass('has-back-button');
    }
    
    function getReferrer() {
        var search = window.location.search;
        
        var referrerPos = search.indexOf('&--referrer=');
        
        if (referrerPos < 0) {
            return -1;
        }
        referrerPos = search.indexOf('=', referrerPos)+1;
        var referrerEndPos = search.indexOf('&', referrerPos);
        var referrerIndex = -1;
        if (referrerEndPos < 0) {
            referrerIndex = search.substring(referrerPos);
            
        } else {
            referrerIndex = search.substring(referrerPos, referrerEndPos);
            
        }
        referrerIndex = parseInt(referrerIndex);
        return referrerIndex;
    }
    
    function addReferrer(link, index) {
        
        if (!link) {
            return link;
        }
        if (link.indexOf('javascript:') === 0) {
            return link;
        }
        if (link.indexOf('--referrer') > 0) {
            return link;
        }
        if (link.indexOf('?') < 0) {
            link += '?';
        }
        if (index) {
            return link + '&--referrer=' + index;
        }
        return link + '&--referrer=' + currPos;
    }
    
    var thisReferrer = getReferrer();
    registerXatafaceDecorator(function(root) {
        
        if (!window.jQuery) {
            console.log('jquery not loaded yet');
            return;
        }

        var $ = jQuery;

        $('[rel][href]').each(function() {
            
            var rel = $(this).attr('rel');
            if (rel == 'child') {
                $(this).attr('href', addReferrer(this.getAttribute('href')));
            } else if (rel == 'sibling') {
                if (thisReferrer >= 0) {
                    $(this).attr('href', addReferrer(this.getAttribute('href'), thisReferrer));
                }
                
            }
            
        });
        
    });
    
    window.addEventListener('DOMContentLoaded', addBackButton);

})();
(function() {
    var $ = jQuery;
    var xataface = window.xataface || {};
    window.xataface = xataface;
    xataface.showInfiniteProgress = showInfiniteProgress;
    xataface.hideInfiniteProgress = hideInfiniteProgress;
    
    var globalInfiniteProgress;
    function showInfiniteProgress(el) {
        var spinner = el ? $('<div class="spin"></div>') : $('<div class="spin fillscreen"></div>');
        if (el) {
            $(el).append(spinner);
        } else {
            if (globalInfiniteProgress && jQuery.contains(document, globalInfiniteProgress)) {
                return globalInfiniteProgress;
            }
            $('body').append(spinner);
            globalInfiniteProgress = spinner.get(0);
            
        }
        return spinner.get(0);
    }
    
    function hideInfiniteProgress(el) {
        if (el) {
            $(el).remove();
        } else {
            if (globalInfiniteProgress) {
                $(globalInfiniteProgress).remove();
                globalInfiniteProgress = null;
            }
        }
        
    }
})();

(function() {
    // Define a goBackToParentContext() method which is used in the new record form
    // when the -add-related-context is supplied (meaning that it is actually adding a record to a relationship).
    var $ = jQuery;
    var xataface = window.xataface || {};
    window.xataface = xataface;
    

    xataface.goBackToParentContext = goBackToParentContext;
    function decodeHtml(html) {
        var txt = document.createElement("textarea");
        txt.innerHTML = html;
        return txt.value;
    }
    function goBackToParentContext() {
        var params = new URLSearchParams(document.location.search.substring(1));
        var contextString = params.get('-add-related-context');
        if (!contextString) {
            // Context string might be embedded in hidden fields on forms
            contextString = $('input[name="-add-related-context"]').val();
            if (contextString) {
                contextString = decodeHtml(contextString);
            }
        }
        if (!contextString) {
            return;
        }
        
        var context = JSON.parse(contextString);
        var id = context.id;
        var tableName = id.substring(0, id.indexOf('?'));
        window.location.search = '?-table=' + encodeURIComponent(tableName)+ '&-action=related_records_list&-relationship=' + encodeURIComponent(context.relationship) + '&-recordid=' + encodeURIComponent(id);
        
        
    }
})();
