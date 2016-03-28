<?php

/**
 * @file
 * Contains \Drupal\iucn_search\Form\SearchFiltersForm.
 */

namespace Drupal\iucn_search\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
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
  public function buildForm(array $form, FormStateInterface $form_state) {
    // @todo: handle exception
    $fqParams = SearchPageController::getSearch()->getFilterQueryParameters();
    $fqReset = [];
    foreach ($fqParams as $field => $value) {
      $term = \Drupal\taxonomy\Entity\Term::load($value);
      if ($term) {
        $getCopy = $_GET;
        unset($getCopy[$field]);
        //@ToDo: \Drupal::l() is deprecated, see how to render a \Drupal\Core\Link.
        $l = \Drupal::l('x', \Drupal\Core\Url::fromRoute('iucn.search', [], ['query' => $getCopy]));
        //@ToDo: Think of a better message.
        $fqReset[] = ['#markup' => "Results are filtered by '{$term->getName()}' {$l}"];
      }
    }
    $form = [[
      '#attributes' => ['class' => ['search-filters', 'invisible']],
      '#title' => $this->t('Search filters'),
      '#type' => 'fieldset',
      'fqReset' => $fqReset,
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
    $query = SearchPageController::getSearch()->getHttpQueryParameters($form_state);
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
