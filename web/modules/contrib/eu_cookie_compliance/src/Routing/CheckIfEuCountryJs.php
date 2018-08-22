<?php

namespace Drupal\eu_cookie_compliance\Routing;

use Symfony\Component\Routing\Route;

/**
 * Defines dynamic routes.
 */
class CheckIfEuCountryJs {

  /**
   * {@inheritdoc}
   */
  public function routes() {
    $routes = array();
    if (\Drupal::moduleHandler()->moduleExists('smart_ip')) {
      $routes['eu_cookie_compliance.check_if_eu_country_js'] = new Route(
        '/eu-cookie-compliance-check',
        [
          '_controller' => '\Drupal\eu_cookie_compliance\Controller\CheckIfEuCountryJsController::content',
        ],
        [
          '_permission' => 'access content',
        ]
      );
    }
    return $routes;
  }

}
