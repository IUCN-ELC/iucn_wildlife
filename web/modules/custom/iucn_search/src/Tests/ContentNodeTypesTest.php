<?php

namespace Drupal\iucn_search\Tests;

use Drupal\search_api\Tests\WebTestBase;

/**
 * Test the Facet functionality.
 *
 * @see Drupal\simpletest\WebTestBase
 *
 * @ingroup iucn_search
 * @group iucn_search
 */
class ContentNodeTypesTest extends WebTestBase {

  /** @var array */
  static public $modules = array('iucn_search');

  protected $profile = 'minimal';

  public function testNodeCountryStructure() {
    // @todo: Test proper node type creation & fields
  }

  public function testNodeCourtDecisionStructure() {
    // @todo: Test proper node type creation & fields
  }
}
