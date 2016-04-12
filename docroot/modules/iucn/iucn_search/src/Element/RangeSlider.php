<?php

/**
 * @file
 * Contains \Drupal\iucn_search\Element\RangeSlider.
 */

namespace Drupal\iucn_search\Element;

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
      '#theme_wrappers' => array('form_element')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    $userInput = $form_state->getUserInput();

    $range = [
      'from' => $element['#min'],
      'to' => $element['#max']
    ];

    return isset($userInput['range']) ? $userInput['range'] : $range;
  }

}
