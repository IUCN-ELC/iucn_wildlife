<?php

/**
 * @file
 * Contains \Drupal\iucn_search\Controller\SearchPageController.
 */

namespace Drupal\iucn_search\Controller;

class SearchPageController extends DefaultSearchController {

  protected function getSortFields() {
    $return = parent::getSortFields();
    $return['relevance'] = [
      'field' => 'score',
      'order' => 'desc',
      'text' => 'relevance',
    ];
    return $return;
  }

  protected function getDefaultSorting () {
    return [
      'sort' => 'score',
      'sortOrder' => 'desc',
    ];
  }

  public function getRoute() {
    return 'iucn.search';
  }

  public function getContentType() {
    return [
      'machine_name' => 'court_decision',
      'singular' => 'court decision',
      'plural' => 'court decisions',
    ];
  }
}
