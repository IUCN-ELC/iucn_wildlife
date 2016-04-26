<?php

/**
 * @file
 * Contains \Drupal\iucn_search\Plugin\Block\SearchFiltersBlock.
 */

namespace Drupal\iucn_search\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Search filters' block.
 *
 * @Block(
 *   id = "search_filters",
 *   admin_label = @Translation("Search filters"),
 * )
 */
class SearchFiltersBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    $label = $config['label_display'] ? $config['label'] : NULL;
    $form = \Drupal::formBuilder()->getForm('Drupal\iucn_search\Form\SearchFiltersForm', $label);
    unset($form['form_build_id']);
    unset($form['form_id']);
    $form['#cache'] = ['max-age' => 0];
    return $form;
  }
}
