(function($, Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.operator_switcher = {
    attach: function (context, settings) {
      var operator = '';
      $('.bootstrap-switch-container').once('operatorSwitcher').on('click', function() {
        var url = window.location.href;
        var facetId = $(this).find('input').attr('id');
        var connectElement = (url.indexOf("?") == -1)? '?':'&';

        if($(this).find('input').attr('data-switcher') == 'or'){
          operator = facetId + '_op=and';
        }
        else {
          operator = facetId +'_op=or';
        }
        if (url.search(facetId + '_op') == -1) {
          url += connectElement + operator;
        } else if (url.search(facetId + '_op=or') != -1) {
          url = url.replace(facetId + '_op=or', operator);
        } else {
          url = url.replace(facetId + '_op=and', operator);
        }
        window.location.href = url;
      });
    }
  };

})(jQuery, Drupal, drupalSettings);
