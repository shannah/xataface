require(DATAFACE_URL+'/js/ajax.js');
require(DATAFACE_URL+'/js/Dataface/Record.js');
function Dataface_ResultList(){}

Dataface_ResultList.prototype.showRecordDetails = function(img,id){
	var cell = document.getElementById(id+'-cell');
	var row = document.getElementById(id+'-row');
	row.style.display = '';
	img.src = DATAFACE_URL+'/images/treeExpanded.gif';
	img.onclick = function(){ resultList.hideRecordDetails(img,id); };
	
	if ( !cell.detailsLoaded ){
		cell.detailsLoaded = true;
		this.http = getHTTPObject();
		var record = new Dataface_Record(getRecord(id));
		var url = record.getURL('-action=ajax_view_record_details');
		this.http.open('GET', url);
		this.img = img;
		this.row = row;
		this.cell = cell;
		this.id = id;
		this.http.onreadystatechange = this.handleShowRecordDetails;
		this.http.send(null);
	}

}

Dataface_ResultList.prototype.handleShowRecordDetails = function(){

	if (resultList.http.readyState == 4 ){
		resultList.cell.innerHTML = resultList.http.responseText;
		if ( typeof(df_add_editable_awareness)=='function' ){
			df_add_editable_awareness(resultList.cell);
		}
		
		
	}
}

Dataface_ResultList.prototype.hideRecordDetails = function(img,id){
	var row = document.getElementById(id+'-row');
	row.style.display = 'none';
	img.src = DATAFACE_URL+'/images/treeCollapsed.gif';
	img.onclick = function(){ resultList.showRecordDetails(img,id); };

}
var resultList = new Dataface_ResultList();