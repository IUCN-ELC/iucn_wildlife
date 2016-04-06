<?php

/**
 * @file
 * Contains \Drupal\elis_consumer\Plugin\migrate\source\ElisConsumerCourtDecisionsSource.
 */


namespace Drupal\elis_consumer\Plugin\migrate\source;

include_once __DIR__ . '/../../../../elis_consumer.xml.inc';

use Drupal\elis_consumer\ElisXMLConsumer;
use Drupal\migrate\Annotation\MigrateSource;
use Drupal\migrate\Entity\MigrationInterface;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\Plugin\migrate\source\SourcePluginBase;
use Drupal\migrate\Row;


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
      'penalty' => 'Penaly of the case',
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
      throw new MigrateSkipRowException("Record title cannot be NULL. (id:{$row->getSourceProperty('id')})");
    }
    $row->setSourceProperty('titleOfText', $titleOfText);
    if (empty($row->getSourceProperty('titleOfTextShort'))) {
      $row->setSourceProperty('titleOfTextShort', substr($titleOfText, 0, 255));
    }
    // Used str_replace('server2.php/', '', ...) because there is a bug in the urls from ELIS
    $linkToFullText = str_replace('server2.php/', '', $row->getSourceProperty('linkToFullText'));
    $row->setSourceProperty('linkToFullText', $linkToFullText);
    $linkToAbstract = str_replace('server2.php/', '', $row->getSourceProperty('linkToAbstract'));
    $row->setSourceProperty('linkToAbstract', $linkToAbstract);
    return TRUE;
  }
}
