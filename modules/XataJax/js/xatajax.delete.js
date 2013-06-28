//require <xatajax.core.js>
(function(){
	
	
	/**
	 * Submits a delete request.
	 *
	 * Parameters:
	 *
	 * onSuccess(data) : A callback on success.
	 * onFail(data):	A callback on fail.
	 * onComplete(data): A callback on complete.
	 *
	 * Other acceptable parameters include any valid Xataface POST parameter
	 * to help select a record.
	 *
	 * Callback parameters:
	 *
	 * code	: The integer response code (see Response Codes)
	 * message: The human readable message.
	 * errors: Array of error objects.  (See Error object below)
	 * deletedIds: Array of ids of deleted records.  (Using Xataface record id format)
	 *
	 *
	 * Response Codes:
	 * ----------------
	 *
	 * 200 : Success.  (All deletions succeeded)
	 * 201 : Partial Success.  (At least one deletion succeeded.  At least one failed also)
	 * 400 : Fail.  No deletions succeeded. At least one failed.
	 * 404 : Record could not be found to delete.
	 * 500 : Other server error.
	 *
	 *
	 * Error object:
	 * --------------
	 *
	 * record_id : The Xataface record id that produced the error.
	 * code :	The error code.
	 * message: The error message
	 *
	 * Example:
	 * --------
	 * 
	 * submitDelete({
	 *	'-table': 'people',
	 *	'person_id': 12,
	 *	onSuccess: function(data){
	 *      alert('delete succeeded');
	 *	},
	 *	onFail: function(data){
	 *  	alert('Delete failed: '+data.message);
	 *  }
	 * });
	 * 
	 */
	function submitDelete(params){
		
		var q = jQuery.extend({},params);
		q['-action'] = 'xatajax_delete';
		
		var onSuccess = params.onSuccess;
		var onFail = params.onFail;
		var onComplete = params.onComplete;
		
		if ( typeof(q.onSuccess) != 'undefined' ) delete q.onSuccess;
		if ( typeof(q.onFail) != 'undefined' ) delete q.onFail;
		if ( typeof(q.onComplete) != 'undefined' ) delete q.onComplete;
		
		$.post(DATAFACE_SITE_HREF, q, function(data){
			handleDeleteResponse(data, params);
		});
	}
	
	
	/**
	 * Handles the response of a delete request.
	 *
	 * @param mixed data The response data.
	 * @param object params The parameters that were passed to the submitDelete
	 *		method.  Possible parameters include:
	 *		onSuccess: A callback function that is called when delete is completed successfully
	 *			with no warnings.
	 *		onComplete: A callback function that is called when delete completes (success or fail).
	 * 		onFail: A callback function that is called when the delete completes but some
	 *			have failed.
	 */
	function handleDeleteResponse(data, params){
		try {
			if ( typeof(data) == 'string' ){
				eval('data='+data+';');
				
			}
			
			switch (data.code){
				case 200:
					if ( typeof(params.onComplete)=='function' ){
						params.onComplete(data);
					}
					
					if ( typeof(params.onSuccess)=='function' ){
						params.onSuccess(data);
					}
					break;
					
				case 201: // Mixed success
				case 404: // No matching records found
				case 500: // Server error
					if ( typeof(params.onComplete)=='function' ){
						params.onComplete(data);
					}
					if ( typeof(params.onFail)=='function' ){
						params.onFail(data);
					}
					break;
				
				
				default:
					
					if ( data.message ){
						throw data.message;
					} else {
						throw 'Unspecified server error.  Check error log for more details.';
					}
			}
			
		} catch (e){
			alert(e);
		}
	}
	
	
	XataJax.submitDelete = submitDelete;
	

})();