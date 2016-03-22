<?php

/**
 * @file
 * Contains \Drupal\elis_consumer\Plugin\migrate\source\ElisConsumerCourtDecisionsSource.
 */

namespace Drupal\elis_consumer\Plugin\migrate\source;

use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\Plugin\migrate\source\SourcePluginBase;
use Drupal\migrate\Row;
use Drupal\migrate\MigrateException;
use GuzzleHttp\Exception\RequestException;

/**
 * Migrate court decision from ELIS database.
 *
 * @MigrateSource(
 *   id = "elis_consumer_court_decisions"
 * )
 */
class ElisConsumerCourtDecisionsSource extends SourcePluginBase {

  /**
   * The path to the XML source.
   *
   * @var string
   */
  protected $path = '';

  /**
   * The field name that is a unique identifier.
   *
   * @var string
   */
  protected $identifier = '';

  /**
   * Date of the first query.
   */
  protected $start_date = '1981-01';

  /**
   * Default query string.
   */
  protected $spage_query_default_string = 'ES:I AND STAT:C';

  /**
   * The dates of all queries.
   */
  protected $date_period = array();

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
  );

  /**
   * @var bool
   *  True if the migration is running within a test.
   */
  private $testing_enabled = FALSE;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, \Drupal\migrate\Entity\MigrationInterface $migration) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);

    $this->path = 'http://www.ecolex.org/elis_isis3w.php?database=cou&search_type=page_search&table=all&format_name=@xmlexp&lang=xmlf&page_header=@xmlh&spage_query=SPAGE_QUERY_VALUE&spage_first=SPAGE_FIRST_VALUE';
    $this->identifier = 'id';

    $this->date_period = $this->get_date_period('Y*', '1 year');

    if (isset($configuration['files_destination'])) {
      $this->files_destination = $configuration['files_destination'];
    }
    $directory = "public://{$this->files_destination}";
    file_prepare_directory($directory, FILE_CREATE_DIRECTORY);
  }

  public function enable_testing() {
    $this->testing_enabled = TRUE;
    $this->date_period = ['*'];
  }

  public function set_path($path) {
    $this->path = $path;
  }

  public function hexadecimally_encode_string($str) {
    $unpack = unpack('H*', $str);
    return reset($unpack);
  }

  public function get_date_period($format, $interval, $start_date = NULL, $end_date = NULL) {
    $return = array();
    $begin = $start_date == NULL ? new \DateTime($this->start_date) : new \DateTime($start_date);
    $end = $end_date == NULL ? new \DateTime() : new \DateTime($end_date);

    $interval = \DateInterval::createFromDateString($interval);
    $period = new \DatePeriod($begin, $interval, $end);

    foreach ($period as $p) {
      $return[] = $p->format($format);
    }

    return $return;
  }

  public function set_date_period($date_period) {
    $this->date_period = $date_period;
  }

  public function count($refresh = FALSE) {
    $query = $this->spage_query_default_string;
    $spage_query = $this->hexadecimally_encode_string($query);
    $spage_first = 0;
    try {
      $xml = $this->getElisXml($this->path, $spage_query, $spage_first);
      return (string)$xml->attributes()->numberResultsFound;
    } catch (RequestException $e) {
      throw new MigrateException($e->getMessage(), $e->getCode(), $e);
    }
  }

  public function getIds() {
    $ids = array();
    $ids[$this->identifier]['type'] = 'string';
    return $ids;
  }

  public function __toString() {
    return 'Migrate court decisions from ELIS.';
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

  public function getResponse($url) {
    try {
      $client = \Drupal::httpClient();
      $response = $client->request('GET', $url, [
        'headers' => [
          'Accept' => 'application/xml',
        ],
        // Uncomment the following to debug the request.
        //'debug' => true,
      ]);
      if ($response->getStatusCode() != 200) {
        throw new MigrateException('Could not retrieve data from url: ' . $url . '.');
      }
      return $response;
    }
    catch (RequestException $e) {
      throw new MigrateException('Error sending the request to: ' . $url . '.');
    }
  }

  public function getItem($data) {
    $ob = new \stdClass();
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
      elseif ($field_name == 'party') {
        $parties[] = $value;
        continue;
      }
      elseif (property_exists($ob, $field_name)) {
        if (is_array($ob->{$field_name})) {
          $ob->{$field_name}[] = $value;
        }
        else {
          $ob->{$field_name} = array($ob->{$field_name}, $value);
        }
      }
      else {
        if ($field_name == 'titleOfText') {
          $ob->titleOfText_original = $value;
        }
        $ob->{$field_name} = $value;
      }
    }
    /* Map taxonomy term reference fields */
    foreach ($this->taxonomy_fields as $field_name => $vocabulary) {
      if (!empty($ob->{$field_name})) {
        $ob->{$field_name} = $this->map_taxonomy_terms_by_name($ob->{$field_name}, $vocabulary);
      }
    }
    $ob->abstract = $abstract;
    $ob->parties = $parties;
    if (property_exists($ob, 'obsolete')) {
      $ob->obsolete = (int) $ob->obsolete;
    }
    return $ob;
  }

  public function getElisXml($url, $spage_query, $spage_first) {
    if ($this->testing_enabled) {
      return simplexml_load_file($url);
    }
    $url = str_replace(
      array('SPAGE_QUERY_VALUE', 'SPAGE_FIRST_VALUE'),
      array($spage_query,$spage_first),
      $url
    );
    $response = $this->getResponse($url);
    $data = trim(utf8_encode($response->getBody()));
    return simplexml_load_string($data);
  }

  public function getDocumentsByMonth($year) {
    $date_period = $this->get_date_period('Ym', '1 month', $year . '-01', ($year + 1) . '-01');
    $docs = [];
    foreach ($date_period as $date) {
      $query = $this->spage_query_default_string . ' AND DM:' . $date;
      $spage_query = $this->hexadecimally_encode_string($query);
      $spage_first = 0;
      $xml = $this->getElisXml($this->path, $spage_query, $spage_first);
      $numberResultsFound = (int)$xml->attributes()->numberResultsFound;
      while (TRUE) {
        foreach ($xml->document as $doc) {
          $docs[(string) $doc->{$this->identifier}] = (array)$this->getItem($doc);
        }
        $spage_first += 20;
        if ($spage_first >= $numberResultsFound) {
          break;
        }
        $xml = $this->getElisXml($this->path, $spage_query, $spage_first);
      }
    }
    return $docs;
  }

  public function getSourceData($url) {
    $query = $this->spage_query_default_string . ' AND DM:' . current($this->date_period);
    $spage_query = $this->hexadecimally_encode_string($query);
    $spage_first = 0;
    try {
      $xml = $this->getElisXml($url, $spage_query, $spage_first);
      $numberResultsFound = (int)$xml->attributes()->numberResultsFound;
      $numberResultsPresented = (int)$xml->attributes()->numberResultsPresented;
      $docs = [];
      $json = json_encode($xml->attributes());
      // The TRUE setting means decode the response into an associative array.
      $array = json_decode($json, TRUE);
      if ($numberResultsFound > $numberResultsPresented) {
        $docs = $this->getDocumentsByMonth(substr(current($this->date_period), 0, 4));
      }
      else {
        while (TRUE) {
          foreach ($xml->document as $doc) {
            $docs[(string) $doc->{$this->identifier}] = (array)$this->getItem($doc);
          }
          $spage_first += 20;
          if ($spage_first >= $numberResultsFound) {
            break;
          }
          $xml = $this->getElisXml($url, $spage_query, $spage_first);
        }
      }
      $array['documents'] = $docs;
      return $array;
    } catch (RequestException $e) {
      throw new MigrateException($e->getMessage(), $e->getCode(), $e);
    }
  }

  public function next() {
    parent::next();
    while ($this->currentRow === NULL && key($this->date_period) !== NULL) {
      $this->iterator = $this->initializeIterator();
      parent::next();
    }
  }

  protected function initializeIterator() {
    $data = $this->getSourceData($this->path);
    next($this->date_period);
    $iterator = new \ArrayIterator($data['documents']);
    return $iterator;
  }

  /**
   * @param $names
   *  An array with entity names.
   * @param string $bundle
   *  The machine name of destination content type.
   * @return array
   *  An array with nids.
   */
  public function map_nodes_by_name($names, $bundle) {
    if (!is_array($names)) {
      $names = array($names);
    }
    $db = \Drupal::database();
    $q = $db->select('node_field_data', 'n')
      ->fields('n', array('nid'))
      ->condition('type', $bundle)
      ->condition('title', $names, 'IN');
    return $q->execute()->fetchCol();
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
    foreach ($terms as $key => &$term_name) {
      if (empty($term_name)) {
        unset($terms[$key]);
      }
      else {
        $term_name = htmlspecialchars_decode($term_name);
        // Remove multiple spaces
        $term_name = preg_replace('/\s+/', ' ', $term_name);
      }
    }
    if (empty($terms)) {
      return [];
    }
    $db = \Drupal::database();
    $q = $db->select('taxonomy_term_field_data', 't')
      ->fields('t', array('tid', 'name'))
      ->condition('vid', $vid)
      ->condition('name', $terms, 'IN');
    $data = $q->execute()->fetchAllKeyed();
    if (count($data) != count($terms) && $create === TRUE) {
      foreach ($terms as $term_name) {
        if (!in_array($term_name, $data)) {
          $term = \Drupal\taxonomy\Entity\Term::create(array(
            'name' => $term_name,
            'vid' => $vid,
          ));
          $term->save();
          $data[$term->id()] = trim($term_name);
        }
      }
    }
    return array_keys($data);
  }

  /**
   * @param array $urls
   * @return array
   *  Array with files fids.
   */
  public function createFiles($urls) {
    if (empty($urls)) {
      return [];
    }
    if (!is_array($urls)) {
      $urls = array($urls);
    }
    $fids = [];
    foreach ($urls as $url) {
      $filename = basename($url);
      $destination = "public://{$this->files_destination}/{$filename}";
      $data = file_get_contents($url);
      $file = file_save_data($data, $destination, FILE_EXISTS_REPLACE);
      if (!empty($file)) {
        $fids[] = $file->id();
      }
    }
    return $fids;
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
    }
    else {
      $row->setSourceProperty('titleOfText', $titleOfText);
    }
    if (empty($row->getSourceProperty('titleOfTextShort'))) {
      $row->setSourceProperty('titleOfTextShort', substr($titleOfText, 0, 255));
    }

    // Used str_replace('server2.php/', '', ...) because there is a bug in the urls from ELIS
    $linkToFullText = str_replace('server2.php/', '', $row->getSourceProperty('linkToFullText'));
    $linkToAbstract = str_replace('server2.php/', '', $row->getSourceProperty('linkToAbstract'));

    $row->setSourceProperty('linkToFullText', $linkToFullText);
    $row->setSourceProperty('country', $this->map_nodes_by_name($row->getSourceProperty('country'), 'country'));

    $row->setSourceProperty('files', $this->createFiles($linkToFullText));
    $row->setSourceProperty('linkToAbstract', $this->createFiles($linkToAbstract));
  }

}
