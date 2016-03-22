<?php

/**
 * @file
 * Contains \Drupal\iucn_search\edw\solr\solrSearch.
 */

namespace Drupal\iucn_search\edw\solr;


use Solarium\QueryType\Select\Result\Document;
use Solarium\QueryType\Select\Result\DocumentInterface;

class SearchResult {

  private $results = array();
  private $countTotal = 0;

  public function __construct($results, $count) {
    $this->results = $results;
    $this->countTotal = $count;
  }

  public function getResults() {
    return $this->results;
  }

  public function getCountTotal() {
    return $this->countTotal;
  }
}

class SolrSearch {

  /** @var array Request parameters (query) */
  protected $parameters = NULL;
  /** @var \Drupal\iucn_search\edw\solr\SolrSearchServer */
  protected $server = NULL;
  protected $facets = array();

  public function __construct(array $parameters, SolrSearchServer $config) {
    $this->parameters = $parameters;
    $this->server = $config;
    $this->initFacets();
  }

  /**
   * @param $page
   * @param $size
   * @return \Drupal\iucn_search\edw\solr\SearchResult
   *   Results
   */
  public function search($page, $size) {
    $search_text = $this->getParameter('q');
    $query = $this->server->createSelectQuery();
    $query_fields = $this->server->getQueryFields();
    $solr_id_field = $this->server->getDocumentIdField();

    $query->setQuery($search_text);
    $query->setFields(array('*', 'score'));
    $query->getEDisMax()->setQueryFields(implode(' ', $query_fields));
    $offset = $page * $size;
    $query->setStart($offset);
    $query->setRows($size);

    // Handle the facets
    $facet_set = $query->getFacetSet();
    $facet_set->setSort('count');
    $facet_set->setLimit(10);
    $facet_set->setMinCount(1);
    $facet_set->setMissing(FALSE);
    /** @var SolrFacet $facet */
    foreach ($this->facets as $facet) {
      $facet->render(SolrFacet::$RENDER_CONTEXT_SOLR, $query, $facet_set, $this->parameters);
    }
    $resultSet = $this->server->executeSearch($query);
    $this->updateFacetValues($resultSet->getFacetSet());
    $documents = $resultSet->getDocuments();
    $countTotal = $resultSet->getNumFound();

    $ret = array();
    /** @var Document $document */
    foreach ($documents as $document) {
      $fields = $document->getFields();
      $id = $fields[$solr_id_field];
      if (is_array($id)) {
        $id = reset($nid);
      }
      $ret[$id] = array('id' => $id);
    }
    return new SearchResult($ret, $countTotal);
  }

  public function getParameter($name) {
    $ret = NULL;
    if (!empty($this->parameters[$name])) {
      $ret = $this->parameters[$name];
      // @todo: Security check input parameters
    }
    return $ret;
  }

  public function getFacets() {
    return $this->facets;
  }

  protected function initFacets() {
    // @ToDo: Translate facet titles
    $facets = [
      'field_ecolex_subjects' => [
        'title' => 'Subject',
        'placeholder' => 'Add subjects...',
        'bundle' => 'ecolex_subjects',
      ],
      'field_country' => [
        'title' => 'Country',
        'placeholder' => 'Add countries...',
        'bundle' => 'country',
      ],
      'field_type_of_text' => [
        'title' => 'Type of court',
        'placeholder' => 'Add types...',
        'bundle' => 'document_types',
      ],
      'field_territorial_subdivisions' => [
        'title' => 'Sub-national/state level',
        'placeholder' => 'Add territory...',
        'bundle' => 'territorial_subdivisions',
      ],
//      'field_subdivision' => [
//        'title' => 'Subdivision',
//        'field' => 'field_subdivision',
//        'bundle' => 'subdivisions',
//      ],
//      'field_justices' => [
//        'title' => 'Justice',
//        'field' => 'field_justices',
//        'bundle' => 'justices',
//      ],
//      'field_instance' => [
//        'title' => 'Instance',
//        'field' => 'field_instance',
//        'bundle' => 'instances',
//      ],
//      'field_decision_status' => [
//        'title' => 'Decision status',
//        'field' => 'field_decision_status',
//        'bundle' => 'decision_status',
//      ],
//      'field_court_jurisdiction' => [
//        'title' => 'Court jurisdiction',
//        'field' => 'field_court_jurisdiction',
//        'bundle' => 'court_jurisdictions',
//      ],
    ];

    $field_names = $this->server->getFieldNames();
    foreach ($facets as $id => $config) {
      $solr_field_name = $field_names[$id];
      $config['id'] = $id;
      $this->facets[$id] = new SolrFacet($id, $config['bundle'], $solr_field_name, $config);
    }
  }


  private function updateFacetValues($facetSet) {
    /** @var SolrFacet $facet */
    foreach ($this->getFacets() as $facet_id => $facet) {
      $solrFacet = $facetSet->getFacet($facet_id);
      $values = $solrFacet->getValues();
      if ($request_parameters = $this->getParameter($facet_id)) {
        // Preserve user selection - add filters request.
        $sticky = explode(',', $_GET[$facet_id]);
        if (!empty($sticky)) {
          foreach ($sticky as $key) {
            if (!array_key_exists($key, $values)) {
              $values[$key] = 0;
            }
          }
        }
      }
      $facet->setValues($values);
    }
  }

  public function getHttpQueryParameters() {
    $query = [];
    if ($q = $this->getParameter('q')) {
      $query['q'] = $q;
    }
    /** @var SolrFacet $facet */
    foreach ($this->getFacets() as $facet_id => $facet) {
      $query = array_merge($query, $facet->render(SolrFacet::$RENDER_CONTEXT_GET));
    }
    return $query;
  }
}
