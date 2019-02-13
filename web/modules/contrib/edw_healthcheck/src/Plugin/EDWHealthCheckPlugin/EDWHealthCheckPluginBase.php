<?php

namespace Drupal\edw_healthcheck\Plugin\EDWHealthCheckPlugin;

use Drupal\Component\Plugin\PluginBase;

/**
 * A base class to help developers implement their own edw_healthcheck plugins.
 *
 * In this case all the properties can be read from the @EDWHealthCheckPlugin
 * annotation.
 *
 * In most cases it is probably fine to just use that value without any
 * additional processing. However, if an individual plugin needed to provide
 * special handling around either of these things it could just override
 * the method in that class definition for that plugin.
 */
abstract class EDWHealthCheckPluginBase extends PluginBase {

  /**
   * {@inheritdoc}
   */
  public function description() {
    // Retrieve the @description property from the annotation and return it.
    return $this->pluginDefinition['description'];
  }

  /**
   * {@inheritdoc}
   */
  public function type() {
    // Retrieve the @type property from the annotation and return it.
    return $this->pluginDefinition['type'];
  }

  /**
   * {@inheritdoc}
   */
  abstract public function getData();

  /**
   * {@inheritdoc}
   */
  abstract public function form();

}
