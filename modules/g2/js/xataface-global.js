

// Heads up! August 2003  - Geir B¾kholt
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
