/**
 * @file
 * Transforms links into a dropdown list.
 */

(function ($) {

  'use strict';

  if (!String.prototype.startsWith) {
    String.prototype.startsWith = function(searchString, position) {
      position = position || 0;
      return this.indexOf(searchString, position) === position;
    };
  }

  Drupal.facets = Drupal.facets || {};
  Drupal.behaviors.facetsDropdownWidget = {
    attach: function (context, settings) {
      Drupal.facets.makeDropdown(context, settings);
    }
  };

  /**
   * Turns all facet links into a dropdown with options for every link.
   *
   * @param {object} context
   *   Context.
   * @param {object} settings
   *   Settings.
   */
  Drupal.facets.makeDropdown = function (context, settings) {


    // Find all dropdown facet links and turn them into an option.
    $('.js-facets-dropdown-links').once('facets-dropdown-transform').each(function () {
      var $ul = $(this);
      var $links = $ul.find('.facet-item a');
      var $dropdown = $('<select />');
      // Preserve all attributes of the list.
      $ul.each(function () {
        $.each(this.attributes, function (idx, elem) {
          $dropdown.attr(elem.name, elem.value);
        });
      });
      // Remove the class which we are using for .once().
      $dropdown.removeClass('js-facets-dropdown-links');

      $dropdown.addClass('facets-dropdown');
      $dropdown.addClass('js-facets-dropdown');

      if ($ul.hasClass('js-multiple-select')) {
        $dropdown.attr('multiple', 'multiple');
      }

      var id = $(this).data('drupal-facet-id');
      // Add aria-labelledby attribute to reference label.
      $dropdown.attr('aria-labelledby', "facet_" + id + "_label");
      var default_option_label = settings.facets.dropdown_widget[id]['facet-default-option-label'];
      $dropdown.attr('data-placeholder', default_option_label);

      // Add empty text option first.
      var $default_option = $('<option />')
        .attr('value', '')
        .text(default_option_label);
      $dropdown.append($default_option);

      $ul.prepend('<li class="default-option"><a href=".">' + default_option_label + '</a></li>');

      var has_active = false;
      $links.each(function () {
        var $link = $(this);

        var active = $link.hasClass('is-active');
        var $option = $('<option />')
          .attr('value', $link.attr('href'))
          .data($link.data());

        if ($link.attr('title')) {
          $option.attr('title', $link.attr('title'));
        }

        if (active) {
          has_active = true;
          // Set empty text value to this link to unselect facet.
          $default_option.attr('value', $link.attr('href'));
          $ul.find('.default-option a').attr("href", $link.attr('href'));
          $option.attr('selected', 'selected');
          $link.find('.js-facet-deactivate').remove();
        }
        $option.html($link.text());
        $dropdown.append($option);
      });

      // Go to the selected option when it's clicked.
      $dropdown.on('change.facets', function () {
        if ($(this).parent().find(".chosen-container").length !== 0) {
          var chosen = $(this).chosen();

          var value = chosen.val();
          if (typeof value !== 'string') {
            if (typeof value === 'object' && !$.isEmptyObject(value)) {
              if (!value[0].startsWith('/')) {
                value = value[1];
              }
              else {
                value = value[0];
              }
            }
            else {
              value = $(this).find('option').first().val();
            }
          }
          window.location.href = value;
        }
        else {
          var anchor = $($ul).find("[data-drupal-facet-item-id='" + $(this).find(':selected').data('drupalFacetItemId') + "']");
          if (anchor.length > 0) {
            $(anchor)[0].click();
          }
          else {
            $ul.find('.default-option a')[0].click();
          }
        }
      });

      // Replace links with dropdown.
      $ul.after($dropdown).hide();
      Drupal.attachBehaviors($dropdown.parent()[0], Drupal.settings);
    });
  };

})(jQuery);