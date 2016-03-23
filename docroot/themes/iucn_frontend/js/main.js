(function ($) {
  $.fn.select2.amd.define('select2/data/custom', [
    'select2/data/array',
    'select2/selection/placeholder',
    'select2/utils'
  ], function (ArrayData, Placeholder, Utils) {
    var CustomData = function ($element, options) {
      CustomData.__super__.constructor.call(this, $element, options);
    };

    Utils.Extend(CustomData, ArrayData);

    Placeholder.prototype.update = function (decorated, data) {
      var $placeholder = this.createPlaceholder(this.placeholder);

      this.$selection.find('.select2-selection__rendered').append($placeholder);

      return decorated.call(this, data);
    };

    return CustomData;
  });

  var CustomData = $.fn.select2.amd.require(('select2/data/custom'));

  $('select').select2({
    dataAdapter: CustomData,
    placeholder: function () {
      $(this).data('placeholder');
    }
  });

  $('.select2-selection').on('click', '.select2-selection__choice__remove', function (event) {
    event.stopPropagation();
  });

  $('#iucn-search-form').on('reset', function (event) {
    event.preventDefault();

    var $this = $(this);

    $('select', $this).val('');
    $this.get(0).reset();
    $this.submit();
  });

  $('input[type="checkbox"]', '#iucn-search-form').bootstrapSwitch({
    labelWidth: 9,
    offText: Drupal.t('or'),
    onText: Drupal.t('and'),
    size: 'mini'
  });

  $('.search-facets').removeClass('invisible');

  function submitSearchForm() {
    $('#iucn-search-form').submit();
  }

  $('select', '#iucn-search-form').change(submitSearchForm);
  $('input[type="checkbox"]', '#iucn-search-form').on('switchChange.bootstrapSwitch', submitSearchForm);
})(jQuery);
