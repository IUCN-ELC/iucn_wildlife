<?php

/**
 * @file
 * Contains \Drupal\elis_consumer\Plugin\migrate\source\ElisConsumerDefaultSource.
 */


namespace Drupal\elis_consumer\Plugin\migrate\source;

include_once __DIR__ . '/../../../../elis_consumer.xml.inc';

use ArrayIterator;
use Drupal;
use Drupal\elis_consumer\ElisXMLConsumer;
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
    $iterator = new ArrayIterator($data);
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
        Drupal::logger('elis_consumer')->info(
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


  /**
   * @param \Drupal\migrate\Row $row
   *
   * @return bool
   * @throws \Exception
   */
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

  /**
   * @param \Drupal\migrate\Row $row
   * @param array $properties
   *
   * @throws \Exception
   */
  protected function fixLinkFields(Row $row, $properties = []) {
    $rowId = $row->getSourceProperty('id');
    foreach ($properties as $field) {
      $values = $row->getSourceProperty($field);
      if (empty($values)) {
        continue;
      }

      if (!is_array($values)) {
        $values = [$values];
      }

      foreach ($values as $key => $value) {
        if (!filter_var($value, FILTER_VALIDATE_URL)) {
          \Drupal::logger('elis_consumer')
            ->notice("Row '@rowId' has invalid url provided for field '@field', value: '@value'!",
              [
                '@field' => $field,
                '@value' => $value,
                '@rowId' => $rowId,
              ]
            );

          unset($values[$key]);
        }
      }
      $row->setSourceProperty($field, $values);
    }
  }


  /**
   * @param \Drupal\migrate\Row $row
   *
   * @throws \Exception
   */
  public function rebuildSpeciesTerm(Row $row) {
    $id = $row->getSourceProperty('id');
    // Transport data from XML parser to prepareRow, funny ... I know :-)
    $wildlifeSpeciesDOIMapping = !empty($row->getSourceProperty('wildlifeSpeciesDOIMapping'))
      ? json_decode($row->getSourceProperty('wildlifeSpeciesDOIMapping'), TRUE)
      : [];
    foreach ($wildlifeSpeciesDOIMapping as $speciesName => $doiURL) {
      $term = $this->findTerm($speciesName);
      $needSave = empty($term->id());
      $existingDOILink = $term->get('field_doi_link_page')->getString();
      if ($existingDOILink != $doiURL) {
        if (!filter_var($doiURL, FILTER_VALIDATE_URL)) {
          \Drupal::logger('elis_consumer')
            ->notice("Row @rowId has invalid url provided for field @field with value @value!",
              [
                '@field' => 'wildlifeSpeciesDOI',
                '@value' => $doiURL,
                '@rowId' => $doiURL,
              ]
            );
          continue;
        }
        $term->set('field_doi_link_page', $doiURL);
        $needSave = TRUE;
      }

      if ($needSave) {
        $violations = $term->validate();
        if ($violations->count()) {
          Drupal::logger('elis_consumer')->error(
            "Record id: @id - Validation errors while trying to save term: @speciesNames (@errors)",
            ['@id' => $id, '@speciesNames' => $speciesName, '@errors' => $violations]
          );
        }
        $term->save();
      }
    }
  }

  /**
   * @param string $name
   * @return \Drupal\taxonomy\Entity\Term
   *
   * @throws  \Exception
   */
  public function findTerm($name) {
    $termStorage = Drupal::entityTypeManager()->getStorage('taxonomy_term');
    /** @var \Drupal\taxonomy\Entity\Term $term */
    $term = $termStorage->loadByProperties(
      [
        'vid' => 'species',
        'name' => $name
      ]
    );
    if (empty($term)) {
      $term = Term::create(
        [
          'vid' => 'species',
          'name' => $name
        ]
      );
    } else {
      $term = reset($term);
    }
    return $term;
  }
}
