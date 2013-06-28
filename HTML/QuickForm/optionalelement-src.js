/**
 * Inserts a new row into the table.
 * @param tableid The id of the table to which the new row should be added.
 */
function addOptionalField(tableid){


	var table = document.getElementById(tableid);
	var selectList = document.getElementById(tableid+'_add');
	
	
	if ( !table || !selectList){
		return;
	}
	
	var selectedField = selectList.options[selectList.selectedIndex].value;
	
	
	
	var tbody = table.getElementsByTagName('tbody');
	if (tbody.length==0){
		return;
	}
	
	
	tbody = tbody[0];
	rows = tbody.getElementsByTagName('tr');
	
	// hidden row that contains all of the widgets with default values.
	var prototype_row = null;
	
	for ( i=0; i<rows.length; i++){
		if ( rows[i].className == 'optionalField__'+selectedField ) {
			rows[i].style.display = '';
			selectList.options[selectList.selectedIndex] = null;
			return;
			break;
		}
	}

	if ( prototype_row == null ){
		return;
	}
	
	// create a copy of the prototype row with a new row
	var new_row = prototype_row.cloneNode(true);
	
	// We need to get some information from the current last row
	var last_row = rows[rows.length-1];
	
	var name_pattern = /^([^\[]+)\[(\d+)\]\[([^\[]+)\]$/;
	var prototype_pattern = /^([^\[]+)\[prototype\]\[([^\[]+)\]$/;
	
	// now that we have our new row, we have to set the name of it
	
	var new_cells = new_row.getElementsByTagName('td');
	if ( last_row ){
		var last_cells = last_row.getElementsByTagName('td');
	} else {
		var last_cells = new Array();
	}
	
	if ( new_cells.length != last_cells.length ){
		return;
	}
	
	// For each cell in the new row, rename it to be the same as corresponding
	// cell of the last row, except we increment the index.
	// eg: addresses[2][line1] becomes addresses[3][line1]
	for ( i=0; i<new_cells.length; i++ ){
		var new_input = new_cells[i].firstChild;
		var last_input = last_cells[i].firstChild;
		
		var last_results = name_pattern.exec( last_input.getAttribute('name') );
		var new_results = prototype_pattern.exec( new_input.getAttribute('name') );
		
		
		
		if ( last_results ){
			// get the last index and increment it
			var index = parseInt(last_results[2]);
			index++;
			new_input.setAttribute('name', last_results[1]+'['+index.toString()+']['+last_results[3]+']');
			
			
		
		} else {
			new_input.setAttribute('name', new_results[1]+'[0]['+new_results[2]+']');
		}
		
		var delete_pattern = /__delete/;
		if ( delete_pattern.exec( new_input.getAttribute('name') ) ){
			new_input.onclick = function(e){ deleteRow(e); }
		}
		
		new_row.className = null;
		new_row.style.display = null;
		
		
	}
	
	// now we add the new row
	
	tbody.appendChild( new_row );
	
				

}