<?php

namespace Drupal\iucn_search\Tests;

use Drupal\search_api\Tests\WebTestBase;
use Drupal\search_api\Entity\Index;

/**
 * Test the Facet functionality.
 *
 * @see Drupal\simpletest\WebTestBase
 *
 * @ingroup iucn_search
 * @group iucn_search
 */
class SolrSearchTest extends WebTestBase {

  static public $modules = array('iucn_search', 'iucn_search_test_search');

  protected $profile = 'minimal';

  public function setUp() {
    parent::setUp();
    // Change the index to use test server.
    $default_index = Index::load('default_node_index');
    $default_index->set('server', 'iucn_search_test');
    $default_index->save();
    // Clear existing items.
    $default_index->getServerInstance()->removeIndex();
  }

  private function createNodes() {
    $edit_node = array(
      'nid' => NULL,
      'type' => 'court_decision',
      'uid' => 1,
      'status' => TRUE,
      'promote' => 0,
    );
    $edit_node['title'] = 'Test 1';
    $node = entity_create('node', $edit_node);
  }

  public function testSearch() {

  }

}
