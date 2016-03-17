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
use Solarium\Client;
use Solarium\Core\Client\Request;

class IucnSearchForm extends FormBase {

  protected $search_url_param = 'q';

  protected $items_per_page = 10;

  protected $items_viewmode = 'search_result';

  protected $resultCount = 0;

  /**
   * A connection to the Solr server.
   *
   * @var \Solarium\Client
   */
  protected $solr;

  /**
   * Configuration for solr server.
   */
  protected $solr_configuration;

  /**
   * Search api index.
   *
   * @var \Drupal\search_api\Entity\Index
   */
  protected $index;

  protected $facets = [];

  public function __construct() {
    try {
      $this->index = Index::load('default_node_index');
      $server = $this->index->getServerInstance();
      $this->solr_configuration = $server->getBackendConfig() + array('key' => $server->id());
      $this->solr = new Client();
      $this->solr->createEndpoint($this->solr_configuration, TRUE);
    }
    catch (\Exception $e) {
      watchdog_exception('iucn_search', $e);
      drupal_set_message(t('An error occurred.'), 'error');
    }

    // @ToDo: Translate facet titles
    $facets = [
      'Subject' => [
        'title' => 'Subject',
        'placeholder' => 'Add subjects...',
        'field' => 'field_ecolex_subjects',
        'entity_type' => 'term',
        'bundle' => 'ecolex_subjects',
      ],
      'Country' => [
        'title' => 'Country',
        'placeholder' => 'Add countries...',
        'field' => 'field_country',
        'entity_type' => 'node',
        'bundle' => 'country',
      ],
      'Type of court' => [
        'title' => 'Type of court',
        'placeholder' => 'Add types...',
        'field' => 'field_type_of_text',
        'entity_type' => 'term',
        'bundle' => 'document_types',
      ],
      'Territorial subdivision' => [
        'title' => 'Sub-national/state level',
        'placeholder' => 'Add territory...',
        'field' => 'field_territorial_subdivisions',
        'entity_type' => 'term',
        'bundle' => 'territorial_subdivisions',
      ],
//      'Subdivision' => [
//        'title' => 'Subdivision',
//        'field' => 'field_subdivision',
//        'entity_type' => 'term',
//        'bundle' => 'subdivisions',
//      ],
//      'Justice' => [
//        'title' => 'Justice',
//        'field' => 'field_justices',
//        'entity_type' => 'term',
//        'bundle' => 'justices',
//      ],
//      'Instance' => [
//        'title' => 'Instance',
//        'field' => 'field_instance',
//        'entity_type' => 'term',
//        'bundle' => 'instances',
//      ],
//      'Decision status' => [
//        'title' => 'Decision status',
//        'field' => 'field_decision_status',
//        'entity_type' => 'term',
//        'bundle' => 'decision_status',
//      ],
//      'Court jurisdiction' => [
//        'title' => 'Court jurisdiction',
//        'field' => 'field_court_jurisdiction',
//        'entity_type' => 'term',
//        'bundle' => 'court_jurisdictions',
//      ],
    ];
    foreach ($facets as $facet) {
      $placeholder = !empty($facet['placeholder']) ? $facet['placeholder'] : 'Add...';
      $operator = !empty($_GET[$facet['field'] . '_operator']) ? $_GET[$facet['field'] . '_operator'] : 'OR';
      $limit = !empty($facet['limit']) ? $facet['limit'] : '-1';
      $min_count = !empty($facet['min_count']) ? $facet['min_count'] : '1';
      $display_type = !empty($facet['display_type']) ? $facet['display_type'] : 'select';
      $this->facets[] = new Facet(
        $facet['title'],
        $placeholder,
        $facet['field'],
        $operator,
        $limit,
        $min_count,
        $display_type,
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
    foreach ($this->facets as $facet) {
      $values = [];
      foreach ($form_state->getValue($facet->getField() . '_values') as $value => $selected) {
        if ($selected) {
          $values[] = $value;
        }
      }
      if (!empty($values)) {
        $query[$facet->getField()] = implode(',', $values);
      }
      if ($op = $form_state->getValue(($facet->getField() . '_operator'))) {
        $query[$facet->getField() . '_operator'] = $op;
      }
    }
    $form_state->setRedirect('iucn.search', [], ['query' => $query]);
  }

  private function setFacets(&$solarium_query, array $field_names) {
    $facet_set = $solarium_query->getFacetSet();
    $facet_set->setSort('count');
    $facet_set->setLimit(10);
    $facet_set->setMinCount(1);
    $facet_set->setMissing(FALSE);
    foreach ($this->facets as $facet) {
      $info = $facet->getArray();
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
    foreach ($this->facets as &$facet) {
      $solrFacet = $facetSet->getFacet($facet->getField());
      $facet->setValues($solrFacet->getValues());
    }
  }

  private function getRenderedFacets() {
    $return = [];
    foreach ($this->facets as $facet) {
      $return[$facet->getField()] = $facet->render();
    }
    return $return;
  }

  private function createSolariumRequest($solarium_query) {
    // Use the 'postbigrequest' plugin if no specific http method is
    // configured. The plugin needs to be loaded before the request is
    // created.
    if ($this->solr_configuration['http_method'] == 'AUTO') {
      $this->solr->getPlugin('postbigrequest');
    }

    $request = $this->solr->createRequest($solarium_query);

    if ($this->solr_configuration['http_method'] == 'POST') {
      $request->setMethod(Request::METHOD_POST);
    }
    elseif ($this->solr_configuration['http_method'] == 'GET') {
      $request->setMethod(Request::METHOD_GET);
    }
    if (strlen($this->solr_configuration['http_user']) && strlen($this->solr_configuration['http_pass'])) {
      $request->setAuthentication($this->solr_configuration['http_user'], $this->solr_configuration['http_pass']);
    }

    // Send search request.
    $response = $this->solr->executeRequest($request);
    $resultSet = $this->solr->createResult($solarium_query, $response);

    return $resultSet;
  }

  private function getSeachResults($search_text, $current_page) {
    $nodes = [];
    try {
      $solarium_query = $this->solr->createSelect();
      $solarium_query->setQuery($search_text);
      $solarium_query->setFields(array('*', 'score'));

      $field_names = $this->index->getServerInstance()->getBackend()->getFieldNames($this->index);
      $search_fields = $this->index->getFulltextFields();
      // Get the index fields to be able to retrieve boosts.
      $index_fields = $this->index->getFields();
      $query_fields = [];
      foreach ($search_fields as $search_field) {
        /** @var \Solarium\QueryType\Update\Query\Document\Document $document */
        $document = $index_fields[$search_field];
        $boost = $document->getBoost() ? '^' . $document->getBoost() : '';
        $query_fields[] = $field_names[$search_field] . $boost;
      }
      $solarium_query->getEDisMax()->setQueryFields(implode(' ', $query_fields));

      $offset = $current_page * $this->items_per_page;
      $solarium_query->setStart($offset);
      $solarium_query->setRows($this->items_per_page);

      $this->setFacets($solarium_query, $field_names);


      $resultSet = $this->createSolariumRequest($solarium_query);
      $documents = $resultSet->getDocuments();

      $this->resultCount = $resultSet->getNumFound();

      foreach ($documents as $document) {
        $fields = $document->getFields();
        $nid = $fields[$field_names['nid']];
        if (is_array($nid)) {
          $nid = reset($nid);
        }
        $node = \Drupal\node\Entity\Node::load($nid);
        $nodes[$nid] = \Drupal::entityTypeManager()->getViewBuilder('node')->view($node, $this->items_viewmode);
      }

      $this->setFacetsValues($resultSet->getFacetSet());
    }
    catch (\Exception $e) {
      watchdog_exception('iucn_search', $e);
      drupal_set_message(t('An error occurred.'), 'error');
    }
    return $nodes;
  }

}
