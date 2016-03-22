<?php

namespace Drupal\iucn_search\Tests;

use Drupal\iucn_search\edw\solr\SolrSearchServer;
use Drupal\simpletest\WebTestBase;

/**
 * Test the Facet functionality.
 *
 * @see Drupal\simpletest\WebTestBase
 *
 * @ingroup iucn_search
 * @group iucn_search
 */
class SolrSearchServerTest extends WebTestBase {

  /** @var array */
  static public $modules = array('iucn_search');

  public function testConstructor() {
    $server = new SolrSearchServer('default_node_index');

    /** @see SolrSearchServer::getIndex() */
    $this->assertTrue($server->getIndex() !== NULL);

    /** @see SolrSearchServer::getServerConfig() */
    $this->assertNotNull($server->getServerConfig());
    $this->assertTrue(count($server->getServerConfig()) > 0);


    /** @see SolrSearchServer::getSolrClient() */
    $this->assertNotNull($server->getSolrClient());

    /** @see SolrSearchServer::getFieldNames() */
    $this->assertNotNull($server->getFieldNames());
    $this->assertTrue(count($server->getFieldNames()) > 0);

    /** @see SolrSearchServer::createSelectQuery() */
    $this->assertNotNull($server->createSelectQuery());

    // @todo:
    // getSearchFields
    // getQueryFields

    /** @see SolrSearchServer::getDocumentIdField() */
    $this->assertNotNull($server->getDocumentIdField());
    $this->assertTrue(strlen($server->getDocumentIdField()) > 0);
  }

  public function disabled_testExecuteSearch() {

  }
}