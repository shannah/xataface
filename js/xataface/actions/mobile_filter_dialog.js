/**
 * Scripts for the mobile filter dialog.
 */
//Reference to the parent window since this is loading in an iframe
var win = window.parent;
var $ = win.jQuery;



// Variable to keep track of the query string.  
// It starts out with the current query string of the parent window.
var filterSearch = win.location.search;
if (!filterSearch) {
    filterSearch = '?';
}

/**
 * Shows the filter options for a specific field.
 * @param HTMLElement targetEl The element that triggered the event.
 */
function showOptions(targetEl) {
    var $ = win.jQuery;
    var options = $('>.xf-filter-options', $(targetEl).parent());
    var dialogContent = $('.dialog-content', options);
    options.css({'overflow-y' : '', 'bottom' : '', 'margin-bottom' : ''});
    dialogContent.css({'height' : ''});
    $(options).addClass('slidein');
    if (win.activeSheet.position === 'left' || win.activeSheet.position === 'right') {
        $(options).css('height', '100%');
    }
    win.activeSheet.pushState({
        back : function() {
            $(options).removeClass('slidein');
        },
        el : options.get(0),
        verticalMargins : 40
    });
    setTimeout(function() {
        if (isOverflown(options.get(0))) {
            options.css({'overflow-y' : 'scroll', 'bottom' : '0', 'margin-bottom' : '40px'});
            dialogContent.css({'height' : 'auto'});
        }
    }, 800);
    
}

/**
 * Apply the current filters to the parent page. 
 * This will reload the whole page so we will be leaving our filter session.
 */
function applyFilters() {
    if (win.activeSheet) {
        win.activeSheet.close();
    }
    win.jQuery('<div class="spin fillscreen"></div>').appendTo(win.document.body);
    //win.location.search=filterSearch;
    win.location.replace(filterSearch);
    return false;
}

/**
 * If the relatedFilter flag is set, then this will convert standard query parameters
 * into their related counterparts.
 * -search => -related%3Asearch
 * fieldname => -related%3Afieldname
 */
function encodeFieldName(field) {
    if (window.relatedFilter) {
        if (field.indexOf(':') > 0) {
            field = encodeURIComponent(field);
        }
        if (field == '-search') {
            field = '-related%3Asearch';
        } else if (field.indexOf('-related%3A') < 0) {
            field = '-related%3As%3A' + field;
        }
        
    }
    return field;
}

/**
 * Updates a field value in the current filterSearch string.
 * @param string field Field name
 * @param string value Field value
 */
function updateFieldValue(field, value) {
    field = encodeFieldName(field);
    var re = new RegExp('&'+field+'=[^&]*');
    filterSearch = filterSearch.replace(re, '');
    re = new RegExp('\\?'+field+'=[^&]*');
    filterSearch = filterSearch.replace(re, '?');
    if (value) {
        filterSearch += '&' + field + '=' + encodeURIComponent(value);
    }
}

/**
 * Updates the filter string with the current settings chosen by the user.
 * @param HTMLElement srcEl The element that triggered the change.
 * @param boolean update Whether to update the counts.  Default true.
 */
