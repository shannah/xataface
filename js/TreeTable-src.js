function TreeTable(tableid, querystr){
	this.rowsLoaded = {};
	if ( !querystr ){
		querystr = window.location.search;
	}
	this.table = document.getElementById(tableid);
	this.collapseAllRows();
}

/**
 * This Tree table code was taken from Super Simple JavaScript Tree 
 * at http://sstree.tigris.org/ unsed under the Apache license available
 * at http://www.apache.org/licenses/LICENSE-2.0.txt
 */
TreeTable.prototype.toggleRows = function(elm) {
 var rows = this.table.getElementsByTagName("TR");
 elm.style.backgroundImage = "url("+DATAFACE_URL+"/images/folder-closed.gif)";
 var newDisplay = "none";
 var thisID = elm.parentNode.parentNode.parentNode.id + "-";
 // Are we expanding or contracting? If the first child is hidden, we expand
  for (var i = 0; i < rows.length; i++) {
   var r = rows[i];
   if (this.matchStart(r.id, thisID, true)) {
    if (r.style.display == "none") {
     if (document.all) newDisplay = "block"; //IE4+ specific code
     else newDisplay = "table-row"; //Netscape and Mozilla
     elm.style.backgroundImage = "url("+DATAFACE_URL+"/images/folder-open.gif)";
    }
    break;
   }
 }
 
 // When expanding, only expand one level.  Collapse all desendants.
 var matchDirectChildrenOnly = (newDisplay != "none");

 for (var j = 0; j < rows.length; j++) {
   var s = rows[j];
   if (this.matchStart(s.id, thisID, matchDirectChildrenOnly)) {
     s.style.display = newDisplay;
     var cell = s.getElementsByTagName("TD")[0];
     var tier = cell.getElementsByTagName("DIV")[0];
     var folder = tier.getElementsByTagName("A")[0];
     if (folder.getAttribute("onclick") != null) {
      folder.style.backgroundImage = "url("+DATAFACE_URL+"/images/folder-closed.gif)";
     }
   }
 }
}

TreeTable.prototype.matchStart = function(target, pattern, matchDirectChildrenOnly) {
 var pos = target.indexOf(pattern);
 if (pos != 0) return false;
 if (!matchDirectChildrenOnly) return true;
 if (target.slice(pos + pattern.length, target.length).indexOf("-") >= 0) return false;
 return true;
}


TreeTable.prototype.collapseAllRows = function() {
 var rows = document.getElementsByTagName("TR");
 for (var j = 0; j < rows.length; j++) {
   var r = rows[j];
   if (r.id.indexOf("-") >= 0) {
     r.style.display = "none";    
   }
 }
}



function TreeTable_loadSubrows(rowid, visible){
	if ( !this.rowsLoaded[rowid] ){
		// the row is not loaded yet.. let's make the http request to load the
		// rows
		var url = DATAFACE_SITE_HREF+
					'?-action=ajax_load_tree_table_rows&-table='+this.queryStr+
					'&-rowid='+escape(rowid);
		var http = getHTTPObject();
		http.handleLoadSubrows = this.handleLoadSubrows;	// this will be the handler
		http.treeTable = this; // maintain a link to this treetable object for the handler
		http.rowid = rowid; 	// the id of the row after which the subrows will be added
		http.visible = visible;
		
		http.open("GET", url);
		http.onreadystatechange = this.handleLoadSubrows;
		http.send(null);
		
	}
}

TreeTable.prototype.loadSubrows = TreeTable_loadSubrows;

function TreeTable_handleLoadSubrows(){
	if ( this.readystate == 4 ){
		// the request has been processed
		var rows = document.getElementsByTagName('TR');
		var found = false;
		var prevEl = null;
		var nextEl = null;
		for ( var i=0; i<rows.length; i++ ){
			var r = rows[i];
			if ( r.id == this.rowid ){
				// we have found our insertion point.
				prevEl = r;
			}
			if ( prevEl ){
				nextEl = r;
				break;
			}
		}
		var frag = document.createDocumentFragment();
		frag.innerHTML = http.responseText;
		
		if ( nextEl ){
			nextEl.parentNode.insertBefore(nextEl,frag);
		} else {
			prevEl.parentNode.appendChild(frag);
		}
	}
}
TreeTable.prototype.handleLoadSubrows = TreeTable_handleLoadSubrows;
TreeTable.prototype.trees = {};


function validateTTForm(form){
        var selectedIds = [];
	for (var i=0; i<form.elements.length; i++ ){
		var e = form.elements[i];
		if ( (e.name == '--remkeys[]') && (e.type == 'checkbox') && e.checked ){
			selectedIds.push(e.value);
		}
	}
        form['--selected-ids'] = selectedIds.join('\n');
        if ( selectedIds.length > 0 ){
            return true;
        }
	alert("No records are selected.  Please click the checkbox next to the record on which you wish to perform this action.");
	return false;

}


