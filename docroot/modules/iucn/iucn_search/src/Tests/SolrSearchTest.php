<?php

namespace Drupal\iucn_search\Tests;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Url;
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

  protected $url = '/search';

  public function setUp() {
    parent::setUp();
    date_default_timezone_set('UTC');

    // Set front end theme.
    \Drupal::service('theme_installer')->install(array('iucn_frontend'), TRUE);
    $config = \Drupal::service('config.factory')->getEditable('system.theme');
    $config->set('default', 'iucn_frontend')->save();

    // Change the index to use test server.
    $default_index = Index::load('default_node_index');

    try {
      $default_index->set('server', 'iucn_search_test');
      $default_index->save();
    }
    catch (\Exception $e) {
      // This try-catch is very very important. Do not remove!
    }

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
    $this->assertEqual(11, count($nodes), 'Nodes Created');

    // Get search server.
    $search_server = new SolrSearchServer('default_node_index');

    // Test empty search - all results.
    $search = new SolrSearch(array(), $search_server);
    $result = $search->search(0, 10);
    $this->assertEqual(11, $result->getCountTotal(), 'Nodes indexed');


    // Test field_species.
    // Test field_species facet - Single value OR.
    $params = array(
      'field_species' => '1',
    );
    $search = new SolrSearch($params, $search_server);
    $result = $search->search(0, 10);
    $this->assertEqual(3, $result->getCountTotal(), 'Filter by field_species ' . $params['field_species'] . ' OR');
    $params = array(
      'field_species' => '5',
    );
    $search = new SolrSearch($params, $search_server);
    $result = $search->search(0, 10);
    $this->assertEqual(0, $result->getCountTotal(), 'Filter by field_species subject ' . $params['field_species'] . ' OR');

    // Test field_species facet - Multiple values OR.
    $params = array(
      'field_species' => '1,3',
    );
    $search = new SolrSearch($params, $search_server);
    $result = $search->search(0, 10);
    $this->assertEqual(5, $result->getCountTotal(), 'Filter by field_species subject ' . $params['field_species'] . ' OR');

    // Test field_species facet - Single values AND.
    $params = array(
      'field_species' => '1',
      'field_species_operator' => 'AND',
    );
    $search = new SolrSearch($params, $search_server);
    $result = $search->search(0, 10);
    $this->assertEqual(3, $result->getCountTotal(), 'Filter by field_species subject ' . $params['field_species'] . ' AND');

    // Test field_species facet - Multiple values AND.
    $params = array(
      'field_species' => '1,3',
      'field_species_operator' => 'AND',
    );
    $search = new SolrSearch($params, $search_server);
    $result = $search->search(0, 10);
    $this->assertEqual(1, $result->getCountTotal(), 'Filter by field_species subject ' . $params['field_species'] . ' AND');

    $params = array(
      'field_species' => '1,2,3',
      'field_species_operator' => 'AND',
    );
    $search = new SolrSearch($params, $search_server);
    $result = $search->search(0, 10);
    $this->assertEqual(0, $result->getCountTotal(), 'Filter by field_species subject ' . $params['field_species'] . ' AND');

    // Test field_country.
    // Test field_country facet - Single value OR.
    $params = array(
      'field_country' => '23',
    );
    $search = new SolrSearch($params, $search_server);
    $result = $search->search(0, 10);
    $this->assertEqual(3, $result->getCountTotal(), 'Filter by field_country ' . $params['field_country'] . ' OR');

    // Test country facet - Multiple value OR.
    $params = array(
      'field_country' => '23,25',
    );
    $search = new SolrSearch($params, $search_server);
    $result = $search->search(0, 10);
    $this->assertEqual(5, $result->getCountTotal(), 'Filter by field_country ' . $params['field_country'] . ' OR');

    // Test country facet - Multiple value AND.
    $params = array(
      'field_country' => '23,24',
      'field_country_operator' => 'AND',
    );
    $search = new SolrSearch($params, $search_server);
    $result = $search->search(0, 10);
    $this->assertEqual(2, $result->getCountTotal(), 'Filter by field_country ' . $params['field_country'] . ' AND');

    $params = array(
      'field_country' => '23,24,26',
      'field_country_operator' => 'AND',
    );
    $search = new SolrSearch($params, $search_server);
    $result = $search->search(0, 10);
    $this->assertEqual(1, $result->getCountTotal(), 'Filter by field_country ' . $params['field_country'] . ' AND');

    $params = array(
      'field_country' => '23,24,25',
      'field_country_operator' => 'AND',
    );
    $search = new SolrSearch($params, $search_server);
    $result = $search->search(0, 10);
    $this->assertEqual(0, $result->getCountTotal(), 'Filter by field_country ' . $params['field_country'] . ' AND');



    // Test field_keywords.
    // Test field_keywords facet - Single value OR.
    $params = array(
      'field_keywords' => '28',
    );
    $search = new SolrSearch($params, $search_server);
    $result = $search->search(0, 10);
    $this->assertEqual(3, $result->getCountTotal(), 'Filter by field_keywords ' . $params['field_keywords'] . ' OR');

    // Test type of text facet - Multiple value OR.
    $params = array(
      'field_keywords' => '29,30',
    );
    $search = new SolrSearch($params, $search_server);
    $result = $search->search(0, 10);
    $this->assertEqual(5, $result->getCountTotal(), 'Filter by field_keywords ' . $params['field_keywords'] . ' OR');

    $params = array(
      'field_keywords' => '29,30',
      'field_keywords_operator' => 'AND',
    );
    $search = new SolrSearch($params, $search_server);
    $result = $search->search(0, 10);
    $this->assertEqual(2, $result->getCountTotal(), 'Filter by field_keywords ' . $params['field_keywords'] . ' AND');

    // Test field_territorial_subdivisions.
    // Test field_territorial_subdivisions facet - Single value OR.
    $params = array(
      'field_territorial_subdivisions' => '35',
    );
    $search = new SolrSearch($params, $search_server);
    $result = $search->search(0, 10);
    $this->assertEqual(4, $result->getCountTotal(), 'Filter by field_territorial_subdivisions ' . $params['field_territorial_subdivisions'] . ' OR');

    // Test field_region facet - Multiple value OR.
    $params = array(
      'field_territorial_subdivisions' => '34,35,36',
    );
    $search = new SolrSearch($params, $search_server);
    $result = $search->search(0, 10);
    $this->assertEqual(5, $result->getCountTotal(), 'Filter by field_territorial_subdivisions ' . $params['field_territorial_subdivisions'] . ' OR');


    // Test combined facets.
    $test_case = 'field_species subject single value OR + field_territorial_subdivisions single value OR.';
    $params = array(
      'field_species' => '3',
      'field_territorial_subdivisions' => '35',
    );
    $search = new SolrSearch($params, $search_server);
    $result = $search->search(0, 10);
    $this->assertEqual(2, $result->getCountTotal(), $test_case);

    $test_case = 'field_species subject multiple value OR + field_territorial_subdivisions single value OR.';
    $params = array(
      'field_species' => '1,4',
      'field_territorial_subdivisions' => '35',
    );
    $search = new SolrSearch($params, $search_server);
    $result = $search->search(0, 10);
    $this->assertEqual(3, $result->getCountTotal(), $test_case);

    $test_case = 'field_species multiple value AND + field_keywords single value OR.';
    $params = array(
      'field_species' => '1,2',
      'field_species_operator' => 'AND',
      'field_keywords' => '28',
    );
    $search = new SolrSearch($params, $search_server);
    $result = $search->search(0, 10);
    $this->assertEqual(1, $result->getCountTotal(), $test_case);

    $test_case = 'field_species multiple value AND + field_keywords multiple value OR.';
    $params = array(
      'field_species' => '1,2',
      'field_species_operator' => 'AND',
      'field_keywords' => '28,30',
      'field_keywords_operator' => 'OR',
    );
    $search = new SolrSearch($params, $search_server);
    $result = $search->search(0, 10);
    $this->assertEqual(2, $result->getCountTotal(), $test_case);

    $test_case = 'field_species multiple value AND + field_keywords multiple value AND.';
    $params = array(
      'field_species' => '1,2',
      'field_species_operator' => 'AND',
      'field_keywords' => '28,29',
      'field_keywords_operator' => 'AND',
    );
    $search = new SolrSearch($params, $search_server);
    $result = $search->search(0, 10);
    $this->assertEqual(1, $result->getCountTotal(), $test_case);

    $test_case = 'field_species multiple value OR + field_keywords multiple value OR + field_type_of_text value OR';
    $params = array(
      'field_species' => '1,3',
      'field_keywords' => '29,30',
      'field_type_of_text' => '37,38',
    );
    $search = new SolrSearch($params, $search_server);
    $result = $search->search(0, 10);
    $this->assertEqual(2, $result->getCountTotal(), $test_case);

    $test_case = 'field_species multiple value AND + field_keywords multiple value OR + field_type_of_text multiple value OR';
    $params = array(
      'field_species' => '1,2',
      'field_species_subjects_operator' => 'AND',
      'field_keywords' => '30,31',
      'field_type_of_text' => '37,40',
    );
    $search = new SolrSearch($params, $search_server);
    $result = $search->search(0, 10);
    $this->assertEqual(2, $result->getCountTotal(), $test_case);

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

    // Test date filter.
    $test_case = 'Date of text from 2000 to *';
    $params = array(
      'field_date_of_text' => "[2000-01-01T00:00:00Z TO *]",
    );
    $search = new SolrSearch($params, $search_server);
    $result = $search->search(0, 10);
    $this->assertEqual(11, $result->getCountTotal(), $test_case);

    $test_case = 'Date of text from 2003 to 2006';
    $params = array(
      'field_date_of_text' => "[2003-01-01T00:00:00Z TO 2006-12-31T23:59:59Z]",
    );
    $search = new SolrSearch($params, $search_server);
    $result = $search->search(0, 10);
    $this->assertEqual(4, $result->getCountTotal(), $test_case);

    $test_case = 'Date of text from * to 2008';
    $params = array(
      'field_date_of_text' => "[* TO 2008-12-31T23:59:59Z]",
    );
    $search = new SolrSearch($params, $search_server);
    $result = $search->search(0, 10);
    $this->assertEqual(8, $result->getCountTotal(), $test_case);

  }

  /**
   * Test Search Web page.
   */
  public function testWebSearch() {
    // Test total nodes created.
    $search = '/search';
    $nodes = \Drupal\node\Entity\Node::loadMultiple(array_keys($this->nodes));
    $this->assertEqual(11, count($nodes), 'Nodes Created');

    // Test empty search - all results.
    $params = array();
    $this->drupalGet($search, array('query' => $params));
    // Check for search box.
    $this->assertField('q');
    // Check that all facets are shown.
    $this->assertField('field_country_values[]');
    $this->assertField('field_keywords_values[]');
    $this->assertField('field_type_of_text_values[]');
    $this->assertField('field_territorial_subdivisions_values[]');
    $this->assertField('field_species_values[]');
    $this->assertField('field_language_of_document_values[]');
    // Check operator switchers are present and not checked.
    $this->assertField('field_species_operator');
    $this->assertNoFieldChecked('edit-field-species-operator');
    // Check that operators are not shown for single value fields.
    $this->assertNoField('field_language_of_document_operator', 'Operator not for 1 single value fields');
    $this->assertNoField('field_territorial_subdivisions_operator', 'Operator not for 1 single value fields');
    $this->assertNoField('field_type_of_text_operator', 'Operator not for 1 single value fields');
    $this->assertNoField('field_country_operator', 'Operator not for 1 single value fields');
    // Check some facets values.
    $this->assertRaw('>Rico (3)</option>');
    $this->assertRaw('>Private (3)</option>');
    $this->assertRaw('>France (3)</option>');
    $this->assertRaw('>UK (2)</option>');
    $this->assertRaw('>Bike (3)</option>');
    $this->assertRaw('>Eat (4)</option>');
    $this->assertRaw('>SubdivisionY (4)</option>');
    $this->assertRaw('>Type A (3)</option>');
    $this->assertRaw('>English (4)</option>');
    // Check that sort links are shown.
    $this->assertLinkByHref('/search?sort=field_date_of_text&sortOrder=desc');
    $this->assertLinkByHref('/search?sort=field_date_of_text&sortOrder=asc');
    $this->assertText('Sorted by relevance');
    // Check that 10 nodes per page are shown.
    $this->assertEqual(10, count($this->parse()->xpath('//article')));
    // Check pagination.
    $this->assertNoLinkByHref('?page=0');
    $this->assertLinkByHref('?page=1');
    $this->assertNoLinkByHref('?page=2');
    // Check only court decisions are shown.
    $this->assertEqual(10, count($this->parse()->xpath('//article[contains(@class, "court-decision")]')), 'Show only court decisions.');
    // Get page 2
    $params = array(
      'page' => '1',
    );
    $this->drupalGet($search, array('query' => $params));
    $this->assertEqual(1, count($this->parse()->xpath('//article')));
    $this->assertEqual(1, count($this->parse()->xpath('//article[contains(@class, "court-decision")]')), 'Show only court decisions.');

    // Test Facets.
    $test_case = 'Select 1 species OR';
    $params = array(
      'field_species' => '1',
    );
    $this->drupalGet($search, array('query' => $params));
    $this->assertNoFieldChecked('edit-field-species-operator');
    $this->assertOptionSelected('edit-field-species-values', '1');
    $this->assertRaw('>Rico (3)</option>');
    $this->assertEqual(3, count($this->parse()->xpath('//article')));

    $test_case = 'Select 2 species OR';
    $params = array(
      'field_species' => '1,3',
    );
    $this->drupalGet($search, array('query' => $params));
    $this->assertNoFieldChecked('edit-field-species-operator');
    $this->assertOptionSelected('edit-field-species-values', '1');
    $this->assertOptionSelected('edit-field-species-values', '3');
    $this->assertRaw('>Rico (3)</option>');
    $this->assertRaw('>King Julien (3)</option>');
    $this->assertEqual(5, count($this->parse()->xpath('//article')));

    $test_case = 'Select 2 species found AND';
    $params = array(
      'field_species' => '1,3',
      'field_species_operator' => 'AND',
    );
    $this->setRawContent($this->drupalGet($search, array('query' => $params)));
    $this->assertFieldChecked('edit-field-species-operator');
    $this->assertOptionSelected('edit-field-species-values', '1');
    $this->assertOptionSelected('edit-field-species-values', '3');
    $this->assertRaw('>Rico (1)</option>');
    $this->assertRaw('>King Julien (1)</option>');
    $this->assertEqual(1, count($this->parse()->xpath('//article')));
    $this->assertNoLinkByHref('?page=0');

    $test_case = 'Select 3 species not found AND';
    $params = array(
      'field_species' => '1,2,3',
      'field_species_operator' => 'AND',
    );
    $this->setRawContent($this->drupalGet($search, array('query' => $params)));
    $this->assertFieldChecked('edit-field-species-operator');
    $this->assertOptionSelected('edit-field-species-values', '1');
    $this->assertOptionSelected('edit-field-species-values', '2');
    $this->assertOptionSelected('edit-field-species-values', '3');
    $this->assertRaw('>Rico</option>');
    $this->assertRaw('>Private</option>');
    $this->assertRaw('>King Julien</option>');
    $this->assertEqual(0, count($this->parse()->xpath('//article')));
    $this->assertNoLinkByHref('?page=0');


    // Test Search word.
    $test_case = 'Search unique word in title';
    $params = array(
      'q' => 'ABCD',
    );
    $this->setRawContent($this->drupalGet($search, array('query' => $params)));
    $this->assertEqual(1, count($this->parse()->xpath('//article')));
    $this->assertNoLinkByHref('?page=0');

    $test_case = 'Search word in title and field_abstract';
    $params = array(
      'q' => 'Bluefin Tuna',
    );
    $this->setRawContent($this->drupalGet($search, array('query' => $params)));
    $this->assertEqual(2, count($this->parse()->xpath('//article')));
    $this->assertNoLinkByHref('?page=0');

    $test_case = 'Search by least recent';
    $params = array(
      'sort' => 'field_date_of_entry',
      'sortOrder' => 'asc',
    );
    $this->setRawContent($this->drupalGet($search, array('query' => $params)));
    $this->assertText('The M/V Saiga case');
    $this->assertNoText('Generic title');
    $this->assertNoLinkByHref('?page=0');

    $test_case = 'Search by lmost';
    $params = array(
      'sort' => 'field_date_of_entry',
      'sortOrder' => 'desc',
    );
    $this->setRawContent($this->drupalGet($search, array('query' => $params)));
    $this->assertText('Generic title');
    $this->assertNoText('The M/V Saiga case');
    $this->assertNoLinkByHref('?page=0');
  }
}
