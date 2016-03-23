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

    /** @see getSolrFieldsMappings::getSolrFieldMappings() */
    $this->assertNotNull($server->getSolrFieldsMappings());
    $mappings = $server->getSolrFieldsMappings();
    $data = array(
      'search_api_id' => 'item_id',
      'search_api_relevance' => 'score',
      'search_api_language' => 'sm_5f_search_5f_api_5f_language',
      'type' => 'sm_5f_type',
      'field_abstract' => 'tm_5f_field_5f_abstract',
      'field_type_of_text' => 'im_5f_field_5f_type_5f_of_5f_text',
      'title' => 'tm_5f_title',
      'field_country' => 'im_5f_field_5f_country',
      'nid' => 'im_5f_nid',
      'field_files' => 'im_5f_field_5f_files',
      'field_territorial_subdivisions' => 'im_5f_field_5f_territorial_5f_subdivisions',
      'field_subdivision' => 'im_5f_field_5f_subdivision',
      'field_justices' => 'im_5f_field_5f_justices',
      'field_instance' => 'im_5f_field_5f_instance',
      'field_ecolex_subjects' => 'im_5f_field_5f_ecolex_5f_subjects',
      'field_decision_status' => 'im_5f_field_5f_decision_5f_status',
      'field_court_jurisdiction' => 'im_5f_field_5f_court_5f_jurisdiction',
    );
    foreach($data as $drupal_field => $solr_field) {
      $this->assertEqual($solr_field, $mappings[$drupal_field]);
    }

    /** @see SolrSearchServer::createSelectQuery() */
    $this->assertNotNull($server->createSelectQuery());

    $mappings = $server->getSearchFieldsMappings();
    $data = array(
      'field_abstract' => 'tm_5f_field_5f_abstract^1',
      'title' => 'tm_5f_title^1',
    );
    foreach($data as $drupal_field => $solr_field) {
      $this->assertEqual($solr_field, $mappings[$drupal_field]);
    }

    /** @see SolrSearchServer::getDocumentIdField() */
    $this->assertEqual('im_5f_nid', $server->getDocumentIdField());
  }

  public function disabled_testExecuteSearch() {

  }
}
