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
   * An array of source fields.
   *
   * @var array
   */
  protected $fields = [];

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

  public function __construct(array $configuration, $plugin_id, $plugin_definition, \Drupal\migrate\Entity\MigrationInterface $migration) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);
    $config_fields = array(
      'path',
      'fields',
      'identifier',
    );

    // Store the configuration data.
    foreach ($config_fields as $config_field) {
      if (isset($configuration[$config_field])) {
        $this->{$config_field} = $configuration[$config_field];
      }
      else {
        // Throw Exception
        throw new MigrateException('The source configuration must include ' . $config_field . '.');
      }
    }

    $this->client = \Drupal::httpClient();
  }

  public function hexadecimally_encode_string($str) {
    $unpack = unpack('H*', $str);
    return reset($unpack);
  }

  public function count($refresh = FALSE) {
    $data = $this->getSourceData($this->path);
    $results = $data['@attributes']['numberResultsFound'];
    return $results;
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
    return $this->fields;
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
    // @ToDo: Replace SPAGE_QUERY_FIRST and SPAGE_QUERY_VALUE within url
    $url = 'www.ecolex.org/elis_isis3w.php?database=cou&search_type=page_search&table=all&format_name=@xmlexp&lang=xmlf&page_header=@xmlh&spage_query=45533a4920414e4420535441543a43&spage_first=0';
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

  protected function initializeIterator() {
    $data = $this->getSourceData($this->path);
    $iterator = new \ArrayIterator($data['documents']);
    return $iterator;
  }

  public function prepareRow(Row $row) {
    // @ToDo: prepare all fields
    return parent::prepareRow($row);
  }

}
