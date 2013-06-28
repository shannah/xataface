//require <jquery.packed.js>
//require <xataface/view/View.js>
//require <xataface/DOM.js>

(function(){
	var $ = jQuery;
	var tests = XataJax.load('xataface.tests');
	var View = xataface.view.View;
	var Model = xataface.model.Model;
	var DOM = xataface.DOM;
	tests.BootstrapTestApp = BootstrapTestApp;
	
	
	function BootstrapTestApp(/*HTMLElement*/ el){
		var self =this;
		this.model = new Model({
			data : {
				'comments' : 'This is a test comment'
			}
		});
		this.view = new View({
			el : el,
			model : this.model
		});
		this.view.update();
		
		this.formEl = $('form#testform', el).get(0);
		DOM(this.formEl).ready(function(){
			this.controller.form.model = self.model;
			this.controller.form.decorate();
		});
		
		
		
		
		
	}
	
	(function(){
		$.extend(BootstrapTestApp.prototype, {
			 model : null,
			 view : null,
			 formEl : null
		});
	})();
})();