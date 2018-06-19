<?php

/**
 * @file
 * Contains \Drupal\elis_consumer_test\Plugin\migrate\source\ElisConsumerCourtDecisionsSourceTest.
 */


namespace Drupal\elis_consumer_test\Plugin\migrate\source;
use Drupal\elis_consumer\Plugin\migrate\source\ElisConsumerCourtDecisionsSource;
use Drupal\migrate\Annotation\MigrateSource;
use Drupal\migrate\Entity\MigrationInterface;


/**
 * Migrate court decision from ELIS database.
 *
 * @MigrateSource(
 *   id = "elis_consumer_court_decisions_test"
 * )
 */
class ElisConsumerCourtDecisionsSourceTest extends ElisConsumerCourtDecisionsSource {

  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration) {
    global $base_url;
    $configuration['encoding'] = 'UTF-8';
    $configuration['path'] =  $base_url . '/' . drupal_get_path('module', 'elis_consumer_test') . '/data/CourtDecisions.xml';
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);
  }
}
