//require <xatajax.form.core.js>
//require <xatajax.beans/PropertyChangeListener.js>
//require <xatajax.beans/PropertyChangeSupport.js>
(function(){
	var $ = jQuery;
	var PropertyChangeSupport = XataJax.beans.PropertyChangeSupport;
	var ProprtyChangeListener = XataJax.beans.PropertyChangeListener;
	
	XataJax.form.FormElement = FormElement;
	
	function FormElement(o){
		XataJax.extend(this, new PropertyChangeSupport());
		
		$.extend(this, {
			getBindPath: getBindPath,
			setBindPath: setBindPath,
			setContext: setContext,
			getContext: getContext,
			getComponent: getComponent,
			setComponent: setComponent
		});
		
		var members = {
			bindPath: null,
			context: null,
			component: null
			
		};
		
		/**
		 * A Property Change Listener that listens to the data context and updates
		 * data in the component accordingly.
		 *
		 */
		var contextListener = new PropertyChangeListener({
			propertyChange: function(event){
				if ( event.propertyName == getBindPath() && getComponent() != null){
					getComponent().setValue(event.newValue);
				}
			}
		});
		
		
		/**
		 * A Property Change Listener that listens to the component and updates
		 * the data in the context accordingly.
		 */
		var componentListener = new PropertyChangeListener({
			propertyChange: function(event){
				if ( event.propertyName == 'value' && getBindPath() != null && getContext() != null ){
					getContext().setValue(getBindPath(), event.newValue);
				}
			}
		});
		
		
		function getBindPath(){
			return members.bindPath;
		}
		
		function setBindPath(path){
			if ( members.bindPath != path ){
				var old = members.bindPath;
				if ( old != null && this.getContext() != null ){
					// We need to unbind the context's property change
					// listener because we are changing the bind path.
					this.getContext().removePropertyChangeListener(old, contextListener);
				}
				members.bindPath = path;
				
				if ( path != null && this.getContext() != null ){
					// Now rebind to the new property
					this.getContext().addPropertyChangeListener(path, contextListener);
				}
				this.firePropertyChange('bindPath', old, path);
			}
		}
		
		function setContext(c){
			if ( members.context != c ){
				var old = members.context;
				if ( old != null ){
					old.removePropertyChangeListener(contextListener);
				}
				members.context = c;
				if ( c != null && this.getBindPath() != null ){
					c.addPropertyChangeListener(this.getBindPath(), contextListener); 
				}
				this.firePropertyChange('context', old, c);
			}
		}
		
		function getContext(){
			return members.context;
		}
		
		function setComponent(c){
			if ( members.component != c ){
				var old = members.component;
				if ( old != null ){
					// We need to unregister from property change events
					old.removePropertyChangeListener(componentListener);
				}
				members.component = c;
				if ( c != null ){
					c.addPropertyChangeListener('value', componentListener);
				}
				this.firePropertyChange('component', old, c);
			}
		}
		
		function getComponent(){
			return members.component;
		}
		
		
	}
})();