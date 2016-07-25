<?php

namespace Drupal\eu_cookie_compliance\Tests;

/**
 * Simple EUCC test base.
 */
abstract class EuCookieComplianceTestBasic extends EuCookieComplianceTestBase {

  /**
   * An admin user with administrative permissions for EUCC.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * An admin user with administrative permissions for EUCC.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $anonUser;

  /**
   * The permissions required for a logged in user to test EUCC.
   *
   * @var array
   *   A list of permissions.
   */
  protected $permissions = [
    'display EU Cookie Compliance popup',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $admin_permissions = ['display EU Cookie Compliance popup', 'administer EU Cookie Compliance popup'];

    // Create an admin user to manage EU Cookie Compliance.
    $this->adminUser = $this->drupalCreateUser($admin_permissions);

    // Create an anonymous user to view EU Cookie Compliance popup.
    $this->anonUser = $this->drupalCreateUser($this->permissions);

  }

}
