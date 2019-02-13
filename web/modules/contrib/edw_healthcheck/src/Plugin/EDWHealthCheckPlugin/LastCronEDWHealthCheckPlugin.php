<?php

namespace Drupal\edw_healthcheck\Plugin\EDWHealthCheckPlugin;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a EDWHealthCheck plugin that manages Cron information.
 *
 * This plugin stores information on the last cron execution.
 *
 * @EDWHealthCheckPlugin(
 *   id = "last_cron_edw_healthcheck",
 *   description = @Translation("Information about the last run cron of the project."),
 *   type = "last_cron"
 * )
 */
class LastCronEDWHealthCheckPlugin extends EDWHealthCheckPluginBase implements ContainerFactoryPluginInterface, EDWHealthCheckPluginInterface {

  use StringTranslationTrait;

  /**
   * The request time.
   *
   * @var int
   */
  protected $requestTime;

  /**
   * The last cron run.
   *
   * @var int
   */
  protected $lastCronRun;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    // This class needs to translate strings, so we need to inject the string
    // translation service from the container. This means our plugin class has
    // to implement ContainerFactoryPluginInterface. This requires that we make
    // this create() method, and use it to inject services from the container.
    $last_cron_plugin = new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('string_translation'),
      $container->get('state')->get('system.cron_last'),
      $container->get('datetime.time')->getRequestTime()
    );
    return $last_cron_plugin;
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, TranslationInterface $translation, $last_cron_run, $request_time) {
    // Store the translation service.
    $this->setStringTranslation($translation);
    // Pass the other parameters up to the parent constructor.
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->lastCronRun = $last_cron_run;
    $this->requestTime = $request_time;
  }

  /**
   * Retrieve the data relevant to the plugin's type.
   *
   * @return array
   *   An array that contains the information relevant to the plugin's type.
   */
  public function getData() {
    $last_run = (gmdate("H", $this->lastCronRun) + 3) . ":" . gmdate("i:s", $this->lastCronRun);
    $plugin_data = ['last_cron_plugin' => ['last_cron_run' => $last_run, 'timestamp' => $this->lastCronRun, 'request_time' => $this->requestTime, 'active_and_running' => $this->checkCronStatus(), 'project_type' => 'last_cron']];
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

  /**
   * Get the status of the cron, compared with the last run.
   *
   * @return bool
   *   Returns false if the cron has not ran in 6 hours.
   */
  public function checkCronStatus() {
    return $this->lastCronRun + $this->getFailureThreshold() > $this->requestTime;
  }

  /**
   * Get the failure threshold configured for this plugin.
   *
   * @return int
   *   The failure threshold in seconds. Defaults to 6 hours.
   */
  private function getFailureThreshold() {
    return 21600;
  }
}
