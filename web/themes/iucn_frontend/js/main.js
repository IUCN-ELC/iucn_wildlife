(function($, Drupal, drupalSettings) {
  Drupal.behaviors.search_filters = {
    attach: function (context, settings) {
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

      var $searchFilters = $('.region-sidebar-facets');
      var CustomData = $.fn.select2.amd.require(('select2/data/custom'));
      var $window = $(window);

      $('select.js-multiple-select', context).once('transformSelect2').each(function () {
        $(this).select2({
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
        });
      });
      // .removeClass('select2-hidden-accessible').addClass('sr-only');

      $('.select2-selection').on('click', '.select2-selection__choice__remove', function (event) {
        event.stopPropagation();
      });

      $searchFilters.on({
        reset: function (event) {
          event.preventDefault();

          var $this = $(this);

          $('select.js-multiple-select', $this).val('').trigger('change.select2');
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
            $('select.js-multiple-select', $searchFilters).each(function () {
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

      $('.bef-link-active').addClass('strong');
      var sortText = $('.bef-link-active').text();
      sortText = sortText.substr(0, sortText.length - sortText.lastIndexOf('Sort'));
      sortText = sortText.replace('Sort', 'Sorted');
      $('.bef-link-active').once('sortSelect').text(sortText);

      var currentUrl = window.location.href;
      var deleteBetween = function(start, end, content) {
        return content.substring(0, start) + content.substring(end+1);
      };

      var rangeSlider = $('.facets-widget-range_slider', $searchFilters).once('transformRangeslider').each(function () {
        var facet_id = $(this).find('.item-list__range_slider').data('drupal-facet-id');
        var generalUrl = drupalSettings.facets.sliders[facet_id].url;
        var start = generalUrl.indexOf('year_period');

        // Check if the filter already exists and delete it.
        if(start != generalUrl.lastIndexOf('year_period')) {
          var last = generalUrl.indexOf('=', start);
          generalUrl =  deleteBetween(start, last, generalUrl);
        }
        var search = drupalSettings.facets.sliders[facet_id];
        var url = generalUrl;
        var from ='__range_slider_min__';
        var to = '__range_slider_max__';
        var today = new Date();
        $('.form-control.min').attr('min', search['min']).attr('max',today.getFullYear()).attr('value', search['values']['0']);
        $('.form-control.max').attr('min', search['min']).attr('max',today.getFullYear()).attr('value', (search['values']['1']== search['max'])?today.getFullYear():search['values']['1']);
        var slider = $(this).ionRangeSlider({
          'force_edges': true,
          'prettify_enabled': false,
          grid: true,
          type: 'double',
          min:search['min'],
          max:today.getFullYear(),
          to:search['values']['1'],
          from:search['values']['0'],
          onChange: function (data) {
            from = data.from;
            to = data.to;
            $('[name="year_period_min"]', $searchFilters).val(from);
            $('[name="year_period_max"]', $searchFilters).val(to);
          }
        }).data('ionRangeSlider');

         $('[type="number"]', $searchFilters).change(function () {
             var $this = $(this);

           if ($this.attr('name') === 'year_period_min') {
             from = $this.val();
             slider.update({
               from: from
             });
           } else if ($this.attr('name') === 'year_period_max') {
             to = $this.val();
             slider.update({
               to: to
             });
           }
         });


        $('.btn.btn-link').on('click', function() {
          url = url.replace('__range_slider_min__',  from).replace('__range_slider_max__', to);
          if(url.search('__range_slider_min__') && url.search('__range_slider_max__')==-1) {
            url = url.replace('__range_slider_min__',  search['values']['0'])
          }
          if(url.search('__range_slider_min__')==-1 && url.search('__range_slider_max__')) {
            url = url.replace('__range_slider_max__',  search['values']['1'])
          }
          window.location.href = (url==generalUrl)? currentUrl:url;
        });
      });

      var $searchForm = $('#search-form');

      $('.close', $searchForm).click(function () {
        $('.facets-widget-range_slider', $searchForm).val('');

        $searchForm.submit();
      });

      $('body').on('click', '#sliding-popup .agree-button', function () {
        $('#sliding-popup').remove();
      });

    }
  }})(jQuery, Drupal, drupalSettings);
