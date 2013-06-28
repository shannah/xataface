//require <xataface/DOM.js>
(function(){
	var $ = jQuery;
	var DOM = xataface.DOM;
	var Model = xataface.model.Model;
	
	XataJax.subclass(Application, Model);
	
	function Application(/*HTMLElement*/ el){
		var self = this;
		this.root = DOM(el);
		
		function onReady(evt, data){
			if ( data.propertyName == 'status' && data.newValue == DOM.STATUS_READY ){
				$(self.root).unbind('propertyChanged', onReady);
				
				
			}
			
		}
		
		this.root.decorate();
		
	}
	
})();