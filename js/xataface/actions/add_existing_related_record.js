//require <jquery.packed.js>
(function() {
    var $ = jQuery;
    
    function installAddNewButton() {
        // This will only be there if the user can add new records to the relationship.
        var addNewLink = $('#existing-related-record-form-add-new-link');
        if (addNewLink.length == 0) {
            return;
        }
        var btn = $('<button><i class="material-icons">add</i>Add New </button>');
        btn.on('click', function(evt) {
            evt.preventDefault();
            var search = window.location.search;
            search = search.replace(/&-action=existing_related_record/, '&-action=new_related_record');
            window.location.replace(search);
        });
        btn.insertAfter($('select.record_selector'));
       
    }
    
     $(document).ready(installAddNewButton);
})();