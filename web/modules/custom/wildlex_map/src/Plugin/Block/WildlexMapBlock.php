<?php

namespace Drupal\wildlex_map\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\wildlex_map\CountriesCourtDecisionsService;

/**
 * Provides a 'Wildlex map' Block.
 *
 * @Block(
 *   id = "wildlex_map",
 *   admin_label = @Translation("Wildlex map"),
 * )
 */
class WildlexMapBlock extends BlockBase implements ContainerFactoryPluginInterface {

  protected $courtDecisions;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, CountriesCourtDecisionsService $court_decisions) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->courtDecisions = $court_decisions;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('wildlex_map.countries_court_decisions')
    );
  }

  public function build() {

    $markup = <<<EOD
      <div id="wildlex_map">
        <div class="wildlex_map-buttons">
          <a href="#" class="btn btn-primary zoom-button" data-zoom="reset">reset</a>
          <a href="#" class="btn btn-primary zoom-button" data-zoom="out">zoom out</a>
          <a href="#" class="btn btn-primary zoom-button" data-zoom="in">zoom in</a>
          <!--<div id="zoom-info"></div>-->
        </div>
      </div>
EOD;

    $content = [
      '#markup' => $markup,
      '#attached' => [
          'drupalSettings' => [
              'series'=> $this->courtDecisions->get('court_decision'),
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

}
