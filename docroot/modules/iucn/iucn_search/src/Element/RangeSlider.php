<?php

/**
 * @file
 * Contains \Drupal\iucn_search\Element\RangeSlider.
 */

namespace Drupal\iucn_search\Element;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;

/**
 * Provides a form element for choosing a range.
 *
 * @FormElement("range_slider")
 */
class RangeSlider extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return array(
      '#theme' => 'range_slider',
      '#theme_wrappers' => array('container')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    $min = Html::escape($_GET['yearmin']);
    $max = Html::escape($_GET['yearmax']);
    $value = [
      'from' => !empty($min) ? $min : $element['#min'],
      'to' => !empty($max) ? $max : $element['#max']
    ];

    return $value;
  }

}
