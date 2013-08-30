//require <jquery.packed.js>
(function(){
    var $ = jQuery;
    var pkg = XataJax.load('xataface.data');
    pkg.EntityType = EntityType;
    
    function EntityType(o){
        o = o || {};
        
        this.idProperty = null;
        this.name = null;
        this.foreignKeys = {};
        this.instances = {};
        this.entityClass = null;
        
    }
    
    $.extend(EntityType.prototype, {
        addForeignKey : function(/*String*/propertyName, /*EntityType*/entityType){
            this.foreignKeys[propertyName] = entityType;
        },
        removeForeignKey : function(/*String*/ propertyName){
            delete this.foreignKeys[propertyName];
        }
    });
})();