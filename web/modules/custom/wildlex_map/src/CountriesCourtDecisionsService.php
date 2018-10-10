<?php

namespace Drupal\wildlex_map;

use Drupal\search_api\ParseMode\ParseModePluginManager;
use Drupal\search_api\Entity\Index;
use Drupal\Core\Database\Connection;
use Symfony\Component\HttpFoundation\RequestStack;

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
   * The database connection to use.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  protected $server;

  protected $index;



  /**
   * Class constructor.
   */
  public function __construct(ParseModePluginManager $parse_mode_manager, Connection $connection, RequestStack $request_stack) {
    $this->parseModeManager = $parse_mode_manager;
    $this->connection = $connection;
    $this->requestStack = $request_stack;
    /* @var $index \Drupal\search_api\IndexInterface */
    $this->index = Index::load('default_node_index');

    $this->server = $this->index->getServerInstance();
  }

  public function getRequest(){
    $keys = $this->requestStack->getCurrentRequest()->query->keys();
    var_dump($keys);
    $index_keys = $this->index->getFields();
    var_dump($index_keys);
    var_dump(array_intersect($keys,$index_keys));
  }



  public function get(){

    $this->getRequest();

    /* @var $query \Drupal\search_api\Query\QueryInterface */
    $query = $this->index->query();
    $query->addCondition('type', 'court_decision');
    //$query->addCondition('field_type_of_text', [6,1516], 'IN');
    //$query->addCondition('field_type_of_text', 1516);



    /*foreach ($index->getFields() as $k=>$v){
      echo "$k<br/>";
    }
    die();*/


    if ($this->server->supportsFeature('search_api_facets')) {
      $query->setOption('search_api_facets', [
        'iso' => [
          'field' => 'field_iso',
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
    $countriesIso = $this->countriesIso();

    if (isset($facets['iso']) && $facets['iso'] && $countries = $facets['iso']) {
      foreach ($countries as $key => $country) {
         $iso = $this->process(($country['filter']), '\"');
          $series[] = [
            $iso,
            $country['count'],
            (isset($countriesIso[$iso]) ? $countriesIso[$iso]['entity_id'] : NULL),
          //(isset($countriesIso[$iso]) ? $countriesIso[$iso]['name'] : NULL),
          ];
      }
    }
    return $series;
  }

  protected function process($value = ''){
    return trim(($value), '\"');
  }

  protected function countriesIso(){
    /** @var $query \Drupal\Core\Database\Query\Select */
    $query = $this->connection->select('taxonomy_term__field_iso','f');
    $query->fields('f', ['entity_id', 'field_iso_value']);
  //$query->fields('fd', ['name']);
  //$query->innerJoin('taxonomy_term_field_data', 'fd', "f.entity_id = fd.tid");
    $results = $query->execute()->fetchAllAssoc('field_iso_value', \PDO::FETCH_ASSOC);
    return $results;
  }

}
