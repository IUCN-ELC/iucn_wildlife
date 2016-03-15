<?php

/**
 * @file
 * Contains \Drupal\file_list\Plugin\Field\FieldFormatter\ListFormatter.
 */

namespace Drupal\file_list\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\file\Plugin\Field\FieldFormatter\FileFormatterBase;

/**
 * Plugin implementation of the 'file_list' formatter.
 *
 * @FieldFormatter(
 *   id = "file_list",
 *   label = @Translation("List of files"),
 *   field_types = {
 *     "file"
 *   }
 * )
 */
class ListFormatter extends FileFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();

    if ($files = $this->getEntitiesToView($items, $langcode)) {
      $items = array();

      foreach ($files as $delta => $file) {
        $items[] = array(
          'value' => array(
            '#cache' => array(
              'tags' => $file->getCacheTags()
            ),
            '#file' => $file,
            '#theme' => 'file_link'
          )
        );
      }

      $elements[0] = array();

      if (!empty($items)) {
        $elements[0] = array(
          '#items' => $items,
          '#list_type' => 'ul',
          '#theme' => 'item_list__file_formatter_list'
        );
      }
    }

    return $elements;
  }

}
