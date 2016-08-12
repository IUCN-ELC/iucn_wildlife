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
    $route = \Drupal::request()->get(\Symfony\Cmf\Component\Routing\RouteObjectInterface::ROUTE_NAME);
    switch ($route) {
      case 'iucn.search.legislation':
      case 'iucn.search.literature':
        $facets = [
          'field_country',
          'field_language_of_document',
        ];
        break;
      default:
        $facets = [];
    }
    $form = \Drupal::formBuilder()->getForm('Drupal\iucn_search\Form\SearchFiltersForm', $label, $facets);
    unset($form['form_build_id']);
    unset($form['form_id']);
    $form['#cache'] = ['max-age' => 0];
    return $form;
  }
}
