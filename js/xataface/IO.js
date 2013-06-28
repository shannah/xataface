//require <jquery.packed.js>
//require <xatajax.core.js>
(function(){
	var $ = jQuery;
	var xataface = XataJax.load('xataface');
	xataface.IO = IO;
	xataface.IO.update = update;
	xataface.IO.insert = insert;
	xataface.IO.load = load;
        xataface.IO.remove = remove;
	
	
	/**
	 * @class
	 * @name IO
	 * @memberOf xataface
	 * @description Just a placeholder class.  Most functions are static.
	 * @example
	 * //require &lt;xataface/IO.js&gt;
	 * var IO = XataJax.load('xataface.IO');
	 * var recordId = 'people?person_id=10';
	 * IO.load(recordId, function(res){
	 * 		alert("Person was loaded: "+res.first_name);
	 * 		// Now let's make a change and save them
	 *      var newVals = {
	 * 			first_name: res.first_name+' changed'
	 * 		};
	 * 		IO.update(recordId, newVals, function(updateRes){
	 * 			if ( updateRes.code == 200 ){
	 * 				alert('Successfully saved changes');
	 *			} else {
	 *				alert("Failed: "+updateRes.message);
	 *			}
	 *		});
	 *
	 * });
	 */
	function IO(/**Object*/ params){
	
	
	}
	
	/**
	 * @name UpdateCallback
	 * @memberOf xataface.IO
	 * @function
	 *
	 * @description A callback function to be passed as the callback to xataface.IO.save
	 *
	 * @param {Object} param
	 * @param {int} param.code The response code.  200 for success.  Anything else for failure.
	 * @param {String} param.message The message indicating what happened.  A server status message.
	 * @param {String} param.recordId The ID of the record that was updated.  (Only included upon
	 *		successful update.
	 *
	 * @see xataface.IO.update
	 */
	
	
	/**
	 * @name update
	 * @function
	 * @memberOf xataface.IO
	 *
	 * @description Updates a record's values in the database.
	 * @param {String} recordId The Xataface Record ID of the record to update.
	 * @param {Object} vals Key-value pairs of the data to update in the record.
	 * @param {xataface.IO.UpdateCallback} callback A callback function to call on completion.
	 * @returns {void}
	 */
	function update(/**String*/ recordId, /**Object*/ vals, /**Function*/ callback){
		if ( typeof(callback) == 'undefined' ) callback = function(){};
		
		var q = $.extend({
			'-action': 'ajax_save',
			'--record_id': recordId
			
			},
			vals
		);
		
		$.post(DATAFACE_SITE_HREF, q, callback);
			
	}
	
	
	/**
	 * @name InsertCallback
	 * @memberOf xataface.IO
	 * @function
	 *
	 * @description A callback function to be passed as the callback to xataface.IO.save
	 *
	 * @param {Object} param
	 * @param {int} param.code The response code.  200 for success.  Anything else for failure.
	 * @param {String} param.message The message indicating what happened.  A server status message.
	 * @param {String} param.recordId The ID of the record that was updated.  (Only included upon
	 *		successful update.
	 *
	 * @see xataface.IO.insert
	 */
	
	
	/**
	 * @name insert
	 * @function
	 * @memberOf xataface.IO
	 *
	 * @description Inserts a record into the database.
	 * @param {String} table The name of the table to insert into.
	 * @param {Object} vals Key-value pairs of the data to insert in the record.
	 * @param {xataface.IO.InsertCallback} callback A callback function to call on completion.
	 * @returns {void}
	 */
	function insert(/**String*/ table, /**Object*/ vals, /**Function*/ callback){
		if ( typeof(callback) == 'undefined' ) callback = function(){};
		
		var q = $.extend({
			'-action': 'ajax_insert',
			'-table': table
			
			},
			vals
		);
		
		$.post(DATAFACE_SITE_HREF, q, callback);
		
	
	}
	
	
	/**
	 * @function
	 * @name LoadCallback
	 * @memberOf xataface.IO
	 * @description Callback function format that can be passed to the xataface.IO.load() function.
	 * @param {Object} record The record that was retrieved.
	 * @param {String} record.<fieldname> The value of a field in the record.
	 *
	 * @see The <a href="http://xataface.com/dox/core/latest/classdataface__actions__export__json.html">dataface_actions_export_json</a>
	 *	action for the format of the response.
	 */
	
	
	/**
	 * @function
	 * @name load
	 * @memberOf xataface.IO
	 * @description Loads a record via AJAX using Xataface query conventions.
	 *
	 * @param {Object} query The Xataface query to specify which record to load.
	 * @param {xataface.IO.LoadCallback} callback The callback function to call when loading
	 *  is complete.
	 */
	function load(/**Object*/query, /**Function*/callback){
		if ( typeof(query) == 'string' ){
			query = {
				'--selected-ids': query
			};
		}
		
		$.extend(query, {
			'-action': 'export_json',
			'-mode': 'browse'
		});
		
		$.get(DATAFACE_SITE_HREF, query, function(res){
			callback.call(res, res);
			
		});
	
	}
        /**
	 * @function
	 * @name remove
	 * @memberOf xataface.IO
	 * @description Deletes a record from the database.
	 *
	 * @param {String} recordId The ID of the record to delete.
	 * @param {xataface.IO.LoadCallback} callback The callback function to call when loading
	 *  is complete.
	 */
        function remove(/*String*/ recordId, /*Function*/ callback){
            callback = callback || function(){};
            var q = {
                '--record_id' : recordId,
                '-action' : 'rest_delete',
                '-table' : recordId.substr(0, recordId.indexOf('?'))
            };
            
            $.post(DATAFACE_SITE_HREF, q, callback);
        }
	
	
	

})();