<?php

namespace Drupal\elis_consumer\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\migrate\Entity\Migration;
use Drupal\migrate_tools\MigrateExecutable;
use Drupal\migrate\MigrateMessageInterface;
use Drupal\node\Entity\Node;
use \Drupal\taxonomy\Entity\Term;

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

  public function createNeededCountries() {
    $node = Node::create(array(
      'type' => 'country',
      'title' => 'Guinea',
      'langcode' => 'en',
      'uid' => '1',
      'status' => 1,
    ));
    $node->save();
    $node = Node::create(array(
      'type' => 'country',
      'title' => 'Saint Vincent and the Grenadines',
      'langcode' => 'en',
      'uid' => '1',
      'status' => 1,
    ));
    $node->save();
    $node = Node::create(array(
      'type' => 'country',
      'title' => 'New Zealand',
      'langcode' => 'en',
      'uid' => '1',
      'status' => 1,
    ));
    $node->save();
    $node = Node::create(array(
      'type' => 'country',
      'title' => 'Australia',
      'langcode' => 'en',
      'uid' => '1',
      'status' => 1,
    ));
    $node->save();
    $node = Node::create(array(
      'type' => 'country',
      'title' => 'Japan',
      'langcode' => 'en',
      'uid' => '1',
      'status' => 1,
    ));
    $node->save();
  }

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

    $this->createNeededCountries();
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

    $node = Node::load($nid);

    // id => field_original_id
    $this->assertEqual('COU-143756', $node->field_original_id->getValue()[0]['value']);
    // isisMfn => field_isis_number
    $this->assertEqual('000103', $node->field_isis_number->getValue()[0]['value']);

    // dateOfEntry => field_date_of_entry
    $this->assertEqual('2006-11-29', $node->field_date_of_entry->getValue()[0]['value']);
    // dateOfModification => field_date_of_modification
    $this->assertEqual('2016-03-11', $node->field_date_of_modification->getValue()[0]['value']);
    // dateOfText => field_date_of_text
    $this->assertEqual('1997-12-04', $node->field_date_of_text->getValue()[0]['value']);

    // titleOfTextShort => title
    $this->assertEqual('Montreal Protocol', $node->getTitle());
    // titleOfText => field_original_title
    $this->assertEqual('The M/V Saiga case', $node->field_original_title->getValue()[0]['value']);

    // country => field_country
    $countries = [];
    foreach ($node->field_country->getValue() as $country) {
      $countries[] = Node::load($country['target_id'])->getTitle();
    }
    $compare = ['Guinea', 'Saint Vincent and the Grenadines'];
    $this->assertTrue(array_diff($countries, $compare) == array_diff($compare, $countries));

    // subject => field_ecolex_subjects
    $subjects = [];
    foreach ($node->field_ecolex_subjects->getValue() as $subject) {
      $subjects[] = Term::load($subject['target_id'])->getName();
    }
    $this->assertEqual(4, count($subjects));
    $compare = ['Fisheries', 'Sea', 'Subject1_EN', 'Legal questions'];
    $this->assertTrue(array_diff($subjects, $compare) == array_diff($compare, $subjects));

    // languageOfDocument => field_language_of_document
    $this->assertEqual('English', Term::load($node->field_language_of_document->getValue()[0]['target_id'])->getName());

    // courtName => field_court_name
    $this->assertEqual('International Tribunal for the Law of the Sea', $node->field_court_name->getValue()[0]['value']);


    // referenceNumber => field_reference_number
    $this->assertEqual('List of cases No. 1', $node->field_reference_number->getValue()[0]['value']);

    // numberOfPages => field_number_of_pages
    $this->assertEqual(19, $node->field_number_of_pages->getValue()[0]['value']);

    // availableIn => field_available_in
    $this->assertEqual('B7 p. 985:22/A', $node->field_available_in->getValue()[0]['value']);

    // linkToFullText => field_url
    $links = $node->field_url->getValue();
    $link_values = array(
      'http://www.ecolex.org/server2neu.php/libcat/docs/TRE/Full/En/TRE-000953.pdf',
    );
    $this->assertEqual(1, count($links));
    foreach($links as $link) {
      $this->assertTrue(in_array($link['uri'], $link_values));
    }
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
