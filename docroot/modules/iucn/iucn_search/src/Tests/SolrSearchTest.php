<?php

namespace Drupal\iucn_search\Tests;

use Drupal\Component\Serialization\Yaml;
use Drupal\search_api\Tests\WebTestBase;
use Drupal\search_api\Entity\Index;
use Drupal\iucn_search\edw\solr\SolrSearchServer;
use Drupal\iucn_search\edw\solr\SolrSearch;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;

/**
 * Test the Facet functionality.
 *
 * @see Drupal\simpletest\WebTestBase
 *
 * @ingroup iucn_search
 * @group iucn_search
 */
class SolrSearchTest extends WebTestBase {

  static public $modules = array('iucn_search_test_search');

  protected $profile = 'minimal';

  protected $nodes = array();
  protected $terms = array();

  public function setUp() {
    parent::setUp();
    // Change the index to use test server.
    $default_index = Index::load('default_node_index');
    $default_index->set('server', 'iucn_search_test');
    $default_index->save();
    // TODO - this cleanup index not working - deleting nodes in tear down.
    $default_index->getServerInstance()->removeIndex($default_index);
    // Create test content.
    $this->createTestContent();
    // Give SOLR some time to commit.
    sleep(5);
  }

  public function tearDown() {
    parent::tearDown();
    foreach ($this->nodes as $node) {
      $node->delete();
    }
  }

  private function createTestContent() {
    $module_path = drupal_get_path('module', 'iucn_search_test_search');
    $terms = Yaml::decode(file_get_contents($module_path . '/data/ReferencedTerms.yml'));
    foreach ($terms as $edit_term) {
      $term = Term::create($edit_term);
      $term->save();
      if (!empty($term->id())) {
        $this->terms[] = $term;
      }
    }

    $nodes = Yaml::decode(file_get_contents($module_path . '/data/Nodes.yml'));
    foreach ($nodes as $edit_node) {
      $node = Node::create($edit_node);
      $node->save();
      if (!empty($node->id())) {
        $this->nodes[$node->id()] = $node;
      }
    }
  }


  /**
   * Test SolrSearch::search().
   */
  public function testSearch() {
    // Test total nodes created.
    $nodes = \Drupal\node\Entity\Node::loadMultiple(array_keys($this->nodes));
    $this->assertEqual(3, count($nodes), 'Nodes Created');

    // Get search server.
    $search_server = new SolrSearchServer('default_node_index');

    // Test empty search - all results.
    $search = new SolrSearch(array(), $search_server);
    $result = $search->search(0, 10);
    $this->assertEqual(3, $result->getCountTotal(), 'Nodes indexed');



    // Test ecolex subject.
    // Test ecolex subject facet - Single value OR.
    $params = array(
      'field_ecolex_subjects' => '1',
    );
    $search = new SolrSearch($params, $search_server);
    $result = $search->search(0, 10);
    $this->assertEqual(2, $result->getCountTotal(), 'Filter by ecolex subject 1 OR');

    // Test ecolex subject facet - Multiple values OR.
    $params = array(
      'field_ecolex_subjects' => '1,2,3',
    );
    $search = new SolrSearch($params, $search_server);
    $result = $search->search(0, 10);
    $this->assertEqual(3, $result->getCountTotal(), 'Filter by ecolex subject 1,2,3 OR');

    // Test ecolex subject facet - Single values AND.
    $params = array(
      'field_ecolex_subjects' => '3',
    );
    $search = new SolrSearch($params, $search_server);
    $result = $search->search(0, 10);
    $this->assertEqual(2, $result->getCountTotal(), 'Filter by ecolex subject 3 AND');

    // Test ecolex subject facet - Multiple values AND.
    $params = array(
      'field_ecolex_subjects' => '1,3',
      'field_ecolex_subjects_operator' => 'AND',
    );
    $search = new SolrSearch($params, $search_server);
    $result = $search->search(0, 10);
    $this->assertEqual(1, $result->getCountTotal(), 'Filter by ecolex subject 1,2 AND');

    $params = array(
      'field_ecolex_subjects' => '1,2,3',
      'field_ecolex_subjects_operator' => 'AND',
    );
    $search = new SolrSearch($params, $search_server);
    $result = $search->search(0, 10);
    $this->assertEqual(0, $result->getCountTotal(), 'Filter by ecolex subject 1,2,3 AND');


  }
}
