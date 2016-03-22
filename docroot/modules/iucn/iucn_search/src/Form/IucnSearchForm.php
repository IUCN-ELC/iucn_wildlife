<?php

/**
 * @file
 * Contains \Drupal\iucn_search\Form\IucnSearchForm.
 */

namespace Drupal\iucn_search\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\iucn_search\IUCN\IUCNSolrSearch;
use Drupal\search_api\Entity\Index;
use Drupal\iucn_search\Edw\Facets\Facet;
use Solarium\Client;
use Solarium\Core\Client\Request;

class IucnSearchForm extends FormBase {

  protected $search_url_param = 'q';
  protected $items_per_page = 10;
  protected $items_viewmode = 'search_result';
  protected $resultCount = 0;

  protected $search = NULL;

  public function __construct() {
    try {
      $this->index = Index::load('default_node_index');
      $server = $this->index->getServerInstance();
      $solr_configuration = $server->getBackendConfig() + array('key' => $server->id());
      $solr = new Client();
      $solr->createEndpoint($solr_configuration, TRUE);
      $this->search = new IUCNSolrSearch($_GET, $solr, $solr_configuration);
    }
    catch (\Exception $e) {
      watchdog_exception('iucn_search', $e);
      drupal_set_message(t('An error occurred.'), 'error');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'iucn_search_form';
  }

  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $text = !empty($_GET[$this->search_url_param]) ? $_GET[$this->search_url_param] : '';
    $current_page = !empty($_GET['page']) ? $_GET['page'] : 0;
    $results = $this->getSeachResults($text, $current_page);
    pager_default_initialize($this->resultCount, $this->items_per_page);
    $elements = [
      '#theme' => 'iucn_search_results',
      '#items' => $results,
    ];
    $form['#attributes']['class'][] = 'row';
    $form['facets'] = [
      'facets' => $this->getRenderedFacets(),
      '#prefix' => '<div class="col-md-3 col-md-push-9 search-facets invisible">',
      '#suffix' => '</div>',
    ];
    $form['results'] = [
//      'search_text' => [
//        '#type' => 'textfield',
//        '#title' => 'Search text',
//        '#default_value' => $text,
//      ],
      'nodes' => [
        '#markup' => \Drupal::service('renderer')->render($elements)
      ],
      'pager' => [
        '#type' => 'pager'
      ],
      '#prefix' => '<div class="col-md-6 col-md-pull-3 search-results">',
      '#suffix' => '</div>',
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => 'Search',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Validation is optional.
  }
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $query = [];
    $search_text = !empty($_GET[$this->search_url_param]) ? $_GET[$this->search_url_param] : '';
    $query[$this->search_url_param] = $search_text;
    foreach ($this->facets as $facet_id => $facet) {
      $values = [];
      foreach ($form_state->getValue($facet_id . '_values') as $value => $selected) {
        if ($selected) {
          $values[] = $value;
        }
      }
      if (!empty($values)) {
        $query[$facet_id] = implode(',', $values);
      }
      if ($op = $form_state->getValue(($facet_id . '_operator'))) {
        $query[$facet_id . '_operator'] = $op;
      }
    }
    $form_state->setRedirect('iucn.search', [], ['query' => $query]);
  }

  private function setrfFacets(&$solarium_query, array $field_names) {
    $facet_set = $solarium_query->getFacetSet();
    $facet_set->setSort('count');
    $facet_set->setLimit(10);
    $facet_set->setMinCount(1);
    $facet_set->setMissing(FALSE);
    /** @var Facet $facet */
    foreach ($this->search->getFacets() as $facet) {
      $info = $facet->getConfig();
      $solr_field_name = $field_names[$info['field']];
      if (!empty($_GET[$info['field']])) {
        $values = explode(',', $_GET[$info['field']]);
        if (count($values) > 1) {
          $op = $info['operator'] ?: 'OR';
          $op = strtoupper($op);
          $val = '(' . implode(" {$op} ", $values) . ')';
        }
        else {
          $val = reset($values);
        }
        $solarium_query->createFilterQuery("facet:{$info['field']}")->setTags(["facet:{$info['field']}"])->setQuery("{$solr_field_name}:$val");
      }
      $facet_field = $facet_set->createFacetField($info['field'])->setField($solr_field_name);
      if (isset($info['operator']) && strtolower($info['operator']) === 'or') {
        $facet_field->setExcludes(["facet:{$info['field']}"]);
      }
      // Set limit, unless it's the default.
      if ($info['limit'] != 10) {
        $limit = $info['limit'] ?: -1;
        $facet_field->setLimit($limit);
      }
      // Set mincount, unless it's the default.
      if ($info['min_count'] != 1) {
        $facet_field->setMinCount($info['min_count']);
      }

      // Set missing, if specified.
      $facet_field->setMissing(!empty($info['missing']) ? $info['missing'] : FALSE);
    }
  }

  private function setFacetsValues($facetSet) {
    foreach ($this->search->getFacets() as $facet_id => &$facet) {
      $solrFacet = $facetSet->getFacet($facet_id);
      $values = $solrFacet->getValues();
      $getVals = explode(',', $_GET[$facet_id]);
      if (!empty($getVals)) {
        foreach ($getVals as $getVal) {
          if (!array_key_exists($getVal, $values)) {
            $values[$getVal] = 0;
          }
        }
      }
      $facet->setValues($values);
    }
  }

  private function getRenderedFacets() {
    $return = [];
    /** @var Facet $facet */
    foreach ($this->search->getFacets() as $facet_id => $facet) {
      $return[$facet_id] = $facet->render(Facet::$RENDER_CONTEXT_WEB);
    }
    return $return;
  }


  private function getSeachResults($search_text, $current_page) {

  }

}
