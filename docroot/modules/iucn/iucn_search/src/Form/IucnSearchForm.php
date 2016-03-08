<?php

/**
 * @file
 * Contains \Drupal\iucn_search\Form\IucnSearchForm.
 */

namespace Drupal\iucn_search\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\search_api\Entity\Index;

class IucnSearchForm extends FormBase {

  protected $search_url_param = 'q';

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
    $form['text'] = [
      '#type' => 'textfield',
      '#title' => 'Search text',
      '#default_value' => $text,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => 'Search',
    ];
    if (!empty($text)) {
      $form['results'] = [
        '#markup' => $this->getSeachResults($text),
      ];
    }
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

  private function getSeachResults($search_text) {
    $index = Index::load('default_node_index');
    $query = $index->query();
    $query->keys($search_text);
    $resultSet = $index->getServerInstance()->search($query);

    $results = [];

    foreach ($resultSet->getResultItems() as $item) {
      $item_nid = $item->getField('nid')->getValues()[0];
      $results[$item_nid] = [
        'nid' => $item_nid,
      ];
      break;
    }
    return $results;
  }

}
