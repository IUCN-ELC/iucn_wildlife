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
    matcher: function (term, text) {
      if (text.selected === true) {
        return null;
      }

      if (term.term !== undefined && text.text.toLowerCase().indexOf(term.term.toLowerCase()) === -1) {
        return null;
      }

      text.term = term.term;

      return text;
    },
    placeholder: function () {
      $(this).data('placeholder');
    },
    templateResult: function (data) {
      var text = data.text;

      if (data.term !== undefined) {
        var index = text.toLowerCase().indexOf(data.term.toLowerCase());
        var length = data.term.length;

        text = text.substr(0, index) + '<em>' + text.substr(index, length) + '</em>' + text.substr(index + length);
      }

      var regex = /\(([0-9]+)\)/;
      var match = text.match(regex) ;

      if (match === null) {
        return data.text;
      }

      var html = '<span class="counter">' + match[1].trim() + '</span>' + text.substring(0, match.index);

      return $.parseHTML(html);
    },
    templateSelection: function (data) {
      var regex = /\(([0-9]+)\)/;
      var match = data.text.match(regex) ;
      var html = match === null ?
        '<span class="option">' + data.text + '</span>' :
        '<span class="option">' + data.text.substring(0, match.index) + '</span> <sup class="badge">' + match[1].trim() + '</sup>';

      return $.parseHTML(html);
    }
  }).removeClass('select2-hidden-accessible').addClass('sr-only');

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
        $('.form-select', $searchFilters).each(function () {
          var $select2 = $(this).data('select2');
          var width = $select2._resolveWidth($select2.$element, $select2.options.get('width'));

          if (width) {
            $select2.$container.width(width);
          }
        });

        timer = null;
      }, throttle);
    }
  });

  var rangeSlider = $('.range-slider', $searchFilters).ionRangeSlider({
    'force_edges': true,
    'prettify_enabled': false,
    grid: true,
    type: 'double',
    onChange: function (data) {
      $('[name="yearmin"]', $searchFilters).val(data.from);
      $('[name="yearmax"]', $searchFilters).val(data.to);
    }
  }).data('ionRangeSlider');

  $('[type="number"]', $searchFilters).change(function () {
    var $this = $(this);

    if ($this.attr('name') === 'yearmin') {
      rangeSlider.update({
        from: $this.val()
      });
    } else if ($this.attr('name') === 'yearmax') {
      rangeSlider.update({
        to: $this.val()
      });
    }
  });

  $('.clear-search-text', '#search-form').click(function () {
    $('input', '#search-form').val('').focus();
  });
})(jQuery);
