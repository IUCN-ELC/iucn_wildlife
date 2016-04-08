<?php

/**
 * @file
 * Contains \Drupal\iucn_search\Controller\SearchPageController.
 */

namespace Drupal\iucn_search\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Cache\Cache;
use Drupal\iucn_search\edw\solr\SearchResult;
use Drupal\iucn_search\edw\solr\SolrSearch;
use Drupal\iucn_search\edw\solr\SolrSearchServer;
use Drupal\node\Entity\Node;

class SearchPageController extends ControllerBase {

  protected $items_per_page = 10;
  protected $items_viewmode = 'search_result';

  /**
   * The size of the trimmed text in characters.
   */
  const TRIMMED_TEXT_SIZE = 300;
  protected static $search = NULL;

  public static function getSearch() {
    if (empty(self::$search)) {
      $parameters = [
        'highlightingFragSize' => self::TRIMMED_TEXT_SIZE,
      ];
      $server_config = new SolrSearchServer('default_node_index');
      self::$search = new SolrSearch($_GET + $parameters, $server_config);
    }
    return self::$search;
  }

  public function __construct() {
  }

  public function searchPage() {
    $current_page = !empty($_GET['page']) ? $_GET['page'] : 0;
    $results = array();
    $cacheTags = array();
    $found = 0;

    /** @var SearchResult $result */
    try {
      self::getSearch()->createSelectQuery([
        'page' => $current_page,
        'size' => $this->items_per_page,
      ]);
      $yearmin = !empty($_GET['yearmin']) ? $_GET['yearmin'] : NULL;
      $yearmax = !empty($_GET['yearmax']) ? $_GET['yearmax'] : NULL;
      if ($yearmin || $yearmax) {
        $yearmin = $yearmin ? "{$yearmin}-01-01T00:00:00Z" : "*";
        $yearmax = $yearmax ? "{$yearmax}-12-31T23:59:59Z" : "*";
        self::getSearch()->addFilterQuery('field_date_of_text', "[{$yearmin} TO {$yearmax}]");
      }
      if ($result = self::getSearch()->getSearchResults()) {
        pager_default_initialize($result->getCountTotal(), $this->items_per_page);
        $found = $result->getCountTotal();
        $rows = $result->getResults();

        foreach ($rows as $nid => $data) {
          /** @var Node $node */
          $node = Node::load($nid);
          $cacheTags = array_merge($cacheTags, $node->getCacheTags());
          if (!empty($node)) {
            $highlighting = $data['highlighting'];
            $title = !empty($highlighting['title']) ? $highlighting['title'] : $node->getTitle();
            $abstract = !empty($highlighting['field_abstract']) ? $highlighting['field_abstract'] : text_summary($node->field_abstract->getValue()[0]['value'], NULL, self::TRIMMED_TEXT_SIZE);
            $node->solr_title = $title;
            $node->solr_abstract = $abstract;
            $results[$nid] = \Drupal::entityTypeManager()->getViewBuilder('node')->view($node, $this->items_viewmode);
          }
        }
      }
    }
    catch(\Exception $e) {
      return $this->handleError($e);
    }

    Cache::invalidateTags($cacheTags);

    $numFound = $found ? $this->formatPlural($found, 'Found 1 court decision', 'Found @count court decisions') : $this->t('Found no court decisions');

    $sorts = [
      'relevance' => [
        'field' => 'score',
        'order' => 'desc',
        'text' => 'relevance',
      ],
      'dateOfTextDesc' => [
        'field' => 'field_date_of_text',
        'order' => 'desc',
        'text' => 'most recent',
      ],
      'dateOfTextAsc' => [
        'field' => 'field_date_of_text',
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
        $markup = '<strong>Sorted by ' . $sort['text'] . '</strong>';
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

    if (empty($results)) {
      $results = [
        '#attributes' => ['class' => ['well', 'blankslate']],
        '#type' => 'container',
        [
          '#attributes' => ['class' => ['ecolexicon', 'ecolexicon-court-decision']],
          '#tag' => 'span',
          '#type' => 'html_tag'
        ],
        [
          '#attributes' => ['class' => ['blankslate-title']],
          '#tag' => 'h3',
          '#type' => 'html_tag',
          '#value' => $this->t('No court decisions found.')
        ],
        [
          '#attributes' => ['class' => []],
          '#tag' => 'p',
          '#type' => 'html_tag',
          '#value' => $this->t('Use the links above to find what you&rsquo;re looking for, or try a new search query. The Search filters are also super helpful for quickly finding court decisions most relevant to you.')
        ]
      ];
    }

    $content = [
      'meta' => [
        '#attributes' => ['class' => ['search-header']],
        '#type' => 'container',
        [
          '#attributes' => ['class' => ['pull-left']],
          '#tag' => 'div',
          '#type' => 'html_tag',
          '#value' => $numFound
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

    if (!$found) {
      unset($content['meta'][1]);
    }

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
