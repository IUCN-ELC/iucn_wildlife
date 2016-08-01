<?php

/**
 * @file
 * Contains \Drupal\migrate\Plugin\migrate\process\TaxonomyTerm.
 */

namespace Drupal\elis_consumer\Plugin\migrate\process;

use Drupal\field\Entity\FieldConfig;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\migrate\Annotation\MigrateProcessPlugin;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;


/**
 * This plugin takes a string and returns a tid
 *
 * @MigrateProcessPlugin(
 *   id = "taxonomy_term"
 * )
 */
class TaxonomyTerm extends ProcessPluginBase {

  /**
   * Create term if not found by label
   *
   * @var bool
   */
  protected $create = TRUE;

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $clean = htmlspecialchars_decode(trim(preg_replace('/\s+/', ' ', $value)));
    if (empty(trim($clean))) {
      return NULL;
    }
    // Find out the target vocabulary from target field configuration
    $bundle = $row->getDestination()['type'];
    $fi = FieldConfig::loadByName('node', $bundle, $destination_property);
    if ($fi->getType() != 'entity_reference') {
      $migrate_executable->saveMessage("Invalid mapping to non-taxonomic field reference: `{$fi->getType()}`", MigrationInterface::MESSAGE_WARNING);
      return NULL;
    }
    $target_taxonomy = reset($fi->getSetting('handler_settings')['target_bundles']);
    if (empty($target_taxonomy)) {
      return NULl;
    }
    $voc = Vocabulary::load($target_taxonomy);
    if (empty($voc->id())) {
      return NULl;
    }
    $q = \Drupal::database()->select('taxonomy_term_field_data', 't')->fields('t', array('tid'));
    $q->condition('vid', $voc->id())->condition('name', $clean);
    if ($tid = $q->execute()->fetchField()) {
      return $tid;
    }
    if ($this->create) {
      $term = Term::create(array('name' => $clean, 'vid' => $voc->id()));
      $term->save();
      $migrate_executable->saveMessage("Created new term: `{$clean}` ({$term->id()})", MigrationInterface::MESSAGE_NOTICE);
      return $term->id();
    }
    $migrate_executable->saveMessage("Failed to find term: `{$clean}`", MigrationInterface::MESSAGE_WARNING);
    return NULL;
  }


  /**
   * {@inheritdoc}
   */
  public function multiple() {
    return FALSE;
  }
}
