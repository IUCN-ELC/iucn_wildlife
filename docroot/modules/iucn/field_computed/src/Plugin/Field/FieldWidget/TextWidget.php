<?php

/**
 * @file
 * Contains \Drupal\field_computed\Plugin\field\widget\TextWidget.
 */

namespace Drupal\field_computed\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'field_computed_text' widget.
 *
 * @FieldWidget(
 *   id = "field_computed_text",
 *   module = "field_computed",
 *   label = @Translation("Random Paragraph"),
 *   field_types = {
 *     "field_computed_paragraph"
 *   }
 * )
 */
class TextWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element += array(
      '#markup' => '<div class="description">' . $this->t('This field prints a paragraph with random data.') . '</div>',
      '#type' => 'item'
    );

    return array('value' => $element);
  }

}
