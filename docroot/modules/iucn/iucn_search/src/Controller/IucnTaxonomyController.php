<?php

/**
 * @file
 * Contains \Drupal\iucn_search\Controller\IucnTaxonomyController.
 */

namespace Drupal\iucn_search\Controller;

use Drupal\taxonomy\TermInterface;

class IucnTaxonomyController {
  public function redirect(TermInterface $taxonomy_term) {
    return ['#markup' => 'merge'];
  }
}