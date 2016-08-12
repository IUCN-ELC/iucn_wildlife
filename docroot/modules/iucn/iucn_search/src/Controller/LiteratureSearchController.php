<?php

/**
 * @file
 * Contains \Drupal\iucn_search\Controller\LiteratureSearchController.
 */

namespace Drupal\iucn_search\Controller;

class LiteratureSearchController extends DefaultSearchController {

  public function getRoute() {
    return 'iucn.search.literature';
  }

  public function getContentType() {
    return [
      'machine_name' => 'literature',
      'singular' => 'literature',
      'plural' => 'literatures',
    ];
  }
}
