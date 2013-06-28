//require <xataface/view/MasterDetailView.js>
//require <xataface/view/TableView.js>
//require <xataface/model/MasterDetailModel.js>
//require <xataface/model/ListModel.js>
(function(){
	var $ = jQuery;
	var model = XataJax.load('xataface.model');
	var ListModel = model.ListModel;
	var MasterDetailModel = model.MasterDetailModel;
	var Model = model.Model;
	var view = XataJax.load('xataface.view');
	var TableView = view.TableView;
	var ListView = view.ListView;
	var View = view.View;
	var MasterDetailView = view.MasterDetailView;
	
	$(document).ready(function(){
		
		var mtpl = $('#master-tpl table.template');
		var dtpl = $('#detail-tpl');
		
		var detailView = new View({
			el : dtpl.clone()
		});
		
		var lm = new ListModel({});
		var table = new TableView({
			template : mtpl,
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
		wrapper.append(detailView.el);
		table.update();
		
		
		// Now create the MasterDetailModel
		
		var masterDetailModel = new MasterDetailModel({
			listModel : lm
		});
		
		var masterDetailView = new MasterDetailView({
			masterView : table,
			detailView : detailView,
			model : masterDetailModel
		});
		
		
		
	});
})();