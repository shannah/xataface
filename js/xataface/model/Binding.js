//require <xataface/model/Model.js>
(function(){
	var $ = jQuery;
	var Model = xataface.model.Model;
	model.Binding = Binding;
	
	function Binding(/*Model*/ m1, /*Model*/ m2, properties){
		var self = this;
		this.properties = properties;
		this.m1 = m1;
		this.m2 = m2;
		this.propertyChange1 = function(evt, data){
			if ( self.properties.indexOf(data.propertyName) > -1 ){
				self.m2.set(data.propertyName, data.newValue);
			}
		};
		
		this.propertyChange2 = function(evt, data){
			if ( self.properties.indexOf(data.propertyName)  > -1){
				self.m1.set(data.propertyName, data.newValue);
			}
			
		};
		
		this.disposeListener = function(evt, data){
			self.unbind();	
		};
		
		$(this.m1).bind('propertyChanged', this.propertyChange1);
		$(this.m2).bind('propertyChanged', this.propertyChange2);
		
		$(this.m1).bind('dispose', this.disposeListener);
		$(this.m2).bind('dispose', this.disposeListener);
	}
	
	(function(){
		$.extend(Binding.prototype, {
			unbind : unbind
		});
		
		function unbind(){
			$(this.m1).unbind('propertyChanged', this.propertyChange1);
			$(this.m2).unbind('propertyChanged', this.propertyChange2);
			$(this.m1).unbind('dispose', this.disposeListener);
			$(this.m2).unbind('dispose', this.disposeListener);
		}
	})();
	
})();