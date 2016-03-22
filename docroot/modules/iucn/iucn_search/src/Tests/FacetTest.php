<?php

namespace Drupal\iucn_search\Tests;

use Drupal\iucn_search\Edw\Facets\Facet;
use Drupal\simpletest\WebTestBase;

class FacetMock extends Facet {

  public function getEntityType() {
    return $this->entity_type;
  }
}

/**
 * Test the Facet functionality.
 *
 * @see Drupal\simpletest\WebTestBase
 *
 * @ingroup iucn_search
 * @group iucn_search
 */
class FacetTest extends WebTestBase {

  /** @var array */
  static public $modules = array('migrate', 'iucn_search');

  public function XtestConstructor() {
    $config = array(
      'title' => 'My English facet',
      'placeholder' => 'My English placeholder',
      'operator' => 'OR',
      'limit' => 3,
      'min_count' => 5,
      'widget' => 'select',
    );
    // Test the constructor
    $facet = new FacetMock('field_ecolex_subjects', 'court_decision', $config);
    $this->assertNotNull($facet);
    $this->assertEqual($config, $facet->getConfig());
    $this->assertEqual('field_ecolex_subjects', $facet->getId());
    $this->assertEqual('My English facet', $facet->getTitle());
    $this->assertEqual('My English placeholder', $facet->getPlaceholder());
    $this->assertEqual('OR', $facet->getOperator());
    $this->assertEqual(3, $facet->getLimit());
    $this->assertEqual(5, $facet->getMinCount());
    $this->assertEqual('select', $facet->getWidget());
    $this->assertEqual('taxonomy_term', $facet->getEntityType());

    $facet->setOperator('AND');
    $this->assertEqual('AND', $facet->getOperator());
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
    $facet = new FacetMock('field_ecolex_subjects', 'court_decision', $config);
    $result = $facet->render(Facet::$RENDER_CONTEXT_WEB);
    // @todo:
  }

  /**
   * @throws \Drupal\Core\Config\ConfigValueException
   *   Exception thrown due to invalid configuration.
   */
  public function XtestInvalidFieldId() {
    $config = array();
    new FacetMock(NULL, 'court_decision', $config);
  }

  /**
   * @throws \Drupal\Core\Config\ConfigValueException
   *   Exception thrown due to invalid configuration.
   */
  public function XtestInvalidBundle() {
    $config = array();
    new FacetMock('field_ecolex_subjects', 'unknown', $config);
  }

  /**
   * @throws \Drupal\Core\Config\ConfigValueException
   *   Exception thrown due to invalid configuration.
   */
  public function XtestInvalidEntityType() {
    $config = array();
    new FacetMock('unknown', 'court_decision', $config);
  }
}
