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
          'drupalSettings' => [
              'series'=> [
                ["SHN",91],["FIN",51],["FJI",22],["FLK",4],["FSM",69],["FRO",70],
                ["NIC",66],["NLD",53],["NOR",7],["NAM",63],["VUT",15],["NCL",66],
                ["NER",34],["NFK",33],["NGA",45],["NZL",96],["NPL",21],["NRU",13],
                ["NIU",6],["COK",19],["XKX",32],["CIV",27],["CHE",65],["COL",64],
                ["CHN",16],["CMR",70],["CHL",15],["CCK",85],["CAN",76],["COG",20],
                ["CAF",93],["COD",36],["CZE",77],["CYP",65],["CXR",14],["CRI",31],
                ["CUW",67],["CPV",63],["CUB",40],["SWZ",58],["SYR",96],["SXM",31],
              ],
            ],
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

  public function courtDecisions(){

  }
}
