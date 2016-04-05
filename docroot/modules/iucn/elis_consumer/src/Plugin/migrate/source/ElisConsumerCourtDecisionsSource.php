<?php

/**
 * @file
 * Contains \Drupal\elis_consumer\Plugin\migrate\source\ElisConsumerCourtDecisionsSource.
 */


namespace Drupal\elis_consumer\Plugin\migrate\source;

include_once __DIR__ . '/../../../../elis_consumer.xml.inc';

use Drupal\elis_consumer\ElisXMLConsumer;
use Drupal\file\FileInterface;
use Drupal\migrate\Annotation\MigrateSource;
use Drupal\migrate\Entity\MigrationInterface;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\Plugin\migrate\source\SourcePluginBase;
use Drupal\migrate\Row;
use Drupal\taxonomy\Entity\Term;

/**
 * Migrate court decision from ELIS database.
 *
 * @MigrateSource(
 *   id = "elis_consumer_court_decisions"
 * )
 */
class ElisConsumerCourtDecisionsSource extends SourcePluginBase {

  /**
   * The directory where files should be stored.
   */
  protected $files_destination = 'court_decisions';

  /**
   * Fields that will be stored in taxonomies.
   *
   * @var array
   *  The key is the source field name and the value is the vocabulary machine name.
   */
  protected $taxonomy_fields = array(
    'subject' => 'ecolex_subjects',
    'typeOfText' => 'document_types',
    'languageOfDocument' => 'document_languages',
    'justices' => 'justices',
    'courtJurisdiction' => 'court_jurisdictions',
    'instance' => 'instances',
    'statusOfDecision' => 'decision_status',
    'subdivision' => 'subdivisions',
    'territorialSubdivision' => 'territorial_subdivisions',
    'region' => 'regions',
    'keyword' => 'keywords',
    'country' => 'countries',
  );

