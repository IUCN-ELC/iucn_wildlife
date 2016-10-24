<?php

namespace Drupal\linkit\Form\Profile;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;

/**
 * Base form for profile add and edit forms.
 */
abstract class FormBase extends EntityForm {

  /**
   * The entity being used by this form.
   *
   * @var \Drupal\linkit\ProfileInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Profile Name'),
      '#default_value' => $this->entity->label(),
      '#description' => $this->t('The human-readable name of this  profile. This name must be unique.'),
      '#required' => TRUE,
      '#size' => 30,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $this->entity->id(),
      '#machine_name' => [
        'exists' => ['\Drupal\linkit\Entity\Profile', 'load'],
      ],
      '#disabled' => !$this->entity->isNew(),
    ];

    $form['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#default_value' => $this->entity->getDescription(),
      '#description' => $this->t('The text will be displayed on the <em>profile collection</em> page.'),
    ];

    $form['additional_settings'] = [
      '#type' => 'vertical_tabs',
      '#weight' => 99,
    ];

    if ($this->moduleHandler->moduleExists('imce')) {
      $form['imce'] = [
        '#type' => 'details',
        '#title' => t('IMCE integration'),
        '#group' => 'additional_settings',
      ];

      $form['imce']['imce_use'] = [
        '#type' => 'checkbox',
        '#title' => t('Enable IMCE File Browser in the editor dialog.'),
        '#default_value' => $this->entity->getThirdPartySetting('imce', 'use', FALSE),
      ];

      $scheme_options = \Drupal::service('stream_wrapper_manager')->getNames(StreamWrapperInterface::READ_VISIBLE);
      $form['imce']['imce_scheme'] = [
        '#type' => 'radios',
        '#title' => t('Scheme'),
        '#options' => $scheme_options,
        '#default_value' => $this->entity->getThirdPartySetting('imce', 'scheme', 'public'),
        '#states' => [
          'visible' => [
            ':input[name="imce_use"]' => ['checked' => TRUE],
          ],
        ],
      ];
    }

    return parent::form($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $linkit_profile = $this->entity;

    // Prevent leading and trailing spaces in linkit profile labels.
    $linkit_profile->set('label', trim($linkit_profile->label()));

    if ($this->moduleHandler->moduleExists('imce')) {
      $linkit_profile->setThirdPartySetting('imce', 'use', $form_state->getValue('imce_use'));
      $linkit_profile->setThirdPartySetting('imce', 'scheme', $form_state->getValue('imce_scheme'));
    }

    $status = $linkit_profile->save();
    $edit_link = $this->entity->toLink($this->t('Edit'), 'edit-form')->toString();
    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created new profile %label.', ['%label' => $linkit_profile->label()]));
        $this->logger('linkit')->notice('Created new profile %label.', ['%label' => $linkit_profile->label(), 'link' => $edit_link]);
        $form_state->setRedirect('linkit.matchers', [
          'linkit_profile' => $linkit_profile->id(),
        ]);
        break;

      case SAVED_UPDATED:
        drupal_set_message($this->t('Updated profile %label.', ['%label' => $linkit_profile->label()]));
        $this->logger('linkit')->notice('Updated profile %label.', ['%label' => $linkit_profile->label(), 'link' => $edit_link]);
        $form_state->setRedirectUrl($linkit_profile->toUrl('edit-form'));
        break;
    }
  }

}
