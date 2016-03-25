<?php

/**
 * @file
 * Contains \Drupal\iucn_search\Controller\IucnTaxonomyController.
 */

namespace Drupal\iucn_search\Controller;

use Drupal\Core\Url;
use Drupal\taxonomy\TermInterface;
use Drupal\field\Entity\FieldConfig;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class IucnTaxonomyController {
  public function redirect(Request $request, TermInterface $taxonomy_term) {
    $vocid = $taxonomy_term->getVocabularyId();
    $field = NULL;

    $ids = \Drupal::entityQuery('field_config')
      ->condition('id', 'node.court_decision.', 'STARTS_WITH')
      ->execute();
    // Fetch all court_decision fields.
    $field_configs = FieldConfig::loadMultiple($ids);
    foreach ($field_configs as $field_instance) {
      if ($field_instance->getType() == 'entity_reference' &&
        $field_instance->getSetting('target_type') == 'taxonomy_term' &&
        in_array($vocid, $field_instance->getSetting('handler_settings')['target_bundles'])) {
        $field = $field_instance->getName();
        break;
      }
    }

    $query = $field ? [$field => $taxonomy_term->id()] : [];
    $url = Url::fromRoute('iucn.search', [], ['query' => $query])->toString();
    return new RedirectResponse($url);
  }
}
