<?php

/**
 * @file
 * Contains Drupal\field_computed\Plugin\Field\FieldType\ParagraphItem.
 */

namespace Drupal\field_computed\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'field_computed_paragraph' field type.
 *
 * @FieldType(
 *   id = "field_computed_paragraph",
 *   label = @Translation("Random Paragraph"),
 *   module = "field_computed",
 *   description = @Translation("This field prints a paragraph with random data."),
 *   default_widget = "field_computed_text",
 *   default_formatter = "field_computed_default"
 * )
 */
class ParagraphItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return array(
      'columns' => array(
        'value' => array(
          'size' => 'big',
          'type' => 'text'
        )
      )
    );
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('value')->getValue();

    return $value === NULL || $value === '';
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('string')
      ->setComputed(TRUE)
      ->setClass('\Drupal\field_computed\TextProcessed');

    return $properties;
  }

}
