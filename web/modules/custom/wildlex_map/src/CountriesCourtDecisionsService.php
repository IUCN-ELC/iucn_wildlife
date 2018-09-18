<?php

namespace Drupal\wildlex_map;
use Drupal\Core\Entity\EntityManagerInterface;
use Solarium\QueryType\Select\Query\Query;
use Drupal\search_api\Query\QueryInterface;
use Drupal\search_api_solr\SolrConnector\SolrConnectorPluginManager;
use Drupal\Component\Utility\Html;
use Drupal\search_api\ParseMode\ParseModePluginManager;
use Drupal\search_api\Entity\Index;

/**
 * Class CountriesCourtDecisionsService.
 */
class CountriesCourtDecisionsService {

  /**
   * The parse mode manager.
   *
   * @var \Drupal\search_api\ParseMode\ParseModePluginManager
   */
  private $parseModeManager;

  /**
   * Class constructor.
   */
  public function __construct(ParseModePluginManager $parse_mode_manager) {
    $this->parseModeManager = $parse_mode_manager;
  }

  public function get(){
    /* @var $index \Drupal\search_api\IndexInterface */
    $index = Index::load('default_node_index');

    /* @var $query \Drupal\search_api\Query\QueryInterface */
    $query = $index->query();
    $query->addCondition('type', 'court_decision');

    $server = $index->getServerInstance();
    if ($server->supportsFeature('search_api_facets')) {
      $query->setOption('search_api_facets', [
        'iso' => [
          'field' => 'field_iso',
          'limit' => 0,
          'operator' => 'AND',
          'min_count' => 1,
          'missing' => TRUE,
        ],
        'country' => [
          'field' => 'field_country',
          'limit' => 0,
          'operator' => 'AND',
          'min_count' => 1,
          'missing' => TRUE,
        ],
      ]);
    }
    $results = $query->execute();
    $facets = $results->getExtraData('search_api_facets', []);

    $series = [];
    if (isset($facets['iso']) && $facets['iso'] && $countries = $facets['iso']) {
      foreach ($countries as $key => $country) {
        if (isset($facets['country'][$key])){
          $series[] = [
            $this->process(($country['filter']), '\"'),
            $country['count'],
            $this->process($facets['country'][$key]['filter']),
          ];
        }
      }
    }

    return $series;
  }

  protected function process($value = ''){
    return trim(($value), '\"');
  }
}
