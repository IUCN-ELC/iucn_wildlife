<?php

/**
 * @file
 * Contains Drupal\field_computed\Plugin\Field\FieldFormatter\DefaultFormatter.
 */

namespace Drupal\field_computed\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'field_computed_default' formatter.
 *
 * @FieldFormatter(
 *   id = "field_computed_default",
 *   module = "field_computed",
 *   label = @Translation("Default"),
 *   field_types = {
 *     "field_computed_text"
 *   }
 * )
 */
class DefaultFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();

    foreach ($items as $delta => $item) {
      $elements[$delta] = array(
        '#cache' => array('max-age' => 0),
        '#tag' => 'p',
        '#type' => 'html_tag',
        '#value' => $item->value
      );
    }

    return $elements;
  }

}
