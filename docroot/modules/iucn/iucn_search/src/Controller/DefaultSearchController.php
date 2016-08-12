<?php

/**
 * @file
 * Contains \Drupal\iucn_search\Controller\DefaultSearchController.
 */

namespace Drupal\iucn_search\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\iucn_search\edw\solr\SolrSearch;
use Drupal\iucn_search\edw\solr\SolrSearchServer;

class DefaultSearchController extends ControllerBase {

  protected $items_per_page = 10;
  protected $items_viewmode = 'search_result';

  /**
   * The size of the trimmed text in characters.
   */
  const TRIMMED_TEXT_SIZE = 300;
  protected static $search = NULL;

  public static function getSearch($type = 'court_decision', $parameters = []) {
    if (empty(self::$search)) {
      $parameters += [
        'highlightingFragSize' => self::TRIMMED_TEXT_SIZE,
        'type' => $type,
      ];
      $yearmin = !empty($_GET['yearmin']) ? $_GET['yearmin'] : NULL;
      $yearmax = !empty($_GET['yearmax']) ? $_GET['yearmax'] : NULL;
      if ($yearmin || $yearmax) {
        $yearmin = $yearmin ? "{$yearmin}-01-01T00:00:00Z" : "*";
        $yearmax = $yearmax ? "{$yearmax}-12-31T23:59:59Z" : "*";
        $parameters['field_date_of_text'] = "[{$yearmin} TO {$yearmax}]";
      }
      $server_config = new SolrSearchServer('default_node_index');
      $query = $_GET + $parameters;
      $query['q'] = iucn_search_query_filter();
      self::$search = new SolrSearch($query, $server_config);
    }

    return self::$search;
  }

  protected function handleError(\Exception $e) {
    $message = $e->getMessage();

    if (empty($message)) {
      $message = $this->t('Backend error');
    }

    $message = $this->t('Search was interrupted: @message', array('@message' => $message));
    watchdog_exception('iucn_search', $e, $message);
    drupal_set_message($message, 'error');

    $ret['error-message'] = array(
      '#type' => 'item',
      '#markup' => $this->t('<p>An internal error has occurred during page load and the process was interrupted.</p><p>We apologise for the inconvenience</p>')
    );

    if (function_exists('dpm')) {
      $ret['error-message-details'] = array(
        '#title' => 'Technical details',
        '#type' => 'item',
        '#markup' => $e->getMessage()
      );

      $ret['error-message-stack'] = array(
        '#type' => 'item',
        '#prefix' => '<pre>',
        '#markup' => trim($e->getTraceAsString()),
        '#suffix' => '</pre>'
      );
    }

    return $ret;
  }

}
