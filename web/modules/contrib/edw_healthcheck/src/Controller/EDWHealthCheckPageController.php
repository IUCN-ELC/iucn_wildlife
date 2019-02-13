<?php

namespace Drupal\edw_healthcheck\Controller;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Controller\ControllerBase;
use Drupal\edw_healthcheck\Plugin\EDWHealthCheckPlugin\EDWHealthCheckPluginManager;
use Drupal\edw_healthcheck\Render\JsonEDWHealthCheckRender;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Class EDWHealthCheckPageController.
 *
 * Produces the HTTP output that the bash script understands.
 *
 * @package Drupal\edw_healthcheck\Controller
 */
class EDWHealthCheckPageController extends ControllerBase {

  /**
   * The EDWHealthCheck plugin manager.
   *
   * We use this to get all of the EDWHealthCheck plugins.
   *
   * @var EDWHealthCheckPluginManager
   */
  protected $pluginManager;

  /**
   * The EDWHealthCheck module's config.
   *
   * We use this to get all of the EDWHealthCheck configuration settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Constructor.
   */
  public function __construct() {
    $this->pluginManager = \Drupal::service('plugin.manager.edw_healthcheck');
    $this->config = \Drupal::config('edw_healthcheck.settings');
  }

  /**
   * Main function building the string to show via HTTP.
   *
   * @param string $topic
   *   Optional parameter that can specify the information needed for rendering.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Returns an Http Response containing the processed output by the module.
   *
   * @throws PluginException
   *    The Plugin Exception needs to be handled.
   */
  public function content($topic = NULL) {
    if (!$this->config->get('edw_healthcheck.statuspage.enabled')) {
      throw new AccessDeniedHttpException("Edw health check page is disabled!");
    }

    $response = (new Response(NULL, Response::HTTP_OK, ['Content-Type' => 'text/json']))
      ->setMaxAge(0)
      ->setExpires();

    if ($topic == 'all') {
      $data = array_merge($this->getPluginData('core'),
        $this->getPluginData('modules'),
        $this->getPluginData('themes'),
        $this->getPluginData('last_cron')
      );
    } else {
      $data = $this->getPluginData($topic);
    }

    $render_instance = new JsonEDWHealthCheckRender();
    $content = $render_instance->render($data);

    $response->setContent($content);

    return $response;
  }

  /**
   * Reusable method.
   *
   * @param string $type
   *    The type of the component that needs to be handled.
   *
   * @return array
   *    The data array processed by the plugin.
   *
   * @throws PluginException
   *    The createInstance method of the Plugin Manager can throw a Plugin
   *    Exception.
   */
  protected function getPluginData($type) {
    if (!in_array($type, ['core', 'modules', 'themes', 'last_cron'])) {
      return [];
    }

    $data = [];
    if ($this->config->get('edw_healthcheck.components.' . $type . '.enabled')) {
      $plugin = $this->pluginManager->createInstance($type . '_edw_healthcheck');
      $data = $plugin->getData();
    }

    return $data;
  }
}
