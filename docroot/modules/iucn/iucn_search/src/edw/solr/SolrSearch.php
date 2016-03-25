<?php

/**
 * @file
 * Contains \Drupal\iucn_search\edw\solr\solrSearch.
 */

namespace Drupal\iucn_search\edw\solr;

use Solarium\QueryType\Select\Query\Component\FacetSet;
use Solarium\QueryType\Select\Result\Document;


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

  public function __construct(array $parameters, SolrSearchServer $server) {
    $this->parameters = $parameters;
    $this->server = $server;
    $this->facets = \Drupal::service('module_handler')->invokeAll('edw_search_solr_facet_info', array('server' => $server));
    \Drupal::service('module_handler')->alter('edw_search_solr_facet_info', $this->facets, $server);
  }

  /**
   * @param $page
   * @param $size
   * @param $filterQueryFields array
   * @return \Drupal\iucn_search\edw\solr\SearchResult
   *   Results
   */
  public function search($page, $size) {
    $search_text = $this->getParameter('q');
    $query = $this->server->createSelectQuery();
    $query_fields = array_values($this->server->getSearchFieldsMappings());
    $solr_id_field = $this->server->getDocumentIdField();
    $solr_field_mappings = $this->server->getSolrFieldsMappings();

    $query->setQuery($search_text);
    $query->setFields(array('*', 'score'));
    $query->getEDisMax()->setQueryFields(implode(' ', $query_fields));
    $offset = $page * $size;
    $query->setStart($offset);
    $query->setRows($size);

    // Handle the facets
    $facetSet = $query->getFacetSet();
    $facetSet->setSort('count');
    $facetSet->setLimit(10);
    $facetSet->setMinCount(1);
    $facetSet->setMissing(FALSE);
    /** @var SolrFacet $facet */
    foreach ($this->facets as $facet) {
      $facet->createSolrFacet($facetSet);
      $facet->alterSolrQuery($query, $this->parameters);
    }
    foreach ($this->getFilterQueryParameters() as $field => $value) {
      $fq = $query->createFilterQuery(array(
        'key' => $solr_field_mappings[$field],
        'query' => "{$solr_field_mappings[$field]}:{$value}",
      ));
      $query->addFilterQuery($fq);
    }
    \Drupal::service('module_handler')->alter('edw_search_solr_query', $query);
    $resultSet = $this->server->executeQuery($query);
    $this->updateFacetValues($resultSet->getFacetSet());
    $documents = $resultSet->getDocuments();
    $countTotal = $resultSet->getNumFound();

    $ret = array();
    /** @var Document $document */
    foreach ($documents as $document) {
      $fields = $document->getFields();
      $id = $fields[$solr_id_field];
      if (is_array($id)) {
        $id = reset($id);
      }
      $ret[$id] = array('id' => $id);
    }
    \Drupal::service('module_handler')->alter('edw_search_solr_results', $ret);
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

  public function getFilterQueryParameters() {
    $ret = [];
    $solr_field_mappings = $this->server->getSolrFieldsMappings();
    foreach ($this->parameters as $field => $value) {
      // Add the filter only if the field in indexed and is not faceted.
      if (!empty($solr_field_mappings[$field]) && !array_key_exists($field, $this->facets)) {
        $ret[$field] = $value;
      }
    }
    return $ret;
  }

  public function getFacets() {
    return $this->facets;
  }

  /**
   * @param \Solarium\QueryType\Select\Result\FacetSet $facetSet
   */
  private function updateFacetValues(\Solarium\QueryType\Select\Result\FacetSet $facetSet) {
    /** @var SolrFacet $facet */
    foreach ($this->getFacets() as $facet_id => $facet) {
      /** @var \Solarium\QueryType\Select\Result\Facet\Field $solrFacet */
      if ($solrFacet = $facetSet->getFacet($facet_id)) {
        $values = $solrFacet->getValues();
        if ($request_parameters = $this->getParameter($facet_id)) {
          // Preserve user selection - add filters request.
          $sticky = explode(',', $request_parameters);
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
  }

  public function getHttpQueryParameters($form_state) {
    $query = [];
    if ($q = $this->getParameter('q')) {
      $query['q'] = $q;
    }
    /** @var SolrFacet $facet */
    foreach ($this->getFacets() as $facet_id => $facet) {
      $query = array_merge($query, $facet->renderAsGetRequest($form_state));
    }
    $query = array_merge($query, $this->getFilterQueryParameters());
    return $query;
  }
}
