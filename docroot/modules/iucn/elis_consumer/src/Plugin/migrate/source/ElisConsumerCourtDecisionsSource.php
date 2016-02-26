<?php

/**
 * @file
 * Contains \Drupal\elis_consumer\Plugin\migrate\source\ElisConsumerCourtDecisionsSource.
 */

namespace Drupal\elis_consumer\Plugin\migrate\source;

use Drupal\migrate_source_json\Plugin\migrate\source\JSONSource;

/**
 * Migrate court decision from ELIS database.
 *
 * @MigrateSource(
 *   id = "elis_consumer_court_decisions"
 * )
 */
class ElisConsumerCourtDecisionsSource extends JSONSource {

  public function __construct(array $configuration, $plugin_id, $plugin_definition, \Drupal\migrate\Entity\MigrationInterface $migration, array $namespaces = array()) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration, $namespaces);
  }

  /**
   * Return a count of all available source records.
   *
   * @return int
   *   The number of available source records.
   */
  public function _count($url) {
    return count($this->reader->getSourceFields($url));
  }

}