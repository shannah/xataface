//require <xataface/view/TableView.js>
//require <xataface/model/ListModel.js>
(function(){
	var $ = jQuery;
	var model = XataJax.load('xataface.model');
	var ListModel = model.ListModel;
	var Model = model.Model;
	var view = XataJax.load('xataface.view');
	var TableView = view.TableView;
	
	$(document).ready(function(){
		
		var tpl = $('#tpl1 table.template');
		
		var lm = new ListModel({});
		var table = new TableView({
			template : tpl,
			model : lm
		});
		
		
		
		var wrapper = $('#wrapper1');
		
		var data = [
		
			{ firstName: 'Steve', lastName: 'Hannah', age : 34, language : 'English' },
			{ firstName: 'Jennifer', lastName: 'Lee', age : 18, language : 'Chinese' },
			{ firstName: 'Barry', lastName: 'Bonds', age : 46, language : 'French' },
			{ firstName: 'Ben', lastName: 'Johnson', age : 51, language : 'Dutch' }
		];
		
		$.each(data, function(k,row){
			lm.add(row);
		});
		
		wrapper.append(table.el);
		table.update();
		
	});
})();