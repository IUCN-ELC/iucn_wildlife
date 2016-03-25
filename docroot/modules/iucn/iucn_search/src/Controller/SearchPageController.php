<?php

/**
 * @file
 * Contains \Drupal\iucn_search\Controller\SearchPageController.
 */

namespace Drupal\iucn_search\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\iucn_search\edw\solr\SearchResult;
use Drupal\iucn_search\edw\solr\SolrSearch;
use Drupal\iucn_search\edw\solr\SolrSearchServer;

class SearchPageController extends ControllerBase {

  protected $items_per_page = 5;
  protected $items_viewmode = 'search_result';
  protected static $search = NULL;

  public static function getSearch() {
    if (empty(self::$search)) {
        $server_config = new SolrSearchServer('default_node_index');
        self::$search = new SolrSearch($_GET, $server_config);
    }
    return self::$search;
  }

  public function __construct() {
  }

  public function searchPage() {
    $current_page = !empty($_GET['page']) ? $_GET['page'] : 0;
    $results = array();

    /** @var SearchResult $result */
    try {
      if ($result = self::getSearch()->search($current_page, $this->items_per_page)) {
        pager_default_initialize($result->getCountTotal(), $this->items_per_page);
        $rows = $result->getResults();

        foreach ($rows as $nid => $data) {
          $node = \Drupal\node\Entity\Node::load($nid);

          if (!empty($node)) {
            $results[$nid] = \Drupal::entityTypeManager()->getViewBuilder('node')->view($node, $this->items_viewmode);
          }
        }
      }
    } catch(\Exception $e) {
      return $this->handleError($e);
    }

    $content = [$results, ['#type' => 'pager']];

    return $content;
  }

  private function handleError(\Exception $e) {
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