function updateFilters(srcEl, update) {
    if (update === undefined) {
        update = true;
    }
    var $ = win.jQuery;
    
    
    if ($(srcEl).hasClass('keyword-search-field')) {
        // This is the main -search field we treat it differently than the rest.
        updateFieldValue('-search', $(srcEl).val());
        if (update) {
            updateCounts();
        }
        return;
    }
    
    var wrapper = $(srcEl).parents('[data-field]').first();
    var filterType = $(wrapper).attr('data-filter-type');
    var field = encodeFieldName($(wrapper).attr('data-field'));
    var topListItem = $(wrapper).parent();
    var filterValueSpan = $('span.xf-filter-value', topListItem);
    
    
    /**
     * Update filter for filter type
     */
    function updateFilterFilter() {
        
        var selectedKeys = [];
        var selectedString = '';
        $('input[data-key]', wrapper).each(function() {
            if (!this.checked) {
                return;
            }
            selectedKeys.push(this.getAttribute('data-key'));
        });
        if (selectedKeys.length == 0) {
            if (filterSearch.indexOf('&'+field+'=') >= 0) {
                var re = new RegExp('&'+field+'=[^&]*');
            
                filterSearch = filterSearch.replace(re, '');
                if (selectedKeys.length > 0) {
                    filterValueSpan.text('('+selectedKeys.length+')');
                } else {
                    filterValueSpan.text('');
                }
            
                if (update) updateCounts();
                return;
            }
        }
        var selectedString = '=' + selectedKeys.join(' OR =');
        if (filterSearch.indexOf('&'+field+'=') >= 0) {
            var re = new RegExp('&'+field+'=[^&]*');
        
            filterSearch = filterSearch.replace(re, '');
        }
        filterSearch += '&'+field+'='+encodeURIComponent(selectedString);
        if (selectedKeys.length > 0) {
            filterValueSpan.text('('+selectedKeys.length+')');
        } else {
            filterValueSpan.text('');
        }
        if (update) updateCounts();
    }
    
    /**
     * Update filter for text type
     */
    function updateTextFilter() {
        var textInput = $('input.search-field', wrapper);
        var checkedOption = $('input.text-filter-option:checked', wrapper);
        var fieldVal = textInput.val();
        switch(checkedOption.val()) {
            case 'exact' :
                fieldVal = '=' + fieldVal;
                break;
            case 'startswith' :
                fieldVal = '~' + fieldVal + '%';
                break;
        
            case 'endswith' :
                fieldVal = '~%' + fieldVal;
                break;
        }
        updateFieldValue(field, fieldVal);
        if (fieldVal.length > 0) {
            filterValueSpan.text(': '+textInput.val());
        } else {
            filterValueSpan.text('');
        }
        if (update) updateCounts();
        
    }
    
    /**
     * Update filter for range type
     */
    function updateRangeFilter() {
        var minInput = $('input.range-min', wrapper);
        var maxInput = $('input.range-max', wrapper);
        var minVal = minInput.val();
        if (minVal === null || minVal === undefined) {
            minVal = '';
        }
        var maxVal = maxInput.val();
        if (maxVal === null || maxVal === undefined) {
            maxVal = '';
        }
        
        if (minInput.attr('type') == 'datetime-local') {
            minVal = minVal.replace('T', ' ');
        }
        if (maxInput.attr('type') == 'datetime-local') {
            maxVal = maxVal.replace('T', ' ');
        }
        
        var fieldVal = '';
        if (minVal !== '' && maxVal !== '') {
            fieldVal = minVal + '..' + maxVal;
        } else if (minVal !== '' && maxVal === '') {
            fieldVal = '>=' + minVal;
        } else if (maxVal !== '' && minVal === '') {
            fieldVal = '<=' + maxVal;
        }
        updateFieldValue(field, fieldVal);
        if (fieldVal.length > 0) {
            filterValueSpan.text(': '+fieldVal);
        } else {
            filterValueSpan.text('');
        }
        if (update) updateCounts();
    }
    
    /**
     * Update filter for filter vocabulary
     */
    function updateFilterVocabularyBox() {
        var fieldVal = $(srcEl).val();
        $('input.search-field, input.range-min, input.range-max', wrapper).val('');
        $('input.text-filter-option[value="contains"]', wrapper).each(function() {
            this.checked = true;
        });
        updateFieldValue(field, fieldVal);
        if (fieldVal.length > 0) {
            filterValueSpan.text(': '+$(srcEl).attr('data-option-value'));
        } else {
            filterValueSpan.text('');
        }
        if (update) updateCounts();
    }
    if ($(srcEl).hasClass('filter-vocabulary-box')) {
        updateFilterVocabularyBox();
    } else if (filterType == 'filter') {
        updateFilterFilter();
    } else if (filterType == 'text') {
        updateTextFilter();
    } else if (filterType == 'range' || filterType == 'min' || filterType == 'max') {
        updateRangeFilter();
    }
    
    
}

