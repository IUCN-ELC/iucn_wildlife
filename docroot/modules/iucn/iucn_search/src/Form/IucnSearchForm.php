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
  protected $items_per_page = 10;

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
    $current_page = !empty($_GET['page']) ? $_GET['page'] : 1;
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
      '#items' => $this->getSeachResults($text, $current_page),
    ];
    $form['results'] = [
      '#markup' => \Drupal::service('renderer')->render($elements),
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

  private function getSeachResults($search_text, $current_page) {
    $results = [];

    if (empty($index = Index::load('default_node_index'))) {
      drupal_set_message(t('The search index is not properly configured.'), 'error');
      return $results;
    }
    try {
      $query = $index->query();
      $query->keys($search_text);
      $query->addCondition('type_1', 'court_decision', '=');
      $offset = ($current_page - 1) * $this->items_per_page;
      $query->range($offset, $this->items_per_page);
      $resultSet = $index->getServerInstance()->search($query);

      foreach ($resultSet->getResultItems() as $item) {
        $item_nid = $item->getField('nid')->getValues()[0];
        $results[$item_nid] = \Drupal\node\Entity\Node::load($item_nid);
      }
    }
    catch (\Exception $e) {
      watchdog_exception('iucn_search', $e);
      drupal_set_message(t('An error occurred.'), 'error');
      }
    return $results;
  }

}
