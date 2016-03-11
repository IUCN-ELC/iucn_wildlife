<?php

/**
 * @file
 * Contains \Drupal\iucn_search\Form\IucnSearchForm.
 */

namespace Drupal\iucn_search\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\search_api\Entity\Index;
use Drupal\iucn_search\Edw\Facets\Facet;

class IucnSearchForm extends FormBase {

  protected $search_url_param = 'q';
  protected $items_per_page = 10;
  protected $items_viewmode = 'search_index';
  protected $resultCount = 0;
  protected $facets = [];

  public function __construct() {
    // @ToDo: Configure operator, limit, min_count for each facet
    // @ToDo: Translate facet titles
    // @ToDo: Move facets configuration in a .yml config file
    $facets = [
      'Country' => [
        'title' => 'Country',
        'field' => 'field_country',
        'entity_type' => 'node',
        'bundle' => 'country',
      ],
      'Type' => [
        'title' => 'Type',
        'field' => 'field_type_of_text',
        'entity_type' => 'term',
        'bundle' => 'document_types',
      ],
    ];
    foreach ($facets as $facet) {
      $this->facets[] = new Facet(
        $facet['title'],
        $facet['field'],
        'OR',
        '10',
        '1',
        $facet['entity_type'],
        $facet['bundle']
      );
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
    $form['text'] = [
      '#type' => 'textfield',
      '#title' => 'Search text',
      '#default_value' => $text,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => 'Search',
    ];
    $elements = [
      '#theme' => 'iucn_search_results',
      '#items' => $results,
    ];
    $form['display'] = [
      'results' => [
        'nodes' => ['#markup' => \Drupal::service('renderer')->render($elements)],
        'pager' => ['#type' => 'pager'],
      ],
      'facets' => $this->getRenderedFacets(),
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
    $search_text = $form_state->getValue('text');
    $form_state->setRedirect('iucn.search', [], ['query' => [$this->search_url_param => $search_text]]);
  }

  private function setQueryFacets(\Drupal\search_api\Query\QueryInterface &$query) {
    $query_facets = [];
    foreach ($this->facets as $facet) {
      $query_facets[] = $facet->getArray();
    }
    $query->setOption('search_api_facets', $query_facets);
  }

  private function setFacetsValues(array $values) {
    foreach ($this->facets as $key => &$facet) {
      $facet->setValues($values[$key]);
    }
  }

  private function getRenderedFacets() {
    $return = [];
    foreach ($this->facets as $facet) {
      $return[(string) $facet] = $facet->render();
    }
    return $return;
  }

  private function getSeachResults($search_text, $current_page) {
    $nodes = [];
    if (empty($index = Index::load('default_node_index'))) {
      drupal_set_message(t('The search index is not properly configured.'), 'error');
      return $results;
    }
    try {
      $query = $index->query();
      $query->keys($search_text);
      $offset = $current_page * $this->items_per_page;
      $query->range($offset, $this->items_per_page);
      $this->setQueryFacets($query);
      $resultSet = $query->execute();

      $this->resultCount = $resultSet->getResultCount();
      $this->setFacetsValues($resultSet->getExtraData('search_api_facets'));

      foreach ($resultSet->getResultItems() as $item) {
        $item_nid = $item->getField('nid')->getValues()[0];
        $node = \Drupal\node\Entity\Node::load($item_nid);
        $nodes[$item_nid] = \Drupal::entityTypeManager()->getViewBuilder('node')->view($node, $this->items_viewmode);
      }
    }
    catch (\Exception $e) {
      watchdog_exception('iucn_search', $e);
      drupal_set_message(t('An error occurred.'), 'error');
    }
    return $nodes;
  }

}
