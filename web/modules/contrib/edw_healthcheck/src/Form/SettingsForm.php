<?php

namespace Drupal\edw_healthcheck\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Allows admins to configure the EDWHealthCheck module.
 *
 * @package Drupal\edw_healthcheck\Form
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'edw_healthcheck_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'edw_healthcheck.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('edw_healthcheck.settings');

    $form['edw_healthcheck_status_page'] = [
      '#type' => 'fieldset',
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
      '#title' => $this->t('Status page settings'),
    ];
    $form['edw_healthcheck_status_page']['edw_healthcheck_enable_status_page'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable the EDWHealthCheck status page.'),
      '#default_value' => $config->get('edw_healthcheck.statuspage.enabled'),
    ];
    $form['edw_healthcheck_components'] = [
        '#type' => 'fieldset',
        '#collapsible' => TRUE,
        '#collapsed' => FALSE,
        '#title' => $this->t('EDWHealthCheck components settings'),
    ];
    $form['edw_healthcheck_components']['edw_healthcheck_enable_core_component'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Enable EDWHealthCheck monitoring for the Drupal Core.'),
        '#default_value' => $config->get('edw_healthcheck.components.core.enabled'),
        '#attributes' => ($config->get('edw_healthcheck.statuspage.enabled') == FALSE ? ['disabled' => TRUE] : []),
        '#states' => [
            'disabled' => [
             ':input[name="edw_healthcheck_enable_status_page"]' => ['checked' => FALSE]],
        ],
    ];
    $form['edw_healthcheck_components']['edw_healthcheck_enable_modules_component'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Enable EDWHealthCheck monitoring for the active Modules.'),
        '#default_value' => $config->get('edw_healthcheck.components.modules.enabled'),
        '#attributes' => ($config->get('edw_healthcheck.statuspage.enabled') == FALSE ? ['disabled' => TRUE] : []),
        '#states' => [
            'disabled' => [
                ':input[name="edw_healthcheck_enable_status_page"]' => ['checked' => FALSE]],
        ],
    ];
    $form['edw_healthcheck_components']['edw_healthcheck_enable_themes_component'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Enable EDWHealthCheck monitoring for the installed Themes.'),
        '#default_value' => $config->get('edw_healthcheck.components.themes.enabled'),
        '#attributes' => ($config->get('edw_healthcheck.statuspage.enabled') == FALSE ? ['disabled' => TRUE] : []),
        '#states' => [
            'disabled' => [
                ':input[name="edw_healthcheck_enable_status_page"]' => ['checked' => FALSE]],
        ],
    ];
    $form['edw_healthcheck_components']['edw_healthcheck_enable_last_cron_component'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable EDWHealthCheck monitoring for the last cron execution.'),
      '#default_value' => $config->get('edw_healthcheck.components.last_cron.enabled'),
      '#attributes' => ($config->get('edw_healthcheck.statuspage.enabled') == FALSE ? ['disabled' => TRUE] : []),
      '#states' => [
        'disabled' => [
          ':input[name="edw_healthcheck_enable_status_page"]' => ['checked' => FALSE]],
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('edw_healthcheck.settings');
    $status_page = $form_state->getValue('edw_healthcheck_enable_status_page');
    $config->set('edw_healthcheck.statuspage.enabled', (bool) $status_page);

    $config->set('edw_healthcheck.statuspage.path', $form_state->getValue('edw_healthcheck_page_path'));
    $config->set('edw_healthcheck.statuspage.controller', $form_state->getValue('edw_healthcheck_page_controller'));
    $config->set('edw_healthcheck.statuspage.getparam', $form_state->getValue('edw_healthcheck_enable_status_page_get'));

    $config->set('edw_healthcheck.components.core.enabled', $form_state->getValue('edw_healthcheck_enable_core_component'));
    $config->set('edw_healthcheck.components.modules.enabled', $form_state->getValue('edw_healthcheck_enable_modules_component'));
    $config->set('edw_healthcheck.components.themes.enabled', $form_state->getValue('edw_healthcheck_enable_themes_component'));
    $config->set('edw_healthcheck.components.last_cron.enabled', $form_state->getValue('edw_healthcheck_enable_last_cron_component'));

    $config->save();

    parent::submitForm($form, $form_state);
  }

}
