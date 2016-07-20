<?php

namespace Drupal\search_api_attachments\Plugin\search_api_attachments;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\search_api_attachments\TextExtractorPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Drupal\file\Entity\File;

/**
 * Provides solr extractor.
 *
 * @SearchApiAttachmentsTextExtractor(
 *   id = "solr_extractor",
 *   label = @Translation("Solr Extractor"),
 *   description = @Translation("Adds Solr extractor support."),
 * )
 */
class SolrExtractor extends TextExtractorPluginBase {

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, ConfigFactoryInterface $config_factory, StreamWrapperManagerInterface $stream_wrapper_manager, MimeTypeGuesserInterface $mime_type_guesser, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $config_factory, $stream_wrapper_manager, $mime_type_guesser);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('stream_wrapper_manager'),
      $container->get('file.mime_type.guesser'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Extract file with a search api solr backend.
   *
   * @param \Drupal\file\Entity\File $file
   *   A file object.
   *
   * @return string
   *   The text extracted from the file.
   */
  public function extract(File $file) {
    $filepath = $this->getRealpath($file->getFileUri());
    // Load the chosen Solr server entity.
    $conditions = array(
      'status' => TRUE,
      'id' => $this->configuration['solr_server'],
    );
    $server = $this->entityTypeManager->getStorage('search_api_server')->loadByProperties($conditions);
    $server = reset($server);
    // Get the Solr backend.
    /** @var \Drupal\search_api_solr\Plugin\search_api\backend\SearchApiSolrBackend $backend */
    $backend = $server->getBackend();
    // Initialise the Client.
    $client = $backend->getSolrConnection();
    // Create the Query.
    $query = $client->createExtract();
    // setExtractOnly is only available in solarium 3.3.0 and up.
    $query->setExtractOnly(TRUE);
    $query->setFile($filepath);

    // Override the extract handler.
    // @see \Solarium\QueryType\Extract\Query::setHandler().
    if (isset($this->configuration['solr_tika_path'])) {
      $query->setHandler($this->configuration['solr_tika_path']);
    }

    // Execute the query.
    $result = $client->extract($query);
    $response = $result->getResponse();
    $json_data = $response->getBody();
    $array_data = Json::decode($json_data);
    // $array_data contains json array with two keys : [filename] that contains
    // the extracted text we need and [filename]_metadata that contains some
    // extra metadata.
    $xml_data = $array_data[$filepath];
    // We need to get only what is in body tag.
    $xmlencoder = new XmlEncoder();
    $dom_data = $xmlencoder->decode($xml_data, 'xml');
    $dom_data = $dom_data['body'];

    $htmlencoder = new XmlEncoder();
    $htmlencoder = $htmlencoder->encode($dom_data, 'xml');

    $body = strip_tags($htmlencoder);
    return $body;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = array();
    $conditions = array(
      'status' => TRUE,
      'backend' => array(
        'search_api_solr',
        'search_api_solr_acquia',
        'search_api_solr_acquia_multi_subs',
      ),
    );

    $search_api_solr_servers = $this->entityTypeManager->getStorage('search_api_server')->loadByProperties($conditions);
    $options = array();
    foreach ($search_api_solr_servers as $solr_server) {
      $options[$solr_server->id()] = $solr_server->label();
    }

    $form['solr_server'] = array(
      '#type' => 'select',
      '#title' => $this->t('Solr server'),
      '#description' => $this->t('Select the solr server you want to use.'),
      '#empty_value' => '',
      '#options' => $options,
      '#default_value' => $this->configuration['solr_server'],
      '#required' => TRUE,
    );

    $form['solr_tika_path'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Tika path'),
      '#description' => $this->t('Specify a custom Tika extractor handler for
        the Solr server, e.g. update/extract, or extract/tika. When no value
        provided, the default "update/extract" is used. When no value is
        set, then the handler provided by Solarium is used.'),
      '#default_value' => empty($this->configuration['solr_tika_path']) ? 'update/extract' : $this->configuration['solr_tika_path'],
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    if (isset($values['text_extractor_config']['solr_server']) && $values['text_extractor_config']['solr_server'] == '') {
      $form_state->setError($form['text_extractor_config']['solr_server'], $this->t('Please choose the solr server to use for extraction.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['solr_server'] = $form_state->getValue(array('text_extractor_config', 'solr_server'));
    $this->configuration['solr_tika_path'] = $form_state->getValue(array('text_extractor_config', 'solr_tika_path'));
    parent::submitConfigurationForm($form, $form_state);
  }

}
