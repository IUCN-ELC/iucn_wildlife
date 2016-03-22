<?php

namespace Drupal\iucn_search\Tests\mockup;

class SolrFacetMock extends \Drupal\iucn_search\edw\solr\SolrFacet {

  public function getEntityType() {
    return $this->getConfigValue('entity_type');
  }

  public function getBundle() {
    return $this->getConfigValue('bundle');
  }
}