  protected $source = NULL;
  protected $data = array();

  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);
    if (isset($configuration['files_destination'])) {
      $this->files_destination = $configuration['files_destination'];
    }
    $directory = "public://{$this->files_destination}";
    file_prepare_directory($directory, FILE_CREATE_DIRECTORY);

    $path = 'http://www.ecolex.org/elis_isis3w.php?database=cou&search_type=page_search&table=all&format_name=@xmlexp&lang=xmlf&page_header=@xmlh&spage_query=SPAGE_QUERY_VALUE&spage_first=SPAGE_FIRST_VALUE';
    if (!empty($configuration['path'])) {
      $path = $configuration['path'];
    }

    $encoding = 'iso-8859-15';
    if (!empty($configuration['encoding'])) {
      $encoding = $configuration['encoding'];
    }
    $this->source = new ElisXMLConsumer($path, 'id', $encoding, '2014-01', '');
  }

  protected function getData() {
    if (empty($this->data)) {
      $rows = $this->source->getData();
      foreach($rows as $id => $row) {
        if (!empty((string)$row->projectInformation) && strtoupper((string)$row->projectInformation) == 'WILD') {
          $this->data[$id] = $row;
        }
      }
    }
    return $this->data;
  }

  public function count($refresh = FALSE) {
    $data = $this->getData();
    return count($data);
  }

  public function getIds() {
    return array(
      'id' => array('type' => 'string')
    );
  }

  public function __toString() {
    return 'Migrate court decisions from ELIS having `projectInformation` = `WILD`';
  }

  public function fields() {
    return array(
      'id' => 'Remote primary key',
      'id2' => 'ID2',
      'isisMfn' => 'Isis number',
      'dateOfEntry' => 'Date of entry',
      'dateOfModification' => 'Date of modification',
      'titleOfText' => 'Title of text',
      'titleOfTextShort' => 'Title of text short',
      'titleOfText_original' => 'Title of text in english',
      'titleOfText_languages' => 'Languages of titleOfText field',
      'country' => 'Country',
      'subject' => 'Subject',
      'languageOfDocument' => 'Language',
      'courtName' => 'Court name',
      'dateOfText' => 'Date of text',
      'referenceNumber' => 'Reference number',
      'numberOfPages' => 'Number of pages',
      'availableIn' => 'Available in',
      'linkToFullText' => 'Link to the full text',
      'linkToFullText_languages' => 'Languages of linkToFullText field',
      'keyword' => 'Keywords',
      'abstract' => 'Abstract',
      'typeOfText' => 'Type of text',
      'abstract_languages' => 'Languages of abstract field',
      'referenceToNationalLegislation' => 'Reference to legislation',
    );
  }


  public function getItem($data) {
    $ret = array();
    $parties = array();
    $abstract = '';
    foreach ($data as $field_name => $value) {
      $value = (string) $value;
      if (mb_detect_encoding($value) != 'UTF-8') {
        $value = utf8_encode($value);
      }
      if (strpos($value, 'www.') === 0) {
        $value = 'http://' . $value;
      }
      if ($field_name == 'abstract') {
        $abstract .= $value . PHP_EOL;
        continue;
      }
      else if ($field_name == 'party') {
        $parties[] = $value;
        continue;
      }
      else if (!empty($ret[$field_name])) {
        if (is_array($ret[$field_name])) {
          $ret[$field_name][] = $value;
        }
        else {
          $ret[$field_name] = array($ret[$field_name], $value);
        }
      }
      else {
        if ($field_name == 'titleOfText') {
          $ret['titleOfText_original'] = $value;
        }
        $ret[$field_name] = $value;
      }
    }
    $ret['abstract'] = $abstract;
    $ret['parties'] = $parties;
    $ret['obsolete'] = !empty($data->obsolete) && $data->obsolete == 'true';
    return $ret;
  }

  protected function initializeIterator() {
    $data = $this->getData();
    foreach($data as &$doc) {
      $doc = $this->getItem($doc);
    }
    $iterator = new \ArrayIterator($data);
    return $iterator;
  }


  /**
   * @param $terms
   *  An array with term names.
   * @param $vid
   *  Vocabulary machine name.
   * @param bool $create
   *  If TRUE, nonexistent terms will be created.
   * @return array
   *  An array with tids.
   */
  public function map_taxonomy_terms_by_name($terms, $vid, $create = TRUE) {
    if (!is_array($terms)) {
      $terms = array($terms);
    }
    $filtered = array();
    // Filter
    foreach ($terms as $key => $term_name) {
      // Cleanup spaces & special characters
      $name = htmlspecialchars_decode(trim(preg_replace('/\s+/', ' ', $term_name)));
      if (!empty($name)) {
        $filtered[] = $name;
      }
    }
    if (empty($filtered)) {
      return [];
    }
    $db = \Drupal::database();
    $q = $db->select('taxonomy_term_field_data', 't');
    $q->fields('t', array('tid', 'name'));
    $q->condition('vid', $vid);
    $q->condition('name', $filtered, 'IN');
    $data = $q->execute()->fetchAllKeyed();
    if (count($data) != count($filtered)) {
      foreach ($filtered as $term_name) {
        if ($create && !in_array($term_name, $data)) {
          $term = Term::create(array('name' => $term_name, 'vid' => $vid));
          $term->save();
          $data[$term->id()] = $term_name;
        }
      }
    }
    return array_keys($data);
  }


  public function getTitle(Row $row) {
    if (empty($titleOfText = $row->getSourceProperty('titleOfText')) &&
        empty($titleOfText = $row->getSourceProperty('titleOfTextSp')) &&
        empty($titleOfText = $row->getSourceProperty('titleOfTextFr')) &&
        empty($titleOfText = $row->getSourceProperty('titleOfTextOther'))) {
      return NULL;
    }
    return $titleOfText;
  }

  public function prepareRow(Row $row) {
    parent::prepareRow($row);
    $titleOfText = $this->getTitle($row);
    if (empty($titleOfText)) {
      $this->idMap->saveIdMapping($row, array(), MigrateIdMapInterface::STATUS_IGNORED);
      $message = 'Title cannot be NULL. (' . $row->getSourceProperty('id') . ')';
      \Drupal::logger('elis_consumer_court_decisions')
        ->warning($message);
      return FALSE;
    }
    else {
      $row->setSourceProperty('titleOfText', $titleOfText);
    }
    if (empty($row->getSourceProperty('titleOfTextShort'))) {
      $row->setSourceProperty('titleOfTextShort', substr($titleOfText, 0, 255));
    }

    // Used str_replace('server2.php/', '', ...) because there is a bug in the urls from ELIS
    $linkToFullText = str_replace('server2.php/', '', $row->getSourceProperty('linkToFullText'));
    $row->setSourceProperty('linkToFullText', $linkToFullText);
    $linkToAbstract = str_replace('server2.php/', '', $row->getSourceProperty('linkToAbstract'));
    $row->setSourceProperty('linkToAbstract', $linkToAbstract);

    /* Map taxonomy term reference fields */
    foreach ($this->taxonomy_fields as $field_name => $vocabulary) {
      if ($row->hasSourceProperty($field_name) && $unmapped = $row->getSourceProperty($field_name)) {
        $mapped = $this->map_taxonomy_terms_by_name($unmapped, $vocabulary);
        $row->setSourceProperty($field_name, $mapped);
      }
    }

    return TRUE;
  }
}
