<?php

/**
 * @file
 * Contains \Drupal\iucn_search\Form\IucnSettingsForm.
 */

namespace Drupal\iucn_search\Form;


use \Drupal\Core\Form\ConfigFormBase;
use \Drupal\Core\Form\FormStateInterface;

/**
 * Configure settings for IUCN Wildlife project.
 */
class IucnSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'iucn_configuration_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'iucn.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('iucn.settings');

    $form['reference_to_legislation_pattern'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Reference to legislation PATTERN'),
      '#description' => $this->t('e.g. http://www.ecolex.org/ecolex/ledge/view/RecordDetails?id=$RECID&index=documents'),
      '#default_value' => $config->get('reference_to_legislation_pattern'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('iucn.settings')
      ->set('reference_to_legislation_pattern', $form_state->getValue('reference_to_legislation_pattern'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}
