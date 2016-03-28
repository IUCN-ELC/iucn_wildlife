(function ($) {
  $.fn.select2.amd.define('select2/data/custom', [
    'select2/data/array',
    'select2/selection/multiple',
    'select2/selection/placeholder',
    'select2/utils'
  ], function (ArrayData, MultipleSelection, Placeholder, Utils) {
    var CustomData = function ($element, options) {
      CustomData.__super__.constructor.call(this, $element, options);
    };

    Utils.Extend(CustomData, ArrayData);

    MultipleSelection.prototype.selectionContainer = function () {
      var $container = $(
        '<li class="select2-selection__choice">' +
          '<span class="select2-selection__choice__remove" role="presentation">' +
            '&times;' +
          '</span>' +
          '&nbsp;' +
        '</li>'
      );

      return $container;
    };

    Placeholder.prototype.update = function (decorated, data) {
      var $placeholder = this.createPlaceholder(this.placeholder);

      this.$selection.find('.select2-selection__rendered').append($placeholder);

      return decorated.call(this, data);
    };

    return CustomData;
  });

  var $searchFilters = $('#iucn-search-filters');
  var CustomData = $.fn.select2.amd.require(('select2/data/custom'));
  var $window = $(window);

  $('.form-select', $searchFilters).select2({
    dataAdapter: CustomData,
    placeholder: function () {
      $(this).data('placeholder');
    },
    templateResult: function (data) {
      var splits = data.text.split(' (');

      if (splits.length === 1) {
        return data.text;
      }

      var html = '<span class="counter">' + splits[1].split(')')[0] + '</span>' + splits[0];

      return $.parseHTML(html);
    },
    templateSelection: function (data, container) {
      var splits = data.text.split(' (');

      if (splits.length === 1) {
        return data.text;
      }

      var html = splits[0] + ' <sup class="badge">' + splits[1].split(')')[0] + '</sup>';

      return $.parseHTML(html);
    }
  });

  $('.select2-selection').on('click', '.select2-selection__choice__remove', function (event) {
    event.stopPropagation();
  });

  $searchFilters.on({
    reset: function (event) {
      event.preventDefault();

      var $this = $(this);

      $('.form-select', $this).val('').trigger('change.select2');
      $('.form-checkbox', $this).bootstrapSwitch('state', false, true);

      $this.get(0).reset();
      $this.submit();
    },
    submit: function () {
      var offset = $window.scrollTop();

      window.sessionStorage.setItem('offset', offset);
    }
  });

  $('input[type="checkbox"]', $searchFilters).bootstrapSwitch({
    labelWidth: 9,
    offText: Drupal.t('or'),
    onText: Drupal.t('and'),
    size: 'mini'
  });

  $('.search-submit', $searchFilters).hide();
  $('.search-filters', $searchFilters).removeClass('invisible');

  var offset = window.sessionStorage.getItem('offset');

  if (offset) {
    window.sessionStorage.removeItem('offset');

    $window.scrollTop(offset);
  }

  var submit = function () {
    $searchFilters.submit();
  };

  $('select', $searchFilters).change(submit);
  $('input[type="checkbox"]', $searchFilters).on('switchChange.bootstrapSwitch', submit);

  var throttle = 200;
  var timer;

  $window.resize(function () {
    if (!timer) {
      timer = setTimeout(function () {
        $('.form-select', $searchFilters).select2({
          width: 'resolve'
        });

        timer = null;
      }, throttle);
    }
  });
})(jQuery);
