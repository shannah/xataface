//require <xatajax.undo.js>
//require <xatajax.undo/UndoableEditEvent.js>
//require <xatajax.undo/UndoableEdit.js>
//require <xatajax.undo/UndoableEditListener.js>
(function(){
	var $ = jQuery;
	var Exception = XataJax.Exception;
	var UndoableEditEvent = XataJax.undo.UndoableEditEvent;
	var UndoableEdit = XataJax.undo.UndoableEdit;
	var UndoableEditListener = XataJax.undo.UndoableEditListener;
	
	XataJax.undo.UndoableEditSupport = UndoableEditSupport;
	
	/**
	 * Class that provides plumbing for undoable edits.  Any class that wants
	 * to implement undo support should extend this class.
	 *
	 * @constructor
	 *
	 """
	 new UndoableEditSupport();
	 """
	 */
	function UndoableEditSupport(o){
		/**
		 """
		 UndoableEditSupport.__methods__ = publicAPI;
		 """
		*/
		
	
		/**
		 * Extends Object.
		 */
		XataJax.extend(this, new Object());
		
		var publicAPI = {
			addUndoableEditListener: addUndoableEditListener,
			removeUndoableEditListener: removeUndoableEditListener,
			getUndoableEditListeners: getUndoableEditListeners,
			beginUpdate: beginUpdate,
			postEdit: postEdit,
			endUpdate: endUpdate,
			getUpdateLevel: getUpdateLevel
		};
		
		/**
		 * Register public API methods.
		 */
		XataJax.publicAPI(this, publicAPI);
		
		
		/**
		 * Private Member Variables
		 */
		
		/**
		 * @type {array UndoableEdit}
		 *
		 * A stack to store the current UndoableEdit transaction.  The 
		 * top most edit of the stack contains all edits between calls
		 * of beginUpdate and endUpdate.
		 */
		var updateStack = [];
		
		/**
		 * @type {array UndoableEditListener}
		 *
		 * List of registered listeners who would like to receive notification
		 * when edits are posted.
		 */
		var listeners = [];
		
		/**
		 * Adds a listener to receive undoable edit events when updates are posted.
		 *
		 * @param {UndoableEditListener} l
		 */
		function addUndoableEditListener(l){
			listeners.push(l);
		}
		
		
		/**
		 * Removes a listener from receiving undoable edit events when updates are
		 * posted.
		 *
		 * @param {UndoableEditListener} l
		 */
		function removeUndoableEditListener(l){
			var idx = listeners.indexOf(l);
			if ( idx != -1 ){
				listeners.splice(idx,1);
			}
		}
		
		
		/**
		 * Returns array of UndoableEditListeners that are listening
		 * for edit events.
		 *
		 * @returns {array UndoableEditListener}
		 */
		function getUndoableEditListeners(){
			return $.merge([], listeners);
		}
		
		
		/**
		 * Begins an update transaction.  All edits posted between a call to
		 * beginUpdate and a matching endUpdate will be combined into a single
		 * compound edit before it is ultimately sent to listeners.
		 *
		 * @returns {void}
		 */
		function beginUpdate(){
			var edit = new UndoableEdit();
			updateStack.push(edit);
		}
		
		/**
		 * Posts an edit.  If beginUpdate has been called, this this edit is
		 * just added to the current transaction and will be sent to listeners
		 * upon calling endUpdate.  If beginUpdate has NOT been called (meaning
		 * there is no currently opened transaction) this will send a notice to 
		 * listeners directly.
		 *
		 * @param {UndoableEdit} edit
		 */
		function postEdit(edit){
			var noTransaction = false;
			if ( updateStack.length == 0 ){
				this.beginUpdate();
				noTransaction = true;
			}
			if ( updateStack.length == 0 ){
				throw new Exception({
					message: 'Cannot post edit because there is no edit on the update stack.  Please check the beginUpdate() method to ensure that it adds an edit to the update stack'
				});
			}
			
			var current = updateStack.pop();
			current.addEdit(edit);
			updateStack.push(current);
			if ( noTransaction ){
				endUpdate();
			}
		}
		
		/**
		 * Closes the current transaction and fires UndoableEditEvent to all 
		 * listeners containing all edits that were posted between the last call
		 * to beginUpdate and now.
		 *
		 * @throws {Exception} If beginUpdate hasn't been called yet.
		 */
		function endUpdate(){
			if ( updateStack.length == 0 ){
				throw new Exception({
					message: 'Attempt to end update when no update had been begun yet.  Please call beginUpdate before calling endUpdate.'
				});
			}
			
			var edit = updateStack.pop();
			var event = new UndoableEditEvent({
				source: this,
				edit: edit
			});
			
			$.each(listeners, function(){
				if ( typeof(this.undoableEditHappened) == 'function' ){
					this.undoableEditHappened(event);
				}
			});
		}
		
		
		/**
		 * Gets the number of update levels currently open.  This effectively is 
		 * the number of times beginUpdate has been called minus the number of 
		 * times endUpdate has been called.  (As it is possible to have
		 * nested transaction contexts.
		 *
		 * @returns {int}
		 */
		function getUpdateLevel(){
			return updateStack.length;
		}
		
		
	}
})();