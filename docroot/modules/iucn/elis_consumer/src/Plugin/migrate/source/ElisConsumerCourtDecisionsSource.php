<?php

/**
 * @file
 * Contains \Drupal\elis_consumer\Plugin\migrate\source\ElisConsumerCourtDecisionsSource.
 */

namespace Drupal\elis_consumer\Plugin\migrate\source;

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
   * The HTTP client.
   */
  protected $client;

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

  public function __construct(array $configuration, $plugin_id, $plugin_definition, \Drupal\migrate\Entity\MigrationInterface $migration) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);

    $this->path = 'http://www.ecolex.org/elis_isis3w.php?database=cou&search_type=page_search&table=all&format_name=@xmlexp&lang=xmlf&page_header=@xmlh&spage_query=SPAGE_QUERY_VALUE&spage_first=SPAGE_FIRST_VALUE';
    $this->identifier = 'id';
    
    $this->client = \Drupal::httpClient();
    $this->date_period = $this->get_date_period();
  }

  public function hexadecimally_encode_string($str) {
    $unpack = unpack('H*', $str);
    return reset($unpack);
  }

  public function get_date_period($format = 'Y', $interval = '1 year') {
    $return = array();
    $begin = new \DateTime($this->start_date);
    $end = new \DateTime();

    $interval = \DateInterval::createFromDateString($interval);
    $period = new \DatePeriod($begin, $interval, $end);

    foreach ($period as $p) {
      $return[] = $p->format($format);
    }

    return $return;
  }

  public function count($refresh = FALSE) {
    $query = $this->spage_query_default_string;
    $spage_query = $this->hexadecimally_encode_string($query);
    $spage_first = 0;
    $url = str_replace(
      array('SPAGE_QUERY_VALUE', 'SPAGE_FIRST_VALUE'),
      array($spage_query,$spage_first),
      $this->path
    );
    try {
      $response = $this->getResponse($url);
      $data = trim(utf8_encode($response->getBody()));
      $xml = simplexml_load_string($data);
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
      if ($field_name == 'abstract') {
        $abstract .= (string) $value . PHP_EOL;
        continue;
      }
      elseif ($field_name == 'party') {
        $parties[] = $value;
        continue;
      }
      elseif (property_exists($ob, $field_name)) {
        if (is_array($ob->{$field_name})) {
          $ob->{$field_name}[] = (string)$value;
        }
        else {
          $ob->{$field_name} = array($ob->{$field_name}, (string) $value);
        }
      }
      else {
        if ($field_name == 'titleOfText') {
          $ob->titleOfText_original = (string) $value;
        }
        $ob->{$field_name} = (string) $value;
      }
    }
    $ob->abstract = $abstract;
    $ob->parties = $parties;
    if (property_exists($ob, 'obsolete')) {
      $ob->obsolete = (int) $ob->obsolete;
    }
    return $ob;
  }

  public function getSourceData($url) {
    $query = $this->spage_query_default_string . ' AND DM:' . current($this->date_period) . '*';
    $spage_query = $this->hexadecimally_encode_string($query);
    $spage_first = 0;
    $url = str_replace(
      array('SPAGE_QUERY_VALUE', 'SPAGE_FIRST_VALUE'),
      array($spage_query,$spage_first),
      $url
    );
    try {
      $response = $this->getResponse($url);
      $data = trim(utf8_encode($response->getBody()));
      $xml = simplexml_load_string($data);
      $docs = [];
      foreach ($xml->document as $doc) {
        $docs[(string) $doc->{$this->identifier}] = (array)$this->getItem($doc);
      }
      $json = json_encode($xml->attributes());
      // The TRUE setting means decode the response into an associative array.
      $array = json_decode($json, TRUE);
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

  public function prepareRow(Row $row) {
    parent::prepareRow($row);
    if (empty($row->getSourceProperty('titleOfTextShort'))) {
      if (empty($row->getSourceProperty('titleOfText'))) {
        return FALSE;
      }
      $row->setSourceProperty('titleOfTextShort', $row->getSourceProperty('titleOfText'));
    }
    $row->setSourceProperty('country', $this->map_nodes_by_name($row->getSourceProperty('country'), 'country'));
    $row->setSourceProperty('subject', $this->map_taxonomy_terms_by_name($row->getSourceProperty('subject'), 'ecolex_subjects'));
    $row->setSourceProperty('typeOfText', $this->map_taxonomy_terms_by_name($row->getSourceProperty('typeOfText'), 'document_types'));
  }

}
