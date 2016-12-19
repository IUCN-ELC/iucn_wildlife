<?php

/**
 * @file
 * Contains \Drupal\elis_consumer\Plugin\migrate\source\ElisConsumerDefaultSource.
 */


namespace Drupal\elis_consumer\Plugin\migrate\source;

include_once __DIR__ . '/../../../../elis_consumer.xml.inc';

use Drupal\elis_consumer\ElisXMLConsumer;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\migrate\source\SourcePluginBase;
use Drupal\migrate\Row;

abstract class ElisConsumerDefaultSource extends SourcePluginBase {

  protected $files_destination;
  protected $source = NULL;
  protected $data = array();

  /**
   * Return the url to the elis web service endpoint.
   * The url should contain two important strings:
   *  - SPAGE_QUERY_VALUE
   *  - SPAGE_FIRST_VALUE
   * E.g. http://www.ecolex.org/elis_isis3w.php?database=cou&search_type=page_search&spage_query=SPAGE_QUERY_VALUE&spage_first=SPAGE_FIRST_VALUE
   * @return string
   */
  abstract protected function getElisServiceUrl();

  /**
   * The directory where files should be stored.
   * @return string
   */
  abstract protected function getFilesDestination();

  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);
    $this->files_destination = $this->getFilesDestination();

    if (isset($configuration['files_destination'])) {
      $this->files_destination = $configuration['files_destination'];
    }
    $directory = "public://{$this->files_destination}";
    file_prepare_directory($directory, FILE_CREATE_DIRECTORY);

    if (!empty($configuration['path'])) {
      $path = $configuration['path'];
    }
    else {
      $path = $this->getElisServiceUrl();
    }

    $encoding = 'iso-8859-15';
    if (!empty($configuration['encoding'])) {
      $encoding = $configuration['encoding'];
    }
    $this->source = new ElisXMLConsumer($path, 'id', $encoding, '2014-01', '');
  }

  /**
   * Retrieve the migration item.
   * @param $data
   * @return array
   */
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

      switch ($field_name) {
        case 'abstract':
          $abstract .= $value . PHP_EOL;
          continue;
        case 'party':
          $parties[] = $value;
          continue;
        case 'authorM':
          $field_name = 'authorA';
          break;
        case 'corpAuthorM':
          $field_name = 'corpAuthorA';
          break;
        case 'confDate':
          $dates = explode(" - ", $value);
          foreach ($dates as &$date) {
            $date = date("Y-m-d", strtotime($date));
          }
          $ret[$field_name] = $dates;
          continue;
        case 'dateOfText':
        case 'dateOfEntry':
        case 'dateOfModification':
          if (preg_match('/^\d{4}$/', $value)) {
            $value = "$value-01-01";
          }
          break;
      }

      if (!empty($ret[$field_name])) {
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
    return 'n/a';
  }

  public function getIds() {
    return array(
      'id' => array('type' => 'string')
    );
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
    if (strpos($row->getSourceProperty('id'), 'MON') === 0) {
      if (empty($titleOfText = $row->getSourceProperty('titleOfText')) &&
        empty($titleOfText = $row->getSourceProperty('titleOfTextSp')) &&
        empty($titleOfText = $row->getSourceProperty('titleOfTextFr')) &&
        empty($titleOfText = $row->getSourceProperty('titleOfTextOther'))) {
        return null;
      }
    }
    elseif (empty($titleOfText = $row->getSourceProperty('paperTitleOfText')) &&
      empty($titleOfText = $row->getSourceProperty('paperTitleOfTextSp')) &&
      empty($titleOfText = $row->getSourceProperty('paperTitleOfTextFr')) &&
      empty($titleOfText = $row->getSourceProperty('paperTitleOfTextOther'))) {
      return null;
    }
    return $titleOfText;
  }

  protected function fixDateFields(Row &$row, array $sourceFields) {
    foreach ($sourceFields as $field) {
      $p = preg_match('/(\d\d\d\d)\-(\d\d)\-00/', $row->getSourceProperty($field), $matches);
      if ($p) {
        $month = $matches[2] != '00' ? $matches[2] : '01';
        $row->setSourceProperty($field, "{$matches[1]}-{$month}-01");
      }
    }
  }

  public function prepareRow(Row $row) {
    $titleOfText = $this->getTitle($row);
    if (empty($titleOfText)) {
      return FALSE;
    }
    $row->setSourceProperty('titleOfText', $titleOfText);
    if (empty($row->getSourceProperty('titleOfTextShort'))) {
      $row->setSourceProperty('titleOfTextShort', substr($titleOfText, 0, 255));
    }
    return TRUE;
  }

}
