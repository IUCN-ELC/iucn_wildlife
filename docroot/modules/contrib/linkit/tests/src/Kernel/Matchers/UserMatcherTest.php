<?php

namespace Drupal\Tests\linkit\Kernel\Matchers;

use Drupal\Tests\linkit\Kernel\LinkitKernelTestBase;
use Drupal\user\Entity\Role;

/**
 * Tests user matcher.
 *
 * @group linkit
 */
class UserMatcherTest extends LinkitKernelTestBase {

  /**
   * The matcher manager.
   *
   * @var \Drupal\linkit\MatcherManager
   */
  protected $manager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->manager = $this->container->get('plugin.manager.linkit.matcher');

    $custom_role = Role::create(array(
      'id' => 'custom_role',
      'label' => 'custom_role',
    ));
    $custom_role->save();

    $custom_role_admin = Role::create(array(
      'id' => 'custom_role_admin',
      'label' => 'custom_role_admin',
    ));
    $custom_role_admin->save();

    $this->createUser(['name' => 'lorem']);
    $this->createUser(['name' => 'foo']);

    $account = $this->createUser(['name' => 'ipsumlorem']);
    $account->addRole($custom_role);
    $account->save();

    $account = $this->createUser(['name' => 'lorem_custom_role']);
    $account->addRole($custom_role);
    $account->save();

    $account = $this->createUser(['name' => 'lorem_custom_role_admin']);
    $account->addRole($custom_role_admin);
    $account->save();

    $account = $this->createUser(['name' => 'blocked_lorem']);
    $account->block();
    $account->save();
  }

  /**
   * Tests user matcher.
   */
  public function testUserMatcherWidthDefaultConfiguration() {
    /** @var \Drupal\linkit\MatcherInterface $plugin */
    $plugin = $this->manager->createInstance('entity:user', []);
    $suggestions = $plugin->execute('Lorem');
    $this->assertEquals(4, count($suggestions->getSuggestions()), 'Correct number of suggestions');
  }

  /**
   * Tests user matcher with role filer.
   */
  public function testUserMatcherWidthRoleFiler() {
    /** @var \Drupal\linkit\MatcherInterface $plugin */
    $plugin = $this->manager->createInstance('entity:user', [
      'settings' => [
        'roles' => [
          'custom_role' => 'custom_role',
        ],
      ],
    ]);

    $suggestions = $plugin->execute('Lorem');
    $this->assertEquals(2, count($suggestions->getSuggestions()), 'Correct number of suggestions');
  }

  /**
   * Tests user matcher with include blocked setting activated.
   */
  public function testUserMatcherWidthIncludeBlocked() {
    /** @var \Drupal\linkit\MatcherInterface $plugin */
    $plugin = $this->manager->createInstance('entity:user', [
      'settings' => [
        'include_blocked' => TRUE,
      ],
    ]);

    // Test without permissions to see blocked users.
    $suggestions = $plugin->execute('blocked');
    $this->assertEquals(0, count($suggestions->getSuggestions()), 'Correct number of suggestions');

    // Set the current user to a user with 'administer users' permission.
    \Drupal::currentUser()->setAccount($this->createUser([], ['administer users']));

    // Test with permissions to see blocked users.
    $suggestions = $plugin->execute('blocked');
    $this->assertEquals(1, count($suggestions->getSuggestions()), 'Correct number of suggestions');
  }

}
