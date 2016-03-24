<?php

namespace Drupal\iucn_search\edw\solr;

use Drupal\search_api\Entity\Index;
use Drupal\search_api_solr\Plugin\search_api\backend\SearchApiSolrBackend;
use Solarium\Client;

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
    $this->solrClient = new Client();
    $this->solrClient->createEndpoint($this->server_config, TRUE);

    /** @var SearchApiSolrBackend $backend */
    $backend = $server->getBackend();
    $this->solr_field_mappings = $backend->getFieldNames($this->getIndex());

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
   * @return \Solarium\Client
   * @throws \Exception
   *    When search server cannot be instantiated.
   */
  public function getSolrClient() {
    return $this->solrClient;
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
    return $this->getSolrClient()->createSelect($options);
  }

  /**
   * Do a search on the Solr server.
   * @param \Solarium\QueryType\Select\Query\Query $query
   *
   * @return \Solarium\QueryType\Select\Result\Result
   */
  public function executeQuery(\Solarium\QueryType\Select\Query\Query $query) {
    // Use the 'postbigrequest' plugin if no specific http method is
    // configured. The plugin needs to be loaded before the request is
    // created.
    $config = $this->getServerConfig();
    $client = $this->getSolrClient();
    if ($config['http_method'] == 'AUTO') {
      // Set larger query limit - it might not be enough.
      $client->getPlugin('postbigrequest')->setMaxQueryStringLength(2048);
    }
    $config['http_method'] = 'GET';
    $request = $client->createRequest($query);
    if (!empty($config['http_method'])) {
      $request->setMethod($config['http_method']);
    }
    if (strlen($config['http_user']) && strlen($config['http_pass'])) {
      $request->setAuthentication($config['http_user'], $config['http_pass']);
    }
    // Send search request.
    $response = $client->executeRequest($request);
    $resultSet = $client->createResult($query, $response);
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
