<?php

namespace Drupal\search_api_attachments\Plugin\search_api_attachments;

use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\search_api_attachments\TextExtractorPluginBase;

/**
 * Provides docconv extractor.
 *
 * @SearchApiAttachmentsTextExtractor(
 *   id = "docconv_extractor",
 *   label = @Translation("Docconv Extractor"),
 *   description = @Translation("Adds Docconv extractor support."),
 * )
 */
class DocconvExtractor extends TextExtractorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function extract(File $file) {
    $docconv_path = $this->configuration['docconv_path'];
    $filepath = $this->getRealpath($file->getFileUri());
    $cmd = escapeshellarg($docconv_path) . ' -input ' . escapeshellarg($filepath);

    // UTF-8 multibyte characters will be stripped by escapeshellargs() for
    // the default C-locale.
    // So temporarily set the locale to UTF-8 so that the filepath remains
    // valid.
    $backup_locale = setlocale(LC_CTYPE, '0');
    setlocale(LC_CTYPE, $backup_locale);
    // Support UTF-8 commands.
    // @see http://www.php.net/manual/en/function.shell-exec.php#85095
    shell_exec("LANG=en_US.utf-8");

    return shell_exec($cmd);
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['docconv_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Full path to the docconv binary'),
      '#description' => $this->t('Enter the full path to the docconv binary. Example: "/usr/bin/docconv".'),
      '#default_value' => $this->configuration['docconv_path'],
      '#required' => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $docconv_path = $form_state->getValue([
      'text_extractor_config',
      'docconv_path',
    ]);
    if (!file_exists($docconv_path) && isset($form['text_extractor_config']['docconv_path'])) {
      $form_state->setError($form['text_extractor_config']['docconv_path'], $this->t('The file %path does not exist.', ['%path' => $docconv_path]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['docconv_path'] = $form_state->getValue([
      'text_extractor_config',
      'docconv_path',
    ]);
    parent::submitConfigurationForm($form, $form_state);
  }

}
