/*
    Written by Jonathan Snook, http://www.snook.ca/jonathan
    Add-ons by Robert Nyman, http://www.robertnyman.com
*/


require(DATAFACE_URL+'/js/ajax.js');
function initDocument(){
	df_add_editable_awareness(document);
}

function df_add_editable_awareness(el){
	var els = getElementsByClassName(el, '*', 'df__editable_wrapper');
	
	for (var i=0, max=els.length; i<max; i++){
		
		els[i].onclick = makeEditable;
		els[i].onmouseover = showEditIcon;
		els[i].onmouseout = hideEditIcon;
	
	}
	
	

}


var editIconEl;
var count=0;
function refreshEditIcon(oEvent){
	if ( !oEvent ) oEvent = window.event;

	if ( !editIconEl ){
		editIconEl = document.createElement('img');


		editIconEl.setAttribute('src', DATAFACE_URL+'/images/edit.gif');
		editIconEl.setAttribute('alt', 'Edit this content');
		editIconEl.style.position = 'absolute';
		editIconEl.style['z-index'] = 99;
		document.body.appendChild(editIconEl);
	}
	editIconEl.style.top = (oEvent.clientY)+'px';
	editIconEl.style.left = (oEvent.clientX+12)+'px';
}


function showEditIcon(){
	if ( !this.dataface__icon_visible ){
		this.dataface__icon_visible = true;
		
		this.onmousemove = refreshEditIcon;
		this.style.cursor = 'pointer';
		
	}
	
	
}

function hideEditIcon(){
	if ( this.dataface__icon_visible ){
		this.dataface__icon_visible = false;
		//var editIcons = getElementsByClassName(this, 'img', 'dataface__edit_icon');
		//if ( !editIcons || editIcons.length == 0 ) return;
		//this.removeChild(editIcons[0]);
		this.style.cursor = '';
		if ( editIconEl ){
			document.body.removeChild(editIconEl);
			editIconEl = null;
		}
	}
}

var dataface_editable_src = null;
var dataface_editable_target = null;

function editElement(src,target){

	// First let's find all of the specific elements that will
	// be edited here.
	var editableElements = getElementsByClassName(src, 'span', 'df__editable');
	var fields = [];
	for ( var i=0; i<editableElements.length; i++){
		var dfid = editableElements[i].getAttribute('df:id');
		if ( !dfid ) continue;
		fields.push(dfid);
	}
		alert('here');
	if ( fields.length == 0 ){
		// There were no editable fields in this block, so we'll try to edit 
		// this one
		var dfid = src.getAttribute('df:id');
		if ( dfid ) fields = dfid;
		else return;
	} else {
		fields = fields.join(',');
	}
	
	var targetid = src.getAttribute('id');
		// this may be confusing but the src is the element that is being edited
		// the ajax form treats this as the target. Hence why targetid is actually
		// the id of src.
		
	src.http = getHTTPObject();
	
	src.http.onreadystatechange = editElement_handle;
	
	src.http.open('GET','?-action=ajax_form&-form-type=composite&-target-id='+encodeURIComponent(targetid)+'&-fields='+encodeURIComponent(fields));
	
	src.http.send(null);
	
	src.handleResponse = handleEditableResponse;
	
	dataface_editable_src = src;
	dataface_editable_target = target;
	
}

function editElement_handle(){
	var src = dataface_editable_src;
	if ( !src ) return;
	var target = dataface_editable_target;
	var http = src.http;
	if ( http.readyState == 4 ){
		target.innerHTML = http.responseText;
		var scripts = target.getElementsByTagName('script');
		var len = scripts.length;
		for (var i=0; i<len; i++){
			var script = scripts[i].innerHTML;
			script = script.replace(/^<!--/, '');
			script = script.replace(/-->$/, '');
			eval(script);
		}
		var forms = target.getElementsByTagName('form');
		var form = forms[0];
		var elements = form.elements;
		for ( var i=0; i<form.elements.length; i++){
			if ( elements[i].type == 'text' || elements[i].type == 'textarea' ){
				elements[i].focus();
				break;
			}
		}
		
		
		
	}
}
/*
 * Let's sort this out.
 * for table cells, we want the cell to be editable... however only a portion
 * inside of the link is to be edited.
 */

function makeEditable(){
	this.old_onclick = this.onclick;
	this.old_onmouseover = this.onmouseover;
	this.onclick=null;
	this.onmouseover=null;
	if ( editIconEl ){
		// If we had an edit icon displayed beside the cursor, we remove it
		// now.
		document.body.removeChild(editIconEl);
		editIconEl=null;
	}
	
	// Create a div to store the form to edit this element.
	var formdiv = document.createElement('div');
	formdiv.innerHTML = '<span><img src="'+DATAFACE_URL+'/images/progress.gif"/>Please wait ...</span>';
	if ( !this.getAttribute('df:id') ){
		this.appendChild(formdiv);
		formdiv.setAttribute('onclick', 'cancelBubble(event)');
		var subels = getElementsByClassName(this, 'span','df__editable');
		for ( var i=0; i<subels.length; i++){
			subels[i].style.display = 'none';
		}
		//this.old_onclick = this.onclick;
		//this.onclick = null;
	} else if ( this.nextSibling ){
		this.parentNode.insertBefore(formdiv,this.nextSibling);
	} else {
		this.parentNode.appendChild(formdiv);
	}
	//this.df__editform = formdiv;
	if ( this.getAttribute('df:id') ){
		this.style.display = 'none';
	} else {
		
	}
	this.edit_form = formdiv;
	editElement(this, formdiv);
	
}

function cancelBubble(event){
	var e = event || window.event;
	e.cancelBubble = true;
	if ( e.stopPropagation ) e.stopPropagation();
}

function handleEditableResponse(targetid, changed_values){
	var targetel = document.getElementById(targetid);
	if ( targetel.style.display == 'none' ) targetel.style.display = '';
	if ( !targetel ){
		alert("Could not find target element");
	} else {
		
		// we found the target element
		var els = getElementsByClassName(targetel, 'span', 'df__editable');
		if ( els.length == 0 ){
			els.push(targetel);
			
		}
		// els is an array of all of the spans that are editable - and thus
		// may need to be changed
		for ( var i=0; i<els.length; i++){
			var el = els[i];
			if ( el.style.display == 'none' ) el.style.display = '';
			var dfid = el.getAttribute('df:id');
			
			if ( !dfid ) continue;	// no dataface ID was specified for this element
			
			if ( changed_values[dfid] ){
			
				// Now we just change the HMTL value of this bad boy
				el.innerHTML = changed_values[dfid];
				var innerSpan = el.getElementsByTagName('span');
				if ( innerSpan.length > 0 ){
					el.innerHTML = innerSpan[0].innerHTML;
				}
				
			}
		}
	
	}
	
	// now we should hide the form
	
	//var wrapper = parent.document.getElementById('{$formid}-wrapper');
	
	
	//wrapper.parentNode.parentNode.removeChild(wrapper.parentNode);


	
	if ( targetel.old_onclick) {
		targetel.onclick = targetel.old_onclick;
		
		targetel.old_onclick = null;
		
	} else {

	}
	
	//targetel.edit_form.parentNode.removeChild(targetel.edit_form);
	
	
}

function submitThisForm(form){
	var div = document.createElement('div');
	div.innerHTML = '<img src="'+DATAFACE_URL+'/images/progress.gif" alt="Processing ..." />';
	form.parentNode.appendChild(div);
	form.style.display='none';
	form.submit();
	
}

registerOnloadHandler(initDocument);