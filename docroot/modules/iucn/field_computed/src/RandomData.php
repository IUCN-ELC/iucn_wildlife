<?php

/**
 * @file
 * Contains \Drupal\field_computed\RandomData.
 */

namespace Drupal\field_computed;

use Drupal\Component\Utility\Random;
use Drupal\Core\TypedData\TypedData;

/**
 * A computed property for processing text with a format.
 */
class RandomData extends TypedData {

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    $random = new Random();

    return $random->paragraphs(1);
  }

}
