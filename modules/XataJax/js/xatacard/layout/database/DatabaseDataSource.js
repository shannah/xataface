(function(){	
	
	function DatabaseDataSource(o){
		XataJax.extend(this, new DataSource(o));
		
		
		function loadRequest(req){
			var rs = req.getRecordSet();
			var q = {};
			var sort = rs.getSort();
			if ( sort.length > 0 ){
				q['-sort'] = sort.join(',');
			}
			$.extend(q, rs.getFilters());
			
			q['-schema'] = this.getSchema().getName();
			
			var start = 0;
			var end = 30;
			if ( req.getParameter('start') ){
				start = req.getParameter('start');
				end = start + 30;
			}
			if ( req.getParameter('end') ){
				end = req.getParameter('end');
			}
			var limit = end-start+1;
			q['-skip'] = start;
			q['-limit'] = limit;
			q['-action'] = 'xatacard_load_records';
			
			$.get(DATAFACE_SITE_HREF, q, function(response){
				
			});
		}
	}
})();