<?php

/**
 * @file
 * Contains \Drupal\iucn_search\Form\SearchFiltersForm.
 */

namespace Drupal\iucn_search\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\iucn_search\Controller\SearchPageController;
use Drupal\iucn_search\edw\solr\SolrFacet;

class SearchFiltersForm extends FormBase {

  private $yearmin = '1860';

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
        $url = Url::fromRoute('iucn.search', [], ['query' => $getCopy]);
        $close = sprintf('<a class="close" href="%s">&times;</a>', $url);
        $fqReset[] = ['#markup' =>  sprintf('<span class="filter-label">%s</span><span class="label label-primary">%s %s</span><hr>', $this->t('Court decisions filtered by:'), $term->getName(), $close)];
      }
    }

    $year = [
      '#max' => date('Y'),
      '#min' => 1860,
      '#title' => $this->t('Year/period'),
      '#type' => 'range_slider'
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
      'submit' => [
        '#attributes' => ['class' => ['btn-block', 'search-submit']],
        '#type' => 'submit',
        '#value' => $this->t('Search')
      ],
      'reset' => [
        '#attributes' => [
          'class' => ['btn', 'btn-default', 'btn-sm', 'btn-block', 'search-reset'],
          'type' => 'reset'
        ],
        '#tag' => 'button',
        '#type' => 'html_tag',
        '#value' => $this->t('Reset all filters')
      ]
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $query = SearchPageController::getSearch()->getHttpQueryParameters($form_state);

    $values = $form_state->getValues();

    if (!empty($values['year'])) {
      if ($values['year']['from'] !== $this->yearmin) {
        $query['yearmin'] = $values['year']['from'];
      }

      if ($values['year']['to'] !== date('Y')) {
        $query['yearmax'] = $values['year']['to'];
      }
    }

    $form_state->setRedirect('iucn.search', [], ['query' => $query]);
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
}
