<?php
/**
 * @file
 * Contains \Drupal\iucn_search\Routing\RouteSubscriber.
 */

namespace Drupal\iucn_search\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    $route = $collection->get('entity.taxonomy_term.canonical');
    if ($route) {
      $route->setDefaults(array(
        '_controller' => '\Drupal\iucn_search\Controller\IucnTaxonomyController::redirect',
      ));
    }
  }

}