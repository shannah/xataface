//require <jquery.packed.js>
(function(){

	jQuery(document).ready(function($){
		
		var resultList = $('#result_list');
		var thead = $('thead', resultList);
		var tbody = $('tbody', resultList);
		var headingRow = $('tr', thead);
		if ( typeof(window.xataface) == 'undefined' ) window.xataface = {};
		window.xataface.query = {};
		var queryJson = resultList.attr('data-xataface-query');
		if ( queryJson ) eval('window.xataface.query = '+queryJson+';');
		
		
		var searchRow = $('tr', thead).clone();
		headingRow.addClass('table-headings');
		searchRow.hide();
		$('th', searchRow).html('');
		$('th.searchable-column', searchRow).each(function(){
			$(this).html('<div><input class="column-search-field" name="'+$(this).attr('data-column')+'" type="text"/></div>');
			var fld = $('input', this);
			
			var q = xataface.query;
			if ( typeof(q[$(this).attr('data-column')]) != 'undefined' ){
				fld.val(q[$(this).attr('data-column')]);
			}
		});
		$('th', headingRow).each(function(){
			$('th[data-column="'+$(this).attr('data-column')+'"]', searchRow).each(function(){
				$(this)
						.css('padding', 0)
						.css('margin', 0)
						;
						
					$('div', this).css({
						position: 'relative',
						margin: 0,
						padding: 0,
						width: 'auto',
						height: '1.5em'
						
					});
					var width = $('div', this).width();
					var height = $('div', this).height();
					$('input', this).css({
						position: 'absolute',
						
						padding: 0,
						margin: 0,
						top: 0,
						left: 0,
						right: 0,
						bottom: 0
						
						
					});
			});
		});
		
		$('th.searchable-column', headingRow).each(function(){
			//$(this).prepend('<img class="column-search-button" style="float:right; cursor:pointer" src="'+DATAFACE_URL+'/images/search_icon.gif"/>');
			//var btn = $('.column-search-button', this);
			var th = this;
			var width = $(th).width();
			
			$(th).click(function(){
				if ( searchRow.is(':visible') ){
					searchRow.hide();
					searchRow.css('display','none');
				} else {
					
					//searchRow.fadeIn(500, function(){
					searchRow.show();
					searchRow.css('display','');
					$(searchRow).each(function(){
						//alert('here');
						$('div', this).each(function(){
							var width = $(this).width();
							
							$('input', this).animate({width: width-2}, 500);
						});
						//alert('now');
					});
					//alert('there');
					$('th input', searchRow).each(function(){
						//alert($(this).attr('name'));
						//$(this).width($('a', th).width()*0.75);
						//alert('hello');
						if ( $(this).attr('name') == $(th).attr('data-column') ){
							//alert('world');
							//alert('here');
							$(this).focus();
							$(this).select();
							//alert('universe');
						}
						//alert('god');
					});
				}
				
			});
			
			$('a', th).click(function(e){
				//alert('about to stop');
				e.stopPropagation();
				//alert('stopped');
			});
		});
		
		$('th.searchable-column input', searchRow).keypress(function(e){
			var code = (e.keyCode ? e.keyCode: e.which);
			if ( code == 13 ){
				submitSearch();
				e.preventDefault();
			}
		});
		
		//searchRow.hide();
		thead.append(searchRow);
		
		
		function submitSearch(){
		
			var query = xataface.query;
			$('th.searchable-column input', searchRow).each(function(){
				query[$(this).attr('name')] = $(this).val();
			});
			
			// now let's dismantle the existing query
			var search = window.location.search;
			if ( !search ) search = '?';
			//alert(search.substring(1));
			
			var terms = search.substring(1).split('&');
			var out = [];
			var query2 = query;
			$.each(terms, function(){
				var parts = this.split('=');
				if ( parts.length <= 1 ) out.push(this);
				if ( typeof(query[decodeURIComponent(parts[0])]) != 'undefined' ){
					parts[1] = encodeURIComponent(query[decodeURIComponent(parts[0])]);
					delete query2[decodeURIComponent(parts[0])];
				}
				if ( parts[1] ){
					out.push(parts[0]+'='+parts[1]);
				}
				
				
				
				
			});
			
			$.each(query2, function(k,v){
				out.push(encodeURIComponent(k)+'='+encodeURIComponent(v));
			});
			
			search = '?'+out.join('&');
			window.location.search = search;
			
			
		
		}
		
		
		
	
	});
})();