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

class SearchPageController extends DefaultSearchController {

  public function searchPage() {
    $current_page = !empty($_GET['page']) ? $_GET['page'] : 0;
    $results = array();
    $cacheTags = array();
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
            $cacheTags = array_merge($cacheTags, $node->getCacheTags());
            $highlighting = $data['highlighting'];
            $title = !empty($highlighting['title']) ? $highlighting['title'] : $node->getTitle();
            if (empty($highlighting['field_abstract'])) {
              $abstract = !empty($highlighting['search_api_attachments_field_files']) ? $highlighting['search_api_attachments_field_files'] : text_summary($node->field_abstract->getValue()[0]['value'], NULL, self::TRIMMED_TEXT_SIZE);
            }
            else {
              $abstract = $highlighting['field_abstract'];
            }
            $node->solr_title = $title;
            $node->solr_abstract = $abstract;
            $results[$nid] = \Drupal::entityTypeManager()->getViewBuilder('node')->view($node, $this->items_viewmode);
          }
        }
      }

      Cache::invalidateTags($cacheTags);

      $numFound = $this->formatPlural($found, 'Found 1 court decision', 'Found @count court decisions');

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

      // Render the frontpage links in search results bar
      $menu_tree = \Drupal::menuTree();
      $menu_name = 'homepage-links';
      $parameters = $menu_tree->getCurrentRouteMenuTreeParameters($menu_name);
      $tree = $menu_tree->load($menu_name, $parameters);
      $manipulators = array(
        // Use the default sorting of menu links.
        array('callable' => 'menu.default_tree_manipulators:generateIndexAndSort'),
      );
      $tree = $menu_tree->transform($tree, $manipulators);
      $menu = $menu_tree->build($tree);
      $frontpage_links = \Drupal::service('renderer')->render($menu);

      if (empty($results)) {
        if ($found) {
          // The SOLR returned results but we couldn't find them in Drupal
          throw new \Exception("SOLR server is out of sync.");
        }
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
        '#cache' => ['contexts' => ['url']],
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

}
