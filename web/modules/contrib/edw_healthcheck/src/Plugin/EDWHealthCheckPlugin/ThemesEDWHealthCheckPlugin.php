<?php

namespace Drupal\edw_healthcheck\Plugin\EDWHealthCheckPlugin;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a EDWHealthCheck plugin that manages Theme information.
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
 *   id = "themes_edw_healthcheck",
 *   description = @Translation("Information about the Themes of the project."),
 *   type = "themes"
 * )
 */
class ThemesEDWHealthCheckPlugin extends EDWHealthCheckPluginBase implements ContainerFactoryPluginInterface, EDWHealthCheckPluginInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    // This class needs to translate strings, so we need to inject the string
    // translation service from the container. This means our plugin class has
    // to implement ContainerFactoryPluginInterface. This requires that we make
    // this create() method, and use it to inject services from the container.
    $themes_plugin = new static(
          $configuration,
          $plugin_id,
          $plugin_definition,
          $container->get('string_translation')
    );
    return $themes_plugin;
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, TranslationInterface $translation) {
    // Store the translation service.
    $this->setStringTranslation($translation);
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
    $themes = \Drupal::service('theme_handler')->rebuildThemeData();

    $plugin_data = array();
    foreach ($themes as $theme_name => $desc) {
      $plugin_data[$theme_name] = (array) $desc;
      $plugin_data[$theme_name]['project_type'] = 'theme';
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
