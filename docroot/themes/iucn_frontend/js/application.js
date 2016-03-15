jQuery('select').select2();

jQuery('select', '#iucn-search-form').change(function() {
    jQuery('#iucn-search-form').submit();
});