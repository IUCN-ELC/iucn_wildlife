<?php

/**
 * @file
 * Contains \Drupal\iucn_search\Controller\SearchPageController.
 */

namespace Drupal\iucn_search\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\iucn_search\edw\solr\SearchResult;
use Drupal\iucn_search\edw\solr\SolrSearch;
use Drupal\iucn_search\edw\solr\SolrSearchServer;
use Drupal\node\Entity\Node;

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
    $found = 0;

    /** @var SearchResult $result */
    try {
      if ($result = self::getSearch()->search($current_page, $this->items_per_page)) {
        pager_default_initialize($result->getCountTotal(), $this->items_per_page);
        $found = $result->getCountTotal();
        $rows = $result->getResults();

        foreach ($rows as $nid => $data) {
          /** @var Node $node */
          $node = Node::load($nid);
          if (!empty($node)) {
            $highlighting = $data['highlighting'];
            $title = !empty($highlighting['title']) ? $highlighting['title'] : $node->getTitle();
            $url = Url::fromRoute('entity.node.canonical', ['node' => $nid])->toString();
            $results[$nid] = \Drupal::entityTypeManager()->getViewBuilder('node')->view($node, $this->items_viewmode);
            $results[$nid]['#node']->label = ['#markup' => $title];
          }
        }
      }
    } catch(\Exception $e) {
      return $this->handleError($e);
    }

    $found = $found == 0 ? 'no' : $found;
    $numFound = [
      '#markup' => $this->formatPlural($found, 'Found 1 result', 'Found @count results')
    ];

    $sorts = [
      'relevance' => [
        'field' => 'score',
        'order' => 'desc',
        'text' => 'relevance',
      ],
      'dateOfEntryDesc' => [
        'field' => 'field_date_of_entry',
        'order' => 'desc',
        'text' => 'most recent',
      ],
      'dateOfEntryAsc' => [
        'field' => 'field_date_of_entry',
        'order' => 'asc',
        'text' => 'least recent',
      ],
    ];
    $activeSort = !empty($_GET['sort']) ? $_GET['sort'] : 'score';
    $activeOrder = !empty($_GET['sortOrder']) ? $_GET['sortOrder'] : 'desc';
    $getCopy = $_GET;
    $sortMarkup = [];
    foreach ($sorts as $key => $sort) {
      if ($activeSort == $sort['field'] && $activeOrder == $sort['order']) {
        $markup = 'Sorted by ' . $sort['text'];
      }
      else {
        $getCopy['sort'] = $sort['field'];
        $getCopy['sortOrder'] = $sort['order'];
        $url = Url::fromRoute('iucn.search', [], ['query' => $getCopy]);
        $markup = Link::fromTextAndUrl('Sort by ' . $sort['text'], $url)->toString();
      }
      $sortMarkup[$key] = [
        '#type' => 'item',
        '#markup' => $markup,
      ];
    }

    $content = [
      'meta' => [
        '#attributes' => ['class' => ['clearfix']],
        '#type' => 'container',
        [
          '#attributes' => ['class' => ['pull-left']],
          '#type' => 'container',
          $numFound
        ],
        [
          '#attributes' => ['class' => ['list-inline', 'pull-right']],
          '#items' => $sortMarkup,
          '#list_type' => 'ul',
          '#theme' => 'item_list'
        ]
      ],
      'results' => $results,
      'pager' => ['#type' => 'pager'],
    ];

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
