//require <xatajax.undo/UndoableEdit.js>
//require <xatajax.undo/UndoableEditListener.js>
//require <xatajax.undo/UndoableEditEvent.js>
(function(){

	/**
	 * Import globals to local scope.
	 */
	var $ = jQuery;
	var UndoableEdit = XataJax.undo.UndoableEdit;
	var UndoableEditListener = XataJax.undo.UndoableEditListener;
	var UndoableEditEvent = XataJax.undo.UndoableEditEvent;
	var Exception = XataJax.Exception;
	
	
	/**
	 * Register the public API
	 */
	XataJax.undo.UndoManager = UndoManager;
	
	
	/**
	 * Error code for exceptions thrown when there are no edits left
	 * to redo, but the method call implies that we expect there to be.
	 * e.g. the redoTo(UndoableEdit) method.
	 */
	XataJax.errorcodes.REDO_STACK_EMPTY = XataJax.nextErrorCode();
	
	/**
	 * Error code for exceptions thrown when there are no edits left to
	 * undo, but the method call implies that we expect there to be.
	 * e.g. undoTo(UndoableEdit) method.
	 */
	XataJax.errorcodes.UNDO_STACK_EMPTY = XataJax.nextErrorCode();
	
	
	/**
	 * The UndoManager constructor.  This is essentially a port of the Java
	 * Swing UndoManager class into javascript so that we can add Undo/Redo
	 * support to a javascript application.
	 *
	 * @see http://download.oracle.com/javase/6/docs/api/javax/swing/undo/UndoManager.html
	 * for more details about the Java API that we loosely copied here.
	 *
	 * @constructor
	 """
	 new UndoManager();
	 """
	 */
	function UndoManager(o){
		
		/**
		 * Extends the UndoableEdit class.
		 */
		XataJax.extend(this, new UndoableEditListener(o));
		XataJax.extend(this, new UndoableEdit(o));
		
		var publicAPI = {
		
			setLimit: setLimit,
			getLimit: getLimit,
			trimForLimit: trimForLimit,
			addEdit: addEdit,
			canRedo: canRedo,
			canUndo: canUndo,
			canUndoOrRedo: canUndoOrRedo,
			discardAllEdits: discardAllEdits,
			editToBeRedone: editToBeRedone,
			editToBeUndone: editToBeUndone,
			redoTo: redoTo,
			undoOrRedo: undoOrRedo,
			undoInsignificant: undoInsignificant,
			undo: undo,
			redo: redo,
			undoTo: undoTo,
			getUndoPresentationName: getUndoPresentationName,
			getRedoPresentationName: getRedoPresentationName,
			getUndoOrRedoPresentationName: getUndoOrRedoPresentationName,
			end: end
		};
		
		/**
		 * Register public methods.
		 */
		XataJax.publicAPI(this, publicAPI);
		
		
		/**
		 * Private member variables.-------------------------------------------------
		 */
		 
		/**
		 * @type array(UndoableEdit) Stack of edits that can be undone.
		 */
		var undoStack = [];
		
		/**
		 * @type array(UndoableEdit) Stack of edits that can be redone.
		 */
		var redoStack = [];
		
		/**
		 * @type boolean A flag to indicate if this manager has been ended and
		 *		ceases to act as an undoManager anymore.  If this flag is set
		 * 		then this will function like a regular UndoableEdit object.
		 */
		var ended = false;
		
		/**
		 * @type int
		 * The maxiumm number of edits that can be stored.
		 */
		var limit = 30;
		
		
		/**
		 * END PRIVATE MEMBERS-------------------------------------------------------
		 */
		 
		/**
		 * Ends this manager so that it will be treated just like a normal edit.
		 */
		function end(){
			ended = true;
		}
		
		
		/**
		 * Sets the maximum number of edits that can be undone.
		 * @param {int} l
		 */
		function setLimit(l){
			limit = l;
			this.trimForLimit();
		}
		
		
		/**
		 * Gets the maximum number of edits that can be undone.
		 *
		 * @returns {int}
		 */
		function getLimit(){
			return limit;
		}
		
		
		/**
		 * Trims the undo stack down to only contain the maximum limit of 
		 * edits (@see getLimit()).  Edits are deleted from oldest to newest
		 * and their die() method is called as they are deleted.
		 */
		function trimForLimit(){
			while ( undoStack.length > this.getLimit() ){
				var edit = undoStack.shift();
				edit.die();
			}
		}
		
		/**
		 * Adds an edit to the undo manager.
		 * @param {UndoableEdit} The UndoableEdit object that is being added. This 
		 *		method also has the effect of clearing the redoStack.
		 */
		function addEdit(edit){
			// we need to clear the redoStack out
			redoStack = [];
			undoStack.push(edit);
			this.trimForLimit();
		}
		
		
		/**
		 * Returns true if edits may be redone. If end has been invoked, this returns 
		 *	the value from super. Otherwise, this returns true if there are any 
		 * edits to be redone (editToBeRedone returns non-null).
		 * 
		 * @returns {boolean}
		 */
		function canRedo(){
			if ( ended ){
				return this.getSuper(UndoableEdit).canRedo();
			}
			if ( redoStack.length == 0 ){
				return false;
			}
			for ( var i=redoStack.length-1; i>=0; i--){
				if ( !redoStack[i].canRedo() ){
					return false;
				}
				if ( redoStack[i].isSignificant() ){
					return true;
				}
			}
			return false;
		}
		
		/**
		 * Returns true if edits may be undone. If end has been invoked, this returns 
		 * the value from super. Otherwise this returns true if there are any edits 
		 * to be undone (editToBeUndone returns non-null).
		 *
		 * @returns {boolean}
		 */
		function canUndo(){
			if ( ended ){
				return this.getSuper(UndoableEdit).canUndo();
			}
		
			if ( undoStack.length == 0 ){
				return false;
			}
			for ( var i=undoStack.length-1; i>=0; i-- ){
				if ( !undoStack[i].canUndo() ){
					return false;
				}
				if ( undoStack[i].isSignificant() ){
					return true;
				}
			}
			return false;
		}
		
		/**
		 * Returns true if it is possible to invoke undo or redo.
		 *
		 * @returns {boolean}
		 */
		function canUndoOrRedo(){
			return (this.canUndo()||this.canRedo());
		}
		
		
		/**
		 * Empties the undo manager sending each edit a die message in the process.
		 */
		function discardAllEdits(){
			while ( undoStack.length > 0 ){
				undoStack.shift().die();
			}
			while ( redoStack.length > 0 ){
				redoStack.shift().die();
			}
		}
		
		/**
		 * Returns the the next significant edit to be redone if redo is invoked. 
		 * This returns null if there are no edits to be redone.
		 *
		 * @returns {UndoableEdit}
		 */
		function editToBeRedone(){
			if ( !this.canRedo() ) return null;
			for ( var i=redoStack.length-1; i>=0; i-- ){
				if ( redoStack[i].canRedo() && redoStack[i].isSignificant() ){
					return redoStack[i];
				}
			}
			return null;
		}
		
		
		/**
		 * Returns the the next significant edit to be undone if undo is invoked. 
		 * This returns null if there are no edits to be undone.
		 *
		 * @returns {UndoableEdit}
		 */
		function editToBeUndone(){
			if ( !this.canUndo() ) return null;
			for ( var i=undoStack.length-1; i>=0; i-- ){
				if ( undoStack[i].canUndo() && undoStack[i].isSignificant() ){
					return undoStack[i];
				}
			}
			
			return null;
		}
		
		
		/**
		 * Redoes all changes from the index of the next edit to edit.
		 *
		 * @param {UndoableEdit} edit The edit up to which is to be redone (inclusive).
		 * @throws Exception(XataJax.errorcodes.CANNOT_REDO_EXCEPTION)
		 */
		function redoTo(edit){
			if ( redoStack.length == 0 ){
				throw new Exception({
					message: 'No edits available to redo.',
					code: XataJax.errorcodes.CANNOT_REDO_EXCEPTION
				});
			}
			var curr = null;
			while ( redoStack.length > 0 ){
				curr = redoStack.pop();
				
				curr.redo();
				undoStack.push(curr);
				if ( curr == edit ){
					return;
				}
			}
			

			throw new Exception({
				message: 'Redo stack is empty',
				code: XataJax.errorcodes.REDO_STACK_EMPTY
			});

		}
		
		/**
		 * Convenience method that invokes one of undo or redo. If any edits have been 
		 * undone this invokes redo, otherwise it invokes undo.
		 *
		 * @returns {boolean}
		 */
		function undoOrRedo(){
			if ( this.canRedo() ){
				this.redo();
			} else if ( this.canUndo() ){
				this.undo();
			} else {
				throw new Exception({
					message: 'No undos or redos to do',
					code: XataJax.errorcodes.REDO_STACK_EMPTY
				});
			}
		}
		
		/**
		 * Undoes all of the insignificant edits at the top of the stack.
		 * After running this method either the undo stack is empty or
		 * the very top item is a significant edit.
		 *
		 * @returns {void}
		 */
		function undoInsignificant(){
			var curr = null;
			while ( undoStack.length > 0 ){
				curr = undoStack.pop();
				if ( curr.isSignificant() ){
					undoStack.push(curr);
					return;
				}
				curr.undo();
				redoStack.push(curr);
				
			}
			
		}
		
		
		/**
		 * Undoes the appropriate edits. If end has been invoked this calls through 
		 * to the superclass, otherwise this invokes undo on all edits between the 
		 * index of the next edit and the last significant edit, updating the index of 
		 * the next edit appropriately.
		 *
		 * @throws {Exception(XataJax.errorcodes.CANNOT_UNDO_EXCEPTION)}
		 */
		function undo(){
			if ( ended ){
				return this.getSuper(UndoableEdit).undo();
			}
			var nextEdit = this.editToBeUndone();
			if ( nextEdit != null ){
				this.undoTo(nextEdit);
				this.undoInsignificant();
			}
		}
		
		
		/**
		 * Redoes the appropriate edits. If end has been invoked this calls through 
		 * to the superclass. Otherwise this invokes redo on all edits between the 
		 * index of the next edit and the next significant edit, updating the index of 
		 * the next edit appropriately.
		 *
		 * @throws {Exception(XataJax.errorcodes.CANNOT_UNDO_EXCEPTION)}
		 */
		function redo(){
			if ( ended ){
				return this.getSuper(UndoableEdit).redo();
			}
			var nextEdit = this.editToBeRedone();
			this.redoTo(nextEdit);
			this.redoInsignificant();
		}
		
		/**
		 * Undoes all edits up to and including the specified edit.
		 *
		 * @param {UndoableEdit} edit
		 * @throws {Exception(XataJax.errorcodes.CANNOT_UNDO_EXCEPTION)}
		 */
		function undoTo(edit){
			var curr = null;
			while ( undoStack.length > 0 ){
				curr = undoStack.pop();
				curr.undo();
				redoStack.push(curr);
				if ( curr == edit ){
					return;
				}
			}
			
			throw new Exception({
				message: 'Undo stack is empty and we still haven\'t reached the edit we want to remove.',
				code: XataJax.errorcodes.UNDO_STACK_EMPTY = XataJax.nextErrorCode()
			});
		}
		
		
		/**
		 * Returns a description of the undoable form of this edit. If end has been invoked 
		 * this calls into super. Otherwise if there are edits to be undone, this returns 
		 * the value from the next significant edit that will be undone.
		 *
		 * @returns {String}
		 */
		function getUndoPresentationName(){
			if ( ended ){
				return this.getSuper(UndoableEdit).getUndoPresentationName();
			}
			var next = this.editToBeUndone();
			if ( next != null ){
				return next.getUndoPresentationName();
			} else {
				return this.getSuper(UndoableEdit).getUndoPresentationName();
			}
			
		}
		
		/**
		 * Returns a description of the redoable form of this edit. If end has been 
		 * invoked this calls into super. Otherwise if there are edits to be redone, 
		 * this returns the value from the next significant edit that will be redone. 
		 * 
		 * @returns {String}
		 */
		function getRedoPresentationName(){
			if ( ended ){
				return this.getSuper(UndoableEdit).getRedoPresentationName();
			}
			var next = this.editToBeRedone();
			if ( next != null ){
				return next.getRedoPresentationName();
			} else {
				return this.getSuper(UndoableEdit).getUndoPresentationName();
			}
		}
		
		/**
		 * Convenience method that returns either getUndoPresentationName or 
		 * getRedoPresentationName. If the index of the next edit equals the size 
		 * of the edits list, getUndoPresentationName is returned, otherwise 
		 * getRedoPresentationName is returned.
		 *
		 * @returns {String}
		 */
		 
		function getUndoOrRedoPresentationName(){
			if ( this.canRedo() ){
				return this.getRedoPresentationName();
			} else if ( this.canUndo() ){
				return this.getUndoPresentationName();
			} else {
				return null;
			}
		}
		
		/**
		 * UndoableEditListener method
		 *
		 * @param {UndoableEditEvent} event
		 */
		function undoableEditHappened(event){
			this.addEdit(event.edit);
		}
	}
	
})();