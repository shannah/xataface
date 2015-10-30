/**
 * Global Javascript Functions included in all pages when the g2 
 * module is enabled.
 *
 * @author Steve Hannah <steve@weblite.ca>
 * Copyright (c) 2011 Web Lite Solutions Corp.
 * All rights reserved.
 */
//require <xataface/lang.js>
//require <jquery.packed.js>
//require <xatajax.util.js>
//require <xatajax.actions.js>
//require <xataface/modules/g2/advanced-find.js>
//require <jquery.floatheader.js>
(function(){
	var $ = jQuery;
	var _ = xataface.lang.get;
	
	/**
	 * Help to format the page when it is finished loading.  Attach listeners
	 * etc...
	 */
	$(document).ready(function(){
	
		// START Left column fixes
		/**
		 * We need to hide the left column if there is nothing in it.  Helps for 
		 * page layout.
		 */
		$('#dataface-sections-left-column').each(function(){
			var txt = $(this).text().replace(/^\W+/,'').replace(/\W+$/);
			if ( !txt && $('img', this).length == 0 ) $(this).hide();
		});
		
		$('#left_column').each(function(){
			var txt = $(this).text().replace(/^\W+/,'').replace(/\W+$/);
			if ( !txt && $('img', this).length == 0) $(this).hide();
		});
		
		// END Left column fixes
	
	
	
		// START Prune List Actions
		/**
		 * We need to hide the list actions that aren't relevant.
		 */
		var resultListTable = $('#result_list').get(0);
		
		if ( resultListTable ){
		   $(resultListTable).floatHeader({recalculate:true});
			var rowPermissions = {};
			$('input.rowSelectorCheckbox[data-xf-permissions]', resultListTable).each(function(){
				var perms = $(this).attr('data-xf-permissions').split(',');
				$.each(perms, function(){
					rowPermissions[this] = 1;
				});
			});
			// We need to remove any actions for which there are no rows that can be acted upon
			$('.result-list-actions li.selected-action').each(function(){
				var perm = $(this).children('a').attr('data-xf-permission');
				if ( perm && !rowPermissions[perm]){
					$(this).hide();
				}
				
			});
			
			
		}
			
		// END Prune List Actions


		// START Adjust List cell sizes
		/**
		 * We need to improve the look of the list view so we'll calculate some 
		 * appropriate sizes for the cells.
		 */
		 /*
		$('table.listing td.field-content, table.listing th').each(function(){
			if ( $(this).width() > 200 ){
				//alert($(this).width());
				
				var div = $('<div></div')
					.css({
						'white-space': 'normal',
						'height': '1em',
						'overflow': 'hidden',
						'padding':0,
						'margin':0
					});
				$(div).append($(this).contents());
				$(this).empty();
				$(this).append(div);
				$(this).css({
					'white-space':'normal !important'
				});
				//$(this).css('white-space','normal !important').css('height','1em').css('overflow','hidden');
				
			}
		});
		*/
		$('table.listing > tbody > tr > td span[data-fulltext]').each(function(){
		    var span = this;
		    $(span).addClass('short-text');
		    var moreDiv = null;
		    var td = $(this).parent();
		    while ( $(td).prop('tagName').toLowerCase() != 'td' ){
		        td = $(td).parent();
		    }
		    td = $(td).get(0);
		    $(td).css({
                        //position : 'relative',
                        //display: 'block'
                    });
		    var moreButton = $('<a>')
		        .addClass('listing-show-more-button')
		        .attr('href','#')
		        .html('...')
		        .click(showMore).
		        get(0);
		    var lessButton = $('<a href="#" class="listing-show-less-button">...</a>').click(showLess).get(0);
		    
		    function showMore(){
		        var width = $(td).width();
		        
		        if ( moreDiv == null ){
		            var divContent = null;
		            
		            var parentA = $(span).parent('a');
		            if ( parentA.size() > 0 ){
		                
		                divContent = parentA.clone();
		                $('span', divContent)
		                    .removeClass('short-text')
		                    .removeAttr('data-fulltext')
		                    .text($(span).attr('data-fulltext'));
		            } else {
		                divContent = $(span).clone();
		                divContent.removeClass('short-text').text($(span).attr('data-fulltext'));
		            }
		                
		            var divWidth = width-$(moreButton).width()-10;
		            moreDiv = $('<div style="white-space:normal;"></div>')
		                .css('width', divWidth)
		                .append(divContent)
		                .addClass('full-text')
		                .get(0);
		            $(td).prepend(moreDiv);
		        }
		        $(td).addClass('expanded');
		        
		        
		        return false;
		        
		    }
		    
		    function showLess(){
		        $(td).removeClass('expanded');
		        return false;
		    }
		    $(td).append(moreButton);
		    $(td).append(lessButton);
		});
		$('table.listing td.row-actions-cell').each(function(){
		
			var reqWidth = 0;
			$('.row-actions a', this).each(function(){
				reqWidth += $(this).outerWidth(true);
			});
			
			$(this).width(reqWidth);
			$(this).css({
				padding: 0,
				margin: 0,
				'padding-right': '5px',
				'padding-top': '3px'
			});
			
		});


		// END Adjust List Cell Sizes
		
		
		// START Set Up Drop-Down Actions
		/**
		 * Some of the actions are drop-down menus.  We need to attach the 
		 * appropriate behaviors to them and also show the corrected "selected"
		 * state depending on which action or mode is currently selected.
		 */
		$(".xf-dropdown a.trigger")
			.each(function(){
				var atag = this;
				$(this).parent().find('ul li.selected > a').each(function(){
					$(atag).append(': '+$(this).text());
					$(atag).parent().addClass('selected');
				});
			})
			.append('<span class="arrow"></span>')
			.click(function() { //When trigger is clicked...
			
				var atag = this;
				//Following events are applied to the subnav itself (moving subnav up and down)
				if ( $(this).hasClass('menu-visible') ){
					$(this).removeClass('menu-visible');
					$(this).parent().find(">ul").slideUp('slow'); //When the mouse hovers out of the subnav, move it back up
					$('body').unbind('click.xf-dropdown');
				} else {
					$(this).addClass('menu-visible');
					$(this).parent().find(">ul")
						.each(function(){
							if ( $(atag).hasClass('horizontal-trigger') ){
								//alert($(atag).offset().top);
								var pos = $(atag).position();
								$(this)
									.css('top',0)
									.css('left', 20)
									;
									
								//$(this).offset({top: pos.top-100, left: pos.left+$(atag).outerWidth()});
								
							}
							$(this).css('z-index', 10000);
						
						})
						.slideDown('fast', function(){
							$('body').bind('click.xf-dropdown', function(){
								$(atag).trigger('click');
							});
						
						}).show(); //Drop down the subnav on click
					
				}
				return false;
				
	
			//Following events are applied to the trigger (Hover events for the trigger)
			})
			.hover(function() { 
					$(this).addClass("subhover"); //On hover over, add class "subhover"
				}, 
				function(){	//On Hover Out
					$(this).removeClass("subhover"); //On hover out, remove class "subhover"
				}
			);
		
		
		// END Set up Drop-down Actions
		
		
		// START PRUNE List actions further
		/**
		 * We previously pruned the list actions based on permissions.  Now we're going 
		 * to prunt them if there are no checkboxes. 
		 */
		//check to see if there are any checkboxes available to select
		var hasResultListCheckboxes = XataJax.actions.hasRecordSelectors($('.resultList'));
		var hasRelatedListCheckboxes = XataJax.actions.hasRecordSelectors($('.relatedList'));
		
		
		$('.selected-action a')
			.each(function(){
				if ( !hasResultListCheckboxes ){
					$(this).parent().hide();
				}
			})
			.click(function(){
				XataJax.actions.handleSelectedAction(this, '.resultList');
				return false;
			}
		);
		
		$('.related-selected-action a')
			.each(function(){
				if ( !hasRelatedListCheckboxes ){
					$(this).parent().hide();
				}
			})
			.click(function(){
				XataJax.actions.handleSelectedAction(this, '.relatedList');
				return false;
			}
		);
		
		// END PRUNE List actions further
		
		
		// Handler to set the size of the button bars and stay in correct place
		// when scrolling
		$('.xf-button-bar').each(function(){
			var bar = this;
			var container = $(bar).parent();
			var containerOffset = $(container).offset();
			if ( containerOffset  == null ) containerOffset = {left:0, top:0};
			var parentWidth = $(container).width();
			var rightBound = containerOffset.left+parentWidth;
			var windowWidth = $(window).width();
			var pos = $(this).offset();
			var left = pos.left;
			var screenWidth = $(window).width();
			//alert(screenWidth);
			var outerWidth = $(this).outerWidth();
			var excess = outerWidth+pos.left-screenWidth;
			if ( excess > 0 ){
				var oldWidth = $(this).width();
				$(this).width(oldWidth-excess);
				var newWidth = oldWidth-excess;
			}
			//$(this).outerWidth(screenWidth-pos.left);
			
			$(window).scroll(function(){
			
				var container = $(bar).parent();
				var containerOffset = $(container).offset();
				if ( containerOffset == null ) containerOffset = {left:0, top:0};
				var leftMost = containerOffset.left;
				var rightMost = leftMost + $(container).innerWidth();
				
				var currMarginLeft = $(bar).css('margin-left');
				
				var scrollLeft = $(window).scrollLeft();
				
				
				if ( scrollLeft < left ){
					$(bar).css('margin-left', -30);

					$(bar).width(Math.min(newWidth+scrollLeft, $(container).innerWidth()-10));
				} else if ( scrollLeft < excess + 60 ){
					$(bar).css('margin-left', scrollLeft-left-30);
					
				}
				
			});
			
		});
		
		
		// Make sure the list view menu doesn't show up if there's only 
		// one option in it
		$('.list-view-menu').each(function(){
			var self = this;
			if ( $('.action-sub-menu', this).children().size() < 2 ){
				$(self).hide();
			}
		
		});
		
		
		// If there is only one collapsible sidebar in a form, then we remove it
		$('form h3.Dataface_collapsible_sidebar').each(function(){
			var siblings = $(this).parent().parent().find('>div.xf-form-group-wrapper >h3.Dataface_collapsible_sidebar:visible');
			if ( siblings.size() <= 1 ) $(this).hide();
		});
		
		
		$('.xf-save-new-related-record a').click(function(){
			$('form input[name="-Save"]').click();
			return false;
		});
		
		$('.xf-save-new-record a').click(function(){
			$('form input[name="--session:save"]').click();
			return false;
		});
		
		
		// START Result Controller
		/**
		 * We are handling the result controller differently in this version.
		 * We provide a popup that allows the user to change the start and limit
		 * fields with a popup dialog.
		 */
		
		$('.result-stats').each(function(){
			if ( $(this).hasClass('details-stats') ) return;
			var resultStats = this;
                        var isRelated = $(resultStats).hasClass('related-result-stats');
			var start = $('span.start', this).text().replace(/^\W+/,'').replace(/\W+$/);
			var end = $('span.end', this).text().replace(/^\W+/,'').replace(/\W+$/);
			var found = $('span.found', this).text().replace(/^\W+/,'').replace(/\W+$/);
			var limit = $('.limit-field input').val();
			
			start = parseInt(start)-1;
			end = parseInt(end);
			found = parseInt(found);
			limit = parseInt(limit);

			$(this).css('cursor', 'pointer');
			
			$(this).click(function(){
				
				var div = $('<div>')
					.addClass('xf-change-limit-dialog')
					;
					
				var label = $('<p>Show <input class="limitter" type="text" value="'+(limit)+'" size="2"/> per page starting at <input type="text" value="'+start+'" class="starter" size="2"/> </p>');
				$('input.limitter', label).change(function(){
				
					var query = XataJax.util.getRequestParams();
                                        var limitParam = '-limit';
                                        if ( isRelated ){
                                            limitParam = '-related:limit';
                                        }
					query[limitParam] = $(this).val();
					window.location.href = XataJax.util.url(query);
				}).css({
					'font-size': '12px'
				});
				$('input.starter', label).change(function(){
				
					var query = XataJax.util.getRequestParams();
                                        var skipParam = '-skip';
                                        if ( isRelated ){
                                            skipParam = '-related:skip';
                                        }
					query[skipParam] = $(this).val();
					window.location.href = XataJax.util.url(query);
				}).css({
					'font-size': '12px'
				});
				
				div.append(label);
				var offset = $(resultStats).offset();
				
				
				
				$('body').append(div);
				
				$(div).css({
					position: 'absolute',
					top: offset.top+$(resultStats).height(),
					left: Math.min(offset.left, $(window).width()-275),
					'background-color': '#bbccff',
					'z-index': 1000,
					'padding': '2px 5px 2px 10px',
					'border-radius': '5px'
				});
				$(div).show();
				$(div).click(function(e){
					e.preventDefault();
					e.stopPropagation();
				});
				
				function onBodyClick(){
					$(div).remove();
					$('body').unbind('click', onBodyClick);
				}
				setTimeout(function(){
					$('body').bind('click', onBodyClick);
				}, 1000);
				
				
			});
			
		});
		
		
		$('.details-stats').each(function(){
			var resultStats = this;
			var cursor = $('span.cursor', this).text();
			var found = $('span.found', this).text();
			cursor = parseInt(cursor);
			found = parseInt(found);
			$(this).click(function(){
				
				var div = $('<div>')
					.addClass('xf-change-limit-dialog')
					;
					
				var label = $('<p>Show <input class="limitter" type="text" value="'+(cursor)+'" size="2"/> of '+found+' </p>');
				$('input.limitter', label).change(function(){
				
					var query = XataJax.util.getRequestParams();
					query['-cursor'] = parseInt($(this).val())-1;
					window.location.href = XataJax.util.url(query);
				}).css({
					'font-size': '12px'
				});
				
				
				div.append(label);
				var offset = $(resultStats).offset();
				
				
				
				$('body').append(div);
				
				$(div).css({
					position: 'absolute !important',
					top: offset.top+$(resultStats).height(),
					left: Math.min(offset.left, $(window).width()-150),
					'background-color': '#bbccff',
					'z-index': 1000,
					'padding': '2px 5px 2px 10px',
					'border-radius': '5px'
				});
				$(div).show();
				$(div).click(function(e){
					e.preventDefault();
					e.stopPropagation();
				});
				
				function onBodyClick(){
					$(div).remove();
					$('body').unbind('click', onBodyClick);
				}
				setTimeout(function(){
					$('body').bind('click', onBodyClick);
				}, 1000);
				
				
			})
			.css('cursor', 'pointer')
			;
			
		
		});
		
		// END Result Controller
		
		// Handle search
		
		(function(){
			var searchField = $('.xf-search-field').parents('form').submit(function(){
			    $(this).find(':input[value=""]').each(function(){
				    if ($(this).val() === '') {
				        $(this).attr('disabled', true);
				    }
				});
			});
		})();
		
		
		
		// Handle navigation storage.
		(function(){
			if ( typeof(sessionStorage) == 'undefined' ){
				sessionStorage = {};
			}
			
			
			function parseString(str){
				var parts = str.split('&');
				var out = [];
				$.each(parts, function(){
					var kv = this.split('=');
					out[decodeURIComponent(kv[0])] = decodeURIComponent(kv[1]);
				});
				return out;
			}
			
			var currTable = $('meta#xf-meta-tablename').attr('content');
			
			if ( currTable ){
				var currSearch = $('meta#xf-meta-search-query').attr('content');
				var currSearchUrl = window.location.href;
				var searchSelected = false;
				if ( !currSearch ){
					currSearch = sessionStorage['xf-currSearch-'+currTable+'-params'];
					currSearchUrl = sessionStorage['xf-currSearch-'+currTable+'-url'];
					
				} else {
					searchSelected = true;
					sessionStorage['xf-currSearch-'+currTable+'-params'] = currSearch;
					sessionStorage['xf-currSearch-'+currTable+'-url'] = currSearchUrl;
					
				}
				if ( currSearch ){
					var item = $('<li>');
					if ( searchSelected ) item.addClass('selected');
					var a = $('<a>')
						.attr('href', currSearchUrl)
						.attr('title', _('themes.g2.VIEW_SEARCH_RESULTS', 'View Search results'))
						.text(_('themes.g2.SEARCH_RESULTS', 'Search Results'));
					item.append(a);
					
					$('.tableQuicklinks').append(item);
				}
				
				
				
				var currRecord = $('meta#xf-meta-record-title').attr('content');
				var currRecordUrl = window.location.href;
				var recordSelected = false;
				if ( !currRecord ){
					currRecord = sessionStorage['xf-currRecord-'+currTable+'-title'];
					currRecordUrl = sessionStorage['xf-currRecord-'+currTable+'-url'];
					
				} else {
					recordSelected = true;
					sessionStorage['xf-currRecord-'+currTable+'-title'] = currRecord;
					sessionStorage['xf-currRecord-'+currTable+'-url'] = currRecordUrl;
					
				}
				
				
				// Record the parent record when clicking on related links.  This is used
				// by the navigator
				var currRecordId = $('meta#xf-meta-record-id').attr('content');
				if ( currRecordId ){
					(function(){

						$('a.xf-related-record-link[data-xf-related-record-id]').click(function(){
							//alert('here');
							var idKey = 'xf-parent-of-'+$(this).attr('data-xf-related-record-id');
							var idUrl = 'xf-parent-of-url-'+$(this).attr('data-xf-related-record-id');
							var idTitle = 'xf-parent-of-title-'+$(this).attr('data-xf-related-record-id');
							sessionStorage[idKey] = currRecordId;
							sessionStorage[idUrl] = currRecordUrl;
							sessionStorage[idTitle] = currRecord;
							
							return true;
							
						});
					
					})();
					
					
					
					
				}
				
				
				
				
				if ( currRecord ){
					var isChildRecord = false;
					if ( currRecordId ){
						(function(){
						
							var idKey = 'xf-parent-of-'+currRecordId;
							var idUrl = 'xf-parent-of-url-'+currRecordId;
							var idTitle = 'xf-parent-of-title-'+currRecordId;
							//sessionStorage[idKey] = currRecordId;
							//sessionStorage[idUrl] = currRecordUrl;
							//sessionStorage[idTitle] = currRecord;
						
						
							if ( sessionStorage[idUrl] ){
								var item = $('<li>');
								//if ( recordSelected ) item.addClass('selected');
								var a = $('<a>')
									.attr('href', sessionStorage[idUrl])
									.attr('title', sessionStorage[idTitle])
									.text(sessionStorage[idTitle]);
								item.append(a);
								
								$('.tableQuicklinks').append(item);
								isChildRecord = true;
							}
						
						})();
					
					
					}
				
				
					var item = $('<li>');
					if ( recordSelected ) item.addClass('selected');
					var a = $('<a>')
						.attr('href', currRecordUrl)
						.attr('title', currRecord)
						.text(currRecord);
					if ( isChildRecord ){
						$(a).addClass('xf-child-record');
					}
					item.append(a);
					
					$('.tableQuicklinks').append(item);
				}
				
				
				
				var g2 = XataJax.load('xataface.modules.g2');
				var advancedFindForm = new g2.AdvancedFind({});
					
				function handleShowAdvancedFind(){
					advancedFindForm.show();
					//$(this).text('Hide Advanced Search');
					$(this).addClass('expanded').removeClass('collapsed');
					$(this).unbind('click', handleShowAdvancedFind);
					$(this).bind('click', handleHideAdvancedFind);
				};
				
				function handleHideAdvancedFind(){
					advancedFindForm.hide();
					//$(this).text('Advanced Search');
					$(this).addClass('collapsed').removeClass('expanded');
					$(this).unbind('click', handleHideAdvancedFind);
					$(this).bind('click', handleShowAdvancedFind);
				}
				
				$('a.xf-show-advanced-find').bind('click', handleShowAdvancedFind);
				
				
				
				
				
			}
		})();
		
		
		
		
		
	
				
			
			
		
		
		
	});
	
	
	
	
	
	
})();