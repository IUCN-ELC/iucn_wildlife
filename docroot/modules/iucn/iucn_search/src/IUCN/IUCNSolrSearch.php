<?php

/**
 * @file
 * Contains \Drupal\iucn_search\IUCN\IUCNSearch.
 */

namespace Drupal\iucn_search\IUCN;

use Solarium\Client;
use Drupal\iucn_search\Edw\Facets\Facet;


class IUCNSolrSearch {

  protected $parameters = NULL;
  protected $solr = NULL;
  protected $solrConfig = NULL;
  protected $facets = array();

  public function __construct(array $parameters, Client $searchServer, array $searchServerConfig) {
    $this->parameters = $parameters;
    $this->solr = $searchServer;
    $this->initFacets();
  }

  public function search() {

  }

  public function getFacets() {
    return $this->facets;
  }

  protected function initFacets() {
    // @ToDo: Translate facet titles
    $facets = [
      'ecolex_subjects' => [
        'title' => 'Subject',
        'placeholder' => 'Add subjects...',
      ],
      'field_country' => [
        'title' => 'Country',
        'placeholder' => 'Add countries...',
      ],
      'field_type_of_text' => [
        'title' => 'Type of court',
        'placeholder' => 'Add types...',
      ],
      'field_territorial_subdivisions' => [
        'title' => 'Sub-national/state level',
        'placeholder' => 'Add territory...',
      ],
//      'field_subdivision' => [
//        'title' => 'Subdivision',
//        'field' => 'field_subdivision',
//      ],
//      'field_justices' => [
//        'title' => 'Justice',
//        'field' => 'field_justices',
//      ],
//      'field_instance' => [
//        'title' => 'Instance',
//        'field' => 'field_instance',
//      ],
//      'field_decision_status' => [
//        'title' => 'Decision status',
//        'field' => 'field_decision_status',
//      ],
//      'field_court_jurisdiction' => [
//        'title' => 'Court jurisdiction',
//        'field' => 'field_court_jurisdiction',
//      ],
    ];
    foreach ($facets as $id => $config) {
      $config['id'] = $id;
      $this->facets[$id] = new Facet($id, $config);
    }
  }
}
