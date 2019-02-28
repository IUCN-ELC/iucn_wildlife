<?php

/**
 * @file
 * Contains \Drupal\iucn_search\Controller\DefaultSearchController.
 */

namespace Drupal\iucn_search\Controller;

use Drupal\Component\Utility\Html;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\iucn_search\edw\solr\SearchResult;
use Drupal\iucn_search\edw\solr\SolrSearch;
use Drupal\iucn_search\edw\solr\SolrSearchServer;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class DefaultSearchController extends ControllerBase {

  protected $items_per_page = 10;
  protected $items_viewmode = 'search_result';

  /**
   * The size of the trimmed text in characters.
   */
  const TRIMMED_TEXT_SIZE = 300;
  protected static $search = NULL;

  protected $route;
  protected $content_type;

  abstract public function getRoute();

  /**
   * @return array
   *  An array containing:
   *    - machine_name
   *    - singular
   *    - plural
   */
  abstract public function getContentType();

  private $countries_map;

  public function __construct($countries_map) {
    $this->route = $this->getRoute();
    $this->content_type = $this->getContentType()['machine_name'];
    $this->countries_map = $countries_map;
  }



  public static function create(ContainerInterface $container) {
    $countries_map = $container->get('wildlex_map.countries_map');
    return new static($countries_map);
  }

  protected function handleResults($rows) {
    $results = [];
    foreach ($rows as $nid => $data) {
      /** @var Node $node */
      $node = Node::load($nid);
      if (!empty($node)) {
        $results[$nid] = \Drupal::entityTypeManager()->getViewBuilder('node')->view($node, $this->items_viewmode);
      }
    }
    return $results;
  }

  protected function getDefaultSorting () {
    return [
      'sort' => 'field_date_of_text',
      'sortOrder' => 'desc',
    ];
  }

  protected function getSortFields() {
    return [
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
  }

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
      if (empty($query['q'])) {
        $query['q'] = '*';
      }

      self::$search = new SolrSearch($query, $server_config);
    }

    return self::$search;
  }

  public function searchPage() {
    $current_page = !empty($_GET['page']) ? Html::escape($_GET['page']) : 0;
    $results = array();
    $found = 0;

    /** @var SearchResult $result */
    try {
      if ($result = self::getSearch($this->content_type, $this->getDefaultSorting())->search($current_page, $this->items_per_page)) {
        pager_default_initialize($result->getCountTotal(), $this->items_per_page);
        $found = $result->getCountTotal();
        $rows = $result->getResults();

        $results = $this->handleResults($rows);
      }

      $numFound = $this->formatPlural($found, 'Found one search result', 'Found @count search results');
      $numFound .= ' <srtrong><a href="#" data-toggle="modal" data-target="#search_modal" class="search-modal-opener">' . $this->t('Show on map') .'</a></srtrong>';

      $sorts = $this->getSortFields();
      $activeSort = !empty($_GET['sort']) ? Html::escape($_GET['sort']) : $this->getDefaultSorting()['sort'];
      $activeOrder = !empty($_GET['sortOrder']) ? Html::escape($_GET['sortOrder']) : $this->getDefaultSorting()['sortOrder'];
      $getCopy = $_GET;
      $sortMarkup = [];
      foreach ($sorts as $key => $sort) {
        if ($activeSort == $sort['field'] && $activeOrder == $sort['order']) {
          $markup = '<strong>Sorted by ' . $sort['text'] . '</strong>';
        }
        else {
          $getCopy['sort'] = $sort['field'];
          $getCopy['sortOrder'] = $sort['order'];
          $url = Url::fromRoute($this->route, [], ['query' => $getCopy]);
          $markup = Link::fromTextAndUrl('Sort by ' . $sort['text'], $url)->toString();
        }
        $sortMarkup[$key] = [
          '#type' => 'item',
          '#markup' => $markup,
        ];
      }


      if (empty($results)) {
        if ($found) {
          // The SOLR returned results but we couldn't find them in Drupal
          throw new \Exception("SOLR server is out of sync.");
        }
        $results = [
          '#attributes' => ['class' => ['well', 'blankslate']],
          '#type' => 'container',
          [
            '#attributes' => ['class' => ['ecolexicon', 'ecolexicon-' . str_replace('_', '-', $this->content_type)]],
            '#tag' => 'span',
            '#type' => 'html_tag'
          ],
          [
            '#attributes' => ['class' => ['blankslate-title']],
            '#tag' => 'h3',
            '#type' => 'html_tag',
            '#value' => $this->t('No results found.')
          ],
          [
            '#attributes' => ['class' => []],
            '#tag' => 'p',
            '#type' => 'html_tag',
            '#value' => $this->t('Use the links above to find what you&rsquo;re looking for, or try a new search query. The Search filters are also super helpful for quickly finding results most relevant to you.')
          ]
        ];
      }

      $content_type_name = $this->getContentType()['singular'];
      if($content_type_name == t('court decision')){
        $content_type_name = t('court decisions');
      }
      $content = [
        '#cache' => ['contexts' => ['url']],
        '#markup' => $this->countries_map->modalMarkup($this->getContentType(), $found),
        '#attached' => [
          'drupalSettings' => [
            'series'=> $this->countries_map->get($this->content_type),
            'content_type' => ucfirst($content_type_name),
            'search_base_url' => $this->countries_map->searchBaseUrl($this->content_type),
          ],
          'library' => [
            'iucn_search/iucn_search.map.js',
            'wildlex_map/d3.js',
            'wildlex_map/topojson.js',
            'wildlex_map/datamaps.js',
            'wildlex_map/wildlex.map.js'
          ],
        ],
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
        unset($content['meta']);
      }
    }
    catch(\Exception $e) {
      return $this->handleError($e);
    }

    return $content;
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
