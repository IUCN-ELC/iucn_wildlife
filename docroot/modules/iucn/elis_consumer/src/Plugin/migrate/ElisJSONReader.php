<?php

namespace Drupal\elis_consumer\Plugin\migrate;

use Drupal\migrate_source_json\Plugin\migrate\JSONReader;

class ElisJSONReader extends JSONReader {

  public function __construct(array $configuration) {
    parent::__construct($configuration);
  }

  /**
   * Get the source data for reading.
   *
   * @param string $url
   *   The URL to read the source data from.
   *
   * @return \RecursiveIteratorIterator|resource
   *
   * @throws \Drupal\migrate\MigrateException
   */
  public function getSourceData($url) {
    // @ToDo: Replace SPAGE_QUERY_FIRST and SPAGE_QUERY_VALUE within url
    $url = 'www.ecolex.org/elis_isis3w.php?database=cou&search_type=page_search&table=all&format_name=@xmlexp&lang=xmlf&page_header=@xmlh&spage_query=45533a4920414e4420535441543a43&spage_first=0';
    try {
      $response = $this->client->getResponse($url);
      $data = trim(utf8_encode($response->getBody()));
      $xml = simplexml_load_string($data);
      $json = json_encode($xml);
      // The TRUE setting means decode the response into an associative array.
      $array = json_decode($json,TRUE);
      // Return the results in a recursive iterator that
      // can traverse multidimensional arrays.
      return new \RecursiveIteratorIterator(
        new \RecursiveArrayIterator($array),
        \RecursiveIteratorIterator::SELF_FIRST);
    } catch (RequestException $e) {
      throw new MigrateException($e->getMessage(), $e->getCode(), $e);
    }
  }
}