<?php

namespace Drupal\wildlex_structure\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Exception\UndefinedLinkTemplateException;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;
use Drupal\Core\Url;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Field\FieldDefinitionInterface;



use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

use Drupal\link\LinkItemInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;



/**
 * Plugin implementation of the 'label_as_link_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "label_as_link_formatter",
 *   label = @Translation("Label as link"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class labelAsLinkFormatter extends EntityReferenceFormatterBase {
  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
        'link_field' => '',
        'target' => '',
      ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
      $elements['link_field'] = [
        '#title' => t('Get the link from another link field.'),
        '#type' => 'textfield',
        '#description' => 'machine name associated with the link type field',
        '#default_value' => $this->getSetting('link_field'),
      ];
      $elements['target'] = [
        '#type' => 'checkbox',
        '#title' => t('Open link in new window'),
        '#return_value' => '_blank',
        '#default_value' => $this->getSetting('target'),
      ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->getSetting('link_field') ? t('Link from <em>@field</em>', ['@field' => $this->getSetting('link_field')]) : t('No link');
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $uri = FALSE;
    $link_field = $this->getSetting('link_field');

    /* @var \Drupal\node\Entity\Node $node */
    $node = $items->getEntity();
    if ($node && $node->{$link_field}) {
      if ($node->{$link_field}->getFieldDefinition()->getType() == 'link') {
        foreach ($node->{$link_field} as $delta => $item) {
          // By default use the full URL as the link text.
          $uri = $this->buildUrl($item);
        }
      }
    }
      if ($this->getEntitiesToView($items, $langcode)) {
        foreach ($this->getEntitiesToView($items, $langcode) as $delta => $entity) {
          $label = $entity->label();
          if ($uri && !$entity->isNew()) {
            $elements[$delta] = [
              '#type' => 'link',
              '#title' => $label,
              '#url' => $uri,
              '#options' => $uri->getOptions(),
            ];

            if (!empty($items[$delta]->_attributes)) {
              $elements[$delta]['#options'] += ['attributes' => []];
              $elements[$delta]['#options']['attributes'] += $items[$delta]->_attributes;
              // Unset field item attributes since they have been included in the
              // formatter output and shouldn't be rendered in the field template.
              unset($items[$delta]->_attributes);
            }
          }
          else {
            $elements[$delta] = ['#plain_text' => $label];
          }
          $elements[$delta]['#cache']['tags'] = $entity->getCacheTags();
        }
      } else {
        if ($uri) {
          $elements[] = [
            '#type' => 'link',
            '#title' => $uri->toString(),
            '#url' => $uri,
            '#options' => $uri->getOptions(),
          ];

          if (!empty($items[$delta]->_attributes)) {
            $elements[$delta]['#options'] += ['attributes' => []];
            $elements[$delta]['#options']['attributes'] += $items[$delta]->_attributes;
            // Unset field item attributes since they have been included in the
            // formatter output and shouldn't be rendered in the field template.
            unset($items[$delta]->_attributes);
          }
        }
      }



    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity) {
    return $entity->access('view label', NULL, TRUE);
  }

  protected function buildUrl(LinkItemInterface $item) {
    $url = $item->getUrl() ?: Url::fromRoute('<none>');

    $settings = $this->getSettings();
    $options = $item->options;
    $options += $url->getOptions();

    // Add optional 'rel' attribute to link options.
    if (!empty($settings['rel'])) {
      $options['attributes']['rel'] = $settings['rel'];
    }
    // Add optional 'target' attribute to link options.
    if (!empty($settings['target'])) {
      $options['attributes']['target'] = $settings['target'];
    }
    $url->setOptions($options);

    return $url;
  }

}
