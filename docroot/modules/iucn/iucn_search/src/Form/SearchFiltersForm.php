<?php

/**
 * @file
 * Contains \Drupal\iucn_search\Form\SearchFiltersForm.
 */

namespace Drupal\iucn_search\Form;

use Drupal\Core\Database\Database;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\iucn_search\Controller\SearchPageController;
use Drupal\iucn_search\edw\solr\SolrFacet;

class SearchFiltersForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'iucn_search_filters';
  }

  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $title = NULL) {
    // @todo: handle exception
    $fqParams = SearchPageController::getSearch()->getFilterQueryParameters();
    $fqReset = [];
    foreach ($fqParams as $field => $value) {
      $term = \Drupal\taxonomy\Entity\Term::load($value);
      if ($term) {
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

    if (!empty($_GET['yearmin'])) {
      $year['#from'] = $_GET['yearmin'];
    }

    if (!empty($_GET['yearmax'])) {
      $year['#to'] = $_GET['yearmax'];
    }

    $form = [
      'panel' => [
        '#attributes' => ['class' => ['search-filters', 'invisible']],
        '#title' => $title,
        '#type' => 'fieldset',
        'term' => $fqReset,
        'facets' => $this->getRenderedFacets(),
        'year' => $year
      ],
    ];
    if ($q = iucn_search_query_filter()) {
      $form['last_query'] = [ '#type' => 'hidden', '#value' => $q, ];
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
        'href' => Url::fromRoute('iucn.search')->toString(),
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

  private function getRenderedFacets() {
    $return = [];

    // @todo: handle exception
    /** @var SolrFacet $facet */
    foreach (SearchPageController::getSearch()->getFacets() as $facet_id => $facet) {
      $return[$facet_id] = $facet->renderAsWidget($_GET);
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
