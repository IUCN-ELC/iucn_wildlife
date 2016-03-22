<?php

namespace Drupal\iucn_search\Tests;

use Drupal\iucn_search\edw\solr\SolrFacet;
use Drupal\iucn_search\Tests\mockup\SolrFacetMock;
use Drupal\simpletest\WebTestBase;

/**
 * Test the Facet functionality.
 *
 * @see Drupal\simpletest\WebTestBase
 *
 * @ingroup iucn_search
 * @group iucn_search
 */
class SolrFacetTest extends WebTestBase {

  /** @var array */
  static public $modules = array('migrate', 'iucn_search');

  public function testConstructor() {
    $config = array(
      'title' => 'My English facet',
      'placeholder' => 'My English placeholder',
      'operator' => 'OR',
      'limit' => 3,
      'min_count' => 5,
      'widget' => 'select',
      'missing' => TRUE,
    );
    // Test the constructor
    $facet = new SolrFacetMock('field_ecolex_subjects', 'court_decision', 'solr_field_id', $config);
    $this->assertNotNull($facet);
    $this->assertEqual('field_ecolex_subjects', $facet->getId());
    $this->assertEqual('solr_field_id', $facet->getSolrFieldId());
    $this->assertEqual('My English facet', $facet->getTitle());
    $this->assertEqual('My English placeholder', $facet->getPlaceholder());
    $this->assertEqual('OR', $facet->getOperator());
    $this->assertEqual(3, $facet->getLimit());
    $this->assertEqual(5, $facet->getMinCount());
    $this->assertTrue($facet->getMissing());
    $this->assertEqual('court_decision', $facet->getBundle());
    $this->assertEqual('select', $facet->getWidget());
    $this->assertEqual('taxonomy_term', $facet->getEntityType());

    $config['bundle'] = 'court_decision';
    $config['solr_field_id'] = 'solr_field_id';
    $config['entity_type'] = 'taxonomy_term';
    $this->assertEqual($config, $facet->getConfig());

    $facet->setOperator(SolrFacet::$OPERATOR_AND);
    $this->assertEqual(SolrFacet::$OPERATOR_AND, $facet->getOperator());
  }

  public function testRender() {
    $config = array(
      'title' => 'My English facet',
      'placeholder' => 'My English placeholder',
      'operator' => 'OR',
      'limit' => 3,
      'min_count' => 5,
      'widget' => 'select',
    );
    // Test the constructor
    $facet = new SolrFacetMock('field_ecolex_subjects', 'court_decision', 'solr_field_id', $config);
    $result = $facet->render(SolrFacet::$RENDER_CONTEXT_WEB);
    $this->fail('Not implemented'); //@todo
  }

  /**
   * @throws \Drupal\Core\Config\ConfigValueException
   *   Exception thrown due to invalid configuration.
   */
  public function testInvalidFieldId() {
    $config = array();
    new SolrFacetMock(NULL, 'court_decision', 'solr_field_id', $config);
  }

  /**
   * @throws \Drupal\Core\Config\ConfigValueException
   *   Exception thrown due to invalid configuration.
   */
  public function testInvalidBundle() {
    $config = array();
    new SolrFacetMock('field_ecolex_subjects', 'unknown', 'solr_field_id', $config);
  }

  /**
   * @throws \Drupal\Core\Config\ConfigValueException
   *   Exception thrown due to invalid configuration.
   */
  public function testInvalidEntityType() {
    $config = array();
    new SolrFacetMock('unknown', 'court_decision', 'solr_field_id', $config);
  }

  /**
   * @throws \Drupal\Core\Config\ConfigValueException
   *   Exception thrown due to invalid configuration.
   */
  public function testInvalidSolrFieldId() {
    $config = array();
    new SolrFacetMock('unknown', 'court_decision', NULL, $config);
  }
}
