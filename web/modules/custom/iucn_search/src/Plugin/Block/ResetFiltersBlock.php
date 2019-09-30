<?php

namespace Drupal\iucn_search\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;

/**
 * Provides a 'Reset filters block' block.
 *
 * @Block(
 *   id = "reset_filters_block",
 *   admin_label = @Translation("Reset filters block"),
 * )
 */
class ResetFiltersBlock extends BlockBase {

  public function build() {
    $form['actions']['reset'] = [
      '#attributes' => [
        'class' => [
          'btn',
          'btn-default',
          'btn-sm',
          'btn-block',
          'search-reset',
        ],
        'href' => Url::fromRoute(\Drupal::request()
          ->get(\Symfony\Cmf\Component\Routing\RouteObjectInterface::ROUTE_NAME))
          ->toString(),
      ],
      '#tag' => 'a',
      '#type' => 'html_tag',
      '#value' => $this->t('Reset all filters'),
    ];
    return $form;
  }

}
