<?php

namespace Drupal\search_api_solr\Controller;

/**
 * Provides a listing of SolrCache.
 */
class SolrCacheListBuilder extends AbstractSolrEntityListBuilder {

  /**
   * @var string
   */
  protected $label = 'Solr Cache';

  /**
   * Returns a list of all disabled caches for current server.
   *
   * @return array
   * @throws \Drupal\search_api\SearchApiException
   */
  protected function getDisabledEntities(): array {
    /** @var \Drupal\search_api_solr\SolrBackendInterface $backend */
    $backend = $this->getBackend();
    return $backend->getDisabledCaches();
  }

}
