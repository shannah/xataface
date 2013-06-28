//require <xataface/form/Form.js>
//require <xataface/tests/BootstrapTestApp.js>
// require <xataface/model/Binding.js>
(function(){
	var $ = jQuery;
	var Form = xataface.form.Form;
	//var Binding = xataface.model.Binding;
	var BootstrapTestApp = XataJax.load('xataface.tests.BootstrapTestApp');
	BootstrapTestApp.TestForm = TestForm;
	function TestForm(/*HTMLElement*/ el){
		this.form = new Form({
			el : el
		});
		this.form.decorate();
		
	}
})();