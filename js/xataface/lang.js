(function(){
    window['xataface'] = window['xataface'] || {};
    var xataface = window.xataface;
    var strings = {};
    
    xataface.lang = {
        'set' : function(key,val){
            if ( typeof(val) === 'undefined' && typeof(key) === 'object' ){
                for ( var i in key ){
                    xataface.lang.set(i, key[i]);
                }
            } else {
                strings[key] = val;
            }
        },
        
        'get' : function(key, defaultVal){
            if ( typeof(key) === 'object' ){
                for ( var i in key ){
                    key[i] = xataface.lang.get(i, key[i]);
                }
                return key;
            }
            if ( typeof(strings[key]) !== 'undefined' ){
                return strings[key];
            }
            return defaultVal;
        }
        
    
    };
    
    if ( typeof(xataface['__strings__']) === 'object' ){
        xataface.lang.set(xataface['__strings__']);
        xataface['__strings__'] = {};
    }

})();