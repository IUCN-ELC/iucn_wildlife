<?php

/**
 * @file
 * Contains \Drupal\iucn_search\Controller\LegislationSearchController.
 */

namespace Drupal\iucn_search\Controller;

class LegislationSearchController extends DefaultSearchController {

  public function getRoute() {
    return 'iucn.search.legislation';
  }

  public function getContentType() {
    return [
      'machine_name' => 'legislation',
      'singular' => 'legislation',
      'plural' => 'legislations',
    ];
  }

}
