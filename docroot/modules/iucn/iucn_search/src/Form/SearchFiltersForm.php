<?php

/**
 * @file
 * Contains \Drupal\iucn_search\Form\SearchFiltersForm.
 */

namespace Drupal\iucn_search\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\iucn_search\edw\solr\SearchResult;
use Drupal\iucn_search\edw\solr\SolrFacet;
use Drupal\iucn_search\edw\solr\SolrSearch;
use Drupal\iucn_search\edw\solr\SolrSearchServer;

class SearchFiltersForm extends FormBase {

  protected $search = NULL;

  public function __construct() {
    try {
      $server_config = new SolrSearchServer('default_node_index');
      $this->search = new SolrSearch($_GET, $server_config);
    } catch (\Exception $e) {
      watchdog_exception('iucn_search', $e);
      drupal_set_message($this->t('An error occurred.'), 'error');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'iucn_search_filters';
  }

  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = [[
      '#attributes' => ['class' => ['search-filters', 'invisible']],
      '#title' => $this->t('Search filters'),
      '#type' => 'fieldset',
      $this->getRenderedFacets()
    ], [
      '#attributes' => ['class' => ['btn-block', 'search-submit']],
      '#type' => 'submit',
      '#value' => $this->t('Search')
    ], [
      '#attributes' => [
        'class' => ['btn', 'btn-default', 'btn-sm', 'btn-block', 'search-reset'],
        'type' => 'reset'
      ],
      '#tag' => 'button',
      '#type' => 'html_tag',
      '#value' => $this->t('Reset all filters')
    ]];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $query = $this->search->getHttpQueryParameters($form_state);
    $form_state->setRedirect('iucn.search_page_controller', [], ['query' => $query]);
  }

  private function getRenderedFacets() {
    $return = [];

    /** @var SolrFacet $facet */
    foreach ($this->search->getFacets() as $facet_id => $facet) {
      $return[$facet_id] = $facet->renderAsWidget($_GET);
    }

    return $return;
  }

}
