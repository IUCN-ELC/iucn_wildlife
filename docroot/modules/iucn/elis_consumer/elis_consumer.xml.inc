<?php

namespace Drupal\elis_consumer;
/**
 * Class ElisXMLConsumer extracts data from ELIS system.
 *
 * <code>
 * $ob = new ElisXMLConsumer(
 *     'http://www.ecolex.org/elis_isis3w.php?database=cou&search_type=page_search&table=all&format_name=@xmlexp&lang=xmlf&page_header=@xmlh&spage_query=SPAGE_QUERY_VALUE&spage_first=SPAGE_FIRST_VALUE',
 *     'id',
 *     'iso-8859-15',
 *     '1999-01',
 *     'STAT:T'
 * );
 * $ob->getData();
 * </code>
 */
class ElisXMLConsumer {

  protected $urlTemplate;
  protected $dateOfEntryFilter;
  protected $elisAdditionalFilter = NULL;
  protected $keyField;
  protected $xmlEncoding = NULL;

  protected $data = array();

  /**
   * ElisXMLConsumer constructor.
   * @param $urlTemplate
   * @param $keyField
   * @param string $xmlEncoding
   * @param string $dateOfEntryFilter
   * @param string $elisAdditionalFilter
   *     Example: 'ES:I AND STAT:C'
   */
  public function __construct($urlTemplate, $keyField, $xmlEncoding = 'iso-8859-15', $dateOfEntryFilter = '1999-01', $elisAdditionalFilter = '') {
    $this->urlTemplate = $urlTemplate;
    $this->keyField = $keyField;
    $this->dateOfEntryFilter = $dateOfEntryFilter;
    $this->xmlEncoding = $xmlEncoding;
    $this->elisAdditionalFilter = $elisAdditionalFilter;
  }

  /**
   * Add additional filter to the ELIS query
   *
   * @param string $q
   *     Use ELIS language, e.g.
   *        STAT:C (completed/published records)
   *        STAT:T (temporary records)
   *        MD:201602 (Modification date)
   */
  public function setELISAdditionalfilter($q) {
    $this->elisAdditionalFilter = $q;
  }

  /**
   * Build a year range.
   *
   * @param string $start_date
   *     Start date, e.g. 1999-01
   * @return array
   *     [1981, 1982, ... ,2016]
   */
  protected function rangeFromDate($start_date) {
    $ret = array();
    // Compute the interval
    $begin = new \DateTime($start_date);
    $end = new \DateTime();
    $interval = \DateInterval::createFromDateString('1 year');
    $period = new \DatePeriod($begin, $interval, $end);
    /** @var \DateTime $p */
    foreach ($period as $p) {
      $ret[] = $p->format('Y');
    }
    return $ret;
  }

  /**
   * Pull records from the remote service
   */
  public function getData() {
    $data = array();
    $interval = $this->rangeFromDate($this->dateOfEntryFilter);
    while (key($interval) !== NULL) {
      $this->pullRemoteData($data, $interval);
    }
    return $data;
  }

  /**
   *
   * Query the remote service for the whole interval and pull the records.
   *
   * @param array $data
   *     Array to store remote records. Passed by reference.
   * @param array $interval
   *     Date interval (ie. [1999, 2000, 2001 ... ]
   * @param bool $monthly
   *     Query remote by month (TRUE) or by year (FALSE). By default we filter by
   *     year, but when there are more than ~700 records, ELIS does not return
   *     more records, so we fallback to monthly query.
   */
  protected function pullRemoteData(&$data, &$interval, $monthly = FALSE) {
    $months = array('*');
    if ($monthly) {
      $months = array('01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12');
    }
    foreach ($months as $month) {
      if (empty($this->elisAdditionalFilter)) {
        $query = 'DE:' . current($interval) . $month;
      }
      else {
        $query = $this->elisAdditionalFilter . ' AND DE:' . current($interval) . $month;
      }
      // hexadecimal encoding for ELIS protocol
      $unpack = unpack('H*', $query);
      $spage_query = reset($unpack);
      $spage_first = 0;
      $url = str_replace(
        array('SPAGE_QUERY_VALUE', 'SPAGE_FIRST_VALUE'),
        array($spage_query,$spage_first),
        $this->urlTemplate
      );
      elis_consumer_log($query, 'notice');
      $xml = elis_consumer_xml_http_request($url, $this->xmlEncoding);
      while (!empty($xml) && ($numberResultsFound = intval($xml->attributes()['numberResultsFound'])) > $spage_first) {
        if ($numberResultsFound > 300 && !$monthly) {
          $this->pullRemoteData($data, $interval, 'month');
          break;
        }
        else {
          $i = 0;
          foreach ($xml->document as $doc) {
            $id = (string) $doc->{$this->keyField};
            if (!empty($id)) {
              elis_consumer_log('                ' . $id, 'notice');
              $data[$id] = $doc;
              $i++;
            }
          }
          elis_consumer_log('Items:' . $i . ' Total: ' . count($data), 'notice');
          $spage_first += 20;
          if ($spage_first < $numberResultsFound) {
            $url = str_replace(
              array('SPAGE_QUERY_VALUE', 'SPAGE_FIRST_VALUE'),
              array($spage_query,$spage_first),
              $this->urlTemplate
            );
            elis_consumer_log($query . ' Page: ' . ($spage_first / 20), 'notice');
            $xml = elis_consumer_xml_http_request($url, $this->xmlEncoding);
          }
        }
      }
    }
    next($interval);
  }
}


function elis_consumer_xml_http_request($url, $xml_encoding) {
  elis_consumer_log($url);
  $ret = NULL;
  if ($response = elis_consumer_http_request($url, array('Accept' => 'application/xml'))) {
    try {
      libxml_clear_errors();
      if (strtolower($xml_encoding) == 'utf-8') {
        $data = trim($response);
      }
      else {
        $data = trim(utf8_decode($response));
      }
      $ret = simplexml_load_string(utf8_encode($data));
    } catch (\Exception $e) {
      $error = libxml_get_last_error();
      $msg = sprintf('elis_consumer_xml_http_request: Cannot parse XML (%s), line %d:%d, %s', $error->file, $error->line, $error->column, $error->message);
      elis_consumer_log($msg, 'error');
    }
  }
  return $ret;
}


function elis_consumer_http_request($url, $headers = array()) {
  // elis_consumer_log(__METHOD__ . ": $url", RfcLogLevel::INFO);
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
  curl_setopt($ch, CURLOPT_NOBODY, 0);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
  $ret = curl_exec($ch);
  $info = curl_getinfo($ch);
  if ($info['http_code'] != 200) {
    $code = $info['http_code'];
    elis_consumer_log(__METHOD__ . ": Failure ({$code}) for URL: $url", 'error');
    $ret = NULL;
  }
  curl_close($ch);
  return $ret;
}


/**
 * Log system for the ws_consumer_odata migrations.
 *
 * @param string $message
 *   Log message
 * @param string $severity
 *    See drush_log severity
 */
function elis_consumer_log($message, $severity = 'notice') {
  if (function_exists('drush_log')) {
    drush_log($message, $severity);
  }
  else {
    echo "$message\n";
  }
}
