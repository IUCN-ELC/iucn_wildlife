<?php

namespace Drupal\iucn_search\edw\solr;

use Drupal\search_api\Entity\Index;
use Drupal\search_api_solr\Plugin\search_api\backend\SearchApiSolrBackend;
use Solarium\Client;

class SolrSearchServer {

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
    /** @var SearchApiSolrBackend $backend */
    $backend = $server->getBackend();
    return $backend->getFieldNames($this->getIndex());
  }

  public function getIndexedFields() {
    return $this->getIndex()->getFields();
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

  public function getSearchedFields() {
    //@todo: Is this enough?
    return $this->getIndex()->getFulltextFields();
  }
}
