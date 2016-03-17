(function ($) {
  $('select').select2({
    placeholder: function () {
      $(this).data('placeholder');
    }
  });

  $('input[type="checkbox"]', '#iucn-search-form').bootstrapSwitch({
    offText: Drupal.t('or'),
    onText: Drupal.t('and'),
    size: 'mini'
  });

  $('.facets.invisible').removeClass('invisible');

  function submitSearchForm() {
    $('#iucn-search-form').submit();
  }

  $('select', '#iucn-search-form').change(submitSearchForm);
  $('input[type="checkbox"]', '#iucn-search-form').on('switchChange.bootstrapSwitch', submitSearchForm);
})(jQuery);
