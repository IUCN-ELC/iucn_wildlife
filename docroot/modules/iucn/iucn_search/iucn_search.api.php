<?php

/**
 * @file
 * Hooks provided by the iucn_search module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Lets modules alter facets.
 *
 * @param \Drupal\iucn_search\edw\solr\SolrSearchServer $server
 *   The Search server to pull information from about the indexed fields.
 *
 * @return array
 *   Array of SolrFacet objects
 */
function hook_edw_search_solr_facet_info(\Drupal\iucn_search\edw\solr\SolrSearchServer $server) {
  $config = array(
    'title' => 'Country',
    'placeholder' => 'Add countries...',
    'bundle' => 'country',
  );
  $field_names = $server->getSolrFieldsMappings();
  return array(
    'field_country' => new \Drupal\iucn_search\edw\solr\SolrFacet('field_country', 'country', $field_names['field_country'], $config)
  );
}


/**
 * Lets modules alter facets.
 *
 * @param array $facets
 *   Array with existing facets
 * @param \Drupal\iucn_search\edw\solr\SolrSearchServer $server
 *   The Search server to pull information from about the indexed fields.
 */
function hook_edw_search_solr_facet_info_alter(array $facets, \Drupal\iucn_search\edw\solr\SolrSearchServer $server) {
}
