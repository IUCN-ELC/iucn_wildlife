jQuery('select').select2();

jQuery('select', '#iucn-search-form').change(submitSearchForm);
jQuery('input[type="checkbox"]', '#iucn-search-form').change(submitSearchForm);

function submitSearchForm() {
    jQuery('#iucn-search-form').submit();
}