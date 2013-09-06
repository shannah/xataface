//require <jquery.packed.js>
//require <jquery-ui.min.js>
//require-css <jquery-ui/jquery-ui.css>
//require <RecordBrowser/RecordBrowser.js>
(function(){
    var $ = jQuery;
    registerXatafaceDecorator(function(root){
        $("a.xf-checkbox-widget-other-link").each(function(){
            var tablename = $(this).attr('data-table-name');
            var relname = $(this).attr('data-relationship-name');
            var fldname = $(this).attr('data-field-name');
            var keys = eval($(this).attr('data-keys'));
            var btn = this;
            $(this).RecordDialog({
                table: tablename,
                callback: function(data){
                    var val = [];
                    for ( var i=0; i<keys.length; i++){
                        val.push(encodeURIComponent(keys[i])+'='+encodeURIComponent(data[keys[i]]));
                    }
                    val = val.join('&');
                    fldname = relname+'['+val+']';


                    $(btn).before('<input type="checkbox" name="'+fldname+'" value="'+val+'" checked="1"/>'+data["__title__"]+'<br/>');
                }
            });
        });
    });
})();