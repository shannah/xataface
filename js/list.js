//require <jquery.packed.js>
(function(){

	jQuery(document).ready(function($){
	
		
	
		
		var resultList = $('table#result_list');
                
		var thead = resultList.children('thead');
		var tbody = resultList.children('tbody');
		var headingRow = $(thead).children('tr');
                var tfoot = resultList.children('tfoot');
                tfoot.children('tr.template').each(function(){
                    var self = this;
                    var footRow = $('<tr>');
                    
                    
                    // Index columns by column name
                    var columnIndex = {};
                    var label = $('.label', self).text();
                    $(self).children().each(function(){
                        var columnName = $(this).attr('data-column');
                        if ( columnName ){
                            columnIndex[columnName] = this;
                        }
                    });
                    var numLeadingBlanks = 0;
                    var foundFirstNonBlank = false;
                    $('tr',thead).first().children().each(function(){
                        var columnName = $(this).attr('data-column');
                        if ( columnName && columnIndex[columnName] ){
                            console.log("Appending for ");
                            console.log(this);
                            if ( !foundFirstNonBlank){
                                foundFirstNonBlank = true;
                                if ( numLeadingBlanks > 0 ){
                                    if (label ){
                                        $(footRow).append($('<th>').attr('colspan', numLeadingBlanks).text(label));
                                    } else {
                                        $(footRow).append($('<td>').attr('colspan', numLeadingBlanks));
                                    }
                                }
                            }
                            $(footRow).append($(columnIndex[columnName]).clone());
                            
                            
                        } else {
                            if ( foundFirstNonBlank ){
                                $(footRow).append($('<td>'));
                            } else {
                                numLeadingBlanks++;
                            }
                        }
                    });
                    $(tfoot).append(footRow);
                    $(tfoot).show();
                    
                });
                tfoot.children('tr.template').remove();
                
		if ( typeof(window.xataface) == 'undefined' ) window.xataface = {};
		window.xataface.query = {};
		var queryJson = resultList.attr('data-xataface-query');
		if ( queryJson ) eval('window.xataface.query = '+queryJson+';');
		
		
		var searchRow = $('tr', thead).clone();
		headingRow.addClass('table-headings');
		searchRow.hide();
		$('th', searchRow).html('');
		$('th.searchable-column', searchRow).each(function(){
			$(this).html('<div><input class="column-search-field" name="'+$(this).attr('data-search-column')+'" type="text"/></div>');
			var fld = $('input', this);
			
			var q = xataface.query;
			if ( typeof(q[$(this).attr('data-search-column')]) != 'undefined' ){
				fld.val(q[$(this).attr('data-search-column')]);
			}
		});
		$('th', headingRow).each(function(){
			$('th[data-search-column="'+$(this).attr('data-search-column')+'"]', searchRow).each(function(){
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
						if ( $(this).attr('name') == $(th).attr('data-search-column') ){
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
		
		
		// Decorate the show/hide columns action
		$('li.show-hide-columns-action a').click(function(){
			var iframe = $('<iframe>')
				.attr('width', '100%')
				.attr('height', $(window).height() * 0.8)
				
				.on('load', function(){
					var winWidth = $(window).width() * 0.8;
					var width = Math.min(800, winWidth);
					$(this).width(width);
					//dialog.dialog("option" , "position", "center");
					
					var showHideController = iframe.contentWindow.xataface.controllers.ShowHideColumnsController;
					showHideController.saveCallbacks.push(function(data){
						data.preventDefault = true;
						dialog.dialog('close');
						window.location.reload(true);
					});
					
				})
				.attr('src', $(this).attr('href')+'&--format=iframe')
				.get(0);
				;
			var dialog = $("<div></div>").append(iframe).appendTo("body").dialog({
				autoOpen: false,
				modal: true,
				resizable: false,
				width: "auto",
				height: "auto",
				close: function () {
					$(iframe).attr("src", "");
				},
				buttons : {
					'Save' : function(){
						$('button.save', iframe.contentWindow.document.body).click();
					}
				},
				create: function(event, ui) {
				   $('body').addClass('stop-scrolling');
				 },
				 beforeClose: function(event, ui) {
				   $('body').removeClass('stop-scrolling');
				 }
			});
			/*jQuery(iframe).dialog({
				autoOpen : true,
				modal : true,
				resizable : false,
				
				width : "auto",
				height: "auto"
			});*/
			dialog.dialog("option", "title", "Show/Hide Columns").dialog("open");
			return false;
		});
	
	});
	
	
	
})();