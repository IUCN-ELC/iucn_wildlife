<?php

namespace Drupal\edw_healthcheck\Plugin\EDWHealthCheckPlugin;

/**
 * An interface for all EDWHealthCheck type plugins.
 *
 * When defining a new plugin type you need to define an interface that all
 * plugins of the new type will implement. This ensures that consumers of the
 * plugin type have a consistent way of accessing the plugin's functionality. It
 * should include access to any public properties, and methods for accomplishing
 * whatever business logic anyone accessing the plugin might want to use.
 */
interface EDWHealthCheckPluginInterface {

  /**
   * Provide a description of the edw_healthcheck plugin.
   *
   * @return string
   *   A string description of the EDWHealthCheck plugin.
   */
  public function description();

  /**
   * Provide the type of information that the plugin handles.
   *
   * @return string
   *   A string defining the type of information handled by the plugin.
   */
  public function type();

  /**
   * Retrieve the data relevant to the plugin's type.
   *
   * @return array
   *   An array that contains the information relevant to the plugin's type.
   */
  public function getData();

  /**
   * Generate the form information specific to the plugin.
   *
   * @return array
   *   An array built with the settings form information for the plugin.
   */
  public function form();

}
