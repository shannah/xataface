//require <xataface/view/View.js>
(function(){
	var $ = jQuery;
	var view = XataJax.load('xataface.view');
	var model = XataJax.load('xataface.model');
	var View = view.View;
	var Model = model.Model;
	
	$(document).ready(function(){
		alert('here');
		var wrapper = $('#wrapper1');
		var tpl = $('#tpl1');
		var data = [
		
			{ firstName: 'Steve', lastName: 'Hannah' },
			{ firstName: 'Jennifer', lastName: 'Lee' },
			{ firstName: 'Barry', lastName: 'Bonds' },
			{ firstName: 'Ben', lastName: 'Johnson' }
		];
		
		var models = [];
		$.each(data, function(k,v){
			models.push(new Model({ data : v }));
		});
		
		$.each(models, function(k,model){
			var view = new View({
				model : model,
				el : tpl.clone()
			});
			wrapper.append(view.el);
			view.update();
		});
		
		
		
		
		
	});
})();