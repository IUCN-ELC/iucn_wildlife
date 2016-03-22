<?php

/**
 * @file
 * Contains \Drupal\iucn_search\IUCN\IUCNSearch.
 */

namespace Drupal\iucn_search\IUCN;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\search_api\Backend\BackendInterface;
use Solarium\Client;
use Drupal\iucn_search\Edw\Facets\Facet;
use Drupal\search_api\Entity\Index;


class SearchServerConfiguration {

  protected $index_id = 'default_node_index';
  protected $client = NULL;


  public function __construct($index_id) {
    $this->index_id = $index_id;
  }

  /** @return \Drupal\search_api\Entity\Index search_index */
  public function getIndex() {
    return Index::load($this->index_id);
  }

  protected function getServerInstance() {
    return $this->getIndex()->getServerInstance();
  }

  public function getServerConfig() {
    $server = $this->getServerInstance();
    return $server->getBackendConfig() + array('key' => $server->id());
  }

  /**
   * @return \Solarium\Client
   * @throws \Exception
   *    When search server cannot be instantiated.
   */
  public function getSolrClient() {
    if (empty($this->client)) {
      $this->client = new Client();
      $server_config = $this->getServerConfig();
      $this->client->createEndpoint($server_config, TRUE);
    }
    return $this->client;
  }

  public function getFieldNames() {
    $server = $this->getServerInstance();
    /** @var BackendInterface $backend */
    $backend = $server->getBackend();
    return $backend->getFieldNames($this->getIndex());
  }

}


class IUCNSolrSearch {

  /** @var array Request parameters (query) */
  protected $parameters = NULL;
  /** @var \Solarium\Client */
  protected $solr = NULL;
  /** @var \Drupal\iucn_search\IUCN\SearchServerConfiguration */
  protected $config = NULL;
  protected $facets = array();

  public function __construct(array $parameters, SearchServerConfiguration $config) {
    $this->parameters = $parameters;
    $this->config = $config;
    $this->initFacets();
  }

  public function search($page, $size) {
    $nodes = [];
    $search_text = $this->getParameter('q');
    try {
      $index = $this->config->getIndex();
      $query = $this->solr->createSelect();
      $query->setQuery($search_text);
      $query->setFields(array('*', 'score'));

      $field_names = $this->config->getFieldNames();

      $search_fields = $this->config->getIndex()->getFulltextFields();
      // Get the index fields to be able to retrieve boosts.
      $index_fields = $index->getFields();
      $query_fields = [];
      foreach ($search_fields as $search_field) {
        /** @var \Solarium\QueryType\Update\Query\Document\Document $document */
        $document = $index_fields[$search_field];
        $boost = $document->getBoost() ? '^' . $document->getBoost() : '';
        $query_fields[] = $field_names[$search_field] . $boost;
      }
      $query->getEDisMax()->setQueryFields(implode(' ', $query_fields));

      $offset = $page * $size;
      $query->setStart($offset);
      $query->setRows($size);
      // $this->setFacets($query, $field_names);
      $resultSet = $this->createSolariumRequest($query);
      $documents = $resultSet->getDocuments();
      $this->resultCount = $resultSet->getNumFound();

      foreach ($documents as $document) {
        $fields = $document->getFields();
        $nid = $fields[$field_names['nid']];
        if (is_array($nid)) {
          $nid = reset($nid);
        }
        $node = \Drupal\node\Entity\Node::load($nid);
        $nodes[$nid] = \Drupal::entityTypeManager()->getViewBuilder('node')->view($node, $this->items_viewmode);
      }

      $this->setFacetsValues($resultSet->getFacetSet());
    }
    catch (\Exception $e) {
      watchdog_exception('iucn_search', $e);
      drupal_set_message(t('An error occurred.'), 'error');
    }
    return $nodes;
  }

  public function getParameter($name) {
    $ret = NULL;
    if (!empty($this->parameters[$name])) {
      $ret = $this->parameters[$name];
      // @todo: Security check input parameters
    }
    return $ret;
  }

  public function getFacets() {
    return $this->facets;
  }

  protected function initFacets() {
    // @ToDo: Translate facet titles
    $facets = [
      'ecolex_subjects' => [
        'title' => 'Subject',
        'placeholder' => 'Add subjects...',
      ],
      'field_country' => [
        'title' => 'Country',
        'placeholder' => 'Add countries...',
      ],
      'field_type_of_text' => [
        'title' => 'Type of court',
        'placeholder' => 'Add types...',
      ],
      'field_territorial_subdivisions' => [
        'title' => 'Sub-national/state level',
        'placeholder' => 'Add territory...',
      ],
//      'field_subdivision' => [
//        'title' => 'Subdivision',
//        'field' => 'field_subdivision',
//      ],
//      'field_justices' => [
//        'title' => 'Justice',
//        'field' => 'field_justices',
//      ],
//      'field_instance' => [
//        'title' => 'Instance',
//        'field' => 'field_instance',
//      ],
//      'field_decision_status' => [
//        'title' => 'Decision status',
//        'field' => 'field_decision_status',
//      ],
//      'field_court_jurisdiction' => [
//        'title' => 'Court jurisdiction',
//        'field' => 'field_court_jurisdiction',
//      ],
    ];
    foreach ($facets as $id => $config) {
      $config['id'] = $id;
      $this->facets[$id] = new Facet($id, $config['bundle'], $config);
    }
  }

  /**
   * @return \Solarium\Core\Query\Result\ResultInterface
   */
  private function createSolariumRequest($solarium_query) {
    // Use the 'postbigrequest' plugin if no specific http method is
    // configured. The plugin needs to be loaded before the request is
    // created.
    $config = $this->config->getServerConfig();
    if ($config['http_method'] == 'AUTO') {
      $this->solr->getPlugin('postbigrequest');
    }
    $request = $this->solr->createRequest($solarium_query);

    if ($config['http_method'] == 'POST') {
      $request->setMethod(Request::METHOD_POST);
    }
    elseif ($config['http_method'] == 'GET') {
      $request->setMethod(Request::METHOD_GET);
    }
    if (strlen($config['http_user']) && strlen($config['http_pass'])) {
      $request->setAuthentication($config['http_user'], $config['http_pass']);
    }
    // Send search request.
    $response = $this->solr->executeRequest($request);
    $resultSet = $this->solr->createResult($solarium_query, $response);
    return $resultSet;
  }
}
