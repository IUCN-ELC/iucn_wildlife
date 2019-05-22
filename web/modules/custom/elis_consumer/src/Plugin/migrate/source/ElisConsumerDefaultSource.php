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
use Drupal\taxonomy\Entity\Term;

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

      $value = str_replace(['^a', '^b'], ['', ' '], $value);

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
        if ($row->projectInformation->count() > 0) {
          foreach ($row->projectInformation as $key => $projectInformation) {
            if(strtoupper($projectInformation) == 'WILD') {
              $this->data[$id] = $row;
              break;
            }
          }
        }
      }
    }
    return $this->data;
  }

  public function count($refresh = FALSE) {
    return -1;
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
    if (empty($titleOfText = $row->getSourceProperty('paperTitleOfText')) &&
      empty($titleOfText = $row->getSourceProperty('paperTitleOfTextSp')) &&
      empty($titleOfText = $row->getSourceProperty('paperTitleOfTextFr')) &&
      empty($titleOfText = $row->getSourceProperty('paperTitleOfTextOther'))) {
      $titleOfText = null;
    }

    if (empty($titleOfText) || strpos($row->getSourceProperty('id'), 'MON') === 0) {
      if (empty($titleOfText = $row->getSourceProperty('titleOfText')) &&
        empty($titleOfText = $row->getSourceProperty('titleOfTextSp')) &&
        empty($titleOfText = $row->getSourceProperty('titleOfTextFr')) &&
        empty($titleOfText = $row->getSourceProperty('titleOfTextOther'))) {
      }
    }
    return $titleOfText;
  }

  protected function fixDateFields(Row &$row, array $sourceFields) {
    foreach ($sourceFields as $field) {
      if(empty($row->getSourceProperty($field))) {
        continue;
      }
      $error = FALSE;
      //Set the defaults.
      $year = '1900';
      $month ='01';
      $day ='01';

      $p = preg_match('/(\d\d\d\d)\-(\d\d)\-(\d\d)/', $row->getSourceProperty($field), $matches);
      if ($p) {
        $year = "{$matches[1]}";
        if ($matches[2] > 0 && $matches[2] <= 12) {
          $month ="{$matches[2]}";
        } else {
          $error = TRUE;
        }

        if ($d = \DateTime::createFromFormat('Y-m-d', "$year-$month-{$matches[3]}")) {
          if ($d->format('Y-m-d') == "$year-$month-{$matches[3]}") {
            $day ="{$matches[3]}";
          } else {
            $error = TRUE;
          }
        } else {
          $error = TRUE;
        }
      } else {
        $error = TRUE;
        $s = preg_match('/(\d\d\d\d)/', $row->getSourceProperty($field), $smatches);
        if ($s) {
          $year = "{$smatches[1]}";
        }
      }


      if ($error) {
        \Drupal::logger('elis_consumer')->info(
          "Received wrong date: @date, field: @field,id: @id, title: @title - set the date to: @new_date",
          [
            '@date' => ($row->getSourceProperty($field) ? $row->getSourceProperty($field) : 'empty'),
            '@field' => $field,
            '@id' => $row->getSourceProperty('id'),
            '@title' => $row->getSourceProperty('titleOfText'),
            '@new_date' => "$year-$month-$day",
          ]);
      }



      $row->setSourceProperty($field, "$year-$month-$day");
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

  public function rebuildSpeciesTerm(Row $row) {
    $speciesName = $row->getSourceProperty('wildlifeSpecies');
    $linkPage = $row->getSourceProperty('wildlifeSpeciesDOI');

    $termStorage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
    $needSave = false;
    $term = $termStorage->loadByProperties(
      [
        'vid' => 'species',
        'name' => $speciesName
      ]
    );

    if (empty($term)) {
      $term = Term::create(
        [
          'vid' => 'species',
          'name' => $speciesName
        ]
      );
      $needSave = true;

      $violations = $term->validate();
      if ($this->count($violations)) {
        \Drupal::logger('elis_consumer')->error("Validation errors when trying to save term {$speciesName} !");
      }
    } else {
      $term = reset($term);
    }

    $doiTermLink = null;
    if (!empty($term->get('field_doi_link_page')->getValue())) {
      $doiTermLink = $term->get('field_doi_link_page')->uri;
    }

    if ($doiTermLink != $linkPage) {
      $term->set('field_doi_link_page', $linkPage);
      $needSave = true;
    }

    if ($needSave) {
      $term->save();
    }
  }
}
