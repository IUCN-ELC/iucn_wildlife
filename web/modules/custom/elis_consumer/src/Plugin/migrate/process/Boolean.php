<?php

/**
 * @file
 * Contains \Drupal\migrate\Plugin\migrate\process\Boolean.
 */

namespace Drupal\elis_consumer\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\migrate\Annotation\MigrateProcessPlugin;


/**
 * This plugin takes a string and returns a tid
 *
 * @MigrateProcessPlugin(
 *   id = "boolean"
 * )
 */
class Boolean extends ProcessPluginBase {

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
    $value = strtolower(trim($value));
    $value = !empty($value) && ($value == 'true' || $value == 'y' || $value == 'on' || $value == 'yes' || $value == 'ok');
    return $value ? 1 : 0;
  }


  /**
   * {@inheritdoc}
   */
  public function multiple() {
    return FALSE;
  }
}
