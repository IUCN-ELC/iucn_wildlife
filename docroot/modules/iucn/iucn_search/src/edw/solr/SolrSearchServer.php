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
  }

  /** @return \Drupal\search_api\Entity\Index search_index */
  public function getIndex() {
    return $this->index;
  }

  /** @return array */
  public function getServerConfig() {
    return $this->server_config;
  }

  /**
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

  public function createSelectQuery() {
    $client = $this->getSolrClient();
    return $client->createSelect();
  }

  /**
   * @return \Solarium\QueryType\Select\Result\Result
   */
  public function executeSearch($query) {
    // Use the 'postbigrequest' plugin if no specific http method is
    // configured. The plugin needs to be loaded before the request is
    // created.
    $config = $this->getServerConfig();
    $client = $this->getSolrClient();
    if ($config['http_method'] == 'AUTO') {
      $client->getPlugin('postbigrequest');
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

  public function getSearchFields() {
    //@todo: Is this enough?
    return $this->getIndex()->getFulltextFields();
  }

  public function getQueryFields() {
    $field_names = $this->getSolrFieldsMappings();
    $query_fields = array();
    $search_fields = $this->getSearchFields();
    // Index fields contain boost data.
    $index_fields = $this->getIndex()->getFields();
    foreach ($search_fields as $search_field) {
      /** @var \Solarium\QueryType\Update\Query\Document\Document $document */
      $document = $index_fields[$search_field];
      $boost = $document->getBoost() ? '^' . $document->getBoost() : '';
      $query_fields[] = $field_names[$search_field] . $boost;
    }
    return $query_fields;
  }

  public function getDocumentIdField() {
    $field_names = $this->getSolrFieldsMappings();
    return $field_names['nid'];
  }
}
