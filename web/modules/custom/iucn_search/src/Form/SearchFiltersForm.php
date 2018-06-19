<?php

/**
 * @file
 * Contains \Drupal\iucn_search\Form\SearchFiltersForm.
 */

namespace Drupal\iucn_search\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Database\Database;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\iucn_search\Controller\SearchPageController;
use Drupal\iucn_search\edw\solr\SolrFacet;
use Drupal\taxonomy\Entity\Term;

class SearchFiltersForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'iucn_search_filters';
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param null $title
   *  The title of the block.
   * @param array $facets
   *  Array of facets to be rendered. If empty, all facets will be rendered.
   * @return array
   */
  public function buildForm(array $form, FormStateInterface $form_state, $title = NULL, $facets = []) {
    // @todo: handle exception
    $facets = $this->getRenderedFacets($facets);
    $hiddenInputs = [];
    $fqParams = SearchPageController::getSearch()->getFilterQueryParameters();
    $fqReset = [];
    foreach ($fqParams as $field => $value) {
      $term = Term::load($value);
      if ($term) {
        if (!array_key_exists($field, $facets)) {
          $hiddenInputs[$field] = [
            '#type' => 'hidden',
            '#default_value' => $value,
          ];
        }
        $getCopy = $_GET;
        unset($getCopy[$field]);
        /** @var Url $url */
        $url = Url::fromRoute('iucn.search', [], ['query' => $getCopy]);
        $close = sprintf('<a class="close" href="%s">&times;</a>', $url->toString());
        $fqReset[] = ['#markup' =>  sprintf('<span class="filter-label">%s</span><span class="label label-primary">%s %s</span><hr>', $this->t('Court decisions filtered by:'), $term->getName(), $close)];
      }
    }

    $year = [
      '#max'   => self::getYearMax(),
      '#min'   => self::getYearMin(),
      '#title' => $this->t('Year/period'),
      '#type'  => 'range_slider'
    ];

    $yearMin = !empty($_GET['yearmin']) ? Html::escape($_GET['yearmin']) : 0;
    if (!empty($yearMin)) {
      $year['#from'] = $yearMin;
    }

    $yearMax = !empty($_GET['yearmax']) ? Html::escape($_GET['yearmax']) : 0;
    if (!empty($yearMax)) {
      $year['#to'] = $yearMax;
    }

    $form = [
      'panel' => [
        '#attributes' => ['class' => ['search-filters', 'invisible']],
        '#title' => $title,
        '#type' => 'fieldset',
        'hidden' => $hiddenInputs,
        'term' => $fqReset,
        'facets' => $facets,
        'year' => $year
      ],
    ];
    if ($q = iucn_search_query_filter()) {
      $form['q'] = ['#type' => 'hidden', '#value' => $q];
    }
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#attributes' => ['class' => ['btn-block', 'search-submit']],
      '#type' => 'submit',
      '#value' => $this->t('Search')
    ];
    $form['actions']['reset'] = [
      '#attributes' => [
        'class' => ['btn', 'btn-default', 'btn-sm', 'btn-block', 'search-reset'],
        'href' => Url::fromRoute(\Drupal::request()->get(\Symfony\Cmf\Component\Routing\RouteObjectInterface::ROUTE_NAME))->toString(),
      ],
      '#tag' => 'a',
      '#type' => 'html_tag',
      '#value' => $this->t('Reset all filters')
    ];

    $form['#method'] = 'get';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    //This function has to be empty because we use $form['#method'] = 'get'
  }

  private function getRenderedFacets($facets = []) {
    $return = [];

    // @todo: handle exception
    /** @var SolrFacet $facet */
    foreach (SearchPageController::getSearch()->getFacets() as $facet_id => $facet) {
      $return[$facet_id] = $facet->renderAsWidget($_GET);
    }

    if (!empty($facets)) {
      foreach ($return as $facet_id => $facet) {
        if (!in_array($facet_id, $facets)) {
          unset($return[$facet_id]);
        }
      }
    }

    return $return;
  }

  private static function getYearMax() {
    return date('Y');
  }

  /**
   *
   * SELECT MIN(YEAR(STR_TO_DATE(field_date_of_text_value, '%Y'))) AS `year` FROM node__field_date_of_text;
   */
  private static function getYearMin() {
    $q = Database::getConnection()->select('node__field_date_of_text', 'a');
    $q->addExpression("MIN(YEAR(STR_TO_DATE(field_date_of_text_value, '%Y')))", 'year');
    if ($min = $q->execute()->fetchField()) {
      return $min;
    }
    return 1998;
  }
}
