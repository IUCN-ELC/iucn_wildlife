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
    $options = [];
    $this->migrateExecutable = new MigrateExecutable($this->couMigration, $log, $options);
  }

  public function testCouMigration() {
    $this->migrateExecutable->import();
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'court_decision');
    $nids = $query->execute();
    $this->assertEqual(count($nids), 3);

    $query = \Drupal::database()
      ->select('migrate_map_elis_consumer_court_decisions', 'map')
      ->fields('map', ['destid1'])
      ->condition('map.sourceid1', 'COU-143756');
    $nid = reset($query->execute()->fetchCol());

    $node = \Drupal\node\Entity\Node::load($nid);

    // id => field_original_id
    $this->assertEqual('COU-143756', $node->field_original_id->getValue()[0]['value']);
    // isisMfn => field_isis_number
    $this->assertEqual('000103', $node->field_isis_number->getValue()[0]['value']);
    // dateOfEntry => field_date_of_entry
    $this->assertEqual('2006-11-29', date('Y-m-d', $node->field_date_of_entry->getValue()[0]['value']));
    // dateOfModification => field_date_of_modification
    $this->assertEqual('2016-03-11', date('Y-m-d', $node->field_date_of_modification->getValue()[0]['value']));

    // titleOfTextShort => title
    $this->assertEqual('Montreal Protocol', $node->getTitle());
    // titleOfText => field_original_title
    $this->assertEqual('The M/V Saiga case', $node->field_original_title->getValue()[0]['value']);

    // country => field_country
    $countries = [];
    foreach ($node->field_country->getValue() as $country) {
      $countries[] = \Drupal\node\Entity\Node::load($country['target_id'])->getTitle();
    }
    $compare = ['Guinea', 'Saint Vincent and the Grenadines'];
    $this->assertTrue(array_diff($countries, $compare) == array_diff($compare, $countries));
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
