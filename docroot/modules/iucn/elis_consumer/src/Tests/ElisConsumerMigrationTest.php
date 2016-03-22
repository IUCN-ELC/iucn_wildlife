<?php

namespace Drupal\elis_consumer\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\migrate\Entity\Migration;
use Drupal\migrate_tools\MigrateExecutable;
use Drupal\migrate\MigrateMessageInterface;

/**
 * Test migrations from ELIS.
 *
 * @group elis_consumer
 */
class ElisConsumerMigrationTest extends WebTestBase {

  public static $modules = ['iucn_search'];

  /**
   * @var \Drupal\migrate\Entity\Migration
   */
  private $couMigration;

  private $migrateExecutable;

  public function setUp() {
    parent::setUp();
    $this->couMigration = Migration::load('elis_consumer_court_decisions');
    $log = new DefaultElisMigrateMessage();
    $sourcePlugin = $this->couMigration->getSourcePlugin();
    $path = drupal_get_path('module', 'elis_consumer') . '/src/Tests/data/CourtDecisions.xml';
    $sourcePlugin->set_path($path);
    $sourcePlugin->enable_testing();
    $this->migrateExecutable = new MigrateExecutable($this->couMigration, $log, $options);
  }

  public function testCouMigration1() {
    $this->migrateExecutable->import();
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'court_decision');
    $nids = $query->execute();
    $this->assertEqual(count($nids), 3);
  }

}

class DefaultElisMigrateMessage implements MigrateMessageInterface {

  /**
   * Output a message from the migration.
   *
   * @param string $message
   *   The message to display.
   * @param string $type
   *   The type of message to display.
   *
   */
  public function display($message, $type = 'status') {
    print "{$type}: {$message}" . PHP_EOL;
  }

}