/**
 * Updates the counts.  This will trigger an ajax request to get the number of 
 * results there would be for the current filter selections.
 */
function updateCounts() {
    var search = filterSearch;
    if (!search) {
        search = '?';
    }
    var $ = win.jQuery;
    if (!$ || !$.get) return;
    
    if (search.indexOf('-action=') >= 0) {
        search = search.replace(/-action=[^&]*/, '-action=ajax_count_results');
    } else {
        search += '&-action=ajax_count_results';
    }
    $.get(search, function(res) {
        if (res && res.found) {
            document.querySelector('button.xf-apply-btn > .num-results').innerHTML = ''+res.found;

        }
    })
}

/**
 * Resets the filters - so there are no filters.
 */
function resetFilters() {
    var filterBoxes = document.querySelectorAll('input.filter-box');
    
    filterBoxes.forEach(function(input) {
        if (input.checked) {
            input.checked = false;
            updateFilters(input, false);
        }
    });
    var textFilterOptions = document.querySelectorAll('input.text-filter-option');
    textFilterOptions.forEach(function(input) {
        if (input.getAttribute('value') == 'contains') {
            input.checked = true;
        } else {
            input.checked = false;
        }
    });
    var textInputs = document.querySelectorAll('input.search-field');
    textInputs.forEach(function(input) {
        $(input).val('');
        updateFilters(input, false);
    });
    
    var rangeInputs = document.querySelectorAll('input.range-min, input.range-max');
    rangeInputs.forEach(function(input) {
        $(input).val('');
        updateFilters(input, false);
    });
    
    var filterVocabInputs = document.querySelectorAll('input.filter-vocabulary-box');
    filterVocabInputs.forEach(function(input) {
        if ($(input).attr('value') == '') {
            input.checked = true;
            updateFilters(input, false);
        } else {
            input.checked = false;
        }
    });
    
    var keywordSearches = document.querySelectorAll('input.keyword-search-field');
    keywordSearches.forEach(function(input) {
        $(input).val('');
        updateFilters(input, false);
    });
    
    updateCounts();
}

/**
 * Checks if element is overflown.
 */
function isOverflown(element) {
    
    if (element.scrollHeight > window.innerHeight + 40 || element.clientHeight > window.innerHeight + 40) {
        return true;
    }
    return false;
}

function checkCustomOption(srcEl) {
    var $ = win.jQuery;
    var wrapper = $(srcEl).parents('[data-field]').first();
    $('[value="custom"]', wrapper).each(function() {
        this.checked = true;
    });
}

// Add listeners to all of the input fields related to filtering to dynamically update
// the counts
var searchFields = document.querySelectorAll('input.search-field, input.range-min, input.range-max');
var timeoutHandle = null;
searchFields.forEach(function(input) {
    input.addEventListener('input', function(e) {
        if (timeoutHandle) {
            clearTimeout(timeoutHandle);
        }
        if ($(input).val()) {
            checkCustomOption(input);
        }
        timeoutHandle = setTimeout(function() {
            updateFilters(input);
        }, 500);
    });
});

var textFilterOptions = document.querySelectorAll('input.text-filter-option');
textFilterOptions.forEach(function(input) {
    input.addEventListener('click', function(e) {
        updateFilters(input);
    });
});

var clearButtons = document.querySelectorAll('i.clear-btn');
clearButtons.forEach(function(btn) {
    btn.addEventListener('click', function(e) {
        btn.nextElementSibling.value = '';
        updateFilters(btn.nextElementSibling);
    });
});

var keywordSearchFields = document.querySelectorAll('input.keyword-search-field');
keywordSearchFields.forEach(function(input) {
    input.addEventListener('input', function(e) {
        if (timeoutHandle) {
            clearTimeout(timeoutHandle);
        }

        timeoutHandle = setTimeout(function() {
            updateFilters(input);
        }, 500);
    });
});

updateCounts();