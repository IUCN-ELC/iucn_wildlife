jQuery('select').select2({
    placeholder: function(){
        $(this).data('placeholder');
    }
});

jQuery('input[type="checkbox"]', '#iucn-search-form').bootstrapSwitch({
    onText: Drupal.t('and'),
    offText: Drupal.t('or'),
    size: 'mini',
});

jQuery('.facets.invisible').removeClass('invisible');

jQuery('select', '#iucn-search-form').change(submitSearchForm);
jQuery('input[type="checkbox"]', '#iucn-search-form').on('switchChange.bootstrapSwitch', submitSearchForm);


function submitSearchForm() {
    jQuery('#iucn-search-form').submit();
}