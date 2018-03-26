<?php

namespace Drupal\iucn_search\edw\solr;

use Drupal\search_api\Entity\Index;
use Drupal\search_api_solr\Plugin\search_api\backend\SearchApiSolrBackend;
use Solarium\Client;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

class SolrSearchServer {

  protected $index_id = 'default_node_index';
  /** @var \Drupal\search_api\Entity\Index */
  protected $index = NULL;
  protected $solrClient = NULL;
  protected $server = NULL;
  protected $server_config = array();
  protected $solr_field_mappings = array();
  protected $search_field_mappings = array();


  public function __construct($index_id) {
    $this->index_id = $index_id;
    $this->index = Index::load($this->index_id);
    $server = $this->index->getServerInstance();
    $this->server = $server;
    $this->server_config = $server->getBackendConfig() + array('key' => $server->id());
    $this->solrClient = $server->getBackend()->getSolrConnector();

    $this->setSolrFieldsMappings();

    $mappings = $this->getSolrFieldsMappings();
    $ft_fields = $this->getIndex()->getFulltextFields();
    $index_fields = $this->getIndex()->getFields();

    foreach($ft_fields as $drupal_field_name) {
      /** @var \Solarium\QueryType\Update\Query\Document\Document $document */
      $document = $index_fields[$drupal_field_name];
      $boost = $document->getBoost() ? '^' . $document->getBoost() : '';
      $this->search_field_mappings[$drupal_field_name] = $mappings[$drupal_field_name] . $boost;
    }
  }

  /** @return \Drupal\search_api\Entity\Index search_index */
  public function getIndex() {
    return $this->index;
  }

  /**
   * Retrieve the Solr server configuration.
   *
   * @return array
   */
  public function getServerConfig() {
    return $this->server_config;
  }

  /**
   * Return the Solr client that executes the actual request (i.e. Solarium).
   *
   * @return \Drupal\search_api_solr\SolrConnectorInterface
   * @throws \Exception
   *    When search server cannot be instantiated.
   */
  public function getSolrClient() {
    return $this->solrClient;
  }

  private function setSolrFieldsMappings() {
    $mappings = [];
    /** @var SearchApiSolrBackend $backend */
    $backend = $this->server->getBackend();
    $singleFields = $backend->getSolrFieldNames($this->getIndex(), TRUE);
    $multiFields = $backend->getSolrFieldNames($this->getIndex());
    $field_configs = FieldStorageConfig::loadMultiple();
    foreach ($field_configs as $field_storage) {
      $name = $field_storage->getName();
      if (array_key_exists($name, $singleFields) || array_key_exists($name, $multiFields)) {
        $mappings[$name] = $field_storage->getCardinality() == 1 ? $singleFields[$name] : $multiFields[$name];
      }
    }
    foreach ($singleFields as $key => $field) {
      // Add the rest of the fields (eg: 'search_api_relevance')
      if (!array_key_exists($key, $mappings)) {
        $mappings[$key] = $field;
      }
    }
    $this->solr_field_mappings = $mappings;
  }

  /**
   * Get the mapping between Drupal field and corresponding Solr schema field.
   *
   * <code>
   * array(
   *    'search_api_id' => 'item_id',
   *    'search_api_relevance' => 'score',
   *    'type' => 'sm_5f_type',
   *    'field_country' => 'im_5f_field_5f_country',
   * )
   * </code>
   *
   * @return array
   *   Array keyed by field name
   */
  public function getSolrFieldsMappings() {
    return $this->solr_field_mappings;
  }

  /**
   * Create a new Solr query object.
   *
   * @param array $options
   *    Options passed to the Query interface
   *
   * @return \Solarium\QueryType\Select\Query\Query
   */
  public function createSelectQuery($options = array()) {
    return $this->getSolrClient()->getSelectQuery($options);
  }

  /**
   * Do a search on the Solr server.
   * @param \Solarium\QueryType\Select\Query\Query $query
   *
   * @return \Solarium\Core\Query\Result\ResultInterface
   */
  public function executeQuery(\Solarium\QueryType\Select\Query\Query $query) {
    $config = $this->getServerConfig();
    $config['connector_config']['http_method'] = 'GET';
    $client = $this->getSolrClient();
    // Send search request.
    /** @var \Solarium\Core\Client\Response $response */
    $response = $client->search($query);
    $resultSet = $client->createSearchResult($query, $response);
    return $resultSet;
  }

  /**
   * Mapping between Drupal field and Solr field, only for full-text (searched) fields.
   *
   * array (
   *   'field_abstract' => 'tm_5f_field_5f_abstract^1'
   *   'title' => 'tm_5f_title^1'
   * )
   *
   * @return array
   *    Mapping for Drupal-Solr fields (with boost)
   */
  public function getSearchFieldsMappings() {
    return $this->search_field_mappings;
  }

  /**
   * Get the Solr field corresponding to nid Drupal field.
   *
   * @return string
   */
  public function getDocumentIdField() {
    $field_names = $this->getSolrFieldsMappings();
    return $field_names['nid'];
  }
}
