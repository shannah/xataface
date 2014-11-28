//require <xatajax.core.js>
//require <jquery.packed.js>
//require <RecordDialog/RecordDialog.js>
//require <xataface/IO.js>
(function(){
    var $ = jQuery;
    
    var RecordDialog = xataface.RecordDialog;
    try {
        // If we are inside a parent iframe already due to another record dialog
        // we will use the Record dialog from the parent window (risky??)
        if (xataface.RecordDialog.version === window.top.xataface.RecordDialog.version) {
            RecordDialog = window.top.xataface.RecordDialog;
        }

    } catch (e) {

    }
    
    var components = XataJax.load('xataface.components');
    components.Portlet = Portlet;
    
    /**
     * A component that wraps an HTML element to provide edit, delete, and add buttons
     * as appropriate.
     */
    function Portlet(selector, root, opts){
        this.opts = opts || {};
        this.root = root || $('body').get(0);
        this.selector = selector || '[data-record-id]';
       
    }
    
    $.extend(Portlet.prototype, {
        install : function(){
            var self = this;
            $(this.selector, this.root).each(function(){
                self.installElement(this);
            });
        },
        
        installElement : function(el){
            var self = this;
            
            switch ( el.tagName.toLowerCase() ){
                case 'tr':
                    //console.log(el);
                    self.installTr(el);
                    break;
                case 'li':
                    self.installLi(el);
                    break;
                case 'div':
                    self.installDiv(el);
                    break;

            }
        },
        
        installDiv : function(div){
            var table = $(div).attr('data-table-name');
            if ( table ){
                this.installAddButton(table, div);
            }
        },
        
        installTr : function(tr){
            var self = this;
            if ( $(tr).hasClass('can-edit') || $(tr).hasClass('can-delete')){
                // We need to add a column for editing and deleting.
                var buttonsCell = $(tr).children('td.portlet-buttons');
                if ( buttonsCell.length === 0 ){
                    // The buttons cell hasn't been added yet.  Add it now
                    var table = $(tr).parents('table').first();
                    var rows = $('> thead > tr, > tbody > tr, > tr', table);
                    rows.each(function(){
                        var btnTr = $('<td>').addClass('portlet-buttons');
                        $(this).append(btnTr);
                    });
                    
                }
            }
            var buttonsCell = $(tr).children('td.portlet-buttons');
            if ( $(tr).hasClass('can-delete') ){
                // Install delete button
                self.installDeleteButton($(tr).attr('data-record-id'), buttonsCell);
            }
            if ( $(tr).hasClass('can-edit') ){
                self.installEditButton($(tr).attr('data-record-id'), buttonsCell);
            }
            
            var tableEl = $(tr).parents('table').first();
            if ( tableEl.hasClass('can-add') && tableEl.attr('data-table-name') ){
                var buttonsEl = tableEl.parent().children('.portlet-buttons');
                if ( buttonsEl.length === 0 ){
                    buttonsEl = $('<div>').addClass('portlet-buttons');
                    tableEl.parent().append(buttonsEl);
                }
                console.log("installing add");
                self.installAddButton(tableEl.attr('data-table-name'), buttonsEl);
                
            }
        },
        
        installEditButton : function(recordId, parentEl){
            var self = this;
            var table = recordId.substr(0, recordId.indexOf('?'));
            var btn = $('<button>').click(function(){
                var dlg = new RecordDialog({
                    recordid: recordId,
                    table: table,
                    params : $.extend({}, self.opts.params, self.opts.editParams),
                    width : self.opts.dialogWidth,
                    height : self.opts.dialogHeight,
                    marginW : self.opts.dialogMarginW,
                    marginH : self.opts.dialogMarginH,
                    callback : function(res){
                        if ( res && res.__id__ ){
                            $(self).trigger('afterEdit', {
                                recordId : recordId,
                                response : res
                            });
                            $(self).trigger('afterSave', {
                                recordId : recordId,
                                response : res
                            });
                        } else {
                            alert('Failed to edit record: '+res.message);
                        }
                    }
				});
				dlg.display();
            }).text('Edit');
            $(parentEl).append(btn);
        },
        
        installDeleteButton : function(recordId, parentEl){
            var self = this;
            var btn = $('<button>').click(function(){
                if ( confirm('Are you sure you want to delete this record?') ){
                    xataface.IO.remove(recordId, function(res){
                        if ( res.code === 200 ){
                            $(self).trigger('afterDelete', {
                                recordId : recordId,
                                response : res
                            });
                        } else {
                            alert('Failed to delete record: '+res.message);
                        }
                    });
                }
            }).text('Delete');
            $(parentEl).append(btn);
        },
        
        installAddButton : function(table, parentEl){
            if ( $(parentEl).attr('data-add-button-added') ){
                return;
            } 
            var self = this;
            var btn = $('<button>').addClass('add-btn').click(function(){
                var dlg = new RecordDialog({
                    recordid: null,
                    table: table,
                    params : $.extend({}, self.opts.params, self.opts.newParams),
                    width : self.opts.dialogWidth,
                    height : self.opts.dialogHeight,
                    marginW : self.opts.dialogMarginW,
                    marginH : self.opts.dialogMarginH,
                    callback : function(res){

                        if ( res && res.__id__ ){
                            $(self).trigger('afterNew', {
                                recordId : res.__id__,
                                response : res
                            });
                            $(self).trigger('afterSave', {
                                recordId : res.__id__,
                                response : res
                            });
                        } else {
                            alert('Failed to edit record: '+res.message);
                        }
                    }
				});
				dlg.display();
            }).text((self.opts.addButtonLabel || 'Add New Record'));
            $(parentEl).attr('data-add-button-added',1);
            $(parentEl).append(btn);
        }
        
    });
    
    registerXatafaceDecorator(function(node){
        console.log('bar');
        $('table.xf-portlet, div.xf-portlet', node).each(function(){
            if ( this.tagName.toLowerCase() === 'table' ){
                var newParams = $(this).attr('data-new-params') || '{}';
                newParams = JSON.parse(newParams);
                
                var params = $(this).attr('data-params') || '{}';
                params = JSON.parse(params);
                
                var addButtonLabel = $(this).attr('data-add-button-label') || 'Add New Record';
                
                var portlet = new Portlet('tr[data-record-id]', this, {
                    newParams : newParams,
                    params : params,
                    addButtonLabel : addButtonLabel
                });
                
                
                $(portlet).bind('afterSave', function(){
                    window.location.reload(true);
                });
                $(portlet).bind('afterDelete', function(){
                    window.location.reload(true);
                });
                portlet.install();
            } else {
                
                var newParams = $(this).attr('data-new-params') || '{}';
                newParams = JSON.parse(newParams);
                
                var params = $(this).attr('data-params') || '{}';
                params = JSON.parse(params);
                
                var addButtonLabel = $(this).attr('data-add-button-label') || 'Add New Record';

                var portlet = new Portlet('div[data-table-name]', this, {
                    newParams : newParams,
                    params : params,
                    addButtonLabel : addButtonLabel
                    
                });
                
                $(portlet).bind('afterSave', function(){
                    window.location.reload(true);
                });
                $(portlet).bind('afterDelete', function(){
                    window.location.reload(true);
                });
                
                portlet.install();
            }
        });
    });
})();