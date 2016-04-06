<?php

namespace Drupal\elis_consumer_test\Tests;

use Drupal\migrate\Entity\MigrationInterface;
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

  public static $modules = ['iucn_search', 'elis_consumer_test'];

  /**
   * @var \Drupal\migrate\Entity\Migration
   */
  private $migration;

  /** @var  MigrateExecutable */
  private $migrateExecutable;

  public function setUp() {
    parent::setUp();
    $this->migration = Migration::load('elis_consumer_court_decisions_test');
    $log = new DefaultElisMigrateMessage();
    $this->migrateExecutable = new MigrateExecutable($this->migration, $log);
  }

  public function testCouMigration() {
    $this->assertEqual(MigrationInterface::RESULT_COMPLETED, $this->migrateExecutable->import());
    $nids = \Drupal::entityQuery('node')->condition('type', 'court_decision')->execute();
    $this->assertEqual(count($nids), 2);

    $nid1 = \Drupal::database()
      ->select('migrate_map_elis_consumer_court_decisions_test', 'map')
      ->fields('map', ['destid1'])
      ->condition('map.sourceid1', 'COU-160172')
      ->execute()->fetchField();
    $this->assertTrue($nid1 !== NULL);
    $node1 = Node::load(reset($nids));
    $this->assertTrue($node1 !== NULL);

    $nid2 = \Drupal::database()
      ->select('migrate_map_elis_consumer_court_decisions_test', 'map')
      ->fields('map', ['destid1'])
      ->condition('map.sourceid1', 'COU-160173')
      ->execute()->fetchField();
    $this->assertTrue($nid2 !== NULL);
    $node2 = Node::load($nid2);
    $this->assertTrue($node2 !== NULL);

    // id => field_original_id
    $this->assertEqual('COU-160172', $node1->field_original_id->getValue()[0]['value']);

    // isisMfn => field_isis_number
    $this->assertEqual('160172', $node1->field_isis_number->getValue()[0]['value']);

    // dateOfEntry => field_date_of_entry
    $this->assertEqual('2016-03-23', $node1->field_date_of_entry->getValue()[0]['value']);

    // dateOfModification => field_date_of_modification
    $this->assertEqual('2016-03-23', $node1->field_date_of_modification->getValue()[0]['value']);
    $this->assertTrue(empty($node2->field_date_of_modification->getValue()));

    // titleOfText => field_original_title
    $this->assertEqual('The Republic versus Fikirin Magoso Hamis and two others - No. 66 of 2012', $node1->field_original_title->getValue()[0]['value']);

    // titleOfTextShort => title
    $this->assertEqual('R v. Fikirin Magoso Hamis & 2 others', $node1->getTitle());

    // country => field_country
    $countries = [];
    foreach ($node1->field_country->getValue() as $country) {
      $countries[] = Term::load($country['target_id'])->getName();
    }
    $compare = ['Tanzania, Un. Rep. of', 'Guinea'];
    $this->assertTrue(array_diff($countries, $compare) == array_diff($compare, $countries));

    // subject => field_ecolex_subjects
    $subjects = [];
    foreach ($node1->field_ecolex_subjects->getValue() as $subject) {
      $subjects[] = Term::load($subject['target_id'])->getName();
    }
    $this->assertEqual(2, count($subjects));
    $compare = ['Legal questions', 'Wild species & ecosystems']; //@todo &amp;
    $this->assertTrue(array_diff($subjects, $compare) == array_diff($compare, $subjects));

    // languageOfDocument => field_language_of_document
    $this->assertEqual('English', Term::load($node1->field_language_of_document->getValue()[0]['target_id'])->getName());

    // courtName => field_court_name
    $this->assertEqual('District Court', $node1->field_court_name->getValue()[0]['value']);

    // dateOfText => field_date_of_text
    $this->assertEqual('2014-09-30', $node1->field_date_of_text->getValue()[0]['value']);

    // referenceNumber => field_reference_number
    $this->assertEqual('No. 66 of 2012', $node1->field_reference_number->getValue()[0]['value']);

    // numberOfPages => field_number_of_pages
    $this->assertEqual(5, $node1->field_number_of_pages->getValue()[0]['value']);

    // availableIn => field_available_in
    $this->assertEqual('B7 p. 985:22/A', $node1->field_available_in->getValue()[0]['value']);

    // linkToFullText => field_url
    $links = $node1->field_url->getValue();
    $link_values = array(
      'http://www.ecolex.org/server2neu.php/libcat/docs/COU/Full/En/COU-160006.pdf',
      'http://www.ecolex.org/invalid_file_404/COU-160172_Matrix.pdf'
    );
    $this->assertEqual(2, count($links));
    foreach($links as $link) {
      $this->assertTrue(in_array($link['uri'], $link_values));
    }

    // internetReference => field_internet_reference
    $this->assertEqual('http://www.itlos.org/cgi-bin/cases/case_detail.pl?id=1&lang=en', $node1->field_internet_reference->getValue()[0]['uri']);

    // relatedWebSite => field_related_website
    $this->assertEqual('http://www.itlos.org/cgi-bin/cases/case_detail.pl?id=1&lang=en', $node1->field_related_website->getValue()[0]['uri']);

    // @todo: keyword
    // @todo: abstract
    // @todo: typeOfText
    // @todo: referenceToNationalLegislation
    // @todo: referenceToTreaties
    // @todo: referenceToCourtDecision
    // @todo: subdivision
    // @todo: justices
    // @todo: territorialSubdivision
    // @todo: linkToAbstract
    // @todo: statusOfDecision
    // @todo: referenceToEULegislation
    // @todo: seatOfCourt
    // @todo: courtJurisdiction
    // @todo: instance
    // @todo: officialPublication
    // @todo: region
    // @todo: referenceToFaolex
    // @todo: files

    // wildlifeCharges => field_charges
    $this->assertEqual('(1) unlawful entry into a game reserve, (2) unlawful possession of weapons in a game reserve', $node1->field_charges->getValue()[0]['value']);

    // wildlifeSpecies => field_species
    $values = [];
    foreach ($node1->field_species->getValue() as $value) {
      $values[] = Term::load($value['target_id'])->getName();
    }
    $compare = ['Wildebeest', 'Giraffe'];
    $this->assertTrue(array_diff($values, $compare) == array_diff($compare, $values));

    // wildlifeValue => field_money_value
    $this->assertEqual('TZS 1,920,000', $node1->field_money_value->getValue()[0]['value']);

    // wildlifeTransnational => field_transnational
    $this->assertEqual(1, $node1->field_transnational->getValue()[0]['value']);
    $this->assertEqual(0, $node2->field_transnational->getValue()[0]['value']);

    // wildlifeDecision => field_decision
    $this->assertEqual('Case withdrawn', $node1->field_decision->getValue()[0]['value']);

    // wildlifePenalty => field_penalty
    $this->assertEqual('-', $node1->field_penalty->getValue()[0]['value']);
    $this->assertEqual(
      '(1) first count: serve 1 year imprisonment, (2) second count: serve 3 years imprisonment, (3) third count: serve 3 years imprisonment, (4) fourth count: serve 20 years imprisonment',
      $node2->field_penalty->getValue()[0]['value']
    );

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
