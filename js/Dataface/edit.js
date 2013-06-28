if ( !document.__dataface__ ) document.__dataface__ = {};
if ( !document.__dataface__.recordIndex ) document.__dataface__.recordIndex = {};
if ( !document.__dataface__.tableIndex ) document.__dataface__.tableIndex = {};
if ( !document.__dataface__.requestStack ) document.__dataface__.requestStack = [];
var __dataface__ = document.__dataface__;

__dataface__.recordLoaded = function(id){
	
	return (__dataface__.recordIndex[id] != null);
}

__dataface__.tableLoaded = function(name){
	return (__dataface__.tableIndex[name] != null);
}

__dataface__.fieldLoaded = function(tablename, fieldname){
	return ( tableLoaded(tablename) && __dataface__.tableIndex[tablename].__fields__[fieldname] != null );
}

__dataface__.pushRequest = function(func, params){
	__dataface__.requestStack.push([func, params]);
}
__dataface__.popRequest = function(){
	return __dataface__.requestStack.pop();
}

__dataface__.requestStackSize = function(){ return __dataface__.requestStack.length; }

__dataface__.getRecord = function(id){
	return __dataface__.recordIndex[id];
}

__dataface__.getTable = function(name){
	return __dataface__.tableIndex[name];
}

__dataface__.loadField = function(params, callback){
	__dataface__.errors = [];
	if ( __dataface__.fieldLoaded(params['table'], params['field']) ) return;
	var query = {'action': '-ajax_load_fields', tables': [], 'records': [], 'fields': [] };
	
	if ( !__dataface__.tableLoaded(params['table']) ) query.tables.push(params['table']);
	query.fields.push('*');
	__dataface__.pushRequest(callback[0], callback[1]);
	__dataface__.sendRequest(query, [__dataface__.handleLoadField, null]);
	return;
}

__dataface__.handleLoadField = function(){

	if ( __dataface__.http.readyState == 4 ){
		var data = eval('('+__dataface__.http.responseText+')');
		__dataface__.errors = data.errors;
		__dataface__.messages = data.messages;
		
		
		if ( data.tables ){
			for ( tablename in data.tables ){
				__dataface__.updateTable(tablename, data.tables[tablename]);
			}
		}
		
		if ( data.records ){
			for ( recordId in data.records ){
				__dataface__.updateRecord(recordId, data.records[recordId]);	
			}
		}
		
		__dataface__.performNextRequest();
	}

}


__dataface__.performNextRequest = function(){
	if ( __dataface__.requestStackSize() > 0 ){
		var callback = __dataface__.popRequest();
		var func = callback[0];
		var param = callback[1];
		func(param);
	}

}

__dataface__.sendRequest = function(query, callback){
	__dataface__.http = getHTTPObject();
	var qstr = '?-action='+escape(query.action);
	if ( query.tables ){
		for ( var i=0; i<query.tables.length; i++){
			qstr += '&'+escape('tables['+i+']')+'='+escape(query.tables[i]);
		}
	}
	if ( query.records ){
		for ( var i=0; i<query.records.length; i++){
			qstr += '&'+escape('records['+i+']')+'='+escape(query.records[i]);
		}
	}
	if ( query.fields ){
		for ( var i=0; i<query.fields.length; i++){
			qstr += '&'+escape('fields['+i+']')+'='+escape(query.fields[i]);
		}
	}

	__dataface__.http.open('GET', qstr);
	
	__dataface__.http.onreadystatechange = callback[0];
	__dataface__.http.send(null);
}


__dataface__.setTagMode = function(tag, mode){
	var id = tag.getAttribute('id');
	var date = new Date();
	if ( !tag.__modes__ ) tag.__modes__ = {'view': {'timestamp': date.getTime(), 'innerHTML': tag.innerHTML}};
	if ( !tag.__mode__ ) tag.__mode__ = 'view';
	if ( tag.__mode__ != mode ){
		if ( mode == 'edit' ){
			// we are entering edit mode... must load the field
			
		}
	}
	
	
	if ( !id ) return;
	var recid = id.substring(0, id.indexOf('#'));
	var field = id.substring(id.indexOf('#')+1);
	
}