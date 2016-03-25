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
//    $default_index->getServerInstance()->removeIndex($default_index);
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
    sleep(5);
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

    $ref_nodes = Yaml::decode(file_get_contents($module_path . '/data/ReferencedNodes.yml'));
    foreach ($ref_nodes as $edit_node) {
      $node = Node::create($edit_node);
      $node->save();
      if (!empty($node->id())) {
        $this->nodes[$node->id()] = $node;
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
    $this->assertEqual(13, count($nodes), 'Nodes Created');

    // Get search server.
    $search_server = new SolrSearchServer('default_node_index');

    // Test empty search - all results.
    $search = new SolrSearch(array(), $search_server);
    $result = $search->search(0, 10);
    $this->assertEqual(8, $result->getCountTotal(), 'Nodes indexed');


    // Test ecolex subject.
    // Test ecolex subject facet - Single value OR.
    $params = array(
      'field_ecolex_subjects' => '1',
    );
    $search = new SolrSearch($params, $search_server);
    $result = $search->search(0, 10);
    $this->assertEqual(3, $result->getCountTotal(), 'Filter by ecolex subject ' . $params['field_ecolex_subjects'] . ' OR');
    $params = array(
      'field_ecolex_subjects' => '5',
    );
    $search = new SolrSearch($params, $search_server);
    $result = $search->search(0, 10);
    $this->assertEqual(0, $result->getCountTotal(), 'Filter by ecolex subject ' . $params['field_ecolex_subjects'] . ' OR');

    // Test ecolex subject facet - Multiple values OR.
    $params = array(
      'field_ecolex_subjects' => '1,2',
    );
    $search = new SolrSearch($params, $search_server);
    $result = $search->search(0, 10);
    $this->assertEqual(4, $result->getCountTotal(), 'Filter by ecolex subject ' . $params['field_ecolex_subjects'] . ' OR');

    // Test ecolex subject facet - Single values AND.
    $params = array(
      'field_ecolex_subjects' => '3',
      'field_ecolex_subjects_operator' => 'AND',
    );
    $search = new SolrSearch($params, $search_server);
    $result = $search->search(0, 10);
    $this->assertEqual(3, $result->getCountTotal(), 'Filter by ecolex subject ' . $params['field_ecolex_subjects'] . ' AND');

    // Test ecolex subject facet - Multiple values AND.
    $params = array(
      'field_ecolex_subjects' => '1,2',
      'field_ecolex_subjects_operator' => 'AND',
    );
    $search = new SolrSearch($params, $search_server);
    $result = $search->search(0, 10);
    $this->assertEqual(2, $result->getCountTotal(), 'Filter by ecolex subject ' . $params['field_ecolex_subjects'] . ' AND');

    $params = array(
      'field_ecolex_subjects' => '1,2,4',
      'field_ecolex_subjects_operator' => 'AND',
    );
    $search = new SolrSearch($params, $search_server);
    $result = $search->search(0, 10);
    $this->assertEqual(0, $result->getCountTotal(), 'Filter by ecolex subject ' . $params['field_ecolex_subjects'] . ' AND');


    // Test country.
    // Test country facet - Single value OR.
    $params = array(
      'field_country' => '5',
    );
    $search = new SolrSearch($params, $search_server);
    $result = $search->search(0, 10);
    $this->assertEqual(0, $result->getCountTotal(), 'Filter by country ' . $params['field_country'] . ' OR');

    // Test country facet - Multiple value OR.
    $params = array(
      'field_country' => '1,2',
    );
    $search = new SolrSearch($params, $search_server);
    $result = $search->search(0, 10);
    $this->assertEqual(6, $result->getCountTotal(), 'Filter by country ' . $params['field_country'] . ' OR');

    // Test country facet - Multiple value AND.
    $params = array(
      'field_country' => '1,2',
      'field_country_operator' => 'AND',
    );
    $search = new SolrSearch($params, $search_server);
    $result = $search->search(0, 10);
    $this->assertEqual(2, $result->getCountTotal(), 'Filter by country ' . $params['field_country'] . ' AND');

    $params = array(
      'field_country' => '1,2,4',
      'field_country_operator' => 'AND',
    );
    $search = new SolrSearch($params, $search_server);
    $result = $search->search(0, 10);
    $this->assertEqual(1, $result->getCountTotal(), 'Filter by country ' . $params['field_country'] . ' AND');

    $params = array(
      'field_country' => '1,2,3,4,5',
      'field_country_operator' => 'AND',
    );
    $search = new SolrSearch($params, $search_server);
    $result = $search->search(0, 10);
    $this->assertEqual(0, $result->getCountTotal(), 'Filter by country ' . $params['field_country'] . ' AND');



    // Test type of text.
    // Test type of text facet - Single value OR.
    $params = array(
      'field_type_of_text' => '9',
    );
    $search = new SolrSearch($params, $search_server);
    $result = $search->search(0, 10);
    $this->assertEqual(3, $result->getCountTotal(), 'Filter by type of text ' . $params['field_type_of_text'] . ' OR');

    // Test type of text facet - Multiple value OR.
    $params = array(
      'field_type_of_text' => '9,10',
    );
    $search = new SolrSearch($params, $search_server);
    $result = $search->search(0, 10);
    $this->assertEqual(4, $result->getCountTotal(), 'Filter by type of text ' . $params['field_type_of_text'] . ' OR');

    // Test type of text facet - Single value AND.
    $params = array(
      'field_type_of_text' => '9',
      'field_type_of_text_operator' => 'AND',
    );
    $search = new SolrSearch($params, $search_server);
    $result = $search->search(0, 10);
    $this->assertEqual(3, $result->getCountTotal(), 'Filter by type of text ' . $params['field_type_of_text'] . ' AND');

    // Test country facet - Multiple value AND.
    $params = array(
      'field_type_of_text' => '9,10',
      'field_type_of_text_operator' => 'AND',
    );
    $search = new SolrSearch($params, $search_server);
    $result = $search->search(0, 10);
    $this->assertEqual(1, $result->getCountTotal(), 'Filter by type of text ' . $params['field_type_of_text'] . ' AND');

    $params = array(
      'field_type_of_text' => '8,9,10',
      'field_type_of_text_operator' => 'AND',
    );
    $search = new SolrSearch($params, $search_server);
    $result = $search->search(0, 10);
    $this->assertEqual(0, $result->getCountTotal(), 'Filter by type of text ' . $params['field_type_of_text'] . ' AND');



    // Test subdivisions.
    // Test subdivisions facet - Single value OR.
    $params = array(
      'field_territorial_subdivisions' => '11',
    );
    $search = new SolrSearch($params, $search_server);
    $result = $search->search(0, 10);
    $this->assertEqual(3, $result->getCountTotal(), 'Filter by subdivsions ' . $params['field_territorial_subdivisions'] . ' OR');

    // Test subdivisions facet - Multiple value OR.
    $params = array(
      'field_territorial_subdivisions' => '11,12,13',
    );
    $search = new SolrSearch($params, $search_server);
    $result = $search->search(0, 10);
    $this->assertEqual(4, $result->getCountTotal(), 'Filter by subdivsions ' . $params['field_territorial_subdivisions'] . ' OR');

    // Test subdivisions facet - Single value AND.
    $params = array(
      'field_territorial_subdivisions' => '11',
      'field_territorial_subdivisions_operator' => 'AND',
    );
    $search = new SolrSearch($params, $search_server);
    $result = $search->search(0, 10);
    $this->assertEqual(3, $result->getCountTotal(), 'Filter by subdivsions ' . $params['field_territorial_subdivisions'] . ' AND');

    // Test subdivisions facet - Multiple value AND.
    $params = array(
      'field_territorial_subdivisions' => '11,12',
      'field_territorial_subdivisions_operator' => 'AND',
    );
    $search = new SolrSearch($params, $search_server);
    $result = $search->search(0, 10);
    $this->assertEqual(1, $result->getCountTotal(), 'Filter by subdivsions ' . $params['field_territorial_subdivisions'] . ' AND');

    $params = array(
      'field_territorial_subdivisions' => '11,12,13',
      'field_territorial_subdivisions_operator' => 'AND',
    );
    $search = new SolrSearch($params, $search_server);
    $result = $search->search(0, 10);
    $this->assertEqual(0, $result->getCountTotal(), 'Filter by subdivsions ' . $params['field_territorial_subdivisions'] . ' AND');




    // Test combined facets.
    $test_case = 'Ecolex subject single value OR + Country single value OR.';
    $params = array(
      'field_ecolex_subjects' => '1',
      'field_country' => '1',
    );
    $search = new SolrSearch($params, $search_server);
    $result = $search->search(0, 10);
    $this->assertEqual(2, $result->getCountTotal(), $test_case);

    $test_case = 'Ecolex subject multiple value OR + Country single value OR.';
    $params = array(
      'field_ecolex_subjects' => '1,3',
      'field_country' => '2',
    );
    $search = new SolrSearch($params, $search_server);
    $result = $search->search(0, 10);
    $this->assertEqual(3, $result->getCountTotal(), $test_case);

    $test_case = 'Ecolex subject multiple value OR + Country multiple value OR.';
    $params = array(
      'field_ecolex_subjects' => '1,3',
      'field_country' => '1,3',
    );
    $search = new SolrSearch($params, $search_server);
    $result = $search->search(0, 10);
    $this->assertEqual(4, $result->getCountTotal(), $test_case);

    $test_case = 'Ecolex subject multiple value AND + Country single value OR.';
    $params = array(
      'field_ecolex_subjects' => '1,3',
      'field_ecolex_subjects_operator' => 'AND',
      'field_country' => '2',
    );
    $search = new SolrSearch($params, $search_server);
    $result = $search->search(0, 10);
    $this->assertEqual(1, $result->getCountTotal(), $test_case);

    $test_case = 'Ecolex subject multiple value AND + Country single value OR.';
    $params = array(
      'field_ecolex_subjects' => '1,2',
      'field_ecolex_subjects_operator' => 'AND',
      'field_country' => '2',
    );
    $search = new SolrSearch($params, $search_server);
    $result = $search->search(0, 10);
    $this->assertEqual(0, $result->getCountTotal(), $test_case);

    $test_case = 'Ecolex subject multiple value AND + Country multiple value OR.';
    $params = array(
      'field_ecolex_subjects' => '1,2',
      'field_ecolex_subjects_operator' => 'AND',
      'field_country' => '1,2,3',
    );
    $search = new SolrSearch($params, $search_server);
    $result = $search->search(0, 10);
    $this->assertEqual(2, $result->getCountTotal(), $test_case);

    $test_case = 'Ecolex subject multiple value AND + Type of text multiple value OR.';
    $params = array(
      'field_ecolex_subjects' => '1,2',
      'field_ecolex_subjects_operator' => 'AND',
      'field_type_of_text' => '6',
    );
    $search = new SolrSearch($params, $search_server);
    $result = $search->search(0, 10);
    $this->assertEqual(1, $result->getCountTotal(), $test_case);

    $test_case = 'Ecolex subject multiple value AND + Country multiple value AND.';
    $params = array(
      'field_ecolex_subjects' => '1,2',
      'field_ecolex_subjects_operator' => 'AND',
      'field_country' => '1,2,3',
      'field_country_operator' => 'AND',
    );
    $search = new SolrSearch($params, $search_server);
    $result = $search->search(0, 10);
    $this->assertEqual(0, $result->getCountTotal(), $test_case);

    $test_case = 'Ecolex subject multiple value OR + Country multiple value OR + Type of text multiple value OR';
    $params = array(
      'field_ecolex_subjects' => '1,3',
      'field_country' => '1,3',
      'field_type_of_text' => '6,9',
    );
    $search = new SolrSearch($params, $search_server);
    $result = $search->search(0, 10);
    $this->assertEqual(3, $result->getCountTotal(), $test_case);

    $test_case = 'Ecolex subject multiple value AND + Country single value OR + Type of text multiple value OR';
    $params = array(
      'field_ecolex_subjects' => '1,2',
      'field_ecolex_subjects_operator' => 'AND',
      'field_type_of_text' => '6,10',
    );
    $search = new SolrSearch($params, $search_server);
    $result = $search->search(0, 10);
    $this->assertEqual(1, $result->getCountTotal(), $test_case);

    $test_case = 'Ecolex subject multiple value AND + Country single value OR + Type of text single value OR';
    $params = array(
      'field_ecolex_subjects' => '1,2',
      'field_ecolex_subjects_operator' => 'AND',
      'field_country' => '1',
      'field_type_of_text' => '6',
    );
    $search = new SolrSearch($params, $search_server);
    $result = $search->search(0, 10);
    $this->assertEqual(1, $result->getCountTotal(), $test_case);

    $test_case = 'Ecolex subject multiple value AND + Country single value OR + Type of text multiple value AND';
    $params = array(
      'field_ecolex_subjects' => '1,2',
      'field_ecolex_subjects_operator' => 'AND',
      'field_country' => '1',
      'field_type_of_text' => '6,10',
      'field_type_of_text_operator' => 'AND',
    );
    $search = new SolrSearch($params, $search_server);
    $result = $search->search(0, 10);
    $this->assertEqual(0, $result->getCountTotal(), $test_case);

    // Test search words.
    $test_case = 'Search unique word in title';
    $params = array(
      'q' => 'ABCD',
    );
    $search = new SolrSearch($params, $search_server);
    $result = $search->search(0, 10);
    $this->assertEqual(1, $result->getCountTotal(), $test_case);

    $test_case = 'Search word in title and field_abstract';
    $params = array(
      'q' => 'Bluefin Tuna',
    );
    $search = new SolrSearch($params, $search_server);
    $result = $search->search(0, 10);
    $this->assertEqual(2, $result->getCountTotal(), $test_case);



  }
}
