//require <jquery.packed.js>
//require <xmp-js/xmp.iif.min.js>
(function() {
    var $ = jQuery;
    registerXatafaceDecorator(function() {
        $('img[attribution]').each(function() {
            console.log("found attribution");
            var src = $(this).attr('src');
            if (!src) {
                return;
            }
            console.log("src=", src);
            fetch(src).then(function(response) {
                return response.blob();
            }).then(function(blob) {
                return blob.arrayBuffer();
            }).then(function(buf){
                let xmp = new XMP(buf);
                //raw.value = xmp.find();
                console.log(JSON.stringify(xmp.parse(), null, 4));
            });
        });
    });
})();