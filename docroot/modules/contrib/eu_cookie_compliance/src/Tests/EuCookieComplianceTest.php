<?php

namespace Drupal\eu_cookie_compliance\Tests;

/**
 * Test functionality for EU Cookie Compliance.
 *
 * @group eu_cookie_compliance
 */
class EuCookieComplianceTest extends EuCookieComplianceTestBasic {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'eu_cookie_compliance',
    'eu_cookie_compliance_test'
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
  }

  /**
   * Test routes.
   */
  public function testRoutes() {

    $this->drupalLogin($this->adminUser);

    $this->drupalGet('admin/config/system/eu-cookie-compliance');
    $this->assertResponse(200);

    $this->assertText('EU Cookie Compliance', 'Right Text');
  }

}
