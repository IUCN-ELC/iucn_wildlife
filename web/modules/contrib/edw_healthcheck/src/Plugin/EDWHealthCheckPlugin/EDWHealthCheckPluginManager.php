<?php

namespace Drupal\edw_healthcheck\Plugin\EDWHealthCheckPlugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\edw_healthcheck\Annotation\EDWHealthCheckPlugin;

/**
 * A plugin manager for EDWHealthCheck plugins.
 *
 * The EDWHealthCheckPluginManager class extends the DefaultPluginManager to
 * provide a way to manage EDWHealthCheck plugins. A plugin manager defines
 * a new plugin type and how instances of any plugin of that type will be
 * discovered, instantiated
 * and more.
 *
 * Using the DefaultPluginManager as a starting point sets up our EDWHealthCheck
 * plugin type to use annotated discovery.
 *
 * The plugin manager is also declared as a service in
 * edw_healthcheck.services.yml so that it can be easily accessed and used
 * anytime we need to work with edw_healthcheck plugins.
 */
class EDWHealthCheckPluginManager extends DefaultPluginManager {

  /**
   * Creates the discovery object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    // This tells the plugin manager to look for EDWHealthCheckPlugin plugins
    // in the 'src/Plugin/EDWHealthCheckPlugin' subdirectory of any enabled
    // modules.
    $subdir = 'Plugin/EDWHealthCheckPlugin';

    // The name of the interface that plugins should adhere to. Drupal will
    // enforce this as a requirement. If a plugin does not implement this
    // interface, Drupal will throw an error.
    $plugin_interface = EDWHealthCheckPluginInterface::class;

    // The name of the annotation class that contains the plugin definition.
    $plugin_definition_annotation_name = EDWHealthCheckPlugin::class;

    parent::__construct($subdir, $namespaces, $module_handler, $plugin_interface, $plugin_definition_annotation_name);

    // This sets the caching method for our plugin definitions. Plugin
    // definitions are discovered by examining the $subdir defined above, for
    // any classes with an $plugin_definition_annotation_name. The annotations
    // are read, and then the resulting data is cached using the provided cache
    // backend.
    $this->setCacheBackend($cache_backend, 'edw_healthcheck_info');
  }

}
