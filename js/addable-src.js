/**
 * Javascript functions related to adding content to a page.
 *
 * @created June 23, 2007
 * @author Steve Hannah <shannah@sfu.ca>
 */
function df_getElementsWithAttribute(parent,tagname,attname){
	var els = parent.getElementsByTagName(tagname);
	var out = [];
	for (var i=0, max=els.length; i<max; i++){
		if ( els[i].getAttribute(attname) ) out.push(els[i]);
	}
	return out;
	
}

var df_addNew_target_element;
var df_addNew_form_element;

/**
 * Adds a new record (or related record) to the target element.
 * The second argument stores the element where the form should be
 * placed.
 */
function df_addNew(target,formel){
	if ( typeof target == 'string' ){
		target = document.getElementById(target);
	}
	if ( typeof formel == 'string' ){
		
		formel = document.getElementById(formel);
	}
	
	if ( !target ){
		throw new Exception("Cannot add new item when no target is specified");
	}
	
	if ( !formel ){
		// The form element was not provided or could not be found, so we will
		// add it immediately after the target element.
		alert(target.tagName);
		if ( target.tagName.toLowerCase() == 'tr' ){
			alert("we have a tr tag");
			// We can't just place another child in a table row, so we will
			// create another row just after this row.
			var numcols=0;
			for ( var i=0, max=target.childNodes.length; i<max; i++){
				if ( target.childNodes[i].tagName.toLowerCase() == 'td' || target.childNodes[i].tagName.toLowerCase() == 'th' ){
					var colspan = target.childNodes[i].getAttribute('colspan');
					if ( colspan ){
						numcols += parseInt(colspan);
					} else {
						numcols++;
					}
				}
			}
			
			var formtr = document.createElement('tr');
			
			var random = Math.round(Math.random()*100);
			formtr.setAttribute('id', 'form-'+random);
			
			var formtd = document.createElement('td');
			if ( numcols > 6 ){
				formtd.setAttribute('colspan', 6);
				var formtd2 = document.createElement('td');
				formtd2.setAttribute('colspan', numcols-6);
				formtr.appendChild(formtd);
				formtr.appendChild(formtd2);
			} else {
				formtd.setAttribute('colspan', numcols-6);
				formtr.appendChild(formtd);
			}
			
			
			if ( target.nextSibling ){
				target.parentNode.insertBefore(formtr, target.nextSibling);
			} else {
				target.parentNode.appendChild(formtr);
			}
			
			formel = formtr;	// So we store the row as the form element.
			
		
		} else {
			//We can just add an element after the target
			formel = document.createElement('div');
			var random = Math.round(Math.random()*100);
			formel.setAttribute('id', 'form-'+random);
			
			if ( target.nextSibling ){
				target.parentNode.insertBefore(formel, target.nextSibling);
			} else {
				target.parentNode.appendChild(formel);
			}
		}
	
	}
	
	var editableFields = df_getElementsWithAttribute(target, 'span', 'df:field');
	var fieldnames = [];
	for (var i=0, max=editableFields.length; i<max; i++){
		fieldnames.push(editableFields[i].getAttribute('df:field'));
	}
	fieldnames = fieldnames.join(',');
	var relationship = target.getAttribute('df:relationship');
	var formUrl = null;
	if ( relationship ){
		// we are adding a related record.
		var id = target.getAttribute('df:id');
		formUrl = '?-action=ajax_form&-record='+escape(id)+'&-relationship='+escape(relationship)+'&-form-type=new_related_record&-fields='+escape(fieldnames);
	}
	else {
		var table = target.getAttribute('df:table');
			// The name of the table that is being added.
		var targetid = target.getAttribute('id');
		formUrl = '?-action=ajax_form&-form-type=new&-fields='+escape(fieldnames)+'&-table='+escape(table)+'&-target-id='+escape(targetid);
	}
	
	target.http = getHTTPObject();
	target.http.onreadystatechange = df_addNew_handle;
	
	target.http.open('GET',formUrl);
	target.http.send(null);
	
	target.handleResponse = df_addNew_handleResponse;
	df_addNew_target_element = target;
	df_addNew_form_element = formel;
	target.edit_form = formel;
	
}

function df_addNew_handle(){
	var target = df_addNew_target_element;
	var formel = df_addNew_form_element;
	if ( !target ) return;
	var http = target.http;
	if ( http.readyState == 4 ){
		if (formel.tagName.toLowerCase() == 'tr'){
			formel.firstChild.innerHTML = http.responseText;
		} else {
			formel.innerHTML = http.responseText;
		}
		
	}
}

function df_addNew_handleResponse(targetid, values){
	var targetel = document.getElementById(targetid);
	if ( !targetel ){
		throw new Exception("Could not find target element "+targetid);
	}
	
	var newrow = targetel.cloneNode(true);
	var fieldEls = df_getElementsWithAttribute(newrow, 'span', 'df:field');
	for ( var i=0, max=fieldEls.length; i<max; i++ ){
		var fieldname = fieldEls[i].getAttribute('df:field');
		if ( values[fieldname] ){
			fieldEls[i].innerHTML = values[fieldname];
		}
	}
	
	targetel.parentNode.insertBefore(newrow, targetel);
	if ( df_add_editable_awareness ){
		alert("We have awareness");
		df_add_editable_awareness(newrow);
	}
}