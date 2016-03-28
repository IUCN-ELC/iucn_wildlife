<?php

namespace Drupal\search_api_attachments\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Test the Facet functionality.
 *
 * @see Drupal\KernelTests\KernelTestBase;
 *
 * @ingroup search_api_attachments
 * @group search_api_attachments
 */
class ConfigTest extends WebTestBase {

  static public $modules = array('search_api_solr', 'search_api_attachments', 'search_api_attachments_test_solr');

  function testGet() {
    $this->assertEqual(1, 1);
  }
}
