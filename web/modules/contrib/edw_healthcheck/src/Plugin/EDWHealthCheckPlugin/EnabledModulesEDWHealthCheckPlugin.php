<?php

namespace Drupal\edw_healthcheck\Plugin\EDWHealthCheckPlugin;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a EDWHealthCheck plugin that manages the list of enabled modules.
 *
 * The following is the plugin annotation. This is parsed by Doctrine to make
 * the plugin definition. Any values defined here will be available in the
 * plugin definition.
 *
 * This should be used for metadata that is specifically required to instantiate
 * the plugin, or for example data that might be needed to display a list of all
 * available plugins where the user selects one. This means many plugin
 * annotations can be reduced to a plugin ID, a label and perhaps a description.
 *
 * @EDWHealthCheckPlugin(
 *   id = "enabled_modules_edw_healthcheck",
 *   description = @Translation("List of enabled modules in the project."),
 *   type = "enabled_modules"
 * )
 */
class EnabledModulesEDWHealthCheckPlugin extends EDWHealthCheckPluginBase implements ContainerFactoryPluginInterface, EDWHealthCheckPluginInterface {

  use StringTranslationTrait;

  /** @var \Drupal\Core\Extension\ModuleHandlerInterface */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    // This class needs to translate strings, so we need to inject the string
    // translation service from the container. This means our plugin class has
    // to implement ContainerFactoryPluginInterface. This requires that we make
    // this create() method, and use it to inject services from the container.
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('string_translation'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, TranslationInterface $translation, ModuleHandlerInterface $module_handler) {
    // Store the translation service.
    $this->setStringTranslation($translation);
    $this->moduleHandler = $module_handler;
    // Pass the other parameters up to the parent constructor.
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * Retrieve the data relevant to the plugin's type.
   *
   * @return array
   *   An array that contains the information relevant to the plugin's type.
   */
  public function getData() {
    $plugin_data = array();
    $plugin_data['enabled_modules']['project_type'] = 'enabled_modules';
    if ($raw_data = $this->moduleHandler->getModuleList()) {
      foreach ($raw_data as $module_name => $info) {
        $plugin_data['enabled_modules']['modules'][] = $module_name;
      }
    }
    return $plugin_data;
  }

  /**
   * Generate the form information specific to the plugin.
   *
   * @return array
   *   An array built with the settings form information for the plugin.
   */
  public function form() {
    // To be implemented in a later release.
    return [];
  }

}