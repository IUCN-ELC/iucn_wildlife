<?php

namespace Drupal\wildlex_map;

use Drupal\search_api\ParseMode\ParseModePluginManager;
use Drupal\search_api\Entity\Index;
use Drupal\Core\Database\Connection;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class CountriesCourtDecisionsService.
 */
class CountriesMapService {

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

  /**
   * The search server index.
   *
   * @var \Drupal\search_api\IndexInterface
   */

  protected $index;
  /**
   * The server the index is attached to.
   *
   * @var \Drupal\search_api\ServerInterface
   */
  protected $server;


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
    $request = [];
    $keys = $this->requestStack->getCurrentRequest()->query->keys();
    $index_keys = $this->indexKeys();
    if ($request_keys = array_intersect($keys,$index_keys)) {
      foreach($request_keys as $request_key){
        $request[$request_key] = $this->requestStack->getCurrentRequest()->query->get($request_key);
      }
    }

    return $request;
  }

  public function indexKeys(){
    $keys = [];
    $index_keys = $this->index->getFields();
    foreach($index_keys as $k=>$v){
      $keys[] = $k;
    }
    return $keys;
  }

  public function get($type = 'court_decision'){
    /* @var $query \Drupal\search_api\Query\QueryInterface */
    $query = $this->index->query();
    $query->addCondition('type', $type);

    if ($request = $this->getRequest()) {
      foreach($request as $key => $val) {
        if (is_array($val)) {
          $query->addCondition($key, $val, 'IN');
        } else {
          $query->addCondition($key, $val);
        }
      }
    }

    if ($yearmin = $this->requestStack->getCurrentRequest()->query->get('yearmin')) {
      $query->addCondition('field_date_of_text', "{$yearmin}-01-01T00:00:00Z", '>');
    }
    if ($yearmax = $this->requestStack->getCurrentRequest()->query->get('yearmax')) {
      $query->addCondition('field_date_of_text', "{$yearmax}-12-31T23:59:59Z", '<');
    }

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

  public function modalMarkup($content_type){
    $modal_title = t("Search results for @content_type_name" , ['@content_type_name' => $content_type['plural']]);
    return '
          <div class="modal fade" id="search_modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="exampleModalLongTitle">'. $modal_title .'</h5>
          </div>
          <div class="modal-body">
                        <div id="wildlex_map">
                        </div>
            
          </div>          
          <div class="modal-footer">
                <a href="#" class="btn btn-primary zoom-button" data-zoom="in">Zoom in</a>
                <a href="#" class="btn btn-primary zoom-button" data-zoom="out">Zoom out</a>
                <a href="#" class="btn btn-primary zoom-button" data-zoom="reset">Reset zoom</a>
                <a href="#" class="btn btn-primary" data-dismiss="modal">Close</a>
          </div>
        </div>
      </div>
    </div>';
  }

  public function searchBaseUrl($content_type = 'court_decision'){
   if ($content_type == 'court_decision') {
     $content_type = 'search';
   }

   $parts = [];

    if ($request = $this->getRequest()) {
      foreach($request as $key => $val) {
        if($key == 'field_country') {
          continue;
        }
        if (is_array($val)) {
          foreach($val as $actual) {
            $parts[] = $key . '[]=' .$actual;
          }

        } else {
          $parts[] = $key . '=' . $val;
        }
      }
    }

    if ($yearmin = $this->requestStack->getCurrentRequest()->query->get('yearmin')) {
      $parts[] = 'yearmin=' .$yearmin;
    }
    if ($yearmax = $this->requestStack->getCurrentRequest()->query->get('yearmax')) {
      $parts[] = 'yearmax=' .$yearmax;
    }
    if ($field_species_operator = $this->requestStack->getCurrentRequest()->query->get('field_species_operator')) {
      $parts[] = 'field_species_operator=' .$field_species_operator;
    }



    return '/' . $content_type . ($parts ? '?' . implode('&', $parts) .'&' : '?');
  }

}
