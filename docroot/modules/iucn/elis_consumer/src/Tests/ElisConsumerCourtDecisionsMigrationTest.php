<?php

namespace Drupal\elis_consumer\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\migrate\Entity\Migration;
use Drupal\migrate_tools\MigrateExecutable;
use Drupal\migrate_tools\DrushLogMigrateMessage;

/**
 * Test migrations from ELIS.
 *
 * @group Elis Consumer
 */
class ElisConsumerMigrationTest extends WebTestBase {

  public static $modules = ['iucn_search'];
  protected $profile = 'standard';

  /**
   * @var \Drupal\migrate\Entity\Migration
   */
  private $couMigration;

  private $migrateExecutable;

  public function setUp() {
    parent::setUp();
    $this->couMigration = Migration::load('elis_consumer_court_decisions');
    $options = [
      'limit' => 10,
    ];
    $log = new DrushLogMigrateMessage();
    $sourcePlugin = $couMigration->getSourcePlugin();
    $path = drupal_get_path('module', 'elis_consumer') . '/src/Tests/data/CourtDecisions.xml';
    $sourcePlugin->set_path($path);
    $sourcePlugin->enable_testing();
    $this->migrateExecutable = new MigrateExecutable($couMigration, $log, $options);
  }

  public function testCouMigration1() {
  }

}