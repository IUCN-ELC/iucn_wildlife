<?php

namespace Drupal\wildlex_map\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;

/**
 * Provides a 'Wildlex map' Block.
 *
 * @Block(
 *   id = "wildlex_map",
 *   admin_label = @Translation("Wildlex map"),
 * )
 */
class WildlexMapBlock extends BlockBase {
  public function build() {
    $content = [
      '#markup' => '<div id="wildlex_map_container"></div>',
      '#attached' => [
          'library' => [
            'wildlex_map/d3.js',
            'wildlex_map/topojson.js',
            'wildlex_map/datamaps.js',
            'wildlex_map/wildlex.map.js',
          ],
        ],
      ];
    return $content;
  }
}
